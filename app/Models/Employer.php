<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

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
        'company_size' => 'integer',
    ];

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

    public function shiftRequests(): HasMany
    {
        return $this->hasMany(ShiftRequest::class);
    }

    public function timesheets(): HasMany
    {
        return $this->hasMany(Timesheet::class);
    }

    public function getEmployerId(): int
    {
        return $this->id;
    }

    public function canApproveAssignments(): bool
    {
        return $this->status === 'active';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function hasActiveSubscription(): bool
    {
        return $this->subscriptions()
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->exists();
    }

    public function getPrimaryContact(): ?Contact
    {
        return $this->contacts()
            ->where('is_primary', true)
            ->first();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeWithActiveSubscriptions($query)
    {
        return $query->whereHas('subscriptions', function ($q) {
            $q->where('status', 'active')
                ->where('ends_at', '>', now());
        });
    }
}
