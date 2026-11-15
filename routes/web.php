<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\CourierController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ShipmentController;
use App\Http\Controllers\ShippingRateController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('login.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/customers', fn () => response()->view('errors.404', [
        'feature' => 'Customers',
        'description' => 'Customer management is being prepared and will be available in a future update.',
    ], 404))->name('customers.index');
    Route::resource('couriers', CourierController::class)->only(['index', 'create', 'store', 'show']);
    Route::resource('warehouses', WarehouseController::class);
    Route::get('/items', fn () => response()->view('errors.404', [
        'feature' => 'Inventory',
        'description' => 'Inventory management is being prepared and will be available in a future update.',
    ], 404))->name('items.index');
    Route::get('/shipments/estimate', [ShipmentController::class, 'estimate'])->name('shipments.estimate');
    Route::resource('shipments', ShipmentController::class);
    Route::resource('shipping-rates', ShippingRateController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::get('/stock', [ShipmentController::class, 'stock'])->name('stock.index');
    Route::get('/tracking/{trackingNumber}', [TrackingController::class, 'show'])->name('tracking.show');
    Route::post('/shipments/{shipment}/tracking', [TrackingController::class, 'store'])->name('tracking.store');
    Route::post('/shipments/{shipment}/tracking/schedule', [TrackingController::class, 'schedule'])->name('tracking.schedule');
    Route::view('/ui-test', 'ui-test')->name('ui.test');

    if (app()->environment('local')) {
        Route::get('/_debug/ping', function () {
            $start = microtime(true);

            return response()->json([
                'status' => 'OK',
                'php_time_ms' => round((microtime(true) - $start) * 1000, 2),
            ]);
        });

        Route::get('/_debug/db', function () {
            $totalStart = microtime(true);
            $connectionStart = microtime(true);
            DB::connection()->getPdo();
            $connectionMs = round((microtime(true) - $connectionStart) * 1000, 2);

            $queryStart = microtime(true);
            DB::select('SELECT 1');
            $queryMs = round((microtime(true) - $queryStart) * 1000, 2);

            return response()->json([
                'connection_ms' => $connectionMs,
                'query_ms' => $queryMs,
                'total_php_ms' => round((microtime(true) - $totalStart) * 1000, 2),
            ]);
        });

        Route::get('/_debug/db-timing', function () {
            $start = microtime(true);
            DB::connection()->getPdo();
            $connectionMs = (microtime(true) - $start) * 1000;

            $start = microtime(true);
            $result = DB::selectOne('SELECT 1 AS result');
            $queryMs = (microtime(true) - $start) * 1000;

            return response()->json([
                'connection_ms' => round($connectionMs, 2),
                'query_ms' => round($queryMs, 2),
                'result' => $result->result,
            ]);
        });

        Route::get('/_debug/db-reuse', function () {
            $connectionStart = microtime(true);
            DB::connection()->getPdo();
            $connectionMs = (microtime(true) - $connectionStart) * 1000;
            $queries = [];

            for ($iteration = 1; $iteration <= 5; $iteration++) {
                $start = microtime(true);
                DB::selectOne('SELECT 1 AS result');
                $queries[] = [
                    'iteration' => $iteration,
                    'query_ms' => round((microtime(true) - $start) * 1000, 2),
                ];
            }

            return response()->json([
                'connection_ms' => round($connectionMs, 2),
                'queries' => $queries,
                'total_query_ms' => round(array_sum(array_column($queries, 'query_ms')), 2),
            ]);
        });
    }
});
