<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployerAgencyContract extends Model
{
    use HasFactory;

    protected $fillable = [
        'employer_id',
        'agency_id',
        'status',
        'contract_document_url',
        'contract_start',
        'contract_end',
        'terms',
    ];

    protected $casts = [
        'contract_start' => 'date',
        'contract_end' => 'date',
        'markup_percent' => 'decimal:2',
    ];

    public function employer()
    {
        return $this->belongsTo(Employer::class);
    }

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class, 'contract_id');
    }

    public function isActive()
    {
        return $this->status === 'active' &&
            (!$this->contract_end || $this->contract_end >= now());
    }
}
