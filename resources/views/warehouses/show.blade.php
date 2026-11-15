@extends('layouts.app')

@section('title', $warehouse->name)
@section('breadcrumb', 'Operations / Warehouses')
@section('page-title', $warehouse->name)

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <a href="{{ route('warehouses.index') }}" class="text-xs text-lrx-cyan">← Warehouses</a>
            <h1 class="mt-2 text-2xl font-semibold text-mocha-text">{{ $warehouse->name }} · {{ $warehouse->code }}</h1>
            <p class="mt-1 text-xs text-mocha-subtext">{{ $warehouse->address }} · {{ $warehouse->city }}</p>
        </div>
        <a href="{{ route('warehouses.edit', $warehouse) }}" class="rounded-xl border border-mocha-border px-4 py-2 text-xs text-mocha-subtext">Edit warehouse</a>
    </div>

    <section class="overflow-x-auto rounded-2xl border border-mocha-overlay bg-mocha-surface">
        <table class="w-full min-w-[760px] text-left">
            <thead class="bg-mocha-mantle text-[10px] uppercase tracking-wider text-mocha-muted">
                <tr><th class="px-5 py-4">Shipment / items</th><th class="px-5 py-4">Recipient / destination</th><th class="px-5 py-4">Courier</th><th class="px-5 py-4">Status</th><th class="px-5 py-4">Weight</th></tr>
            </thead>
            <tbody class="divide-y divide-mocha-overlay">
                @forelse ($warehouse->shipments as $shipment)
                    <tr class="align-top odd:bg-mocha-mantle/20">
                        <td class="px-5 py-4">
                            <a href="{{ route('shipments.show', $shipment) }}" class="text-xs font-semibold text-lrx-cyan">{{ $shipment->tracking_number }}</a>
                            @foreach ($shipment->packages as $package)<p class="mt-1 text-[10px] text-mocha-subtext">{{ $package->name }} × {{ $package->quantity }}{{ $package->description ? ' · '.$package->description : '' }}</p>@endforeach
                            @foreach ($shipment->items as $item)<p class="mt-1 text-[10px] text-mocha-subtext">{{ $item->item?->name ?: 'Item' }} × {{ $item->quantity }}</p>@endforeach
                        </td>
                        <td class="px-5 py-4 text-xs text-mocha-text">{{ $shipment->receiver_name ?: $shipment->receiver?->name }}<p class="mt-1 text-[10px] text-mocha-muted">{{ $shipment->destination_city }}</p></td>
                        <td class="px-5 py-4 text-xs text-mocha-subtext">{{ $shipment->courier?->name ?: 'Unassigned' }}</td>
                        <td class="px-5 py-4 text-xs text-lrx-yellow">{{ str($shipment->status)->replace('_', ' ')->title() }}</td>
                        <td class="px-5 py-4 text-xs text-mocha-subtext">{{ number_format((float) $shipment->total_weight, 2) }} kg</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-12 text-center text-xs text-mocha-muted">No shipments assigned to this warehouse.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>
@endsection
