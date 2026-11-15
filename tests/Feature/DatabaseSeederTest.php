<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\ShippingRate;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_can_run_repeatedly_without_duplicating_or_resetting_data(): void
    {
        $this->seed(DatabaseSeeder::class);

        Item::where('sku', 'LAPTOP-001')->update(['stock' => 5]);
        ShippingRate::where('origin_city', 'KOTA DEPOK')
            ->where('destination_city', 'KOTA JAKARTA SELATAN')
            ->where('cargo_type', 'regular')
            ->update(['price_per_kg' => 15000]);

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('customers', 2);
        $this->assertDatabaseCount('couriers', 1);
        $this->assertDatabaseCount('warehouses', 1);
        $this->assertDatabaseCount('items', 2);
        $this->assertDatabaseCount('shipping_rates', 2);
        $this->assertDatabaseHas('items', ['sku' => 'LAPTOP-001', 'stock' => 5]);
        $this->assertDatabaseHas('shipping_rates', [
            'origin_city' => 'KOTA DEPOK',
            'destination_city' => 'KOTA JAKARTA SELATAN',
            'cargo_type' => 'regular',
            'price_per_kg' => 15000,
        ]);
    }

    public function test_database_seeder_creates_admin_from_application_configuration(): void
    {
        config([
            'app.initial_admin' => [
                'username' => 'configured-admin',
                'email' => 'configured-admin@example.test',
                'password' => 'a-strong-test-password',
            ],
        ]);

        $this->seed(DatabaseSeeder::class);

        $admin = User::where('username', 'configured-admin')->firstOrFail();

        $this->assertSame('admin', $admin->role);
        $this->assertTrue(Hash::check('a-strong-test-password', $admin->password));
    }
}
