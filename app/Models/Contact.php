<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'employer_id',
        'user_id',
        'name',
        'email',
        'phone',
        'role',
        'is_primary',
        'can_sign_timesheets',
        'can_approve_assignments',
        'can_manage_locations',
        'meta',
    ];

    protected $casts = [
        'can_sign_timesheets' => 'boolean',
        'can_approve_assignments' => 'boolean',
        'can_manage_locations' => 'boolean',
        'is_primary' => 'boolean',
        'meta' => 'array',
    ];

    public function employer(): BelongsTo
    {
        return $this->belongsTo(Employer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shiftApprovals(): HasMany
    {
        return $this->hasMany(ShiftApproval::class);
    }

    public function timesheets(): HasMany
    {
        return $this->hasMany(Timesheet::class, 'employer_approved_by_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class, 'approved_by_id');
    }

    public function getEmployerId(): int
    {
        return $this->employer_id;
    }

    public function isActive(): bool
    {
        return $this->employer->isActive();
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeCanApproveTimesheets($query)
    {
        return $query->where('can_sign_timesheets', true);
    }

    public function scopeCanApproveAssignments($query)
    {
        return $query->where('can_approve_assignments', true);
    }

    public function scopeCanManageLocations($query)
    {
        return $query->where('can_manage_locations', true);
    }

    public function scopeForEmployer($query, int $employerId)
    {
        return $query->where('employer_id', $employerId);
    }
}
