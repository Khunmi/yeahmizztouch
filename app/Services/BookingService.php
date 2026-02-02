<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\PhotoConsent;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Payment;
use App\Models\PolicySetting;
use App\Models\Service;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingService
{
    /**
     * Hold an appointment slot.
     * Uses PostgreSQL advisory lock + overlap check to prevent race conditions.
     *
     * @param array $payload [
     *   'service_id' => int,
     *   'starts_at' => string (ISO 8601),
     *   'client' => [
     *     'first_name' => string,
     *     'last_name' => string,
     *     'email' => string,
     *     'phone' => string,
     *     'date_of_birth' => string (Y-m-d),
     *   ],
     *   'photo_consent' => string,
     *   'policy_acknowledged' => bool,
     * ]
     * @return Appointment
     * @throws \Exception
     */
    public function holdAppointment(array $payload): Appointment
    {
        $service = Service::findOrFail($payload['service_id']);
        $startsAt = CarbonImmutable::parse($payload['starts_at'])->utc();
        $endsAt = $startsAt->addMinutes($service->duration_minutes);

        // Validate minimum age
        $minimumAge = PolicySetting::getValue('minimum_client_age', 15);
        $clientDob = CarbonImmutable::parse($payload['client']['date_of_birth']);
        if ($clientDob->age < $minimumAge) {
            throw new \InvalidArgumentException("Client must be at least {$minimumAge} years old.");
        }

        // Generate advisory lock key from timestamp
        $lockKey = crc32($startsAt->format('Y-m-d H:i:s'));

        return DB::transaction(function () use (
            $payload, $service, $startsAt, $endsAt, $lockKey, $clientDob
        ) {
            // Acquire PostgreSQL advisory lock (session-level, released on commit/rollback)
            $locked = DB::selectOne('SELECT pg_try_advisory_xact_lock(?) as locked', [$lockKey]);
            
            if (!$locked->locked) {
                throw new \RuntimeException('Unable to acquire lock for this time slot. Please try again.');
            }

            // Re-check for overlapping appointments inside transaction
            $hasOverlap = Appointment::query()
                ->blocksSlot()
                ->overlapping($startsAt, $endsAt)
                ->exists();

            if ($hasOverlap) {
                throw new \RuntimeException('This time slot is no longer available.');
            }

            // Find or create client
            $client = Client::firstOrCreate(
                ['email' => $payload['client']['email']],
                [
                    'first_name' => $payload['client']['first_name'],
                    'last_name' => $payload['client']['last_name'],
                    'phone' => $payload['client']['phone'],
                    'date_of_birth' => $clientDob,
                    'photo_consent' => PhotoConsent::from($payload['photo_consent']),
                    'policy_acknowledged_at' => CarbonImmutable::now(),
                ]
            );

            // Update client info if existing
            if (!$client->wasRecentlyCreated) {
                $client->update([
                    'first_name' => $payload['client']['first_name'],
                    'last_name' => $payload['client']['last_name'],
                    'phone' => $payload['client']['phone'],
                    'date_of_birth' => $clientDob,
                    'photo_consent' => PhotoConsent::from($payload['photo_consent']),
                    'policy_acknowledged_at' => CarbonImmutable::now(),
                ]);
            }

            // Calculate hold expiration
            $holdMinutes = PolicySetting::getValue('hold_duration_minutes', 10);
            $holdExpiresAt = CarbonImmutable::now()->addMinutes($holdMinutes);

            // Create appointment in held status
            $appointment = Appointment::create([
                'client_id' => $client->id,
                'service_id' => $service->id,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => AppointmentStatus::Held,
                'hold_expires_at' => $holdExpiresAt,
                'service_price_cents' => $service->price_cents,
                'deposit_amount_cents' => $service->deposit_cents,
                'photo_consent_at_booking' => PhotoConsent::from($payload['photo_consent']),
                'policy_acknowledged_at' => CarbonImmutable::now(),
            ]);

            Log::info('Appointment held', [
                'appointment_id' => $appointment->id,
                'client_id' => $client->id,
                'service_id' => $service->id,
                'starts_at' => $startsAt->toIso8601String(),
                'hold_expires_at' => $holdExpiresAt->toIso8601String(),
            ]);

            return $appointment->load(['client', 'service']);
        });
    }

    /**
     * Confirm an appointment after successful payment.
     */
    public function confirmAppointment(int $appointmentId, string $stripePaymentIntentId): Appointment
    {
        return DB::transaction(function () use ($appointmentId, $stripePaymentIntentId) {
            $appointment = Appointment::lockForUpdate()->findOrFail($appointmentId);

            if ($appointment->status !== AppointmentStatus::Held) {
                throw new \RuntimeException("Cannot confirm appointment in status: {$appointment->status->value}");
            }

            if ($appointment->isHoldExpired()) {
                throw new \RuntimeException('Appointment hold has expired.');
            }

            // Update appointment status
            $appointment->update([
                'status' => AppointmentStatus::Confirmed,
                'hold_expires_at' => null,
            ]);

            // Create payment record
            Payment::create([
                'appointment_id' => $appointment->id,
                'client_id' => $appointment->client_id,
                'stripe_payment_intent_id' => $stripePaymentIntentId,
                'amount_cents' => $appointment->deposit_amount_cents,
                'payment_type' => PaymentType::Deposit,
                'status' => PaymentStatus::Succeeded,
            ]);

            Log::info('Appointment confirmed', [
                'appointment_id' => $appointment->id,
                'stripe_payment_intent_id' => $stripePaymentIntentId,
            ]);

            return $appointment->fresh(['client', 'service', 'payments']);
        });
    }

    /**
     * Cancel an appointment.
     */
    public function cancelAppointment(
        int $appointmentId,
        string $reason,
        AppointmentStatus $cancelledBy = AppointmentStatus::CancelledByClient
    ): Appointment {
        return DB::transaction(function () use ($appointmentId, $reason, $cancelledBy) {
            $appointment = Appointment::lockForUpdate()->findOrFail($appointmentId);

            if (!$appointment->status->isActive()) {
                throw new \RuntimeException("Cannot cancel appointment in status: {$appointment->status->value}");
            }

            $chargeAmount = 0;

            // Check if late cancellation applies (client-initiated only)
            if ($cancelledBy === AppointmentStatus::CancelledByClient 
                && $appointment->status === AppointmentStatus::Confirmed
                && $appointment->isWithinLateCancelWindow()
            ) {
                $lateCancelPercentage = PolicySetting::getValue('late_cancel_charge_percentage', 70);
                $chargeAmount = (int) round($appointment->service_price_cents * ($lateCancelPercentage / 100));
            }

            $appointment->update([
                'status' => $cancelledBy,
                'cancellation_reason' => $reason,
                'cancelled_at' => CarbonImmutable::now(),
                'cancellation_charge_cents' => $chargeAmount,
            ]);

            Log::info('Appointment cancelled', [
                'appointment_id' => $appointment->id,
                'cancelled_by' => $cancelledBy->value,
                'reason' => $reason,
                'charge_cents' => $chargeAmount,
            ]);

            return $appointment->fresh(['client', 'service']);
        });
    }

    /**
     * Mark appointment as no-show.
     */
    public function markNoShow(int $appointmentId): Appointment
    {
        return DB::transaction(function () use ($appointmentId) {
            $appointment = Appointment::lockForUpdate()->findOrFail($appointmentId);

            if ($appointment->status !== AppointmentStatus::Confirmed) {
                throw new \RuntimeException("Can only mark confirmed appointments as no-show.");
            }

            $noShowPercentage = PolicySetting::getValue('no_show_charge_percentage', 70);
            $chargeAmount = (int) round($appointment->service_price_cents * ($noShowPercentage / 100));

            $appointment->update([
                'status' => AppointmentStatus::NoShow,
                'cancellation_charge_cents' => $chargeAmount,
            ]);

            Log::info('Appointment marked as no-show', [
                'appointment_id' => $appointment->id,
                'charge_cents' => $chargeAmount,
            ]);

            return $appointment->fresh(['client', 'service']);
        });
    }

    /**
     * Apply late fee to appointment.
     */
    public function applyLateFee(int $appointmentId, int $minutesLate): Appointment
    {
        return DB::transaction(function () use ($appointmentId, $minutesLate) {
            $appointment = Appointment::lockForUpdate()->findOrFail($appointmentId);

            if ($appointment->status !== AppointmentStatus::Confirmed) {
                throw new \RuntimeException("Can only apply late fees to confirmed appointments.");
            }

            $lateThreshold = PolicySetting::getValue('late_fee_threshold_minutes', 20);
            $autoCancelMinutes = PolicySetting::getValue('auto_cancel_minutes_late', 40);

            // Auto-cancel if too late
            if ($minutesLate >= $autoCancelMinutes) {
                return $this->cancelAppointment(
                    $appointmentId,
                    "Auto-cancelled: client {$minutesLate} minutes late",
                    AppointmentStatus::CancelledBySalon
                );
            }

            // Apply late fee if past threshold
            if ($minutesLate >= $lateThreshold) {
                $lateFee = PolicySetting::getValue('late_fee_cents', 2000);
                $appointment->update([
                    'late_fee_cents' => $lateFee,
                ]);

                Log::info('Late fee applied', [
                    'appointment_id' => $appointment->id,
                    'minutes_late' => $minutesLate,
                    'fee_cents' => $lateFee,
                ]);
            }

            return $appointment->fresh(['client', 'service']);
        });
    }

    /**
     * Complete an appointment.
     */
    public function completeAppointment(int $appointmentId): Appointment
    {
        return DB::transaction(function () use ($appointmentId) {
            $appointment = Appointment::lockForUpdate()->findOrFail($appointmentId);

            if ($appointment->status !== AppointmentStatus::Confirmed) {
                throw new \RuntimeException("Can only complete confirmed appointments.");
            }

            $appointment->update([
                'status' => AppointmentStatus::Completed,
            ]);

            Log::info('Appointment completed', ['appointment_id' => $appointment->id]);

            return $appointment->fresh(['client', 'service']);
        });
    }

    /**
     * Create a squeeze-in appointment (admin only).
     */
    public function createSqueezeIn(array $payload): Appointment
    {
        $squeezeFee = PolicySetting::getValue('squeeze_in_fee_cents', 4000);
        
        $appointment = $this->holdAppointment($payload);
        
        $appointment->update([
            'is_squeeze_in' => true,
            'squeeze_in_fee_cents' => $squeezeFee,
        ]);

        return $appointment->fresh(['client', 'service']);
    }
}
