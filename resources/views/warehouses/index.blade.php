@extends('layouts.app')

@section('title', 'Warehouses')
@section('breadcrumb', 'Operations / Warehouses')
@section('page-title', 'Warehouses')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="mb-2 text-[10px] font-semibold uppercase tracking-[0.2em] text-lrx-cyan">Parcel locations</p>
            <h1 class="text-2xl font-semibold text-mocha-text">Warehouse A, B & C</h1>
            <p class="mt-1 text-xs text-mocha-muted">See active shipments currently assigned to each warehouse.</p>
        </div>
        <a href="{{ route('warehouses.create') }}" class="lrx-button-primary inline-flex h-10 items-center rounded-xl px-4 text-xs font-semibold">Add warehouse</a>
    </div>

    <div class="grid gap-5 xl:grid-cols-2">
        @forelse ($warehouses as $warehouse)
            <section class="overflow-hidden rounded-2xl border border-mocha-overlay bg-mocha-surface">
                <div class="flex flex-wrap items-start justify-between gap-3 border-b border-mocha-overlay p-5">
                    <div>
                        <a href="{{ route('warehouses.show', $warehouse) }}" class="text-base font-semibold text-mocha-text hover:text-lrx-cyan">{{ $warehouse->name }}</a>
                        <p class="mt-1 text-xs text-mocha-subtext">{{ $warehouse->code }} · {{ $warehouse->city }}</p>
                        <p class="mt-1 text-[10px] text-mocha-muted">{{ $warehouse->address }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="rounded-full border px-2.5 py-1 text-[10px] {{ $warehouse->is_active ? 'border-lrx-green/30 bg-lrx-green/10 text-lrx-green' : 'border-mocha-border text-mocha-muted' }}">{{ $warehouse->is_active ? 'Active' : 'Inactive' }}</span>
                        <span class="rounded-full border border-lrx-cyan/20 bg-lrx-cyan/5 px-2.5 py-1 text-[10px] text-lrx-cyan">{{ $warehouse->active_shipments_count }} active parcels</span>
                        <a href="{{ route('warehouses.edit', $warehouse) }}" class="rounded-lg border border-mocha-border px-3 py-1.5 text-[10px] text-mocha-subtext hover:text-lrx-cyan">Edit</a>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[640px] text-left">
                        <thead class="bg-mocha-mantle text-[10px] uppercase tracking-wider text-mocha-muted">
                            <tr><th class="px-5 py-3">Resi / package</th><th class="px-5 py-3">Recipient</th><th class="px-5 py-3">Courier</th><th class="px-5 py-3">Status</th></tr>
                        </thead>
                        <tbody class="divide-y divide-mocha-overlay">
                            @forelse ($warehouse->shipments as $shipment)
                                <tr class="align-top odd:bg-mocha-mantle/20">
                                    <td class="px-5 py-3">
                                        <a href="{{ route('shipments.show', $shipment) }}" class="text-xs font-semibold text-lrx-cyan">{{ $shipment->tracking_number }}</a>
                                        @foreach ($shipment->packages as $package)
                                            <p class="mt-1 text-[10px] text-mocha-subtext">{{ $package->name }} × {{ $package->quantity }}</p>
                                        @endforeach
                                        @foreach ($shipment->items as $item)
                                            <p class="mt-1 text-[10px] text-mocha-subtext">{{ $item->item?->name ?: 'Item' }} × {{ $item->quantity }}</p>
                                        @endforeach
                                        @if ($shipment->packages->isEmpty() && $shipment->items->isEmpty())
                                            <p class="mt-1 text-[10px] text-mocha-muted">{{ $shipment->cargo_type }} · {{ number_format((float) $shipment->total_weight, 2) }} kg</p>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-xs text-mocha-text">{{ $shipment->receiver_name ?: $shipment->receiver?->name ?: '—' }}</td>
                                    <td class="px-5 py-3 text-xs text-mocha-subtext">{{ $shipment->courier?->name ?: 'Unassigned' }}</td>
                                    <td class="px-5 py-3 text-xs text-lrx-yellow">{{ str($shipment->status)->replace('_', ' ')->title() }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-5 py-8 text-center text-xs text-mocha-muted">No active parcels assigned to this warehouse.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @empty
            <div class="rounded-2xl border border-mocha-overlay bg-mocha-surface p-8 text-center text-sm text-mocha-muted">No warehouses yet. Add Warehouse A, B, or C to assign parcels.</div>
        @endforelse
    </div>
    <div class="text-xs">{{ $warehouses->links() }}</div>
</div>
@endsection
