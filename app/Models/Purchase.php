<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'medicine_id',
        'batch_number',
        'quantity_purchased',
        'quantity_sold',
        'remaining_quantity',
        'purchase_date',
        'expiry_date',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'expiry_date'   => 'date',
    ];

    /**
     * Relationship to Medicine model.
     */
    public function medicine()
    {
        return $this->belongsTo(Medicine::class);
    }

    /**
     * Scope to query only valid, active, non-expired batches with available stock.
     */
    public function scopeValidNonExpired($query)
    {
        return $query->where('expiry_date', '>=', today())
                     ->where('remaining_quantity', '>', 0);
    }

    /**
     * Scope to query expired batches.
     */
    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<', today())
                     ->where('remaining_quantity', '>', 0);
    }
}
