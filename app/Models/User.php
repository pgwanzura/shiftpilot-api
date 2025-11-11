<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;
    use HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'status',
        'address_line1',
        'address_line2',
        'city',
        'county',
        'postcode',
        'country',
        'latitude',
        'longitude',
        'date_of_birth',
        'emergency_contact_name',
        'emergency_contact_phone',
        'meta',
        'email_verified_at',
        'last_login_at'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'date_of_birth' => 'date',
        'meta' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8'
    ];

    protected $appends = [
        'full_address',
        'has_complete_profile',
        'has_complete_address',
        'display_role'
    ];

    /**
     * Role checking methods
     */
    public function isAdmin(): bool
    {
        return $this->isSuperAdmin();
    }

    public function isAgency(): bool
    {
        return $this->isAgencyAdmin() || $this->isAgent();
    }

    public function isAgencyAdmin(): bool
    {
        return $this->role === 'agency_admin';
    }

    public function isAgent(): bool
    {
        return $this->role === 'agent';
    }

    public function isEmployer(): bool
    {
        return $this->isEmployerAdmin() || $this->isContact();
    }

    public function isEmployerAdmin(): bool
    {
        return $this->role === 'employer_admin';
    }

    public function isContact(): bool
    {
        return $this->role === 'contact';
    }

    public function isEmployee(): bool
    {
        return $this->role === 'employee';
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    /**
     * Get agency ID for agency users
     */
    public function getAgencyId(): ?int
    {
        if ($this->isAgencyAdmin() && $this->agency) {
            return $this->agency->id;
        }

        if ($this->isAgent() && $this->agent) {
            return $this->agent->agency_id;
        }

        return null;
    }

    /**
     * Get employer ID for employer users
     */
    public function getEmployerId(): ?int
    {
        if ($this->isEmployerAdmin() && $this->employer) {
            return $this->employer->id;
        }

        if ($this->isContact() && $this->contact) {
            return $this->contact->employer_id;
        }

        return null;
    }

    /**
     * Check if user can approve assignments (employer contacts)
     */
    public function canApproveAssignments(): bool
    {
        if ($this->isEmployerAdmin()) {
            return true;
        }

        if ($this->isContact() && $this->contact) {
            return $this->contact->can_approve_assignments ?? false;
        }

        return false;
    }

    /**
     * Check if user can approve timesheets (employer contacts)
     */
    public function canApproveTimesheets(): bool
    {
        if ($this->isEmployerAdmin()) {
            return true;
        }

        if ($this->isContact() && $this->contact) {
            return $this->contact->can_approve_timesheets ?? false;
        }

        // Fallback to role-based check for users without contact records
        return in_array($this->role, ['employer_admin', 'contact', 'super_admin']);
    }

    /**
     * Check if user can manage agency employees
     */
    public function canManageAgencyEmployees(): bool
    {
        return $this->isAgencyAdmin() || $this->isAgent();
    }

    /**
     * Check if user can manage employer locations
     */
    public function canManageLocations(): bool
    {
        return $this->isEmployerAdmin();
    }

    /**
     * Check if user can create shift requests
     */
    public function canCreateShiftRequests(): bool
    {
        return $this->isEmployerAdmin() || $this->isContact();
    }

    /**
     * Check if user can view financial information
     */
    public function canViewFinancials(): bool
    {
        return $this->isAdmin() || $this->isAgencyAdmin();
    }

    /**
     * Get user's primary entity (Agency, Employer, etc.)
     */
    public function getPrimaryEntity()
    {
        return match (true) {
            $this->isAgencyAdmin() => $this->agency,
            $this->isAgent() => $this->agent?->agency,
            $this->isEmployerAdmin() => $this->employer,
            $this->isContact() => $this->contact?->employer,
            $this->isEmployee() => $this->employee,
            default => null
        };
    }

    /**
     * Get user's display role with entity name
     */
    public function getDisplayRoleAttribute(): string
    {
        $entity = $this->getPrimaryEntity();
        $entityName = $entity?->name ?? '';

        return match ($this->role) {
            'super_admin' => 'Super Administrator',
            'agency_admin' => "Agency Admin" . ($entityName ? " - {$entityName}" : ''),
            'agent' => "Agent" . ($entityName ? " - {$entityName}" : ''),
            'employer_admin' => "Employer Admin" . ($entityName ? " - {$entityName}" : ''),
            'contact' => "Contact" . ($entityName ? " - {$entityName}" : ''),
            'employee' => "Employee",
            default => ucfirst(str_replace('_', ' ', $this->role))
        };
    }

    /**
     * Get full address as string
     */
    public function getFullAddressAttribute(): string
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

    /**
     * Check if user has complete address
     */
    public function getHasCompleteAddressAttribute(): bool
    {
        return !empty($this->address_line1) && !empty($this->postcode) && !empty($this->city);
    }

    /**
     * Check if user has complete profile
     */
    public function getHasCompleteProfileAttribute(): bool
    {
        $required = ['name', 'phone', 'date_of_birth'];

        foreach ($required as $field) {
            if (empty($this->$field)) {
                return false;
            }
        }

        if (!$this->has_complete_address) {
            return false;
        }

        if ($this->isEmployee() && empty($this->emergency_contact_phone)) {
            return false;
        }

        return true;
    }

    /**
     * Scope for users with complete address
     */
    public function scopeWithCompleteAddress($query)
    {
        return $query->whereNotNull('address_line1')
            ->whereNotNull('postcode')
            ->whereNotNull('city');
    }

    /**
     * Scope for users in specific city
     */
    public function scopeInCity($query, $city)
    {
        return $query->where('city', $city);
    }

    /**
     * Scope for users in postcode area
     */
    public function scopeInPostcodeArea($query, $postcodePrefix)
    {
        return $query->where('postcode', 'like', $postcodePrefix . '%');
    }

    /**
     * Scope for users within a geographic radius
     */
    public function scopeWithinRadius($query, $latitude, $longitude, $radiusKm)
    {
        $earthRadius = 6371; // kilometers

        return $query->whereRaw("
            ($earthRadius * ACOS(COS(RADIANS(?)) * 
            COS(RADIANS(latitude)) * COS(RADIANS(longitude) -
             RADIANS(?)) + SIN(RADIANS(?)) * SIN(RADIANS(latitude)))) < ?
        ", [$latitude, $longitude, $latitude, $radiusKm]);
    }

    /**
     * Scope for users in specific countries
     */
    public function scopeInCountries($query, array $countryCodes)
    {
        return $query->whereIn('country', $countryCodes);
    }

    /**
     * Scope for users in European Union countries
     */
    public function scopeInEuropeanUnion($query)
    {
        $euCountries = [
            'AT',
            'BE',
            'BG',
            'HR',
            'CY',
            'CZ',
            'DK',
            'EE',
            'FI',
            'FR',
            'DE',
            'GR',
            'HU',
            'IE',
            'IT',
            'LV',
            'LT',
            'LU',
            'MT',
            'NL',
            'PL',
            'PT',
            'RO',
            'SK',
            'SI',
            'ES',
            'SE'
        ];

        return $query->whereIn('country', $euCountries);
    }

    /**
     * Relationships
     */
    public function agency()
    {
        return $this->hasOne(Agency::class);
    }

    public function agent()
    {
        return $this->hasOne(Agent::class);
    }

    public function employer()
    {
        return $this->hasOne(Employer::class);
    }

    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    public function contact()
    {
        return $this->hasOne(Contact::class);
    }

    /**
     * Get address as an array
     */
    public function getAddressArrayAttribute(): array
    {
        return [
            'address_line1' => $this->address_line1,
            'address_line2' => $this->address_line2,
            'city' => $this->city,
            'county' => $this->county,
            'postcode' => $this->postcode,
            'country' => $this->country,
            'coordinates' => [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude
            ]
        ];
    }

    /**
     * Update address with optional geocoding
     */
    public function updateAddress(array $addressData, bool $geocode = true): bool
    {
        $this->fill($addressData);

        if ($geocode && $this->has_complete_address) {
            $this->geocodeAddress();
        }

        return $this->save();
    }

    /**
     * Geocode address to get coordinates
     */
    public function geocodeAddress(): self
    {
        // TODO: Integrate with a geocoding service like Google Maps
        // Example implementation:
        /*
        $geocoder = app('geocoder');
        $result = $geocoder->geocode($this->full_address)->first();
        
        if ($result) {
            $this->latitude = $result->getCoordinates()->getLatitude();
            $this->longitude = $result->getCoordinates()->getLongitude();
        }
        */

        return $this;
    }

    /**
     * Calculate distance to coordinates in kilometers
     */
    public function distanceTo(float $latitude, float $longitude): ?float
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

    /**
     * Check if user is within distance of coordinates
     */
    public function isWithinDistance(float $latitude, float $longitude, float $distanceKm): bool
    {
        $distance = $this->distanceTo($latitude, $longitude);
        return $distance !== null && $distance <= $distanceKm;
    }

    /**
     * Relationship to country
     */
    public function countryRelation()
    {
        return $this->belongsTo(Country::class, 'country', 'code');
    }

    /**
     * Check if user can manage shifts (existing method)
     */
    public function canManageShifts(): bool
    {
        return in_array($this->role, ['agency_admin', 'employer_admin', 'agent', 'super_admin']);
    }

    /**
     * Check if user is agency user (existing method)
     */
    public function isAgencyUser(): bool
    {
        return $this->role === 'agency_admin' || $this->agency !== null;
    }

    /**
     * Check if user is employer user (existing method)
     */
    public function isEmployerUser(): bool
    {
        return $this->role === 'employer_admin' || $this->employer !== null;
    }

    /**
     * Check if user is agent user (existing method)
     */
    public function isAgentUser(): bool
    {
        return $this->role === 'agent' || $this->agent !== null;
    }
}
