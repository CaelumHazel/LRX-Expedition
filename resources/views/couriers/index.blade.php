@extends('layouts.app')

@section('title', 'Couriers')
@section('breadcrumb', 'Management / Couriers')
@section('page-title', 'Couriers')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-2 text-[10px] font-semibold uppercase tracking-[0.2em] text-lrx-cyan">Delivery assignments</p>
            <h1 class="text-2xl font-semibold text-mocha-text">Courier parcels</h1>
            <p class="mt-1 text-xs text-mocha-muted">See who is carrying each package and its shipment details.</p>
        </div>
        <a href="{{ route('couriers.create') }}" class="lrx-button-primary inline-flex h-10 items-center justify-center gap-2 rounded-xl px-4 text-xs font-semibold transition">
            <span aria-hidden="true">＋</span>
            Add Courier
        </a>
    </div>
    <section class="overflow-x-auto rounded-2xl border border-mocha-overlay bg-mocha-surface">
        <table class="w-full min-w-[900px] text-left">
            <thead class="sticky top-0 bg-mocha-mantle text-[10px] uppercase tracking-wider text-mocha-muted">
                <tr><th class="px-5 py-4">Courier</th><th class="px-5 py-4">Vehicle</th><th class="px-5 py-4">Assigned parcels</th><th class="px-5 py-4">Package details</th><th class="px-5 py-4">Warehouse</th></tr>
            </thead>
            <tbody class="divide-y divide-mocha-overlay">
                @forelse ($couriers as $courier)
                    <tr class="align-top odd:bg-mocha-mantle/20">
                        <td class="px-5 py-4">
                            <a href="{{ route('couriers.show', $courier) }}" class="text-sm font-semibold text-lrx-cyan">{{ $courier->name }}</a>
                            <p class="mt-1 text-[10px] text-mocha-muted">{{ $courier->phone }}</p>
                        </td>
                        <td class="px-5 py-4 text-xs text-mocha-subtext">{{ $courier->vehicle_type ?: '—' }} · {{ $courier->vehicle_number ?: '—' }}</td>
                        <td class="px-5 py-4">
                            <span class="rounded-full border border-lrx-yellow/30 bg-lrx-yellow/10 px-2.5 py-1 text-[10px] text-lrx-yellow">{{ $courier->active_shipments_count }} active</span>
                            @foreach ($courier->shipments as $shipment)
                                <p class="mt-2"><a href="{{ route('shipments.show', $shipment) }}" class="text-[10px] text-lrx-cyan">{{ $shipment->tracking_number }}</a></p>
                            @endforeach
                        </td>
                        <td class="px-5 py-4 text-xs text-mocha-subtext">
                            @foreach ($courier->shipments as $shipment)
                                @forelse ($shipment->packages as $package)
                                    <p class="mb-1">{{ $package->name }} × {{ $package->quantity }}</p>
                                @empty
                                    @foreach ($shipment->items as $item)<p class="mb-1">{{ $item->item?->name ?: 'Item' }} × {{ $item->quantity }}</p>@endforeach
                                @endforelse
                            @endforeach
                        </td>
                        <td class="px-5 py-4 text-xs text-mocha-subtext">
                            @forelse ($courier->shipments as $shipment)
                                <p class="mb-2">{{ $shipment->warehouse?->name ?: 'No warehouse' }}</p>
                            @empty
                                —
                            @endforelse
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-12 text-center text-xs text-mocha-muted">No couriers found.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t border-mocha-overlay px-5 py-4 text-xs">{{ $couriers->links() }}</div>
    </section>
</div>
@endsection
