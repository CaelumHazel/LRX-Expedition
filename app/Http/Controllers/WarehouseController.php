<?php

namespace App\Http\Controllers;

use App\Http\Requests\Warehouse\StoreWarehouseRequest;
use App\Http\Requests\Warehouse\UpdateWarehouseRequest;
use App\Support\IndonesianCityCatalog;
use App\Models\Warehouse;

class WarehouseController extends Controller
{
    public function index()
    {
        $warehouses = Warehouse::query()
            ->with(['shipments' => fn ($query) => $query
                ->whereNotIn('status', ['delivered', 'cancelled'])
                ->with(['sender', 'receiver', 'courier', 'packages', 'items.item'])
                ->latest()])
            ->withCount(['shipments as active_shipments_count' => fn ($query) => $query
                ->whereNotIn('status', ['delivered', 'cancelled'])])
            ->orderBy('name')
            ->paginate(20);

        return view('warehouses.index', compact('warehouses'));
    }

    public function create()
    {
        $cities = app(IndonesianCityCatalog::class)->all();

        return view('warehouses.create', compact('cities'));
    }

    public function store(StoreWarehouseRequest $request)
    {
        Warehouse::create($request->validated());

        return redirect()
            ->route('warehouses.index')
            ->with('success', 'Warehouse berhasil dibuat.');
    }

    public function show(Warehouse $warehouse)
    {
        $warehouse->load([
            'shipments' => fn ($query) => $query
                ->with(['sender', 'receiver', 'courier', 'packages', 'items.item'])
                ->latest(),
            'stockMovements.item',
        ]);

        return view('warehouses.show', compact('warehouse'));
    }

    public function edit(Warehouse $warehouse)
    {
        $cities = app(IndonesianCityCatalog::class)->all();

        return view('warehouses.edit', compact('warehouse', 'cities'));
    }

    public function update(
        UpdateWarehouseRequest $request,
        Warehouse $warehouse
    ) {
        $warehouse->update($request->validated());

        return redirect()
            ->route('warehouses.index')
            ->with('success', 'Warehouse berhasil diperbarui.');
    }

    public function destroy(Warehouse $warehouse)
    {
        if ($warehouse->shipments()->exists()) {
            return back()->with(
                'error',
                'Warehouse tidak dapat dihapus karena sudah digunakan shipment.'
            );
        }

        $warehouse->delete();

        return redirect()
            ->route('warehouses.index')
            ->with('success', 'Warehouse berhasil dihapus.');
    }
}