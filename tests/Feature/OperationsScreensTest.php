<?php

namespace Tests\Feature;

use App\Models\Courier;
use App\Models\Customer;
use App\Models\Shipment;
use App\Models\ShipmentPackage;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\IndonesianCityCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationsScreensTest extends TestCase
{
    use RefreshDatabase;

    public function test_warehouse_and_courier_screens_show_assigned_package_details(): void
    {
        $user = User::factory()->create();
        $sender = Customer::create(['name' => 'Sender', 'phone' => '0811111111', 'address' => 'Origin']);
        $receiver = Customer::create(['name' => 'Receiver', 'phone' => '0822222222', 'address' => 'Destination']);
        $warehouse = Warehouse::create([
            'name' => 'Warehouse A',
            'code' => 'WH-A',
            'address' => 'Full warehouse address',
            'city' => 'KOTA BANDUNG',
            'is_active' => true,
        ]);
        $courier = Courier::create([
            'name' => 'Courier One',
            'phone' => '0833333333',
            'vehicle_type' => 'Van',
            'vehicle_number' => 'B 1234 XYZ',
            'is_active' => true,
        ]);
        $shipment = Shipment::create([
            'tracking_number' => 'LRX'.now()->format('Ymd').'8888',
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'sender_name' => 'Sender',
            'receiver_name' => 'Receiver',
            'courier_id' => $courier->id,
            'warehouse_id' => $warehouse->id,
            'cargo_type' => 'regular',
            'origin_city' => 'KOTA BANDUNG',
            'destination_city' => 'KOTA DEPOK',
            'receiver_address' => 'Full receiver address',
            'current_position' => 'Warehouse A',
            'total_weight' => 2,
            'status' => 'in_transit',
        ]);
        ShipmentPackage::create([
            'shipment_id' => $shipment->id,
            'name' => 'Electronics box',
            'description' => 'Handle with care',
            'quantity' => 2,
        ]);

        $this->actingAs($user)
            ->get(route('warehouses.index'))
            ->assertOk()
            ->assertSee('Warehouse A')
            ->assertSee('Electronics box');

        $this->get(route('warehouses.create'))
            ->assertOk()
            ->assertSee('Add warehouse')
            ->assertSee('KOTA JAYAPURA');

        $this->get(route('warehouses.edit', $warehouse))
            ->assertOk()
            ->assertSee('Edit Warehouse A');

        $this->get(route('warehouses.show', $warehouse))
            ->assertOk()
            ->assertSee('Handle with care');

        $this->get(route('couriers.index'))
            ->assertOk()
            ->assertSee('Courier One')
            ->assertSee('Electronics box');

        $this->get(route('couriers.show', $courier))
            ->assertOk()
            ->assertSee($shipment->tracking_number)
            ->assertSee('Handle with care');
    }

    public function test_inventory_and_customers_404_pages_keep_the_application_shell_and_show_development_state(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('items.index'))
            ->assertNotFound()
            ->assertSee('LRX TRACKING')
            ->assertSee('Inventory')
            ->assertSee('Feature under development')
            ->assertSee('Back to Dashboard');

        $this->get(route('customers.index'))
            ->assertNotFound()
            ->assertSee('LRX TRACKING')
            ->assertSee('Customers')
            ->assertSee('Feature under development');

        $this->get('/unmapped-page')
            ->assertNotFound()
            ->assertSee('LRX TRACKING')
            ->assertSee('Route not found')
            ->assertSee('404');
    }

    public function test_couriers_can_be_added_and_then_selected_for_shipments(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('couriers.index'))
            ->assertOk()
            ->assertSee(route('couriers.create'))
            ->assertSee('Add Courier');

        $this->get(route('couriers.create'))
            ->assertOk()
            ->assertSee('Courier name')
            ->assertSee('Vehicle plate');

        $this->post(route('couriers.store'), [
            'name' => 'Courier Two',
            'phone' => '081234567890',
            'vehicle_type' => 'Motorcycle',
            'vehicle_number' => 'B 1234 XYZ',
            'is_active' => 1,
        ])->assertRedirect(route('couriers.index'));

        $this->assertDatabaseHas('couriers', [
            'name' => 'Courier Two',
            'phone' => '081234567890',
            'is_active' => true,
        ]);

        $this->get(route('shipments.create'))
            ->assertOk()
            ->assertSee('Courier Two — Motorcycle');
    }

    public function test_city_catalog_contains_all_514_regencies_and_cities_across_38_provinces(): void
    {
        $cities = app(IndonesianCityCatalog::class)->all();

        $this->assertCount(514, $cities);
        $this->assertCount(38, array_unique(array_column($cities, 'province_id')));
        $this->assertContains('KOTA JAYAPURA', array_column($cities, 'name'));
    }
}
