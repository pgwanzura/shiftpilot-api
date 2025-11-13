<?php

namespace App\Enums;

enum TimeOffRequestStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'orange',
            self::APPROVED => 'green',
            self::REJECTED => 'red',
        };
    }

    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    public function isApproved(): bool
    {
        return $this === self::APPROVED;
    }

    public function isRejected(): bool
    {
        return $this === self::REJECTED;
    }

    public function isFinal(): bool
    {
        return $this === self::APPROVED || $this === self::REJECTED;
    }

    public function canBeApproved(): bool
    {
        return $this === self::PENDING;
    }

    public function canBeRejected(): bool
    {
        return $this === self::PENDING;
    }

    public function canBeCancelled(): bool
    {
        return $this === self::PENDING;
    }

    public static function activeStates(): array
    {
        return [
            self::PENDING->value,
        ];
    }

    public static function completedStates(): array
    {
        return [
            self::APPROVED->value,
            self::REJECTED->value,
        ];
    }

    public static function options(): array
    {
        return [
            self::PENDING->value => self::PENDING->label(),
            self::APPROVED->value => self::APPROVED->label(),
            self::REJECTED->value => self::REJECTED->label(),
        ];
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function getTransitionTargets(): array
    {
        return match ($this) {
            self::PENDING => [self::APPROVED, self::REJECTED],
            self::APPROVED => [],
            self::REJECTED => [],
        };
    }

    public function isValidTransition(self $targetStatus): bool
    {
        return in_array($targetStatus, $this->getTransitionTargets());
    }

    public function getNextActions(): array
    {
        return match ($this) {
            self::PENDING => ['approve', 'reject'],
            self::APPROVED => [],
            self::REJECTED => [],
        };
    }

    public function getNotificationEvent(): ?string
    {
        return match ($this) {
            self::PENDING => 'time_off.requested',
            self::APPROVED => 'time_off.approved',
            self::REJECTED => 'time_off.rejected',
        };
    }
}
