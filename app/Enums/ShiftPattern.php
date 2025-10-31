<?php

namespace App\Enums;

enum ShiftPattern: string
{
    case ONE_TIME = 'one_time';
    case RECURRING = 'recurring';
    case ONGOING = 'ongoing';

    public function label(): string
    {
        return match ($this) {
            self::ONE_TIME => 'One Time',
            self::RECURRING => 'Recurring',
            self::ONGOING => 'Ongoing',
        };
    }
}
