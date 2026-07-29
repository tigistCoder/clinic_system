<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Medicine extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'generic_name',
        'stock_quantity',
        'price_per_unit',
        'expiry_date',
    ];

    protected $casts = [
        'price_per_unit' => 'decimal:2',
        'expiry_date' => 'date',
    ];

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }
}

