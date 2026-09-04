<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VitalSign extends Model
{
    use HasFactory;

    protected $fillable = [
        'visit_id',
        'nurse_id',
        'systolic_bp',
        'diastolic_bp',
        'pulse_rate',
        'respiratory_rate',
        'temperature',
        'spo2',
        'weight',
        'height',
    ];

    protected $casts = [
        'temperature' => 'float',
        'weight'      => 'float',
        'height'      => 'float',
    ];

    /**
     * የትኛው Visit እንደሆነ
     */
    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    /**
     * ቫይታሉን የሞላችው ነርስ (ከ users table)
     */
    public function nurse()
    {
        return $this->belongsTo(User::class, 'nurse_id');
    }

    /**
     * Accessor: የደም ግፊት በ "120/80" ፎርማት ለማግኘት
     */
    public function getBpAttribute()
    {
        if ($this->systolic_bp && $this->diastolic_bp) {
            return "{$this->systolic_bp}/{$this->diastolic_bp}";
        }
        return 'N/A';
    }

    /**
     * Accessor: BMI ለማስላት (Height በ Cm ስለሆነ ወደ Meter ይቀየራል)
     */
    public function getBmiAttribute()
    {
        if ($this->weight && $this->height && $this->height > 0) {
            $heightInMeters = $this->height / 100;
            return round($this->weight / ($heightInMeters * $heightInMeters), 1);
        }
        return null;
    }

    /**
     * Check if any vital metric is at a critical/abnormal threshold.
     */
    public function isCritical(): bool
    {
        return !empty($this->critical_alerts);
    }

    /**
     * Accessor: is_critical
     */
    public function getIsCriticalAttribute(): bool
    {
        return !empty($this->critical_alerts);
    }

    /**
     * Return list of abnormal/critical metric alert labels.
     *
     * Clinical thresholds:
     * - Blood Pressure: Systolic >= 140 or <= 90; Diastolic >= 90 or <= 60
     * - Heart Rate / Pulse: < 60 bpm or > 100 bpm
     * - Oxygen (SpO2): < 95%
     * - Temperature: >= 38.0°C (High Fever) or <= 35.5°C (Hypothermia)
     *
     * @return array<string>
     */
    public function getCriticalAlertsAttribute(): array
    {
        $alerts = [];

        // Critical Blood Pressure: Systolic >= 140 or <= 90; Diastolic >= 90 or <= 60
        $isCriticalBp = false;
        if ($this->systolic_bp !== null && ($this->systolic_bp >= 140 || $this->systolic_bp <= 90)) {
            $isCriticalBp = true;
        }
        if ($this->diastolic_bp !== null && ($this->diastolic_bp >= 90 || $this->diastolic_bp <= 60)) {
            $isCriticalBp = true;
        }
        if ($isCriticalBp) {
            $alerts[] = 'BP: ' . ($this->bp !== 'N/A' ? $this->bp : "{$this->systolic_bp}/{$this->diastolic_bp}") . ' mmHg';
        }

        // Critical Heart Rate / Pulse: < 60 bpm or > 100 bpm
        if ($this->pulse_rate !== null && ($this->pulse_rate < 60 || $this->pulse_rate > 100)) {
            $alerts[] = "HR: {$this->pulse_rate} bpm";
        }

        // Critical Oxygen (SpO2): < 95%
        if ($this->spo2 !== null && $this->spo2 < 95) {
            $alerts[] = "SpO2: {$this->spo2}%";
        }

        // Critical Temperature: >= 38.0°C or <= 35.5°C
        if ($this->temperature !== null && ($this->temperature >= 38.0 || $this->temperature <= 35.5)) {
            $alerts[] = "Temp: {$this->temperature}°C";
        }

        return $alerts;
    }
}