<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeOffRequest extends Model
{
    use HasFactory;

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    const TYPE_VACATION = 'vacation';
    const TYPE_SICK = 'sick';
    const TYPE_PERSONAL = 'personal';
    const TYPE_BEREAVEMENT = 'bereavement';
    const TYPE_OTHER = 'other';

    protected $fillable = [
        'employee_id',
        'agency_id',
        'start_date',
        'end_date',
        'type',
        'reason',
        'status',
        'approved_by_id',
        'approved_at'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeForAgency($query, $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }

    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved()
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function approve($approvedBy)
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by_id' => $approvedBy->id,
            'approved_at' => now(),
        ]);
    }

    public function reject($approvedBy)
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'approved_by_id' => $approvedBy->id,
            'approved_at' => now(),
        ]);
    }
}