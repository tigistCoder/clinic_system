<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function vitalSign()
    {
        return $this->hasOne(VitalSign::class);
    }

    public function consultation()
    {
        return $this->hasOne(Consultation::class);
    }

    public function labRequests()
    {
        return $this->hasMany(LabRequest::class);
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }
}