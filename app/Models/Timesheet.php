<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Timesheet extends Model
{
    protected $fillable = [
        'shift_id',
        'employee_id',
        'clock_in',
        'clock_out',
        'break_minutes',
        'hours_worked',
        'status',
        'agency_approved_by',
        'agency_approved_at',
        'approved_by_contact_id',
        'approved_at',
        'notes',
        'attachments',
    ];

    protected $casts = [
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
        'hours_worked' => 'decimal:2',
        'agency_approved_at' => 'datetime',
        'approved_at' => 'datetime',
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
        return $this->belongsTo(User::class, 'agency_approved_by');
    }

    public function approvedByContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'approved_by_contact_id');
    }
}
