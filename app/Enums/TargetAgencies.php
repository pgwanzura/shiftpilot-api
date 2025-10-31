<?php

namespace App\Enums;

enum TargetAgencies: string
{
    case ALL = 'all';
    case SPECIFIC = 'specific';

    public function label(): string
    {
        return match ($this) {
            self::ALL => 'All Agencies',
            self::SPECIFIC => 'Specific Agencies',
        };
    }
}
