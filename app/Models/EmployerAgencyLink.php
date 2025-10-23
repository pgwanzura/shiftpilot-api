<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployerAgencyLink extends Model
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

    public function employer()
    {
        return $this->belongsTo(Employer::class);
    }

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }
}
