<?php

namespace App\Models;

use App\Enums\AgencyStatus;
use App\Enums\SubscriptionStatus;
use App\Events\Agency\AgencyStatusChanged;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Agency extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'legal_name',
        'registration_number',
        'billing_email',
        'phone',
        'address_line1',
        'address_line2',
        'city',
        'country',
        'country',
        'longitude',
        'latitude',
        'postcode',
        'default_markup_percent',
        'subscription_status',
        'meta',
    ];

    protected $casts = [
        'default_markup_percent' => 'decimal:2',
        'subscription_status' => SubscriptionStatus::class,
        'meta' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function branches(): HasMany
    {
        return $this->hssMany(AgencyBranch::class);
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

    public function AgencyBranches(): HasMany
    {
        return $this->hasMany(AgencyBranch::class);
    }

    public function agencyResponses(): HasMany
    {
        return $this->hasMany(AgencyResponse::class);
    }

    public function timeOffRequests(): HasMany
    {
        return $this->hasMany(TimeOffRequest::class);
    }

    public function shiftRequests(): HasMany
    {
        return $this->hasMany(ShiftRequest::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class, 'entity_id')
            ->where('entity_type', self::class);
    }

    public function headOffice(): HasOne
    {
        return $this->hasOne(AgencyBranch::class)->where('is_head_office', true);
    }

    public function isActive(): bool
    {
        return $this->subscription_status === SubscriptionStatus::ACTIVE;
    }

    public function hasActiveContractWith(Employer $employer): bool
    {
        return $this->employerAgencyContracts()
            ->where('employer_id', $employer->id)
            ->where('status', 'active')
            ->exists();
    }

    public function getActiveEmployeesCount(): int
    {
        return $this->agencyEmployees()
            ->where('status', 'active')
            ->count();
    }

    public function getActiveAssignmentsCount(): int
    {
        return $this->assignments()
            ->where('status', 'active')
            ->count();
    }

    public function calculateMarkupAmount(float $baseAmount): float
    {
        return $baseAmount * ($this->default_markup_percent / 100);
    }

    public function getMarkedUpAmount(float $baseAmount): float
    {
        return $baseAmount + $this->calculateMarkupAmount($baseAmount);
    }

    protected static function booted(): void
    {
        static::creating(function (Agency $agency) {
            if (empty($agency->subscription_status)) {
                $agency->subscription_status = SubscriptionStatus::ACTIVE;
            }
        });

        static::updated(function (Agency $agency) {
            if ($agency->isDirty('subscription_status')) {
                AgencyStatusChanged::dispatch($agency, $agency->getOriginal('subscription_status'));
            }
        });
    }
}
