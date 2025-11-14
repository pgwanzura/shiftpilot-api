<?php

namespace App\Models;

use App\Enums\AgencyBranchStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgencyBranch extends Model
{
    use HasFactory;

    protected $fillable = [
        'agency_id',
        'name',
        'branch_code',
        'email',
        'phone',
        'address_line1',
        'address_line2',
        'city',
        'county',
        'postcode',
        'country',
        'latitude',
        'longitude',
        'contact_name',
        'contact_email',
        'contact_phone',
        'is_head_office',
        'status',
        'opening_hours',
        'services_offered',
        'meta',
    ];

    protected $casts = [
        'status' => AgencyBranchStatus::class,
        'opening_hours' => 'array',
        'services_offered' => 'array',
        'meta' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function agents(): HasMany
    {
        return $this->hasMany(Agent::class);
    }

    public function agencyEmployees(): HasMany
    {
        return $this->hasMany(AgencyEmployee::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    public function isActive(): bool
    {
        return $this->status === AgencyBranchStatus::ACTIVE;
    }

    public function scopeActive($query)
    {
        return $query->where('status', AgencyBranchStatus::ACTIVE);
    }

    public function getFullAddressAttribute(): string
    {
        $addressParts = array_filter([
            $this->address_line1,
            $this->address_line2,
            $this->city,
            $this->county,
            $this->postcode,
            $this->country,
        ]);

        return implode(', ', $addressParts);
    }

    public function hasGeoLocation(): bool
    {
        return !is_null($this->latitude) && !is_null($this->longitude);
    }

    protected static function booted(): void
    {
        static::creating(function (AgencyBranch $branch) {
            if ($branch->is_head_office) {
                static::where('agency_id', $branch->agency_id)
                    ->where('is_head_office', true)
                    ->update(['is_head_office' => false]);
            }

            if (empty($branch->branch_code)) {
                $branch->branch_code = static::generateBranchCode($branch->agency_id);
            }
        });

        static::updating(function (AgencyBranch $branch) {
            if ($branch->is_head_office && $branch->isDirty('is_head_office')) {
                static::where('agency_id', $branch->agency_id)
                    ->where('id', '!=', $branch->id)
                    ->where('is_head_office', true)
                    ->update(['is_head_office' => false]);
            }
        });
    }

    private static function generateBranchCode(int $agencyId): string
    {
        $agency = Agency::find($agencyId);
        $agencyInitials = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $agency->name), 0, 3));
        $branchCount = static::where('agency_id', $agencyId)->count() + 1;

        return $agencyInitials . '-' . str_pad($branchCount, 3, '0', STR_PAD_LEFT);
    }
}
