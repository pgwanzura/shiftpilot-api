<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgencyResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'shift_request_id',
        'agency_id',
        'proposed_employee_id',
        'status',
        'notes',
        'submitted_by_id',
        'responded_at',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
    ];

    public function shiftRequest()
    {
        return $this->belongsTo(ShiftRequest::class);
    }

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function proposedEmployee()
    {
        return $this->belongsTo(Employee::class, 'proposed_employee_id');
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by_id');
    }

    public function assignment()
    {
        return $this->hasOne(Assignment::class);
    }

    public function isAccepted()
    {
        return $this->status === 'accepted';
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }
}
