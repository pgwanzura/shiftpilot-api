<?php

namespace App\Enums;

enum ShiftOfferStatus: string
{
    case PENDING = 'pending';
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';
    case EXPIRED = 'expired';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::ACCEPTED => 'Accepted',
            self::REJECTED => 'Rejected',
            self::EXPIRED => 'Expired',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING => 'orange',
            self::ACCEPTED => 'green',
            self::REJECTED => 'red',
            self::EXPIRED => 'gray',
        };
    }

    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    public function isAccepted(): bool
    {
        return $this === self::ACCEPTED;
    }

    public function isRejected(): bool
    {
        return $this === self::REJECTED;
    }

    public function isExpired(): bool
    {
        return $this === self::EXPIRED;
    }

    public function isActive(): bool
    {
        return $this === self::PENDING;
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::ACCEPTED, self::REJECTED, self::EXPIRED]);
    }

    public function canBeAccepted(): bool
    {
        return $this === self::PENDING;
    }

    public function canBeRejected(): bool
    {
        return $this === self::PENDING;
    }

    public function canExpire(): bool
    {
        return $this === self::PENDING;
    }

    public function canBeWithdrawn(): bool
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
            self::ACCEPTED->value,
            self::REJECTED->value,
            self::EXPIRED->value,
        ];
    }

    public static function successfulStates(): array
    {
        return [
            self::ACCEPTED->value,
        ];
    }

    public static function unsuccessfulStates(): array
    {
        return [
            self::REJECTED->value,
            self::EXPIRED->value,
        ];
    }

    public static function options(): array
    {
        return [
            self::PENDING->value => self::PENDING->label(),
            self::ACCEPTED->value => self::ACCEPTED->label(),
            self::REJECTED->value => self::REJECTED->label(),
            self::EXPIRED->value => self::EXPIRED->label(),
        ];
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function getTransitionTargets(): array
    {
        return match($this) {
            self::PENDING => [self::ACCEPTED, self::REJECTED, self::EXPIRED],
            self::ACCEPTED => [],
            self::REJECTED => [],
            self::EXPIRED => [],
        };
    }

    public function isValidTransition(self $targetStatus): bool
    {
        return in_array($targetStatus, $this->getTransitionTargets());
    }

    public function getNextActions(): array
    {
        return match($this) {
            self::PENDING => ['accept', 'reject', 'expire'],
            self::ACCEPTED => [],
            self::REJECTED => [],
            self::EXPIRED => [],
        };
    }

    public function getNotificationEvent(): ?string
    {
        return match($this) {
            self::PENDING => 'shift_offer.sent',
            self::ACCEPTED => 'shift_offer.accepted',
            self::REJECTED => 'shift_offer.rejected',
            self::EXPIRED => null, // No specific event for expired
        };
    }

    public function allowsAutoAccept(): bool
    {
        return $this === self::PENDING;
    }

    public function requiresEmployeeAction(): bool
    {
        return $this === self::PENDING;
    }

    public function isActionable(): bool
    {
        return $this === self::PENDING;
    }

    public function getResponseDeadlineStatus(?string $expiresAt): string
    {
        if (!$expiresAt) {
            return 'no_deadline';
        }

        $expiry = \Carbon\Carbon::parse($expiresAt);
        
        if ($this->isFinal()) {
            return 'responded';
        }

        if (now()->gt($expiry)) {
            return 'expired';
        }

        if (now()->diffInHours($expiry) < 24) {
            return 'urgent';
        }

        return 'active';
    }
}