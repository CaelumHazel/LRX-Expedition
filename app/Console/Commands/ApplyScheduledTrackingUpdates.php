<?php

namespace App\Console\Commands;

use App\Models\ScheduledTrackingUpdate;
use App\Models\Shipment;
use App\Models\TrackingLog;
use App\Services\ShipmentInventoryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ApplyScheduledTrackingUpdates extends Command
{
    protected $signature = 'tracking:apply-scheduled-updates';

    protected $description = 'Apply due shipment tracking updates without overriding newer manual updates';

    public function handle(ShipmentInventoryService $inventory): int
    {
        $applied = 0;

        ScheduledTrackingUpdate::query()
            ->whereNull('processed_at')
            ->where('scheduled_at', '<=', now())
            ->orderBy('scheduled_at')
            ->orderBy('id')
            ->pluck('id')
            ->each(function (int $id) use (&$applied, $inventory) {
                $wasApplied = DB::transaction(function () use ($id, $inventory) {
                    $update = ScheduledTrackingUpdate::query()
                        ->lockForUpdate()
                        ->find($id);

                    if (! $update || $update->processed_at) {
                        return false;
                    }

                    $shipment = Shipment::query()
                        ->lockForUpdate()
                        ->find($update->shipment_id);

                    if (! $shipment) {
                        $update->update(['processed_at' => now(), 'result' => 'skipped']);

                        return false;
                    }

                    $hasManualOverride = TrackingLog::query()
                        ->where('shipment_id', $shipment->id)
                        ->where('source', 'manual')
                        ->where('created_at', '>=', $update->created_at)
                        ->exists();

                    if ($hasManualOverride || in_array($shipment->status, ['delivered', 'cancelled'], true)) {
                        $update->update(['processed_at' => now(), 'result' => 'skipped']);

                        return false;
                    }

                    $inventory->applyStatusTransition($shipment, $update->status);

                    TrackingLog::create([
                        'shipment_id' => $shipment->id,
                        'status' => $update->status,
                        'location' => $update->location,
                        'description' => $update->description,
                        'tracked_at' => now(),
                        'user_id' => null,
                        'source' => 'system',
                    ]);

                    $shipment->update([
                        'status' => $update->status,
                        'current_position' => $update->location,
                    ]);

                    $update->update(['processed_at' => now(), 'result' => 'applied']);

                    return true;
                });

                if ($wasApplied) {
                    $applied++;
                }
            });

        $this->info("Applied {$applied} scheduled tracking update(s).");

        if ($applied > 0) {
            Cache::forget('dashboard.statistics');
        }

        return self::SUCCESS;
    }
}
