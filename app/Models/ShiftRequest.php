<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'employer_id',
        'location_id',
        'title',
        'description',
        'role',
        'required_qualifications',
        'experience_level',
        'background_check_required',
        'start_date',
        'end_date',
        'shift_pattern',
        'recurrence_rules',
        'max_hourly_rate',
        'currency',
        'number_of_workers',
        'target_agencies',
        'specific_agency_ids',
        'response_deadline',
        'status',
        'created_by_id',
    ];

    protected $casts = [
        'required_qualifications' => 'array',
        'recurrence_rules' => 'array',
        'specific_agency_ids' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'max_hourly_rate' => 'decimal:2',
        'response_deadline' => 'datetime',
        'background_check_required' => 'boolean',
    ];

    public function employer()
    {
        return $this->belongsTo(Employer::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function agencyResponses()
    {
        return $this->hasMany(AgencyResponse::class);
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['published', 'in_progress']);
    }

    public function scopeForAgency($query, $agencyId)
    {
        return $query->where(function ($q) use ($agencyId) {
            $q->where('target_agencies', 'all')
              ->orWhereJsonContains('specific_agency_ids', $agencyId);
        });
    }

    public function isPublished()
    {
        return $this->status === 'published';
    }

    public function isFilled()
    {
        return $this->status === 'filled';
    }

    public function hasResponseDeadlinePassed()
    {
        return $this->response_deadline && $this->response_deadline->isPast();
    }

    public function getAcceptedResponsesCount()
    {
        return $this->agencyResponses()->where('status', 'accepted')->count();
    }

    public function isFullyFilled()
    {
        return $this->getAcceptedResponsesCount() >= $this->number_of_workers;
    }
}