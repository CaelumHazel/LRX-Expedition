<?php

namespace App\Services;

use App\Models\Item;
use App\Models\Shipment;
use App\Models\StockMovement;
use Illuminate\Validation\ValidationException;

class ShipmentInventoryService
{
    public function reserve(Shipment $shipment): void
    {
        foreach ($shipment->items()->orderBy('item_id')->get() as $shipmentItem) {
            $item = Item::query()->lockForUpdate()->findOrFail($shipmentItem->item_id);

            if ($item->stock < $shipmentItem->quantity) {
                throw ValidationException::withMessages([
                    'items' => "{$item->name} hanya memiliki {$item->stock} unit yang tersedia.",
                ]);
            }

            $item->decrement('stock', $shipmentItem->quantity);
            $this->recordMovement($shipment, $item->id, 'shipment_outbound', $shipmentItem->quantity);
        }

        $shipment->update(['inventory_reserved_at' => now()]);
    }

    public function applyStatusTransition(Shipment $shipment, string $nextStatus): void
    {
        if (! $shipment->inventory_reserved_at) {
            return;
        }

        $isCurrentlyCancelled = $shipment->status === 'cancelled';
        $willBeCancelled = $nextStatus === 'cancelled';

        if ($isCurrentlyCancelled === $willBeCancelled) {
            return;
        }

        if ($willBeCancelled) {
            $this->restore($shipment, 'cancelling');

            return;
        }

        foreach ($shipment->items()->orderBy('item_id')->get() as $shipmentItem) {
            $item = Item::query()->lockForUpdate()->findOrFail($shipmentItem->item_id);

            if ($item->stock < $shipmentItem->quantity) {
                throw ValidationException::withMessages([
                    'status' => "{$item->name} no longer has enough stock to reopen this shipment.",
                ]);
            }

            $item->decrement('stock', $shipmentItem->quantity);
            $this->recordMovement($shipment, $item->id, 'shipment_outbound', $shipmentItem->quantity);
        }
    }

    public function restore(Shipment $shipment, string $reason): void
    {
        foreach ($shipment->items()->orderBy('item_id')->get() as $shipmentItem) {
            $item = Item::query()->lockForUpdate()->findOrFail($shipmentItem->item_id);
            $item->increment('stock', $shipmentItem->quantity);
            StockMovement::create([
                'item_id' => $item->id,
                'warehouse_id' => $shipment->warehouse_id,
                'shipment_id' => $shipment->id,
                'type' => 'shipment_return',
                'quantity' => $shipmentItem->quantity,
                'description' => "Returned to stock after {$reason} shipment {$shipment->tracking_number}.",
            ]);
        }
    }

    private function recordMovement(Shipment $shipment, int $itemId, string $type, int $quantity): void
    {
        StockMovement::create([
            'item_id' => $itemId,
            'warehouse_id' => $shipment->warehouse_id,
            'shipment_id' => $shipment->id,
            'type' => $type,
            'quantity' => $quantity,
            'description' => $type === 'shipment_return'
                ? "Returned to stock after cancelling {$shipment->tracking_number}."
                : "Reserved for shipment {$shipment->tracking_number}.",
        ]);
    }
}
