<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Timesheet extends Model
{
    protected $fillable = [
        'shift_id',
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
        return $this->belongsTo(Contact::class, 'employer_approved_by_id');
    }
}
