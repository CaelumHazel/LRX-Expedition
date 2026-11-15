<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shipment extends Model
{
    protected $fillable = [
        'tracking_number',
        'sender_id',
        'receiver_id',
        'sender_name',
        'sender_phone',
        'receiver_name',
        'receiver_phone',
        'courier_id',
        'warehouse_id',
        'cargo_type',
        'origin_city',
        'destination_city',
        'receiver_address',
        'current_position',
        'total_weight',
        'shipping_cost',
        'declared_value',
        'inventory_reserved_at',
        'estimated_delivery_date',
        'estimated_distance_km',
        'status',
        'notes',
    ];

    protected $casts = [
        'total_weight' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'declared_value' => 'decimal:2',
        'estimated_distance_km' => 'integer',
        'estimated_delivery_date' => 'date',
        'inventory_reserved_at' => 'datetime',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'receiver_id');
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ShipmentItem::class);
    }

    public function packages(): HasMany
    {
        return $this->hasMany(ShipmentPackage::class);
    }

    public function trackingLogs(): HasMany
    {
        return $this->hasMany(TrackingLog::class);
    }

    public function scheduledTrackingUpdates(): HasMany
    {
        return $this->hasMany(ScheduledTrackingUpdate::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}