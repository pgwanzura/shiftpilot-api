<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Employer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'legal_name',
        'registration_number',
        'billing_email',
        'phone',
        'website',
        'address_line1',
        'address_line2',
        'city',
        'county',
        'postcode',
        'country',
        'industry',
        'company_size',
        'status',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'status' => 'string',
    ];

    /**
     * Get the employer's profile.
     */
    public function profile(): MorphOne
    {
        return $this->morphOne(Profile::class, 'profileable');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    public function employerAgencyContracts(): HasMany
    {
        return $this->hasMany(EmployerAgencyContract::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function rateCards(): HasMany
    {
        return $this->hasMany(RateCard::class);
    }

    public function subscriptions(): MorphMany
    {
        return $this->morphMany(Subscription::class, 'subscriber');
    }

    /**
     * Get employer ID for employer.
     */
    public function getEmployerId(): ?int
    {
        return $this->id;
    }

    /**
     * Employer can approve assignments.
     */
    public function canApproveAssignments(): bool
    {
        return true;
    }

    /**
     * Employer can approve timesheets.
     */
    public function canApproveTimesheets(): bool
    {
        return true;
    }
}
