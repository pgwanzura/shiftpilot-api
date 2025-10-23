<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RateCard extends Model
{
    use HasFactory;

    protected $table = 'rate_cards';

    protected $fillable = [
        'employer_id', 'agency_id', 'role_key', 'location_id',
        'day_of_week', 'start_time', 'end_time', 'rate',
        'currency', 'effective_from', 'effective_to'
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'effective_from' => 'date',
        'effective_to' => 'date'
    ];

    public function employer()
    {
        return $this->belongsTo(Employer::class);
    }

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}
