<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeAvailability extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'type',
        'day_of_week',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'timezone',
        'status',
        'priority',
        'location_preference',
        'max_shift_length_hours',
        'min_shift_length_hours',
        'notes',
    ];

    protected $casts = [
        'location_preference' => 'array',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function scopeWhereOverlappingDates($query, string $startDate, string $endDate)
    {
        return $query->where(function ($query) use ($startDate, $endDate) {
            $query->whereDate('start_date', '<=', $endDate)
                ->whereDate('end_date', '>=', $startDate)
                ->orWhereNull('end_date');
        });
    }
}
