<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentType: string
{
    case Deposit = 'deposit';
    case Full = 'full';
    case LateFee = 'late_fee';
    case NoShowCharge = 'no_show_charge';
    case SqueezeInFee = 'squeeze_in_fee';
    case LateCancelCharge = 'late_cancel_charge';

    public function label(): string
    {
        return match ($this) {
            self::Deposit => 'Deposit',
            self::Full => 'Full Payment',
            self::LateFee => 'Late Fee',
            self::NoShowCharge => 'No-Show Charge',
            self::SqueezeInFee => 'Squeeze-In Fee',
            self::LateCancelCharge => 'Late Cancellation Charge',
        };
    }
}
