<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingRate extends Model
{
    protected $fillable = [
        'origin_city',
        'destination_city',
        'cargo_type',
        'courier_id',
        'price_per_kg',
        'estimated_days',
        'is_active',
    ];

    protected $casts = [
        'price_per_kg' => 'decimal:2',
        'estimated_days' => 'integer',
        'is_active' => 'boolean',
    ];

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class);
    }
}