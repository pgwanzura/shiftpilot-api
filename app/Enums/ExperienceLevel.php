<?php

namespace App\Enums;

enum ExperienceLevel: string
{
    case ENTRY = 'entry';
    case INTERMEDIATE = 'intermediate';
    case SENIOR = 'senior';

    public function label(): string
    {
        return match ($this) {
            self::ENTRY => 'Entry Level',
            self::INTERMEDIATE => 'Intermediate',
            self::SENIOR => 'Senior',
        };
    }
}
