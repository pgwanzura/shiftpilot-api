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
        'assignment_id',
        'title',
        'description',
        'day_of_week',
        'start_time',
        'end_time',
        'recurrence_type',
        'status',
        'effective_start_date',
        'effective_end_date',
        'last_generated_date',
        'meta',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'effective_start_date' => 'date',
        'effective_end_date' => 'date',
        'last_generated_date' => 'date',
        'meta' => 'array',
        'day_of_week' => DayOfWeek::class,
        'recurrence_type' => RecurrenceType::class,
        'status' => ShiftTemplateStatus::class,
    ];

    public function assignment()
    {
        return $this->belongsTo(Assignment::class);
    }

    public function shifts()
    {
        return $this->hasMany(Shift::class);
    }

    public function getEmployerAttribute()
    {
        return $this->assignment->contract->employer;
    }

    public function getLocationAttribute()
    {
        return $this->assignment->location;
    }

    public function getRoleAttribute()
    {
        return $this->assignment->role;
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

    public function isEffectiveOn(\Carbon\Carbon $date): bool
    {
        if ($this->effective_start_date && $date->lt($this->effective_start_date)) {
            return false;
        }

        if ($this->effective_end_date && $date->gt($this->effective_end_date)) {
            return false;
        }

        return true;
    }
}
