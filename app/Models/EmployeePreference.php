<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeePreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'preferred_shift_types',
        'preferred_locations',
        'preferred_industries',
        'preferred_roles',
        'max_travel_distance_km',
        'min_hourly_rate',
        'preferred_shift_lengths',
        'preferred_days',
        'preferred_start_times',
        'preferred_employment_types',
        'notification_preferences',
        'communication_preferences',
        'auto_accept_offers',
        'max_shifts_per_week',
    ];

    protected $casts = [
        'preferred_shift_types' => 'array',
        'preferred_locations' => 'array',
        'preferred_industries' => 'array',
        'preferred_roles' => 'array',
        'preferred_shift_lengths' => 'array',
        'preferred_days' => 'array',
        'preferred_start_times' => 'array',
        'preferred_employment_types' => 'array',
        'notification_preferences' => 'array',
        'communication_preferences' => 'array',
        'auto_accept_offers' => 'boolean',
        'min_hourly_rate' => 'decimal:2',
    ];

    protected $appends = [
        'has_preferences',
        'is_auto_accept_enabled',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function getHasPreferencesAttribute(): bool
    {
        return !empty(array_filter([
            $this->preferred_shift_types,
            $this->preferred_locations,
            $this->preferred_industries,
            $this->preferred_roles,
            $this->max_travel_distance_km,
            $this->min_hourly_rate,
            $this->preferred_shift_lengths,
            $this->preferred_days,
            $this->preferred_start_times,
            $this->preferred_employment_types,
        ]));
    }

    public function getIsAutoAcceptEnabledAttribute(): bool
    {
        return $this->auto_accept_offers;
    }

    public function meetsShiftCriteria(array $shiftData): bool
    {
        if ($this->min_hourly_rate && $shiftData['hourly_rate'] < $this->min_hourly_rate) {
            return false;
        }

        if ($this->preferred_locations && !in_array($shiftData['location_id'], $this->preferred_locations)) {
            return false;
        }

        if ($this->preferred_roles && !in_array($shiftData['role'], $this->preferred_roles)) {
            return false;
        }

        if ($this->preferred_shift_types && !in_array($shiftData['shift_type'], $this->preferred_shift_types)) {
            return false;
        }

        if ($this->preferred_days && !in_array($shiftData['day_of_week'], $this->preferred_days)) {
            return false;
        }

        return true;
    }

    public function shouldAutoAccept(array $shiftData): bool
    {
        return $this->auto_accept_offers && $this->meetsShiftCriteria($shiftData);
    }

    public function getNotificationChannel(string $type): array
    {
        $preferences = $this->notification_preferences ?? [];
        return $preferences[$type] ?? ['email', 'in_app'];
    }

    public function updateNotificationPreference(string $type, array $channels): void
    {
        $preferences = $this->notification_preferences ?? [];
        $preferences[$type] = $channels;
        $this->update(['notification_preferences' => $preferences]);
    }

    public function scopeWithAutoAccept($query)
    {
        return $query->where('auto_accept_offers', true);
    }

    public function scopeWithMinRate($query, $minRate)
    {
        return $query->where('min_hourly_rate', '<=', $minRate)
            ->orWhereNull('min_hourly_rate');
    }

    public function scopeWithLocationPreference($query, $locationId)
    {
        return $query->whereJsonContains('preferred_locations', $locationId)
            ->orWhereNull('preferred_locations');
    }

    public static function getForEmployee(Employee $employee): ?self
    {
        return static::where('employee_id', $employee->id)->first();
    }

    public static function createOrUpdateForEmployee(Employee $employee, array $data): self
    {
        return static::updateOrCreate(
            ['employee_id' => $employee->id],
            $data
        );
    }
}
