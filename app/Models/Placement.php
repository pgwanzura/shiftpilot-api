<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Placement extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'employer_id',
        'agency_id',
        'start_date',
        'end_date',
        'status',
        'employee_rate',
        'client_rate',
        'notes',
    ];

    protected $casts = [
        'employee_rate' => 'decimal:2',
        'client_rate' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function employer()
    {
        return $this->belongsTo(Employer::class);
    }

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function shifts()
    {
        return $this->hasMany(Shift::class);
    }
}
