<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Appointment;
use App\Models\PolicySetting;
use App\Models\Service;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class AvailabilityService
{
    /**
     * Get available time slots for a service on a given date.
     *
     * @return Collection<int, array{time: string, available: bool}>
     */
    public function getAvailableSlots(int $serviceId, string $date): Collection
    {
        $service = Service::findOrFail($serviceId);
        $dateObj = CarbonImmutable::parse($date)->startOfDay();

        // Get business hours (hardcoded for MVP)
        $businessStart = PolicySetting::getValue('business_hours_start', '09:00');
        $businessEnd = PolicySetting::getValue('business_hours_end', '18:00');
        $slotInterval = PolicySetting::getValue('slot_interval_minutes', 15);
        $bufferMinutes = PolicySetting::getValue('buffer_minutes', 0);

        // Calculate time boundaries
        $dayStart = $dateObj->setTimeFromTimeString($businessStart);
        $dayEnd = $dateObj->setTimeFromTimeString($businessEnd);

        // Generate all possible slots
        $slots = collect();
        $current = $dayStart;

        while ($current->addMinutes($service->duration_minutes)->lte($dayEnd)) {
            $slots->push($current);
            $current = $current->addMinutes($slotInterval);
        }

        // Get existing appointments that block slots
        $blockedAppointments = Appointment::query()
            ->blocksSlot()
            ->whereDate('starts_at', $dateObj)
            ->get(['starts_at', 'ends_at']);

        // Check availability for each slot
        return $slots->map(function (CarbonImmutable $slotStart) use (
            $service, $blockedAppointments, $bufferMinutes
        ) {
            $slotEnd = $slotStart->addMinutes($service->duration_minutes + $bufferMinutes);

            // Check for overlaps
            $isBlocked = $blockedAppointments->contains(function ($appt) use ($slotStart, $slotEnd) {
                return $slotStart->lt($appt->ends_at) && $slotEnd->gt($appt->starts_at);
            });

            // Also check if slot is in the past
            $isPast = $slotStart->lte(CarbonImmutable::now());

            return [
                'time' => $slotStart->format('H:i'),
                'starts_at' => $slotStart->toIso8601String(),
                'available' => !$isBlocked && !$isPast,
            ];
        });
    }

    /**
     * Get available dates for the next N days.
     *
     * @return Collection<int, array{date: string, has_availability: bool}>
     */
    public function getAvailableDates(int $serviceId, int $daysAhead = 30): Collection
    {
        $dates = collect();
        $today = CarbonImmutable::now()->startOfDay();

        for ($i = 0; $i < $daysAhead; $i++) {
            $date = $today->addDays($i);
            $slots = $this->getAvailableSlots($serviceId, $date->format('Y-m-d'));
            $hasAvailability = $slots->where('available', true)->isNotEmpty();

            $dates->push([
                'date' => $date->format('Y-m-d'),
                'day_name' => $date->format('l'),
                'has_availability' => $hasAvailability,
            ]);
        }

        return $dates;
    }
}
