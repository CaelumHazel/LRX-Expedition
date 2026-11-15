<?php

namespace Tests\Feature;

use App\Models\ShippingRate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShippingRateManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_admin_can_create_and_deactivate_a_route_rate(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $this->get(route('shipping-rates.index'))->assertOk()->assertSee('Add a route rate');

        $this->post(route('shipping-rates.store'), [
            'origin_city' => 'Jakarta',
            'destination_city' => 'Bandung',
            'cargo_type' => 'regular',
            'price_per_kg' => 6500,
            'estimated_days' => 2,
            'is_active' => 1,
        ])->assertRedirect(route('shipping-rates.index'));

        $rate = ShippingRate::firstOrFail();
        $this->assertTrue($rate->is_active);

        $this->put(route('shipping-rates.update', $rate), [
            'origin_city' => 'Jakarta',
            'destination_city' => 'Bandung',
            'cargo_type' => 'regular',
            'price_per_kg' => 7000,
            'estimated_days' => 3,
        ])->assertRedirect(route('shipping-rates.index'));

        $this->assertDatabaseHas('shipping_rates', [
            'id' => $rate->id,
            'price_per_kg' => 7000,
            'estimated_days' => 3,
            'is_active' => false,
        ]);
    }

    public function test_staff_cannot_manage_shipping_rates(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staff)
            ->get(route('shipping-rates.index'))
            ->assertForbidden();

        $this->actingAs($staff)
            ->post(route('shipping-rates.store'), [
                'origin_city' => 'Jakarta',
                'destination_city' => 'Bandung',
                'cargo_type' => 'regular',
                'price_per_kg' => 6500,
                'estimated_days' => 2,
            ])
            ->assertForbidden();
    }
}
