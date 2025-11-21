<?php

namespace App\Enums;

enum AssignmentType: string
{
    case TEMP = 'temp';
    case CONTRACT = 'contract';
    case DIRECT = 'direct';

    public function label(): string
    {
        return match ($this) {
            self::TEMP => 'Temporary Assignment',
            self::CONTRACT => 'Contract Assignment',
            self::DIRECT => 'Direct Assignment',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::TEMP => 'A Temporary assignment created for short-term staffing needs',
            self::CONTRACT => 'A Contract assignment created for long-term engagements',
            self::DIRECT => 'A Direct assignment created for immediate staffing requirements',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
