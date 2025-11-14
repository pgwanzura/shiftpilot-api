<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'employer_id',
        'name',
        'email',
        'phone',
        'role',
        'can_sign_timesheets',
        'meta',
    ];

    protected $casts = [
        'can_sign_timesheets' => 'boolean',
        'meta' => 'array',
    ];


    /**
     * The employer that the contact belongs to.
     */
    public function employer(): BelongsTo
    {
        return $this->belongsTo(Employer::class);
    }

    public function shiftApprovals(): HasMany
    {
        return $this->hasMany(ShiftApproval::class, 'contact_id');
    }

    public function timesheets(): HasMany
    {
        // Assuming timesheets are approved by employer contact, foreign key will be 'employer_approved_by_id'
        return $this->hasMany(Timesheet::class, 'employer_approved_by_id');
    }

    /**
     * Get employer ID for contact.
     */
    public function getEmployerId(): ?int
    {
        return $this->employer_id;
    }

    /**
     * Check if contact can approve assignments.
     */
    public function canApproveAssignments(): bool
    {
        // Assuming contacts can approve assignments as per schema roles and permissions
        return $this->can_sign_timesheets; // Example, adjust based on actual schema rules
    }

    /**
     * Check if contact can approve timesheets.
     */
    public function canApproveTimesheets(): bool
    {
        // Assuming contacts can approve timesheets as per schema roles and permissions
        return $this->can_sign_timesheets; // Example, adjust based on actual schema rules
    }
}
