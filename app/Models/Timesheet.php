<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

class Timesheet extends Model
{
    use HasFactory;

    protected $fillable = [
        'shift_id',
        'employee_id',
        'clock_in',
        'clock_out',
        'break_minutes',
        'hours_worked',
        'status',
        'agency_approved_by_id',
        'agency_approved_at',
        'employer_approved_by_id',
        'employer_approved_at',
        'notes',
        'attachments',
    ];

    protected $casts = [
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
        'hours_worked' => 'decimal:2',
        'agency_approved_at' => 'datetime',
        'employer_approved_at' => 'datetime',
        'attachments' => 'array',
        'break_minutes' => 'integer',
    ];

    protected $appends = [
        'net_hours_worked',
        'is_approved',
        'requires_approval',
    ];

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function agencyApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agency_approved_by_id');
    }

    public function employerApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employer_approved_by_id');
    }

    protected function netHoursWorked(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->hours_worked) {
                    return 0.0;
                }
                $breakHours = $this->break_minutes / 60;
                return max(0, $this->hours_worked - $breakHours);
            }
        );
    }

    protected function isApproved(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->status === 'approved'
        );
    }

    protected function requiresApproval(): Attribute
    {
        return Attribute::make(
            get: fn() => in_array($this->status, ['pending', 'submitted'])
        );
    }

    public function calculateHoursWorked(): float
    {
        if (!$this->clock_in || !$this->clock_out) {
            return 0.0;
        }

        $start = Carbon::parse($this->clock_in);
        $end = Carbon::parse($this->clock_out);

        return $start->diffInHours($end);
    }

    public function autoCalculateHours(): bool
    {
        $calculatedHours = $this->calculateHoursWorked();

        if ($calculatedHours > 0) {
            $this->hours_worked = $calculatedHours;
            return true;
        }

        return false;
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }

    public function isApprovedStatus(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isDisputed(): bool
    {
        return $this->status === 'disputed';
    }

    public function hasAgencyApproval(): bool
    {
        return !is_null($this->agency_approved_by_id) && !is_null($this->agency_approved_at);
    }

    public function hasEmployerApproval(): bool
    {
        return !is_null($this->employer_approved_by_id) && !is_null($this->employer_approved_at);
    }

    public function isFullyApproved(): bool
    {
        return $this->hasAgencyApproval() && $this->hasEmployerApproval();
    }

    public function canBeEdited(): bool
    {
        return $this->isPending() || $this->isRejected();
    }

    public function canBeSubmitted(): bool
    {
        return $this->isPending() && $this->clock_in && $this->clock_out;
    }

    public function canBeApprovedByAgency(): bool
    {
        return $this->isSubmitted() && !$this->hasAgencyApproval();
    }

    public function canBeApprovedByEmployer(): bool
    {
        return $this->hasAgencyApproval() && !$this->hasEmployerApproval();
    }

    public function approveByAgency(int $userId): bool
    {
        if (!$this->canBeApprovedByAgency()) {
            return false;
        }

        return $this->update([
            'agency_approved_by_id' => $userId,
            'agency_approved_at' => now(),
        ]);
    }

    public function approveByEmployer(int $contactId): bool
    {
        if (!$this->canBeApprovedByEmployer()) {
            return false;
        }

        $this->update([
            'employer_approved_by_id' => $contactId,
            'employer_approved_at' => now(),
            'status' => 'approved',
        ]);

        return true;
    }

    public function reject(string $reason = null): bool
    {
        if (!$this->requiresApproval) {
            return false;
        }

        return $this->update([
            'status' => 'rejected',
            'notes' => $reason ? ($this->notes . "\nRejection: " . $reason) : $this->notes,
        ]);
    }

    public function dispute(string $reason): bool
    {
        if (!$this->isApprovedStatus()) {
            return false;
        }

        return $this->update([
            'status' => 'disputed',
            'notes' => $this->notes . "\nDispute: " . $reason,
        ]);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeDisputed($query)
    {
        return $query->where('status', 'disputed');
    }

    public function scopeRequiringAgencyApproval($query)
    {
        return $query->where('status', 'submitted')
            ->whereNull('agency_approved_by_id');
    }

    public function scopeRequiringEmployerApproval($query)
    {
        return $query->whereNotNull('agency_approved_by_id')
            ->whereNull('employer_approved_by_id')
            ->where('status', 'submitted');
    }

    public function scopeVisibleToAgency(Builder $query, int $agencyId): Builder
    {
        return $query->whereHas('shift.assignment.contract.agency', function (Builder $q) use ($agencyId) {
            $q->where('id', $agencyId);
        });
    }

    public function scopeVisibleToAgent(Builder $query, int $agentId): Builder
    {
        $agent = Agent::find($agentId);
        return $this->scopeVisibleToAgency($query, $agent->agency_id);
    }

    public function scopeVisibleToEmployer(Builder $query, int $employerId): Builder
    {
        return $query->whereHas('shift.assignment.contract', function (Builder $q) use ($employerId) {
            $q->where('employer_id', $employerId);
        });
    }

    public function scopeForEmployee(Builder $query, int $employeeId): Builder
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeDateRange(Builder $query, Carbon $startDate, Carbon $endDate): Builder
    {
        return $query->whereBetween('clock_in', [$startDate, $endDate]);
    }

    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('clock_in', '>=', now()->subDays($days));
    }

    protected static function booted()
    {
        static::creating(function (Timesheet $timesheet) {
            if ($timesheet->clock_in && $timesheet->clock_out && !$timesheet->hours_worked) {
                $timesheet->autoCalculateHours();
            }
        });

        static::updating(function (Timesheet $timesheet) {
            if ($timesheet->isDirty(['clock_in', 'clock_out']) && $timesheet->clock_in && $timesheet->clock_out) {
                $timesheet->autoCalculateHours();
            }

            if ($timesheet->isFullyApproved() && $timesheet->isDirty(['agency_approved_by_id', 'employer_approved_by_id'])) {
                $timesheet->status = 'approved';
            }
        });
    }
}
