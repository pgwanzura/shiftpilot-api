<?php

namespace App\Models;

use App\Enums\TimeOffRequestStatus;
use App\Enums\TimeOffType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeOffRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'agency_id',
        'start_date',
        'end_date',
        'type',
        'reason',
        'status',
        'approved_by_id',
        'approved_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
        'type' => TimeOffType::class,
        'status' => TimeOffRequestStatus::class,
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function isPending(): bool
    {
        return $this->status->isPending();
    }

    public function isApproved(): bool
    {
        return $this->status->isApproved();
    }

    public function isRejected(): bool
    {
        return $this->status->isRejected();
    }

    public function canBeApproved(): bool
    {
        return $this->status->canBeApproved();
    }

    public function canBeRejected(): bool
    {
        return $this->status->canBeRejected();
    }

    public function approve(User $approvedBy): bool
    {
        if (!$this->canBeApproved()) {
            return false;
        }

        return $this->update([
            'status' => TimeOffRequestStatus::APPROVED,
            'approved_by_id' => $approvedBy->id,
            'approved_at' => now(),
        ]);
    }

    public function reject(User $rejectedBy): bool
    {
        if (!$this->canBeRejected()) {
            return false;
        }

        return $this->update([
            'status' => TimeOffRequestStatus::REJECTED,
            'approved_by_id' => $rejectedBy->id,
            'approved_at' => now(),
        ]);
    }
}
