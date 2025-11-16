<?php

namespace App\Models;

use App\Enums\ShiftOfferStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Builder;

class ShiftOffer extends Model
{
    use HasFactory;

    protected $fillable = [
        'shift_id',
        'agency_employee_id',
        'agency_id',
        'agent_id',
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

    protected $appends = [
        'is_expired',
        'can_respond',
    ];

    protected static function booted()
    {
        static::creating(function ($offer) {
            if (!$offer->agency_id && $offer->agent_id) {
                $offer->agency_id = $offer->agent->agency_id;
            }
        });

        static::saving(function ($offer) {
            if ($offer->isDirty('status') && in_array($offer->status, ['accepted', 'rejected'])) {
                $offer->responded_at = now();
            }
        });
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function agencyEmployee(): BelongsTo
    {
        return $this->belongsTo(AgencyEmployee::class);
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function employee(): HasOneThrough
    {
        return $this->hasOneThrough(
            Employee::class,
            AgencyEmployee::class,
            'id',
            'id',
            'agency_employee_id',
            'employee_id'
        );
    }

    public function isPending(): bool
    {
        return $this->status === ShiftOfferStatus::PENDING;
    }

    public function isAccepted(): bool
    {
        return $this->status === ShiftOfferStatus::ACCEPTED;
    }

    public function isRejected(): bool
    {
        return $this->status === ShiftOfferStatus::REJECTED;
    }

    public function isExpired(): bool
    {
        return $this->status === ShiftOfferStatus::EXPIRED;
    }

    public function isCancelled(): bool
    {
        return $this->status === ShiftOfferStatus::CANCELLED;
    }

    protected function getIsExpiredAttribute(): bool
    {
        return $this->expires_at->isPast() && $this->isPending();
    }

    protected function getCanRespondAttribute(): bool
    {
        return $this->isPending() && !$this->getIsExpiredAttribute();
    }

    public function canBeAccepted(): bool
    {
        if (!$this->getCanRespondAttribute()) {
            return false;
        }

        if (!$this->shift->isAvailable()) {
            return false;
        }

        if (!$this->agencyEmployee->canAcceptNewAssignments()) {
            return false;
        }

        return $this->validateAgencyConsistency();
    }

    public function canBeRejected(): bool
    {
        return $this->getCanRespondAttribute();
    }

    public function canBeCancelled(): bool
    {
        
        return $this->isPending() && $this->agent_id === auth()->id();
    }

    public function accept(?string $notes = null): bool
    {
        if (!$this->canBeAccepted()) {
            return false;
        }

        return $this->update([
            'status' => ShiftOfferStatus::ACCEPTED,
            'response_notes' => $notes,
        ]);
    }

    public function reject(?string $notes = null): bool
    {
        if (!$this->canBeRejected()) {
            return false;
        }

        return $this->update([
            'status' => ShiftOfferStatus::REJECTED,
            'response_notes' => $notes,
        ]);
    }

    public function cancel(): bool
    {
        if (!$this->canBeCancelled()) {
            return false;
        }

        return $this->update([
            'status' => ShiftOfferStatus::CANCELLED,
        ]);
    }

    public function markAsExpired(): bool
    {
        if (!$this->isPending() || !$this->getIsExpiredAttribute()) {
            return false;
        }

        return $this->update([
            'status' => ShiftOfferStatus::EXPIRED,
        ]);
    }

    public function validateAgencyConsistency(): bool
    {
        return $this->agent->agency_id === $this->agency_id &&
            $this->agencyEmployee->agency_id === $this->agency_id;
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

    public function scopeCancelled($query)
    {
        return $query->where('status', ShiftOfferStatus::CANCELLED);
    }

    public function scopeActive($query)
    {
        return $query->where('status', ShiftOfferStatus::PENDING)
            ->where('expires_at', '>', now());
    }

    public function scopeExpiringSoon($query, $hours = 24)
    {
        return $query->where('status', ShiftOfferStatus::PENDING)
            ->whereBetween('expires_at', [now(), now()->addHours($hours)]);
    }

    public function scopeForAgency($query, $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }

    public function scopeForAgent($query, $agentId)
    {
        return $query->where('agent_id', $agentId);
    }

    public function scopeForAgencyEmployee($query, $agencyEmployeeId)
    {
        return $query->where('agency_employee_id', $agencyEmployeeId);
    }

    public function scopeForShift($query, $shiftId)
    {
        return $query->where('shift_id', $shiftId);
    }

    public function scopeRequiringAction($query)
    {
        return $query->where('status', ShiftOfferStatus::PENDING)
            ->where('expires_at', '>', now());
    }

    public function scopeVisibleToAgency(Builder $query, int $agencyId): Builder
    {
        return $query->where('agency_id', $agencyId);
    }

    public function scopeVisibleToAgent(Builder $query, int $agentId): Builder
    {
        $agent = Agent::find($agentId);
        return $query->where('agency_id', $agent->agency_id);
    }
}
