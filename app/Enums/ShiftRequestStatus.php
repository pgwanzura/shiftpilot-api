<?php

namespace App\Enums;

enum ShiftRequestStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case IN_PROGRESS = 'in_progress';
    case FILLED = 'filled';
    case CANCELLED = 'cancelled';
    case COMPLETED = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PUBLISHED => 'Published',
            self::IN_PROGRESS => 'In Progress',
            self::FILLED => 'Filled',
            self::CANCELLED => 'Cancelled',
            self::COMPLETED => 'Completed',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::PUBLISHED, self::IN_PROGRESS]);
    }

    public function canAcceptResponses(): bool
    {
        return $this === self::PUBLISHED;
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::FILLED, self::CANCELLED, self::COMPLETED]);
    }

    public function canBeModified(): bool
    {
        return in_array($this, [self::DRAFT, self::PUBLISHED]);
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
