<?php

declare(strict_types=1);

namespace App\Enums;

enum AppointmentStatus: string
{
    case Held = 'held';
    case Confirmed = 'confirmed';
    case CancelledByClient = 'cancelled_by_client';
    case CancelledBySalon = 'cancelled_by_salon';
    case CancelledBySystem = 'cancelled_by_system';
    case NoShow = 'no_show';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Held => 'Held',
            self::Confirmed => 'Confirmed',
            self::CancelledByClient => 'Cancelled by Client',
            self::CancelledBySalon => 'Cancelled by Salon',
            self::CancelledBySystem => 'Expired',
            self::NoShow => 'No Show',
            self::Completed => 'Completed',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::Held, self::Confirmed]);
    }

    public function isCancelled(): bool
    {
        return in_array($this, [
            self::CancelledByClient,
            self::CancelledBySalon,
            self::CancelledBySystem,
        ]);
    }

    public function blocksTimeSlot(): bool
    {
        return in_array($this, [self::Held, self::Confirmed]);
    }
}
