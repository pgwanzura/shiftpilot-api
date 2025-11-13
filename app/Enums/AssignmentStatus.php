<?php

namespace App\Enums;

enum AssignmentStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case SUSPENDED = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::ACTIVE => 'Active',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
            self::SUSPENDED => 'Suspended',
        };
    }

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    public function canBeModified(): bool
    {
        return in_array($this, [self::PENDING, self::ACTIVE]);
    }

    public static function activeStates(): array
    {
        return [self::PENDING->value, self::ACTIVE->value];
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
