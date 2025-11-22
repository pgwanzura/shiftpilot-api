<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Agent extends Model
{
    use HasFactory;

    protected $fillable = [
        'agency_id',
        'user_id',
    ];

    protected $casts = [
        'agency_id' => 'integer',
        'user_id' => 'integer',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->relationLoaded('user') && $this->user->isActive();
    }

    public function belongsToAgency(int $agencyId): bool
    {
        return $this->agency_id === $agencyId;
    }
}
