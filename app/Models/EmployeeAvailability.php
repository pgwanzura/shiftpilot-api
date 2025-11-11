<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class EmployeeAvailability extends Model
{
    use HasFactory;

    const MONDAY = 1;
    const TUESDAY = 2;
    const WEDNESDAY = 4;
    const THURSDAY = 8;
    const FRIDAY = 16;
    const SATURDAY = 32;
    const SUNDAY = 64;

    const WEEKDAYS = 31;
    const WEEKENDS = 96;
    const ALL_WEEK = 127;

    protected $fillable = [
        'employee_id',
        'start_date',
        'end_date',
        'days_mask',
        'start_time',
        'end_time',
        'type',
        'priority',
        'max_hours',
        'flexible',
        'constraints'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'constraints' => 'array',
        'flexible' => 'boolean'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function isAvailableOnDate(Carbon $date): bool
    {
        if ($date->lt($this->start_date)) {
            return false;
        }

        if ($this->end_date && $date->gt($this->end_date)) {
            return false;
        }

        $dayBit = 1 << ($date->dayOfWeekIso - 1);
        return (bool)($this->days_mask & $dayBit);
    }

    public function isEffectiveOnDate(Carbon $date): bool
    {
        if ($date->lt($this->start_date)) {
            return false;
        }

        if ($this->end_date && $date->gt($this->end_date)) {
            return false;
        }

        return true;
    }

    public function canWorkShift(Carbon $shiftStart, Carbon $shiftEnd): bool
    {
        if (!$this->isAvailableOnDate($shiftStart)) {
            return false;
        }

        $shiftStartTime = $shiftStart->format('H:i:s');
        $shiftEndTime = $shiftEnd->format('H:i:s');

        if ($shiftStartTime < $this->start_time || $shiftEndTime > $this->end_time) {
            return false;
        }

        if ($this->max_hours) {
            $shiftHours = $shiftStart->diffInHours($shiftEnd);
            if ($shiftHours > $this->max_hours) {
                return false;
            }
        }

        return true;
    }

    public function getDaysArray(): array
    {
        $days = [];
        $dayNames = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        foreach ($dayNames as $index => $day) {
            if ($this->days_mask & (1 << $index)) {
                $days[] = $day;
            }
        }

        return $days;
    }

    public function scopeForDate($query, Carbon $date)
    {
        $dayBit = 1 << ($date->dayOfWeekIso - 1);
        $dateString = $date->format('Y-m-d');

        return $query->where('days_mask', '&', $dayBit)
            ->where('start_date', '<=', $dateString)
            ->where(function ($q) use ($dateString) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $dateString);
            });
    }

    public function scopeForShift($query, Carbon $start, Carbon $end)
    {
        $dayBit = 1 << ($start->dayOfWeekIso - 1);
        $startDate = $start->format('Y-m-d');
        $startTime = $start->format('H:i:s');
        $endTime = $end->format('H:i:s');

        return $query->where('days_mask', '&', $dayBit)
            ->where('start_date', '<=', $startDate)
            ->where(function ($q) use ($startDate) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $startDate);
            })
            ->where('start_time', '<=', $startTime)
            ->where('end_time', '>=', $endTime);
    }

    public function scopePreferred($query)
    {
        return $query->where('type', 'preferred')->orderBy('priority', 'desc');
    }

    public function scopeAvailable($query)
    {
        return $query->whereIn('type', ['preferred', 'available']);
    }

    public function scopeCurrentlyEffective($query)
    {
        $today = Carbon::today()->format('Y-m-d');

        return $query->where('start_date', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $today);
            });
    }
}
