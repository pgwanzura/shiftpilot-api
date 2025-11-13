<?php

namespace App\Enums;

enum AssignmentType: string
{
    case DIRECT = 'direct';
    case STANDARD = 'standard';

    public function label(): string
    {
        return match ($this) {
            self::DIRECT => 'Direct Assignment',
            self::STANDARD => 'Standard Assignment',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::DIRECT => 'Employer directly assigns employee without agency bidding',
            self::STANDARD => 'Assignment created through shift request and agency response process',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
