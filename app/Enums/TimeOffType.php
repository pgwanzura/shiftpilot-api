<?php

namespace App\Enums;

enum TimeOffType: string
{
    case VACATION = 'vacation';
    case SICK = 'sick';
    case PERSONAL = 'personal';
    case BEREAVEMENT = 'bereavement';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::VACATION => 'Vacation',
            self::SICK => 'Sick Leave',
            self::PERSONAL => 'Personal Time',
            self::BEREAVEMENT => 'Bereavement',
            self::OTHER => 'Other',
        };
    }

    public function requiresApproval(): bool
    {
        return match ($this) {
            self::VACATION => true,
            self::SICK => false, // Sick leave might be auto-approved or have different rules
            self::PERSONAL => true,
            self::BEREAVEMENT => false, // Bereavement is typically auto-approved
            self::OTHER => true,
        };
    }

    public function isPaid(): bool
    {
        return match ($this) {
            self::VACATION => true,
            self::SICK => true,
            self::PERSONAL => false, // Personal time might be unpaid
            self::BEREAVEMENT => true,
            self::OTHER => false,
        };
    }

    public function maxDurationDays(): ?int
    {
        return match ($this) {
            self::VACATION => 30,
            self::SICK => 14,
            self::PERSONAL => 5,
            self::BEREAVEMENT => 7,
            self::OTHER => null,
        };
    }

    public function requiresDocumentation(): bool
    {
        return match ($this) {
            self::SICK => true,
            self::BEREAVEMENT => true,
            default => false,
        };
    }

    public static function options(): array
    {
        return [
            self::VACATION->value => self::VACATION->label(),
            self::SICK->value => self::SICK->label(),
            self::PERSONAL->value => self::PERSONAL->label(),
            self::BEREAVEMENT->value => self::BEREAVEMENT->label(),
            self::OTHER->value => self::OTHER->label(),
        ];
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
