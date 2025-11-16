<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'user_id',
        'role',
        'joined_at',
        'left_at',
        'muted_until',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
        'muted_until' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('left_at');
    }

    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    public function scopeMuted($query)
    {
        return $query->whereNotNull('muted_until')->where('muted_until', '>', now());
    }

    public function isActive(): bool
    {
        return $this->left_at === null;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isMuted(): bool
    {
        return $this->muted_until && $this->muted_until->isFuture();
    }

    public function leave(): bool
    {
        return $this->update(['left_at' => now()]);
    }

    public function mute(\DateTimeInterface $until): bool
    {
        return $this->update(['muted_until' => $until]);
    }

    public function unmute(): bool
    {
        return $this->update(['muted_until' => null]);
    }
}
