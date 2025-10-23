<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'employer_id',
        'name',
        'address',
        'latitude',
        'longitude',
        'meta',
    ];

    protected $casts = [
        'latitude' => 'decimal:6',
        'longitude' => 'decimal:6',
        'meta' => 'array',
    ];

    public function employer()
    {
        return $this->belongsTo(Employer::class);
    }

    public function shifts()
    {
        return $this->hasMany(Shift::class);
    }

    public function shiftTemplates()
    {
        return $this->hasMany(ShiftTemplate::class);
    }

    public function rateCards()
    {
        return $this->hasMany(RateCard::class);
    }
}
