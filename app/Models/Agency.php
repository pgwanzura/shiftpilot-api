<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Agency extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'legal_name',
        'registration_number',
        'billing_email',
        'address',
        'city',
        'country',
        'default_markup_percent',
        'subscription_status',
        'meta',
    ];

    protected $casts = [
        'default_markup_percent' => 'decimal:2',
        'meta' => 'array',
    ];

    /**
     * Get the agency's profile.
     */
    public function profile(): MorphOne
    {
        return $this->morphOne(Profile::class, 'profileable');
    }

    public function agents(): HasMany
    {
        return $this->hasMany(Agent::class);
    }

    public function agencyEmployees(): HasMany
    {
        return $this->hasMany(AgencyEmployee::class);
    }

    public function employerAgencyContracts(): HasMany
    {
        return $this->hasMany(EmployerAgencyContract::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class);
    }

    public function rateCards(): HasMany
    {
        return $this->hasMany(RateCard::class);
    }

    public function subscriptions(): MorphMany
    {
        return $this->morphMany(Subscription::class, 'subscriber');
    }

    public function agencyResponses(): HasMany
    {
        return $this->hasMany(AgencyResponse::class);
    }

    public function timeOffRequests(): HasMany
    {
        return $this->hasMany(TimeOffRequest::class);
    }

    /**
     * Get agency ID for agency.
     */
    public function getAgencyId(): ?int
    {
        return $this->id;
    }

    /**
     * Agency cannot approve assignments directly.
     */
    public function canApproveAssignments(): bool
    {
        return false;
    }

    /**
     * Agency can approve timesheets.
     */
    public function canApproveTimesheets(): bool
    {
        // Assuming agency can approve timesheets as per schema roles
        return true;
    }
}