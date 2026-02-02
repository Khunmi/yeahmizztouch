<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Service;
use App\Models\SlotHold;
use App\Models\Payment;
use App\Models\PaymentLog;
use App\Models\Appointment;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;
use Stripe\PaymentIntent;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * PaymentService
 * 
 * Handles all Stripe payment operations:
 * - Creating checkout sessions
 * - Processing webhooks
 * - Logging payment events
 */
class PaymentService
{
    private BookingService $bookingService;
    private NotificationService $notificationService;

    public function __construct(
        BookingService $bookingService,
        NotificationService $notificationService
    ) {
        $this->bookingService = $bookingService;
        $this->notificationService = $notificationService;
        
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Create a Stripe Checkout session for a booking.
     */
    public function createCheckoutSession(SlotHold $hold, Service $service, Client $client): StripeSession
    {
        $amountCents = $this->calculatePaymentAmount($service);
        $paymentType = $this->getPaymentType($service);

        $session = StripeSession::create([
            'payment_method_types' => ['card'],
            'mode' => 'payment',
            'customer_email' => $client->email,
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => $service->name,
                            'description' => sprintf(
                                '%s on %s at %s',
                                $service->name,
                                $hold->date->format('l, F j, Y'),
                                $hold->formatted_time
                            ),
                        ],
                        'unit_amount' => $amountCents,
                    ],
                    'quantity' => 1,
                ],
            ],
            'metadata' => [
                'hold_uuid' => $hold->uuid,
                'service_id' => $service->id,
                'client_id' => $client->id,
                'appointment_date' => $hold->date->format('Y-m-d'),
                'start_time' => $hold->start_time,
                'payment_type' => $paymentType,
            ],
            'success_url' => route('booking.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('booking.cancel') . '?hold=' . $hold->uuid,
            'expires_at' => now()->addMinutes(30)->timestamp,
        ]);

        Payment::create([
            'client_id' => $client->id,
            'stripe_checkout_session_id' => $session->id,
            'stripe_payment_intent_id' => $session->payment_intent ?? 'pending_' . $session->id,
            'amount_cents' => $amountCents,
            'currency' => 'usd',
            'status' => 'pending',
            'type' => $paymentType,
            'metadata' => [
                'hold_uuid' => $hold->uuid,
                'service_name' => $service->name,
            ],
        ]);

        $hold->extend(35);

        return $session;
    }

    /**
     * Process a Stripe webhook event.
     */
    public function handleWebhook(string $payload, string $sigHeader): array
    {
        try {
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                config('services.stripe.webhook_secret')
            );
        } catch (SignatureVerificationException $e) {
            Log::error('Stripe webhook signature verification failed', ['error' => $e->getMessage()]);
            throw new Exception('Invalid signature');
        }

        if (PaymentLog::alreadyProcessed($event->id)) {
            return ['status' => 'already_processed'];
        }

        $log = PaymentLog::logEvent($event->id, $event->type, $event->toArray());

        try {
            $result = match ($event->type) {
                'checkout.session.completed' => $this->handleCheckoutComplete($event->data->object),
                'payment_intent.succeeded' => $this->handlePaymentSucceeded($event->data->object),
                'payment_intent.payment_failed' => $this->handlePaymentFailed($event->data->object),
                default => ['status' => 'ignored', 'type' => $event->type],
            };

            $log->markProcessed();
            return $result;

        } catch (Exception $e) {
            Log::error('Stripe webhook processing failed', [
                'event_id' => $event->id,
                'event_type' => $event->type,
                'error' => $e->getMessage(),
            ]);
            $log->markFailed($e->getMessage());
            throw $e;
        }
    }

    private function handleCheckoutComplete(object $session): array
    {
        Log::info('Processing checkout.session.completed', ['session_id' => $session->id]);

        $payment = Payment::byCheckoutSession($session->id)->first();
        
        if (!$payment) {
            return ['status' => 'payment_not_found'];
        }

        if ($session->payment_intent) {
            $payment->stripe_payment_intent_id = $session->payment_intent;
        }

        if ($session->payment_status !== 'paid') {
            return ['status' => 'awaiting_payment'];
        }

        $payment->markSucceeded();

        $holdUuid = $session->metadata->hold_uuid ?? null;
        
        if (!$holdUuid) {
            return ['status' => 'error', 'message' => 'No hold UUID'];
        }

        $hold = $this->bookingService->getHoldByUuid($holdUuid);
        
        if (!$hold) {
            return ['status' => 'error', 'message' => 'Hold not found'];
        }

        try {
            $appointment = $this->bookingService->confirmBooking($hold, $payment->client);

            $payment->appointment_id = $appointment->id;
            $payment->save();

            $this->notificationService->sendBookingConfirmation($appointment);
            $this->notificationService->sendAdminNewBookingAlert($appointment);

            return ['status' => 'success', 'appointment_uuid' => $appointment->uuid];

        } catch (Exception $e) {
            Log::error('Failed to confirm booking', ['error' => $e->getMessage()]);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function handlePaymentSucceeded(object $paymentIntent): array
    {
        $payment = Payment::byPaymentIntent($paymentIntent->id)->first();
        
        if (!$payment) {
            return ['status' => 'payment_not_found'];
        }

        if ($payment->isSuccessful() && $payment->appointment_id) {
            return ['status' => 'already_confirmed'];
        }

        $payment->markSucceeded();
        return ['status' => 'payment_marked_succeeded'];
    }

    private function handlePaymentFailed(object $paymentIntent): array
    {
        $payment = Payment::byPaymentIntent($paymentIntent->id)->first();
        
        if ($payment) {
            $payment->markFailed();

            $metadata = $payment->metadata ?? [];
            $holdUuid = $metadata['hold_uuid'] ?? null;
            
            if ($holdUuid) {
                $hold = $this->bookingService->getHoldByUuid($holdUuid);
                if ($hold) {
                    $this->bookingService->releaseHold($hold);
                }
            }
        }

        return ['status' => 'payment_failed_processed'];
    }

    private function calculatePaymentAmount(Service $service): int
    {
        if (config('salon.require_full_payment', false)) {
            return $service->price_cents;
        }

        if ($service->deposit_cents) {
            return $service->deposit_cents;
        }

        $depositPercent = config('salon.deposit_percentage', 25);
        return (int) ceil($service->price_cents * ($depositPercent / 100));
    }

    private function getPaymentType(Service $service): string
    {
        return config('salon.require_full_payment', false) ? 'full_payment' : 'deposit';
    }

    public function getPaymentBySession(string $sessionId): ?Payment
    {
        return Payment::byCheckoutSession($sessionId)->first();
    }

    public function retrieveSession(string $sessionId): ?StripeSession
    {
        try {
            return StripeSession::retrieve($sessionId);
        } catch (Exception $e) {
            Log::error('Failed to retrieve Stripe session', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
