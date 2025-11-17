<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'conversation_type',
        'context_type',
        'context_id',
        'last_message_id',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function lastMessage(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'last_message_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    public function context(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeDirect($query)
    {
        return $query->where('conversation_type', 'direct');
    }

    public function scopeGroup($query)
    {
        return $query->where('conversation_type', 'group');
    }

    public function scopeShift($query)
    {
        return $query->where('conversation_type', 'shift');
    }

    public function scopeAssignment($query)
    {
        return $query->where('conversation_type', 'assignment');
    }

    public function isDirect(): bool
    {
        return $this->conversation_type === 'direct';
    }

    public function isGroup(): bool
    {
        return $this->conversation_type === 'group';
    }

    public function hasParticipant(int $userId): bool
    {
        return $this->participants()->where('user_id', $userId)->exists();
    }

    public function getActiveParticipantsCount(): int
    {
        return $this->participants()->whereNull('left_at')->count();
    }

    public function archive(): bool
    {
        return $this->update(['archived_at' => now()]);
    }

    public function scopeNotArchived($query)
    {
        return $query->whereNull('archived_at');
    }
}
