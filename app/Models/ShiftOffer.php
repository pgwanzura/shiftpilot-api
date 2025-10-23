<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftOffer extends Model
{
    use HasFactory;

    protected $table = 'shift_offers';

    protected $fillable = [
        'shift_id', 'employee_id', 'offered_by_id', 'status',
        'expires_at', 'responded_at', 'response_notes'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'responded_at' => 'datetime'
    ];

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function offeredBy()
    {
        return $this->belongsTo(User::class, 'offered_by_id');
    }
}
