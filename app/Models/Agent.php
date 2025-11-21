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
        'agency_branch_id',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
