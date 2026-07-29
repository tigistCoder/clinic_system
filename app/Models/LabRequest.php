<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LabRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'visit_id',
        'test_name',
        'price',
        'payment_status',
        'cashier_id',
        'result',
        'lab_tech_id',
        'status',
    ];

    protected $casts = [
        'result' => 'array',
        'price' => 'decimal:2',
    ];

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function labTech()
    {
        return $this->belongsTo(User::class, 'lab_tech_id');
    }
}

