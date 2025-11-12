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
        'name',
        'email',
        'phone',
        'permissions',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    /**
     * Get the agent's profile.
     */
    public function profile(): MorphOne
    {
        return $this->morphOne(Profile::class, 'profileable');
    }

    /**
     * The agency that the agent belongs to.
     */
    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    /**
     * Get agency ID for agent.
     */
    public function getAgencyId(): ?int
    {
        return $this->agency_id;
    }

    /**
     * Agents cannot approve assignments directly unless specified otherwise.
     */
    public function canApproveAssignments(): bool
    {
        return false;
    }

    /**
     * Agents can approve timesheets.
     */
    public function canApproveTimesheets(): bool
    {
        // Assuming agents can approve timesheets as per schema roles
        // This might need more granular permission checks based on your policy logic
        return true;
    }
}
