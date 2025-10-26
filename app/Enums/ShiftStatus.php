<?php

namespace App\Enums;

enum ShiftStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            self::CANCELLED => 'Cancelled',
            self::IN_PROGRESS => 'In Progress',
            self::COMPLETED => 'Completed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'yellow',
            self::APPROVED => 'green',
            self::REJECTED => 'red',
            self::CANCELLED => 'gray',
            self::IN_PROGRESS => 'blue',
            self::COMPLETED => 'purple',
        };
    }

    public function canBeUpdated(): bool
    {
        return in_array($this, [self::PENDING, self::APPROVED]);
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
