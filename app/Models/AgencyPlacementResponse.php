<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgencyPlacementResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'placement_id',
        'agency_id',
        'status',
        'submitted_employees',
        'employer_feedback',
    ];

    protected $casts = [
        'submitted_employees' => 'array',
        'employer_feedback' => 'array',
        'submitted_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REJECTED = 'rejected';
    const STATUS_WITHDRAWN = 'withdrawn';

    public function placement()
    {
        return $this->belongsTo(Placement::class);
    }

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function shifts()
    {
        return $this->hasMany(Shift::class);
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', self::STATUS_SUBMITTED);
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', self::STATUS_ACCEPTED);
    }

    // Methods
    public function submit()
    {
        $this->update([
            'status' => self::STATUS_SUBMITTED,
            'submitted_at' => now(),
        ]);
    }

    public function accept()
    {
        $this->update([
            'status' => self::STATUS_ACCEPTED,
            'responded_at' => now(),
        ]);
    }

    public function reject($reason = null)
    {
        $employerFeedback = $this->employer_feedback ?? [];
        $employerFeedback['rejection_reason'] = $reason;

        $this->update([
            'status' => self::STATUS_REJECTED,
            'employer_feedback' => $employerFeedback,
            'responded_at' => now(),
        ]);
    }

    public function withdraw()
    {
        $this->update([
            'status' => self::STATUS_WITHDRAWN,
            'responded_at' => now(),
        ]);
    }
}