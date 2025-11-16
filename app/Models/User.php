<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'status',
        'meta',
        'email_verified_at',
        'last_login_at'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'meta' => 'array',
    ];

    public function agent()
    {
        return $this->hasOne(Agent::class);
    }

    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    public function contact()
    {
        return $this->hasOne(Contact::class);
    }

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->email_verified_at);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function recordLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }

    public function markAsVerified(): void
    {
        $this->update(['email_verified_at' => now()]);
    }
}
