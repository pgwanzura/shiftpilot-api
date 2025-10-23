<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'billing_email',
        'address',
        'city',
        'country',
        'subscription_status',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    public function locations()
    {
        return $this->hasMany(Location::class);
    }

    public function shifts()
    {
        return $this->hasMany(Shift::class);
    }

    public function employerAgencyLinks()
    {
        return $this->hasMany(EmployerAgencyLink::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function placements()
    {
        return $this->hasMany(Placement::class);
    }

    public function rateCards()
    {
        return $this->hasMany(RateCard::class);
    }

    public function shiftTemplates()
    {
        return $this->hasMany(ShiftTemplate::class);
    }

    public function subscriptions()
    {
        return $this->morphMany(Subscription::class, 'subscriber');
    }
}
