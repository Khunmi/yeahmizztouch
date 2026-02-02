<?php

declare(strict_types=1);

namespace App\Enums;

enum PhotoConsent: string
{
    case FullOk = 'full_ok';
    case NoFace = 'no_face';
    case Declined = 'declined';

    public function label(): string
    {
        return match ($this) {
            self::FullOk => 'Full consent (face included)',
            self::NoFace => 'No face shown',
            self::Declined => 'Declined',
        };
    }
}
