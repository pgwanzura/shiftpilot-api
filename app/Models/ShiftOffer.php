<?php

namespace App\Models;

use App\Enums\ShiftOfferStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftOffer extends Model
{
    use HasFactory;

    protected $fillable = [
        'shift_id',
        'agency_employee_id',
        'offered_by_id',
        'status',
        'expires_at',
        'responded_at',
        'response_notes',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'responded_at' => 'datetime',
        'status' => ShiftOfferStatus::class,
    ];

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function agencyEmployee(): BelongsTo
    {
        return $this->belongsTo(AgencyEmployee::class);
    }

    public function offeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'offered_by_id');
    }

    public function isPending(): bool
    {
        return $this->status->isPending();
    }

    public function isAccepted(): bool
    {
        return $this->status->isAccepted();
    }

    public function isRejected(): bool
    {
        return $this->status->isRejected();
    }

    public function isExpired(): bool
    {
        return $this->status->isExpired();
    }

    public function canBeAccepted(): bool
    {
        return $this->status->canBeAccepted() && !$this->isExpired();
    }

    public function canBeRejected(): bool
    {
        return $this->status->canBeRejected() && !$this->isExpired();
    }

    public function isExpiredByTime(): bool
    {
        return $this->expires_at && now()->gt($this->expires_at) && $this->isPending();
    }

    public function accept(string $notes = null): bool
    {
        if (!$this->canBeAccepted()) {
            return false;
        }

        return $this->update([
            'status' => ShiftOfferStatus::ACCEPTED,
            'responded_at' => now(),
            'response_notes' => $notes,
        ]);
    }

    public function reject(string $notes = null): bool
    {
        if (!$this->canBeRejected()) {
            return false;
        }

        return $this->update([
            'status' => ShiftOfferStatus::REJECTED,
            'responded_at' => now(),
            'response_notes' => $notes,
        ]);
    }

    public function markAsExpired(): bool
    {
        if (!$this->status->canExpire() || !$this->isExpiredByTime()) {
            return false;
        }

        return $this->update([
            'status' => ShiftOfferStatus::EXPIRED,
            'responded_at' => now(),
        ]);
    }

    public function getResponseDeadlineStatus(): string
    {
        return $this->status->getResponseDeadlineStatus($this->expires_at);
    }

    public function scopePending($query)
    {
        return $query->where('status', ShiftOfferStatus::PENDING);
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', ShiftOfferStatus::ACCEPTED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', ShiftOfferStatus::REJECTED);
    }

    public function scopeExpired($query)
    {
        return $query->where('status', ShiftOfferStatus::EXPIRED);
    }

    public function scopeActive($query)
    {
        return $query->where('status', ShiftOfferStatus::PENDING)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeRequiringAction($query)
    {
        return $query->where('status', ShiftOfferStatus::PENDING)
            ->where('expires_at', '>', now());
    }
}
