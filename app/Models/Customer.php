<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'address',
    ];

    public function sentShipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'sender_id');
    }

    public function receivedShipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'receiver_id');
    }
}