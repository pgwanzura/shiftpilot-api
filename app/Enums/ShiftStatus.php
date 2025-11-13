<?php

namespace App\Enums;

enum ShiftStatus: string
{
    case SCHEDULED = 'scheduled';
    case PENDING_APPROVAL = 'pending_approval';
    case APPROVED = 'approved';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case NO_SHOW = 'no_show';
    case DISPUTED = 'disputed';
    case REJECTED = 'rejected';
    case UNAVAILABLE = 'unavailable';

    public function label(): string
    {
        return match ($this) {
            self::SCHEDULED => 'Scheduled',
            self::PENDING_APPROVAL => 'Pending Approval',
            self::APPROVED => 'Approved',
            self::IN_PROGRESS => 'In Progress',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
            self::NO_SHOW => 'No Show',
            self::DISPUTED => 'Disputed',
            self::REJECTED => 'Rejected',
            self::UNAVAILABLE => 'Unavailable',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::SCHEDULED => 'blue',
            self::PENDING_APPROVAL => 'yellow',
            self::APPROVED => 'green',
            self::IN_PROGRESS => 'purple',
            self::COMPLETED => 'green',
            self::CANCELLED => 'red',
            self::NO_SHOW => 'red',
            self::DISPUTED => 'orange',
            self::REJECTED => 'red',
            self::UNAVAILABLE => 'gray',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::SCHEDULED => 'calendar',
            self::PENDING_APPROVAL => 'clock',
            self::APPROVED => 'check-circle',
            self::IN_PROGRESS => 'play-circle',
            self::COMPLETED => 'check-circle',
            self::CANCELLED => 'x-circle',
            self::NO_SHOW => 'user-x',
            self::DISPUTED => 'alert-triangle',
            self::REJECTED => 'x-circle',
            self::UNAVAILABLE => 'slash',
        };
    }

    public function isUpcoming(): bool
    {
        return in_array($this, [
            self::SCHEDULED,
            self::PENDING_APPROVAL,
            self::APPROVED,
        ]);
    }

    public function isActive(): bool
    {
        return in_array($this, [
            self::SCHEDULED,
            self::PENDING_APPROVAL,
            self::APPROVED,
            self::IN_PROGRESS,
        ]);
    }

    public function isWorkable(): bool
    {
        return in_array($this, [
            self::APPROVED,
            self::IN_PROGRESS,
        ]);
    }

    public function canBeStarted(): bool
    {
        return in_array($this, [
            self::SCHEDULED,
            self::APPROVED,
        ]);
    }

    public function canBeCompleted(): bool
    {
        return in_array($this, [
            self::IN_PROGRESS,
        ]);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this, [
            self::SCHEDULED,
            self::PENDING_APPROVAL,
            self::APPROVED,
        ]);
    }

    public function canHaveTimesheet(): bool
    {
        return in_array($this, [
            self::IN_PROGRESS,
            self::COMPLETED,
            self::NO_SHOW,
            self::DISPUTED,
        ]);
    }

    public function isFinal(): bool
    {
        return in_array($this, [
            self::COMPLETED,
            self::CANCELLED,
            self::NO_SHOW,
            self::REJECTED,
            self::UNAVAILABLE,
        ]);
    }

    public function requiresApproval(): bool
    {
        return $this === self::PENDING_APPROVAL;
    }

    public function isProblematic(): bool
    {
        return in_array($this, [
            self::NO_SHOW,
            self::DISPUTED,
            self::REJECTED,
        ]);
    }

    public function canTransitionTo(ShiftStatus $newStatus): bool
    {
        return match ($this) {
            self::SCHEDULED => in_array($newStatus, [
                self::PENDING_APPROVAL,
                self::APPROVED,
                self::IN_PROGRESS,
                self::CANCELLED,
                self::UNAVAILABLE,
            ]),
            self::PENDING_APPROVAL => in_array($newStatus, [
                self::APPROVED,
                self::REJECTED,
                self::CANCELLED,
            ]),
            self::APPROVED => in_array($newStatus, [
                self::IN_PROGRESS,
                self::CANCELLED,
                self::UNAVAILABLE,
            ]),
            self::IN_PROGRESS => in_array($newStatus, [
                self::COMPLETED,
                self::NO_SHOW,
                self::DISPUTED,
            ]),
            self::COMPLETED => in_array($newStatus, [
                self::DISPUTED,
            ]),
            self::DISPUTED => in_array($newStatus, [
                self::COMPLETED,
                self::CANCELLED,
            ]),
            default => false,
        };
    }

    public static function activeStates(): array
    {
        return [
            self::SCHEDULED->value,
            self::PENDING_APPROVAL->value,
            self::APPROVED->value,
            self::IN_PROGRESS->value,
        ];
    }

    public static function completedStates(): array
    {
        return [
            self::COMPLETED->value,
            self::CANCELLED->value,
            self::NO_SHOW->value,
            self::REJECTED->value,
            self::UNAVAILABLE->value,
        ];
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
