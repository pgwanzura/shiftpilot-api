<?php

namespace App\Models;

use App\Enums\DayOfWeek;
use App\Enums\RecurrenceType;
use App\Enums\ShiftTemplateStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'recurrence_rules',
        'timezone',
        'status',
        'effective_start_date',
        'effective_end_date',
        'max_occurrences',
        'auto_publish',
        'generation_count',
        'last_generated_date',
        'meta',
        'created_by_id',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'effective_start_date' => 'date',
        'effective_end_date' => 'date',
        'last_generated_date' => 'date',
        'meta' => 'array',
        'recurrence_rules' => 'array',
        'day_of_week' => DayOfWeek::class,
        'recurrence_type' => RecurrenceType::class,
        'status' => ShiftTemplateStatus::class,
        'max_occurrences' => 'integer',
        'auto_publish' => 'boolean',
        'generation_count' => 'integer',
    ];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', ShiftTemplateStatus::ACTIVE);
    }

    public function scopeForAssignment($query, $assignmentId)
    {
        return $query->where('assignment_id', $assignmentId);
    }

    public function scopeEffectiveOn($query, $date)
    {
        return $query->where(function ($q) use ($date) {
            $q->whereNull('effective_start_date')
                ->orWhere('effective_start_date', '<=', $date);
        })->where(function ($q) use ($date) {
            $q->whereNull('effective_end_date')
                ->orWhere('effective_end_date', '>=', $date);
        });
    }

    public function isActive(): bool
    {
        return $this->status === ShiftTemplateStatus::ACTIVE;
    }

    public function isPaused(): bool
    {
        return $this->status === ShiftTemplateStatus::PAUSED;
    }

    public function isInactive(): bool
    {
        return $this->status === ShiftTemplateStatus::INACTIVE;
    }

    public function getDurationInHours(): float
    {
        return $this->start_time->diffInHours($this->end_time);
    }

    public function getDurationAttribute(): string
    {
        $start = $this->start_time;
        $end = $this->end_time;
        $hours = $start->diffInHours($end);
        $minutes = $start->diffInMinutes($end) % 60;

        return sprintf('%02d:%02d', $hours, $minutes);
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

    public function hasReachedMaxOccurrences(): bool
    {
        return $this->max_occurrences && $this->generation_count >= $this->max_occurrences;
    }

    public function shouldAutoPublish(): bool
    {
        return $this->auto_publish && $this->isActive();
    }

    public function canGenerateMoreShifts(): bool
    {
        return $this->isActive() && !$this->hasReachedMaxOccurrences();
    }

    public function getNextGenerationDate(): ?\Carbon\Carbon
    {
        if (!$this->canGenerateMoreShifts()) {
            return null;
        }

        $lastDate = $this->last_generated_date ? \Carbon\Carbon::parse($this->last_generated_date) : now();

        return match ($this->recurrence_type) {
            RecurrenceType::WEEKLY => $lastDate->addWeek(),
            RecurrenceType::BIWEEKLY => $lastDate->addWeeks(2),
            RecurrenceType::MONTHLY => $lastDate->addMonth(),
            default => null,
        };
    }

    public function getEmployerAttribute()
    {
        return $this->assignment->contract->employer;
    }

    public function getLocationAttribute()
    {
        return $this->assignment->location;
    }

    public function getAgencyAttribute()
    {
        return $this->assignment->contract->agency;
    }

    public function getAgencyEmployeeAttribute()
    {
        return $this->assignment->agencyEmployee;
    }

    public function validateAssignmentCompatibility(): bool
    {
        if (!$this->assignment || !$this->assignment->isActive()) {
            return false;
        }

        if (!$this->assignment->agencyEmployee->isActive()) {
            return false;
        }

        if (!$this->isEffectiveOn(now())) {
            return false;
        }

        return true;
    }

    public function incrementGenerationCount(): void
    {
        $this->update([
            'generation_count' => $this->generation_count + 1,
            'last_generated_date' => now(),
        ]);
    }

    public function markAsCompleted(): void
    {
        $this->update(['status' => ShiftTemplateStatus::INACTIVE]);
    }

    public function pause(): void
    {
        $this->update(['status' => ShiftTemplateStatus::PAUSED]);
    }

    public function resume(): void
    {
        $this->update(['status' => ShiftTemplateStatus::ACTIVE]);
    }
}
