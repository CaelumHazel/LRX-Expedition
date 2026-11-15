<?php

namespace App\Http\Controllers;

use App\Http\Requests\Tracking\StoreTrackingRequest;
use App\Http\Requests\Tracking\StoreScheduledTrackingRequest;
use App\Models\ScheduledTrackingUpdate;
use App\Models\Shipment;
use App\Models\TrackingLog;
use App\Support\TrackingBarcode;
use App\Services\ShipmentInventoryService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TrackingController extends Controller
{
    public function schedule(StoreScheduledTrackingRequest $request, Shipment $shipment)
    {
        ScheduledTrackingUpdate::create([
            'shipment_id' => $shipment->id,
            'created_by' => auth()->id(),
            ...$request->validated(),
        ]);

        return back()->with('success', 'Tracking update dijadwalkan.');
    }

    public function show(string $trackingNumber)
    {
        $shipment = Shipment::where(
            'tracking_number',
            $trackingNumber
        )
            ->with([
                'sender',
                'receiver',
                'courier',
                'warehouse',
                'items.item',
                'trackingLogs' => function ($query) {
                    $query->with('user')->latest('tracked_at');
                },
            ])
            ->firstOrFail();

        $barcode = app(TrackingBarcode::class)->render($shipment->tracking_number);

        return view('shipments.show', compact('shipment', 'barcode'));
    }

    public function store(
        StoreTrackingRequest $request,
        Shipment $shipment,
        ShipmentInventoryService $inventory
    ) {
        if (auth()->user()?->role !== 'admin') {
            throw new HttpException(403, 'Hanya admin yang dapat memperbarui tracking.');
        }

        DB::transaction(function () use (
            $request,
            $shipment,
            $inventory
        ) {
            $data = $request->validated();
            $lockedShipment = Shipment::query()
                ->lockForUpdate()
                ->findOrFail($shipment->id);
            $inventory->applyStatusTransition($lockedShipment, $data['status']);

            TrackingLog::create([
                'shipment_id' => $lockedShipment->id,
                'status' => $data['status'],
                'location' => $data['location'],
                'description' => $data['description'] ?? null,
                'tracked_at' => now(),
                'user_id' => auth()->id(),
                'source' => 'manual',
            ]);

            $lockedShipment->update([
                'status' => $data['status'],
                'current_position' => $data['location'],
            ]);
        });

        Cache::forget('dashboard.statistics');

        return back()->with(
            'success',
            'Tracking berhasil diperbarui.'
        );
    }
}