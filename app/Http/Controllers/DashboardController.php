<?php

namespace App\Http\Controllers;

use App\Models\Courier;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Shipment;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index()
    {
        $data = Cache::remember('dashboard.statistics', now()->addSeconds(30), function () {
            $counts = Shipment::query()
                ->selectRaw('status, COUNT(*) as aggregate')
                ->groupBy('status')
                ->pluck('aggregate', 'status');

            return [
                'totalShipments' => (int) $counts->sum(),
                'pendingShipments' => (int) ($counts['pending'] ?? 0),
                'inTransitShipments' => (int) ($counts['in_transit'] ?? 0),
                'deliveredShipments' => (int) ($counts['delivered'] ?? 0),
                'totalCustomers' => Customer::count(),
                'totalItems' => Item::count(),
                'totalWarehouses' => Warehouse::count(),
                'totalCouriers' => Courier::count(),
            ];
        });

        $recentShipments = Shipment::with(['sender', 'receiver'])
            ->latest()
            ->limit(8)
            ->get();

        return view('dashboard.index', $data + compact('recentShipments'));
    }
}
