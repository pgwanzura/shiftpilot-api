<?php

namespace App\Enums;

enum ShiftTemplateStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case ARCHIVED = 'archived';
    case PAUSED = 'paused';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::ARCHIVED => 'Archived',
            self::PAUSED => 'Paused',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
