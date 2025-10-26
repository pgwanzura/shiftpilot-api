<?php

namespace App\Enums;

enum DayOfWeek: string
{
    case MONDAY = 'mon';
    case TUESDAY = 'tue';
    case WEDNESDAY = 'wed';
    case THURSDAY = 'thu';
    case FRIDAY = 'fri';
    case SATURDAY = 'sat';
    case SUNDAY = 'sun';

    public function label(): string
    {
        return match ($this) {
            self::MONDAY => 'Monday',
            self::TUESDAY => 'Tuesday',
            self::WEDNESDAY => 'Wednesday',
            self::THURSDAY => 'Thursday',
            self::FRIDAY => 'Friday',
            self::SATURDAY => 'Saturday',
            self::SUNDAY => 'Sunday',
        };
    }

    public function numeric(): int
    {
        return match ($this) {
            self::MONDAY => 1,
            self::TUESDAY => 2,
            self::WEDNESDAY => 3,
            self::THURSDAY => 4,
            self::FRIDAY => 5,
            self::SATURDAY => 6,
            self::SUNDAY => 7,
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
