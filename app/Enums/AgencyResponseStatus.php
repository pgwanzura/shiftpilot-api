<?php

namespace App\Enums;

enum AgencyResponseStatus: string
{
    case PENDING = 'pending';
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';
    case WITHDRAWN = 'withdrawn';
    case COUNTER_OFFERED = 'counter_offered';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::ACCEPTED => 'Accepted',
            self::REJECTED => 'Rejected',
            self::WITHDRAWN => 'Withdrawn',
            self::COUNTER_OFFERED => 'Counter Offered',
        };
    }

    public function canBeAccepted(): bool
    {
        return in_array($this, [self::PENDING, self::COUNTER_OFFERED]);
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::ACCEPTED, self::REJECTED, self::WITHDRAWN]);
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
