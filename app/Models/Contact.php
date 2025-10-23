<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'employer_id',
        'user_id',
        'name',
        'email',
        'phone',
        'role',
        'can_sign_timesheets',
        'meta',
    ];

    protected $casts = [
        'can_sign_timesheets' => 'boolean',
        'meta' => 'array',
    ];

    public function employer()
    {
        return $this->belongsTo(Employer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shiftApprovals()
    {
        return $this->hasMany(ShiftApproval::class);
    }

    public function timesheets()
    {
        return $this->hasMany(Timesheet::class, 'approved_by_contact_id');
    }
}
