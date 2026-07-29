<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'card_number',
        'full_name',
        'age',
        'gender',
        'phone_number',
        'address',
    ];

    /**
     * አንድ ታካሚ ብዙ Visits ሊኖሩት ይችላሉ
     */
    public function visits()
    {
        return $this->hasMany(Visit::class);
    }

    /**
     * የታካሚውን የመጨረሻ Visit ለማግኘት (Latest Visit)
     */
    public function latestVisit()
    {
        return $this->hasOne(Visit::class)->latestOfMany();
    }

    /**
     * በአሁኑ ሰዓት በሂደት ላይ ያለ Visit ለማግኘት
     */
    public function activeVisit()
    {
        return $this->hasOne(Visit::class)->where('visit_status', 'active')->latestOfMany();
    }

    /**
     * Accessor: የካርድ ቁጥር ካልተሞላ አውቶማቲክ P-0000X ብሎ እንዲያወጣ
     */
    public function getFormattedCardNumberAttribute()
    {
        return $this->card_number ?? 'P-' . str_pad($this->id, 5, '0', STR_PAD_LEFT);
    }
}