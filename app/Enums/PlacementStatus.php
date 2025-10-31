<?php

namespace App\Enums;

enum PlacementStatus: string
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case FILLED = 'filled';
    case CANCELLED = 'cancelled';
    case COMPLETED = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::ACTIVE => 'Active',
            self::FILLED => 'Filled',
            self::CANCELLED => 'Cancelled',
            self::COMPLETED => 'Completed',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
