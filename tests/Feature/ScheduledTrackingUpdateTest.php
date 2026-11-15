<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\ScheduledTrackingUpdate;
use App\Models\Shipment;
use App\Models\TrackingLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduledTrackingUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_due_scheduled_update_changes_shipment_and_adds_system_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $shipment = $this->createShipment();

        $this->actingAs($admin)->post(route('tracking.schedule', $shipment), [
            'status' => 'in_transit',
            'location' => 'Jakarta sorting center',
            'scheduled_at' => now()->addMinutes(5)->format('Y-m-d\TH:i'),
            'description' => 'Scheduled departure scan.',
        ])->assertRedirect();

        $scheduled = ScheduledTrackingUpdate::firstOrFail();
        $scheduled->update(['scheduled_at' => now()->subMinute()]);

        $this->artisan('tracking:apply-scheduled-updates')->assertExitCode(0);

        $this->assertDatabaseHas('shipments', [
            'id' => $shipment->id,
            'status' => 'in_transit',
            'current_position' => 'Jakarta sorting center',
        ]);
        $this->assertDatabaseHas('tracking_logs', [
            'shipment_id' => $shipment->id,
            'status' => 'in_transit',
            'location' => 'Jakarta sorting center',
            'source' => 'system',
            'user_id' => null,
        ]);
        $this->assertDatabaseHas('scheduled_tracking_updates', [
            'id' => $scheduled->id,
            'result' => 'applied',
        ]);
    }

    public function test_newer_manual_update_supersedes_a_due_scheduled_update(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $shipment = $this->createShipment();

        $this->actingAs($admin)->post(route('tracking.schedule', $shipment), [
            'status' => 'out_for_delivery',
            'location' => 'Destination city',
            'scheduled_at' => now()->addMinutes(5)->format('Y-m-d\TH:i'),
        ])->assertRedirect();

        $scheduled = ScheduledTrackingUpdate::firstOrFail();

        $this->post(route('tracking.store', $shipment), [
            'status' => 'in_transit',
            'location' => 'Manual scan override',
        ])->assertRedirect();

        $scheduled->update(['scheduled_at' => now()->subMinute()]);

        $this->artisan('tracking:apply-scheduled-updates')->assertExitCode(0);

        $this->assertDatabaseHas('shipments', [
            'id' => $shipment->id,
            'status' => 'in_transit',
            'current_position' => 'Manual scan override',
        ]);
        $this->assertDatabaseHas('scheduled_tracking_updates', [
            'id' => $scheduled->id,
            'result' => 'skipped',
        ]);
        $this->assertSame(1, TrackingLog::where('shipment_id', $shipment->id)->count());
    }

    private function createShipment(): Shipment
    {
        $sender = Customer::create(['name' => 'Sender', 'phone' => '0811111111', 'address' => 'Origin']);
        $receiver = Customer::create(['name' => 'Receiver', 'phone' => '0822222222', 'address' => 'Destination']);

        return Shipment::create([
            'tracking_number' => 'LRX'.now()->format('Ymd').random_int(1000, 9999),
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'cargo_type' => 'regular',
            'origin_city' => 'Jakarta',
            'destination_city' => 'Bandung',
            'receiver_address' => 'Destination address',
            'current_position' => 'Jakarta',
            'total_weight' => 1,
            'status' => 'pending',
        ]);
    }
}
