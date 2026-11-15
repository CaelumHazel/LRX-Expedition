@extends('layouts.app')

@section('title', $courier->name)
@section('breadcrumb', 'Management / Couriers')
@section('page-title', $courier->name)

@section('content')
<div class="space-y-6">
    <div>
        <a href="{{ route('couriers.index') }}" class="text-xs text-lrx-cyan">← Couriers</a>
        <h1 class="mt-2 text-2xl font-semibold text-mocha-text">{{ $courier->name }}</h1>
        <p class="mt-1 text-xs text-mocha-subtext">{{ $courier->phone }} · {{ $courier->vehicle_type ?: 'Vehicle not set' }} · {{ $courier->vehicle_number ?: 'No plate number' }}</p>
    </div>
    <section class="overflow-x-auto rounded-2xl border border-mocha-overlay bg-mocha-surface">
        <table class="w-full min-w-[850px] text-left">
            <thead class="bg-mocha-mantle text-[10px] uppercase tracking-wider text-mocha-muted">
                <tr><th class="px-5 py-4">Shipment</th><th class="px-5 py-4">Packages</th><th class="px-5 py-4">Route / warehouse</th><th class="px-5 py-4">Recipient</th><th class="px-5 py-4">Status</th></tr>
            </thead>
            <tbody class="divide-y divide-mocha-overlay">
                @forelse ($courier->shipments as $shipment)
                    <tr class="align-top odd:bg-mocha-mantle/20">
                        <td class="px-5 py-4"><a href="{{ route('shipments.show', $shipment) }}" class="text-xs font-semibold text-lrx-cyan">{{ $shipment->tracking_number }}</a><p class="mt-1 text-[10px] text-mocha-muted">{{ number_format((float) $shipment->total_weight, 2) }} kg</p></td>
                        <td class="px-5 py-4 text-xs text-mocha-subtext">
                            @foreach ($shipment->packages as $package)<p class="mb-1">{{ $package->name }} × {{ $package->quantity }}{{ $package->description ? ' · '.$package->description : '' }}</p>@endforeach
                            @foreach ($shipment->items as $item)<p class="mb-1">{{ $item->item?->name ?: 'Item' }} × {{ $item->quantity }}</p>@endforeach
                            @if ($shipment->packages->isEmpty() && $shipment->items->isEmpty()){{ $shipment->cargo_type }}@endif
                        </td>
                        <td class="px-5 py-4 text-xs text-mocha-subtext">{{ $shipment->origin_city }} → {{ $shipment->destination_city }}<p class="mt-1 text-[10px] text-mocha-muted">{{ $shipment->warehouse?->name ?: 'No warehouse' }}</p></td>
                        <td class="px-5 py-4 text-xs text-mocha-text">{{ $shipment->receiver_name ?: $shipment->receiver?->name ?: '—' }}</td>
                        <td class="px-5 py-4 text-xs text-lrx-yellow">{{ str($shipment->status)->replace('_', ' ')->title() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-12 text-center text-xs text-mocha-muted">No shipments assigned to this courier.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>
@endsection
