<?php

namespace App\Enums;

enum BudgetType: string
{
    case HOURLY = 'hourly';
    case DAILY = 'daily';
    case FIXED = 'fixed';

    public function label(): string
    {
        return match ($this) {
            self::HOURLY => 'Hourly',
            self::DAILY => 'Daily',
            self::FIXED => 'Fixed',
        };
    }
}
