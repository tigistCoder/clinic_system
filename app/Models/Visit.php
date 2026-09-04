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

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function vitalSign()
    {
        return $this->hasOne(VitalSign::class);
    }

    public function vitalSigns()
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

    public function labResults()
    {
        return $this->hasMany(LabResult::class);
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }

    /**
     * Check completion triggers and update visit_status to 'completed' or 'in_progress'.
     * Clean, direct, non-looping query check.
     */
    public function checkAndUpdateCompletionStatus(): bool
    {
        $hasPendingPrescriptions = Prescription::where('visit_id', $this->id)
            ->where('status', '!=', 'dispensed')
            ->exists();

        $hasPendingLab = LabRequest::where('visit_id', $this->id)
            ->where('status', '!=', 'completed')
            ->exists();

        $hasDiagnosis = !empty(trim($this->consultation?->final_dx ?? ''));

        $targetStatus = (!$hasPendingPrescriptions && !$hasPendingLab && $hasDiagnosis) ? 'completed' : 'in_progress';

        if ($this->visit_status !== $targetStatus) {
            $this->update(['visit_status' => $targetStatus]);
        }

        return $targetStatus === 'completed';
    }
}