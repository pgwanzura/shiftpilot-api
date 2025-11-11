<?php
// app/Enums/ShiftStatus.php

namespace App\Enums;

enum ShiftStatus: string
{
    case SCHEDULED = 'scheduled';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case NO_SHOW = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::SCHEDULED => 'Scheduled',
            self::IN_PROGRESS => 'In Progress',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
            self::NO_SHOW => 'No Show',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function canBeUpdated(): bool
    {
        return in_array($this, [
            self::SCHEDULED,
            self::IN_PROGRESS,
        ]);
    }

    public function isActive(): bool
    {
        return in_array($this, [
            self::SCHEDULED,
            self::IN_PROGRESS,
        ]);
    }

    public function isCompleted(): bool
    {
        return $this === self::COMPLETED;
    }

    public function isCancelled(): bool
    {
        return in_array($this, [
            self::CANCELLED,
            self::NO_SHOW,
        ]);
    }
}
