<?php

namespace Database\Seeders;

use App\Models\Courier;
use App\Models\Customer;
use App\Models\Item;
use App\Models\ShippingRate;
use App\Models\Warehouse;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $adminUsername = config('app.initial_admin.username');
        $adminEmail = config('app.initial_admin.email');
        $adminPassword = config('app.initial_admin.password');

        if ($adminUsername && $adminEmail && $adminPassword) {
            User::updateOrCreate(
                ['username' => $adminUsername],
                [
                    'name' => 'Administrator',
                    'email' => $adminEmail,
                    'password' => $adminPassword,
                    'role' => 'admin',
                ]
            );
        }

        Customer::firstOrCreate(
            ['phone' => '081234567890'],
            [
                'name' => 'Djuan Sender',
                'address' => 'Solo, Jawa Tengah',
            ]
        );

        Customer::firstOrCreate(
            ['phone' => '081298765432'],
            [
                'name' => 'Djuan Receiver',
                'address' => 'Jakarta Selatan, DKI Jakarta',
            ]
        );

        Courier::firstOrCreate(
            ['vehicle_number' => 'B 1234 ABC'],
            [
                'name' => 'Budi Courier',
                'phone' => '081111111111',
                'vehicle_type' => 'Motor',
                'is_active' => true,
            ]
        );

        Warehouse::firstOrCreate(
            ['code' => 'WH-JKT'],
            [
                'name' => 'Warehouse Jakarta',
                'address' => 'Jl. Warehouse Jakarta',
                'city' => 'KOTA JAKARTA SELATAN',
                'is_active' => true,
            ]
        );

        Item::firstOrCreate(
            ['sku' => 'LAPTOP-001'],
            [
                'name' => 'Laptop',
                'description' => 'Laptop elektronik',
                'weight' => 2.50,
                'stock' => 10,
                'unit' => 'pcs',
            ]
        );

        Item::firstOrCreate(
            ['sku' => 'KEYBOARD-001'],
            [
                'name' => 'Keyboard',
                'description' => 'Mechanical keyboard',
                'weight' => 1.20,
                'stock' => 20,
                'unit' => 'pcs',
            ]
        );

        ShippingRate::firstOrCreate(
            [
                'origin_city' => 'KOTA DEPOK',
                'destination_city' => 'KOTA JAKARTA SELATAN',
                'cargo_type' => 'regular',
            ],
            [
                'price_per_kg' => 10000,
                'estimated_days' => 2,
                'is_active' => true,
            ]
        );

        ShippingRate::firstOrCreate(
            [
                'origin_city' => 'KOTA JAKARTA SELATAN',
                'destination_city' => 'KOTA DEPOK',
                'cargo_type' => 'regular',
            ],
            [
                'price_per_kg' => 10000,
                'estimated_days' => 2,
                'is_active' => true,
            ]
        );
    }
}