<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Agent extends Model
{
    use HasFactory;

    protected $fillable = [
        'agency_id',
        'user_id',
        'branch_id',
        'permissions',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function getAgencyId(): ?int
    {
        return $this->agency_id;
    }

    public function canApproveAssignments(): bool
    {
        return false;
    }

    public function canApproveTimesheets(): bool
    {
        // Assuming agents can approve timesheets as per schema roles
        // This might need more granular permission checks based on your policy logic
        return true;
    }
}
