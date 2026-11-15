<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Item;
use App\Models\Shipment;
use App\Models\ShippingRate;
use App\Models\TrackingLog;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipmentTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_admin_can_create_shipment_and_record_tracking_updates(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $sender = Customer::create([
            'name' => 'Sender One',
            'phone' => '08123456789',
            'address' => 'Origin address',
        ]);
        $receiver = Customer::create([
            'name' => 'Receiver One',
            'phone' => '08129876543',
            'address' => 'Destination address',
        ]);
        $item = Item::create([
            'name' => 'Book',
            'sku' => 'BOOK-001',
            'weight' => 2.5,
            'stock' => 10,
            'unit' => 'pcs',
        ]);

        ShippingRate::create([
            'origin_city' => 'KOTA JAKARTA SELATAN',
            'destination_city' => 'KOTA BANDUNG',
            'cargo_type' => 'regular',
            'price_per_kg' => 6000,
            'estimated_days' => 2,
            'is_active' => true,
        ]);

        $this->actingAs($admin)->post(route('shipments.store'), [
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'cargo_type' => 'regular',
            'origin_city' => 'KOTA JAKARTA SELATAN',
            'destination_city' => 'KOTA BANDUNG',
            'receiver_address' => 'Jl. Lengkap No. 10, RT 01/RW 02, Bandung, 40111',
            'total_weight' => 4.25,
            'declared_value' => 125000,
            'items' => [
                ['item_id' => $item->id, 'quantity' => 1],
                ['item_id' => $item->id, 'quantity' => 1],
            ],
        ])->assertRedirect();

        $shipment = Shipment::firstOrFail();
        $this->assertSame('LRX'.now()->format('Ymd').'0001', $shipment->tracking_number);
        $this->assertSame('4.25', $shipment->total_weight);
        $this->assertSame('30000.00', $shipment->shipping_cost);
        $this->assertSame('KOTA JAKARTA SELATAN', $shipment->current_position);
        $this->assertSame($admin->id, $shipment->trackingLogs()->firstOrFail()->user_id);
        $this->assertSame(8, $item->fresh()->stock);
        $this->assertDatabaseHas('shipment_items', [
            'shipment_id' => $shipment->id,
            'item_id' => $item->id,
            'quantity' => 2,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'shipment_id' => $shipment->id,
            'item_id' => $item->id,
            'type' => 'shipment_outbound',
            'quantity' => 2,
        ]);

        $this->get(route('shipments.show', $shipment))
            ->assertOk()
            ->assertSee('Shipment barcode')
            ->assertSee($shipment->tracking_number);

        $this->get(route('shipments.create'))
            ->assertOk()
            ->assertSee('Sender name')
            ->assertSee('Receiver name')
            ->assertSee('Item name')
            ->assertSee('Select cargo type')
            ->assertSee('KOTA JAYAPURA')
            ->assertSee('role="combobox"', false)
            ->assertSee('Type to search; use ↑ / ↓ and Enter to select.')
            ->assertSee('Measured shipment weight')
            ->assertSee('Detailed Receiver Address');

        $this->get(route('tracking.show', $shipment->tracking_number))
            ->assertOk()
            ->assertSee('Tracking history');

        $this->get(route('shipments.edit', $shipment))
            ->assertOk()
            ->assertSee('Edit '.$shipment->tracking_number);

        $this->put(route('shipments.update', $shipment), [
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'cargo_type' => 'regular',
            'origin_city' => 'KOTA JAKARTA SELATAN',
            'destination_city' => 'KOTA BANDUNG',
            'receiver_address' => 'Updated full delivery address',
            'total_weight' => 5,
            'declared_value' => 125000,
            'notes' => 'Handle with care',
        ])->assertRedirect(route('shipments.show', $shipment));

        $this->assertDatabaseHas('shipments', [
            'id' => $shipment->id,
            'receiver_address' => 'Updated full delivery address',
            'shipping_cost' => 30000,
        ]);

        $this->post(route('tracking.store', $shipment), [
            'status' => 'in_transit',
            'location' => 'Cikarang sorting center',
            'description' => 'Parcel has departed the origin facility.',
        ])->assertRedirect();

        $this->assertDatabaseHas('shipments', [
            'id' => $shipment->id,
            'status' => 'in_transit',
            'current_position' => 'Cikarang sorting center',
        ]);
        $this->assertDatabaseHas('tracking_logs', [
            'shipment_id' => $shipment->id,
            'status' => 'in_transit',
            'location' => 'Cikarang sorting center',
            'user_id' => $admin->id,
            'source' => 'manual',
        ]);
        $this->assertSame(2, TrackingLog::where('shipment_id', $shipment->id)->count());

        $this->post(route('tracking.store', $shipment), [
            'status' => 'cancelled',
            'location' => 'Cikarang sorting center',
        ])->assertRedirect();

        $this->assertSame(10, $item->fresh()->stock);
        $this->assertDatabaseHas('stock_movements', [
            'shipment_id' => $shipment->id,
            'item_id' => $item->id,
            'type' => 'shipment_return',
            'quantity' => 2,
        ]);

        $this->get(route('shipments.index', ['search' => $shipment->tracking_number]))
            ->assertOk()
            ->assertSee($shipment->tracking_number);
    }

    public function test_staff_cannot_update_tracking(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $sender = Customer::create(['name' => 'Sender', 'phone' => '0811111111', 'address' => 'Origin']);
        $receiver = Customer::create(['name' => 'Receiver', 'phone' => '0822222222', 'address' => 'Destination']);
        $shipment = Shipment::create([
            'tracking_number' => 'LRX'.now()->format('Ymd').'9999',
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

        $this->actingAs($staff)
            ->post(route('tracking.store', $shipment), [
                'status' => 'in_transit',
                'location' => 'Bandung',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('shipments', [
            'id' => $shipment->id,
            'status' => 'pending',
            'current_position' => 'Jakarta',
        ]);
        $this->assertDatabaseCount('tracking_logs', 0);
    }

    public function test_daily_sequence_continues_after_existing_resi_numbers(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $sender = Customer::create(['name' => 'Sender', 'phone' => '0811111111', 'address' => 'Origin']);
        $receiver = Customer::create(['name' => 'Receiver', 'phone' => '0822222222', 'address' => 'Destination']);
        $item = Item::create([
            'name' => 'Parcel',
            'sku' => 'PARCEL-001',
            'weight' => 1,
            'stock' => 2,
            'unit' => 'pcs',
        ]);
        ShippingRate::create([
            'origin_city' => 'KOTA JAKARTA SELATAN',
            'destination_city' => 'KOTA BANDUNG',
            'cargo_type' => 'regular',
            'price_per_kg' => 6000,
            'estimated_days' => 2,
            'is_active' => true,
        ]);
        Shipment::create([
            'tracking_number' => 'LRX'.now()->format('Ymd').'0007',
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'cargo_type' => 'regular',
            'origin_city' => 'KOTA JAKARTA SELATAN',
            'destination_city' => 'KOTA BANDUNG',
            'receiver_address' => 'Destination address',
            'current_position' => 'Jakarta',
            'total_weight' => 1,
            'status' => 'pending',
        ]);

        $this->actingAs($admin)->post(route('shipments.store'), [
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'cargo_type' => 'regular',
            'origin_city' => 'KOTA JAKARTA SELATAN',
            'destination_city' => 'KOTA BANDUNG',
            'receiver_address' => 'Full address',
            'total_weight' => 1,
            'items' => [['item_id' => $item->id, 'quantity' => 1]],
        ])->assertRedirect();

        $this->assertDatabaseHas('shipments', [
            'tracking_number' => 'LRX'.now()->format('Ymd').'0008',
        ]);
    }

    public function test_shipment_accepts_free_text_parties_and_package_details_with_dummy_route_fee(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $warehouseA = Warehouse::create([
            'name' => 'Warehouse A',
            'code' => 'WH-A',
            'address' => 'Full warehouse address A',
            'city' => 'KOTA JAKARTA SELATAN',
            'is_active' => true,
        ]);
        $warehouseB = Warehouse::create([
            'name' => 'Warehouse B',
            'code' => 'WH-B',
            'address' => 'Full warehouse address B',
            'city' => 'KOTA BANDUNG',
            'is_active' => true,
        ]);

        $this->actingAs($admin)->post(route('shipments.store'), [
            'sender_name' => 'Sender Input',
            'sender_phone' => '081234567890',
            'receiver_name' => 'Receiver Input',
            'receiver_phone' => '082345678901',
            'warehouse_id' => $warehouseA->id,
            'cargo_type' => 'express',
            'origin_city' => 'KOTA JAKARTA SELATAN',
            'destination_city' => 'KOTA BANDUNG',
            'receiver_address' => 'Jl. Example No. 10, RT 01/RW 02, Kelurahan, Kecamatan, 40111, dekat taman',
            'total_weight' => 1.5,
            'declared_value' => 250000,
            'items' => [[
                'name' => 'Fragile glassware',
                'description' => 'Packed with protective foam',
                'quantity' => 2,
            ]],
        ])->assertRedirect();

        $shipment = Shipment::with('packages')->firstOrFail();
        $this->assertSame('Sender Input', $shipment->sender_name);
        $this->assertSame('Receiver Input', $shipment->receiver_name);
        $this->assertSame('Warehouse A', $shipment->current_position);
        $this->assertSame('16500.00', $shipment->shipping_cost);
        $this->assertSame(650, $shipment->estimated_distance_km);
        $this->assertSame('Fragile glassware', $shipment->packages->firstOrFail()->name);
        $this->assertNull($shipment->inventory_reserved_at);

        $this->put(route('shipments.update', $shipment), [
            'sender_name' => 'Sender Input',
            'sender_phone' => '081234567890',
            'receiver_name' => 'Receiver Input',
            'receiver_phone' => '082345678901',
            'warehouse_id' => $warehouseB->id,
            'cargo_type' => 'express',
            'origin_city' => 'KOTA JAKARTA SELATAN',
            'destination_city' => 'KOTA BANDUNG',
            'receiver_address' => 'Jl. Example No. 10, RT 01/RW 02, Kelurahan, Kecamatan, 40111, dekat taman',
            'total_weight' => 1.5,
            'declared_value' => 250000,
            'packages' => [[
                'name' => 'Fragile glassware',
                'description' => 'Updated packing note',
                'quantity' => 2,
            ]],
        ])->assertRedirect(route('shipments.show', $shipment));

        $this->assertDatabaseHas('shipments', [
            'id' => $shipment->id,
            'warehouse_id' => $warehouseB->id,
            'current_position' => 'Warehouse B',
        ]);
        $this->assertDatabaseHas('tracking_logs', [
            'shipment_id' => $shipment->id,
            'location' => 'Warehouse B',
            'description' => 'Shipment dipindahkan ke Warehouse B.',
        ]);
        $this->assertDatabaseHas('shipment_packages', [
            'shipment_id' => $shipment->id,
            'description' => 'Updated packing note',
            'quantity' => 2,
        ]);
    }
}
