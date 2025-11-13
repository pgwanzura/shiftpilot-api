<?php

namespace App\Enums;

enum TimesheetStatus: string
{
    case PENDING = 'pending';
    case AGENCY_APPROVED = 'agency_approved';
    case EMPLOYER_APPROVED = 'employer_approved';
    case DISPUTED = 'disputed';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::AGENCY_APPROVED => 'Agency Approved',
            self::EMPLOYER_APPROVED => 'Employer Approved',
            self::DISPUTED => 'Disputed',
            self::REJECTED => 'Rejected',
        };
    }

    public function isApproved(): bool
    {
        return $this === self::EMPLOYER_APPROVED;
    }

    public function canBeProcessed(): bool
    {
        return in_array($this, [self::PENDING, self::AGENCY_APPROVED]);
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
