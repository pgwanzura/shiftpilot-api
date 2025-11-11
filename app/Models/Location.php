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
        'address_line1',
        'address_line2',
        'city',
        'county',
        'postcode',
        'country',
        'latitude',
        'longitude',
        'location_type',
        'contact_name',
        'contact_phone',
        'instructions',
        'meta',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'meta' => 'array',
    ];

    protected $appends = [
        'full_address',
    ];

    // Relationships
    public function employer()
    {
        return $this->belongsTo(Employer::class);
    }

    public function shiftRequests()
    {
        return $this->hasMany(ShiftRequest::class);
    }

    public function shifts()
    {
        return $this->hasMany(Shift::class);
    }

    public function getFullAddressAttribute()
    {
        return collect([
            $this->address_line1,
            $this->address_line2,
            $this->city,
            $this->county,
            $this->postcode,
            $this->country
        ])->filter()->join(', ');
    }

    public function getHasCompleteAddressAttribute()
    {
        return !empty($this->address_line1) && !empty($this->postcode) && !empty($this->city);
    }

    public function geocodeAddress()
    {
        // Implementation for geocoding service
        // This would integrate with Google Maps, OpenStreetMap, etc.
        return $this;
    }

    public function distanceTo($latitude, $longitude)
    {
        if (!$this->latitude || !$this->longitude) {
            return null;
        }

        $earthRadius = 6371; // kilometers

        $latFrom = deg2rad($this->latitude);
        $lonFrom = deg2rad($this->longitude);
        $latTo = deg2rad($latitude);
        $lonTo = deg2rad($longitude);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return $angle * $earthRadius;
    }

    public function isWithinDistance($latitude, $longitude, $distanceKm)
    {
        $distance = $this->distanceTo($latitude, $longitude);
        return $distance !== null && $distance <= $distanceKm;
    }
}
