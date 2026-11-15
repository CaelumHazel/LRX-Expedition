@extends('layouts.app')

@section('title', 'Shipment Stock')
@section('breadcrumb', 'Shipment / Stock')
@section('page-title', 'Shipment Stock')

@section('content')
<div class="space-y-6">
    <div>
        <p class="mb-2 text-[10px] font-semibold uppercase tracking-[0.2em] text-lrx-cyan">Inventory overview</p>
        <h1 class="text-2xl font-semibold text-mocha-text">Shipment Stock</h1>
        <p class="mt-1 text-xs text-mocha-muted">Scan parcels still moving and those already delivered.</p>
    </div>

    <div class="grid gap-3 sm:grid-cols-2">
        <article class="rounded-xl border border-lrx-yellow/20 bg-lrx-yellow/5 p-4">
            <p class="text-xs text-mocha-subtext">Currently shipping</p>
            <p class="mt-2 text-2xl font-semibold text-lrx-yellow">{{ number_format($activeCount) }}</p>
        </article>
        <article class="rounded-xl border border-lrx-green/20 bg-lrx-green/5 p-4">
            <p class="text-xs text-mocha-subtext">Delivered</p>
            <p class="mt-2 text-2xl font-semibold text-lrx-green">{{ number_format($completedCount) }}</p>
        </article>
    </div>

    <section class="overflow-hidden rounded-2xl border border-mocha-overlay bg-mocha-surface">
        <form method="GET" class="grid gap-3 border-b border-mocha-overlay p-4 sm:grid-cols-[1fr_220px_auto]">
            <input name="search" value="{{ request('search') }}" type="search" placeholder="Search tracking number or item" class="h-10 rounded-xl border border-mocha-border bg-mocha-mantle px-3 text-xs text-mocha-text placeholder:text-mocha-muted">
            <select name="status" class="h-10 rounded-xl border border-mocha-border bg-mocha-mantle px-3 text-xs text-mocha-text">
                <option value="">All parcels</option>
                <option value="active" @selected(request('status') === 'active')>In shipment</option>
                <option value="completed" @selected(request('status') === 'completed')>Delivered</option>
            </select>
            <button class="h-10 rounded-xl border border-lrx-cyan/30 bg-lrx-cyan/10 px-4 text-xs text-lrx-cyan">Filter</button>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[800px] text-left">
                <thead class="sticky top-0 bg-mocha-mantle text-[10px] uppercase tracking-wider text-mocha-muted">
                    <tr><th class="px-5 py-4">Resi / Item</th><th class="px-5 py-4">Recipient</th><th class="px-5 py-4">Route</th><th class="px-5 py-4">Weight</th><th class="px-5 py-4">Current state</th></tr>
                </thead>
                <tbody class="divide-y divide-mocha-overlay">
                    @forelse ($shipments as $shipment)
                        <tr class="odd:bg-mocha-mantle/20 hover:bg-mocha-overlay/30">
                            <td class="px-5 py-4">
                                <a href="{{ route('shipments.show', $shipment) }}" class="text-xs font-semibold text-lrx-cyan">{{ $shipment->tracking_number }}</a>
                                @forelse ($shipment->items as $shipmentItem)
                                    <p class="mt-1 text-[10px] text-mocha-subtext">{{ $shipmentItem->item?->name ?: 'Item' }} × {{ $shipmentItem->quantity }}</p>
                                @empty
                                    @forelse ($shipment->packages as $package)
                                        <p class="mt-1 text-[10px] text-mocha-subtext">{{ $package->name }} × {{ $package->quantity }}</p>
                                    @empty
                                    <p class="mt-1 text-[10px] text-mocha-muted">{{ $shipment->cargo_type }}</p>
                                    @endforelse
                                @endforelse
                            </td>
                            <td class="px-5 py-4 text-xs text-mocha-text">{{ $shipment->receiver_name ?: $shipment->receiver?->name ?: '—' }}</td>
                            <td class="px-5 py-4 text-xs text-mocha-subtext">{{ $shipment->origin_city ?: '—' }} → {{ $shipment->destination_city ?: '—' }}</td>
                            <td class="px-5 py-4 text-xs text-mocha-subtext">{{ number_format((float) $shipment->total_weight, 2) }} kg</td>
                            <td class="px-5 py-4">
                                <span class="rounded-full border px-2.5 py-1 text-[10px] {{ $shipment->status === 'delivered' ? 'border-lrx-green/30 bg-lrx-green/10 text-lrx-green' : 'border-lrx-yellow/30 bg-lrx-yellow/10 text-lrx-yellow' }}">
                                    {{ str($shipment->status)->replace('_', ' ')->title() }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-12 text-center text-xs text-mocha-muted">No shipment stock matches these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-mocha-overlay px-5 py-4 text-xs">{{ $shipments->links() }}</div>
    </section>
</div>
@endsection
