<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgencyAssignmentResponse extends Model
{
    use HasFactory;

    protected $table = 'agency_assignment_responses';

    protected $fillable = [
        'assignment_id',
        'agency_id',
        'proposal_text',
        'proposed_rate',
        'estimated_hours',
        'status',
        'rejection_reason',
        'submitted_at',
        'responded_at',
    ];

    protected $casts = [
        'proposed_rate' => 'decimal:2',
        'submitted_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_REVIEWED = 'reviewed';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';

    public static function getStatuses(): array
    {
        return [
            self::STATUS_SUBMITTED => 'Submitted',
            self::STATUS_REVIEWED => 'Reviewed',
            self::STATUS_ACCEPTED => 'Accepted',
            self::STATUS_REJECTED => 'Rejected',
        ];
    }

    // Relationships
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    // Scopes
    public function scopeSubmitted($query)
    {
        return $query->where('status', self::STATUS_SUBMITTED);
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', self::STATUS_ACCEPTED);
    }

    public function scopeForAssignment($query, $assignmentId)
    {
        return $query->where('assignment_id', $assignmentId);
    }

    public function scopeForAgency($query, $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }

    public function scopeWithAssignment($query)
    {
        return $query->with(['assignment', 'assignment.location', 'assignment.employer']);
    }

    public function scopeWithAgency($query)
    {
        return $query->with(['agency']);
    }

    // Business logic methods
    public function canBeUpdated(): bool
    {
        return in_array($this->status, [self::STATUS_SUBMITTED, self::STATUS_REVIEWED]);
    }

    public function markAsReviewed(): void
    {
        $this->update([
            'status' => self::STATUS_REVIEWED,
            'responded_at' => now(),
        ]);
    }

    public function accept(): void
    {
        $this->update([
            'status' => self::STATUS_ACCEPTED,
            'responded_at' => now(),
        ]);
    }

    public function reject(string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'rejection_reason' => $reason,
            'responded_at' => now(),
        ]);
    }

    public function getTotalProposedAmount(): float
    {
        return $this->proposed_rate * $this->estimated_hours;
    }

    public function isAccepted(): bool
    {
        return $this->status === self::STATUS_ACCEPTED;
    }

    public function isSubmitted(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->submitted_at)) {
                $model->submitted_at = now();
            }
        });
    }
}
