<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agency extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function agents()
    {
        return $this->hasMany(Agent::class);
    }

    public function agencyEmployees()
    {
        return $this->hasMany(AgencyEmployee::class);
    }

    public function employerAgencyContracts()
    {
        return $this->hasMany(EmployerAgencyContract::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function payrolls()
    {
        return $this->hasMany(Payroll::class);
    }

    public function payouts()
    {
        return $this->hasMany(Payout::class);
    }

    public function rateCards()
    {
        return $this->hasMany(RateCard::class);
    }

    public function subscriptions()
    {
        return $this->morphMany(Subscription::class, 'subscriber');
    }

    public function agencyResponses()
    {
        return $this->hasMany(AgencyResponse::class);
    }

    public function timeOffRequests()
    {
        return $this->hasMany(TimeOffRequest::class);
    }
}