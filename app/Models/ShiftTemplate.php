<?php

namespace App\Models;

use App\Enums\DayOfWeek;
use App\Enums\RecurrenceType;
use App\Enums\ShiftTemplateStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'employer_id',
        'location_id',
        'title',
        'description',
        'day_of_week',
        'start_time',
        'end_time',
        'role_requirement',
        'required_qualifications',
        'hourly_rate',
        'recurrence_type',
        'status',
        'start_date',
        'end_date',
        'created_by_type',
        'created_by_id',
        'meta',
    ];

    protected $casts = [
        'required_qualifications' => 'array',
        'hourly_rate' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'meta' => 'array',
        'day_of_week' => DayOfWeek::class,
        'recurrence_type' => RecurrenceType::class,
        'status' => ShiftTemplateStatus::class,
    ];

    public function employer()
    {
        return $this->belongsTo(Employer::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function shifts()
    {
        return $this->hasMany(Shift::class);
    }

    public function createdBy()
    {
        return $this->morphTo();
    }

    public function isActive(): bool
    {
        return $this->status === ShiftTemplateStatus::ACTIVE;
    }

    public function getDurationAttribute(): string
    {
        $start = \Carbon\Carbon::parse($this->start_time);
        $end = \Carbon\Carbon::parse($this->end_time);
        return $start->diff($end)->format('%H:%I');
    }
}
