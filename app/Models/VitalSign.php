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
}