<?php

namespace App\Enums;

enum AssignmentType: string
{
    case ONGOING = 'ongoing';
    case TEMPORARY = 'temporary';
    case PROJECT_BASED = 'project_based';
    case SEASONAL = 'seasonal';

    public function label(): string
    {
        return match($this) {
            self::ONGOING => 'Ongoing',
            self::TEMPORARY => 'Temporary',
            self::PROJECT_BASED => 'Project Based',
            self::SEASONAL => 'Seasonal',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}