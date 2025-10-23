<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftApproval extends Model
{
    use HasFactory;

    protected $table = 'shift_approvals';

    protected $fillable = [
        'shift_id', 'contact_id', 'status', 'signed_at',
        'signature_blob_url', 'notes'
    ];

    protected $casts = [
        'signed_at' => 'datetime'
    ];

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
