<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Medicine extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'generic_name',
        'stock_quantity',
        'price_per_unit',
        'reorder_level',
        'expiry_date',
    ];

    protected $casts = [
        'price_per_unit' => 'decimal:2',
        'reorder_level'  => 'integer',
        'expiry_date'    => 'date',
    ];

    /**
     * Relationship to Purchase (Batches).
     */
    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    /**
     * Relationship to Prescriptions.
     */
    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }

    /**
     * Dynamic Stock Level Calculation: Sum remaining_quantity of ONLY non-expired active batches.
     * Memory-optimized: Uses eager loaded $this->purchases collection when available to eliminate N+1 queries.
     */
    public function getValidStockQuantityAttribute(): int
    {
        if ($this->relationLoaded('purchases')) {
            $today = today()->toDateString();
            return (int) $this->purchases
                ->filter(function ($p) use ($today) {
                    $exp = $p->expiry_date instanceof \Carbon\Carbon
                        ? $p->expiry_date->toDateString()
                        : (string) $p->expiry_date;
                    return $exp >= $today && $p->remaining_quantity > 0;
                })
                ->sum('remaining_quantity');
        }

        return (int) $this->purchases()
            ->where('expiry_date', '>=', today())
            ->where('remaining_quantity', '>', 0)
            ->sum('remaining_quantity');
    }

    /**
     * Dynamic Accessor for stock_quantity so legacy calls read valid non-expired stock.
     */
    public function getStockQuantityAttribute(): int
    {
        return $this->valid_stock_quantity;
    }

    /**
     * Sum of remaining_quantity for expired batches (Memory-optimized).
     */
    public function getExpiredStockQuantityAttribute(): int
    {
        if ($this->relationLoaded('purchases')) {
            $today = today()->toDateString();
            return (int) $this->purchases
                ->filter(function ($p) use ($today) {
                    $exp = $p->expiry_date instanceof \Carbon\Carbon
                        ? $p->expiry_date->toDateString()
                        : (string) $p->expiry_date;
                    return $exp < $today && $p->remaining_quantity > 0;
                })
                ->sum('remaining_quantity');
        }

        return (int) $this->purchases()
            ->where('expiry_date', '<', today())
            ->where('remaining_quantity', '>', 0)
            ->sum('remaining_quantity');
    }

    /**
     * Earliest expiry date among active valid non-expired batches (FEFO) (Memory-optimized).
     */
    public function getEarliestExpiryDateAttribute()
    {
        if ($this->relationLoaded('purchases')) {
            $today = today()->toDateString();
            return $this->purchases
                ->filter(function ($p) use ($today) {
                    $exp = $p->expiry_date instanceof \Carbon\Carbon
                        ? $p->expiry_date->toDateString()
                        : (string) $p->expiry_date;
                    return $exp >= $today && $p->remaining_quantity > 0;
                })
                ->min('expiry_date');
        }

        return $this->purchases()
            ->where('expiry_date', '>=', today())
            ->where('remaining_quantity', '>', 0)
            ->min('expiry_date');
    }
}
