<?php

namespace App\Http\Controllers;

use App\Http\Requests\Courier\StoreCourierRequest;
use App\Http\Requests\Courier\UpdateCourierRequest;
use App\Models\Courier;

class CourierController extends Controller
{
    public function index()
    {
        $couriers = Courier::query()
            ->with(['shipments' => fn ($query) => $query
                ->whereNotIn('status', ['delivered', 'cancelled'])
                ->with(['sender', 'receiver', 'warehouse', 'packages', 'items.item'])
                ->latest()])
            ->withCount(['shipments as active_shipments_count' => fn ($query) => $query
                ->whereNotIn('status', ['delivered', 'cancelled'])])
            ->orderBy('name')
            ->paginate(20);

        return view('couriers.index', compact('couriers'));
    }

    public function create()
    {
        return view('couriers.create');
    }

    public function store(StoreCourierRequest $request)
    {
        Courier::create($request->validated());

        return redirect()
            ->route('couriers.index')
            ->with('success', 'Courier berhasil dibuat.');
    }

    public function show(Courier $courier)
    {
        $courier->load([
            'shipments' => fn ($query) => $query
                ->with(['sender', 'receiver', 'warehouse', 'packages', 'items.item'])
                ->latest(),
        ]);

        return view('couriers.show', compact('courier'));
    }

    public function edit(Courier $courier)
    {
        return view('couriers.edit', compact('courier'));
    }

    public function update(
        UpdateCourierRequest $request,
        Courier $courier
    ) {
        $courier->update($request->validated());

        return redirect()
            ->route('couriers.index')
            ->with('success', 'Courier berhasil diperbarui.');
    }

    public function destroy(Courier $courier)
    {
        if ($courier->shipments()->exists()) {
            return back()->with(
                'error',
                'Courier tidak dapat dihapus karena sudah digunakan shipment.'
            );
        }

        $courier->delete();

        return redirect()
            ->route('couriers.index')
            ->with('success', 'Courier berhasil dihapus.');
    }
}