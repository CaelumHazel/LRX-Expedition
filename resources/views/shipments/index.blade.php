@extends('layouts.app')

@section('title', 'Shipments')
@section('breadcrumb', 'Shipment / Management')
@section('page-title', 'Shipment Management')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="mb-2 text-[10px] font-semibold uppercase tracking-[0.2em] text-lrx-cyan">Logistics operations</p>
            <h1 class="text-2xl font-semibold tracking-tight text-mocha-text">Shipment Management</h1>
            <p class="mt-1.5 text-xs text-mocha-muted">Search and filter live shipment records.</p>
        </div>
        <a href="{{ route('shipments.create') }}" class="lrx-button-primary inline-flex h-10 items-center justify-center gap-2 rounded-xl px-4 text-xs font-semibold">
            <span class="text-lg leading-none">+</span> Create Shipment
        </a>
    </div>

    <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
        @foreach ([['Total', 'total', 'text-lrx-cyan'], ['Pending', 'pending', 'text-mocha-muted'], ['In Transit', 'in_transit', 'text-lrx-yellow'], ['Delivered', 'delivered', 'text-lrx-green']] as [$label, $key, $color])
            <article class="lrx-surface rounded-xl border border-mocha-overlay p-4">
                <p class="text-[11px] text-mocha-subtext">{{ $label }} shipments</p>
                <p class="mt-3 text-2xl font-semibold tracking-tight {{ $color }}">{{ number_format($summary[$key]) }}</p>
            </article>
        @endforeach
    </div>

    <section class="overflow-hidden rounded-2xl border border-mocha-overlay bg-mocha-surface/90 shadow-xl shadow-black/10">
        <div class="flex flex-col justify-between gap-3 border-b border-mocha-overlay px-5 py-5 sm:flex-row sm:items-center">
            <div>
                <h2 class="text-sm font-semibold text-mocha-text">All Shipments</h2>
                <p class="mt-1 text-[11px] text-mocha-muted">Tracking, delivery route, and status</p>
            </div>
            <span class="w-fit rounded-lg border border-mocha-border bg-mocha-mantle px-3 py-1.5 text-[10px] text-mocha-subtext">{{ $shipments->total() }} records</span>
        </div>

        <form method="GET" action="{{ route('shipments.index') }}" class="grid grid-cols-1 gap-3 border-b border-mocha-overlay p-4 sm:grid-cols-2 xl:grid-cols-4">
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Resi, sender, or receiver" class="h-10 rounded-xl border border-mocha-border bg-mocha-mantle px-3 text-xs text-mocha-text outline-none placeholder:text-mocha-muted focus:border-lrx-cyan/60">
            <select name="status" class="h-10 rounded-xl border border-mocha-border bg-mocha-mantle px-3 text-xs text-mocha-text">
                <option value="">All statuses</option>
                @foreach (['pending', 'picked_up', 'in_transit', 'at_warehouse', 'out_for_delivery', 'delivered', 'cancelled'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ str($status)->replace('_', ' ')->title() }}</option>
                @endforeach
            </select>
            <select name="cargo_type" class="h-10 rounded-xl border border-mocha-border bg-mocha-mantle px-3 text-xs text-mocha-text">
                <option value="">All cargo types</option>
                @foreach ($cargoTypes as $cargoType)
                    <option value="{{ $cargoType }}" @selected(request('cargo_type') === $cargoType)>{{ $cargoType }}</option>
                @endforeach
            </select>
            <select name="courier_id" class="h-10 rounded-xl border border-mocha-border bg-mocha-mantle px-3 text-xs text-mocha-text">
                <option value="">All couriers</option>
                @foreach ($couriers as $courier)
                    <option value="{{ $courier->id }}" @selected((string) request('courier_id') === (string) $courier->id)>{{ $courier->name }}</option>
                @endforeach
            </select>
            <select name="origin_city" class="h-10 rounded-xl border border-mocha-border bg-mocha-mantle px-3 text-xs text-mocha-text">
                <option value="">All origin cities</option>
                @foreach ($originCities as $city)
                    <option value="{{ $city }}" @selected(request('origin_city') === $city)>{{ $city }}</option>
                @endforeach
            </select>
            <select name="destination_city" class="h-10 rounded-xl border border-mocha-border bg-mocha-mantle px-3 text-xs text-mocha-text">
                <option value="">All destinations</option>
                @foreach ($destinationCities as $city)
                    <option value="{{ $city }}" @selected(request('destination_city') === $city)>{{ $city }}</option>
                @endforeach
            </select>
            <label class="text-[10px] text-mocha-muted">From
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="mt-1 h-9 w-full rounded-lg border border-mocha-border bg-mocha-mantle px-2 text-xs text-mocha-text">
            </label>
            <label class="text-[10px] text-mocha-muted">To
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="mt-1 h-9 w-full rounded-lg border border-mocha-border bg-mocha-mantle px-2 text-xs text-mocha-text">
            </label>
            <div class="flex items-end gap-2 xl:col-span-4">
                <button type="submit" class="h-10 rounded-xl border border-lrx-cyan/30 bg-lrx-cyan/10 px-5 text-xs font-medium text-lrx-cyan transition hover:bg-lrx-cyan/20">Apply filters</button>
                @if (request()->query())
                    <a href="{{ route('shipments.index') }}" class="inline-flex h-10 items-center px-3 text-xs text-mocha-subtext hover:text-mocha-text">Reset</a>
                @endif
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[850px] text-left">
                <thead class="sticky top-0 bg-mocha-mantle/95">
                    <tr class="text-[10px] font-semibold uppercase tracking-wider text-mocha-muted">
                        <th class="px-5 py-4">Tracking number</th>
                        <th class="px-5 py-4">Sender / receiver</th>
                        <th class="px-5 py-4">Route / cargo</th>
                        <th class="px-5 py-4">Weight</th>
                        <th class="px-5 py-4">Shipping fee</th>
                        <th class="px-5 py-4">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-mocha-overlay">
                    @forelse ($shipments as $shipment)
                        @php
                            $statusStyles = match ($shipment->status) {
                                'delivered' => 'bg-lrx-green/10 text-lrx-green border-lrx-green/20',
                                'cancelled' => 'bg-lrx-red/10 text-lrx-red border-lrx-red/20',
                                'pending' => 'bg-mocha-overlay/30 text-mocha-muted border-mocha-border',
                                default => 'bg-lrx-yellow/10 text-lrx-yellow border-lrx-yellow/20',
                            };
                        @endphp
                        <tr class="group odd:bg-mocha-mantle/20 transition hover:bg-mocha-overlay/30">
                            <td class="px-5 py-4">
                                <a href="{{ route('shipments.show', $shipment) }}" class="text-xs font-semibold tracking-wide text-lrx-cyan hover:text-lrx-purple">{{ $shipment->tracking_number }}</a>
                                <p class="mt-1 text-[10px] text-mocha-muted">{{ $shipment->created_at->format('d M Y, H:i') }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <p class="text-xs text-mocha-text">{{ $shipment->sender_name ?: $shipment->sender?->name ?: 'Unknown' }}</p>
                                <p class="mt-1 text-[10px] text-mocha-muted">To: {{ $shipment->receiver_name ?: $shipment->receiver?->name ?: 'Unknown' }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <p class="text-xs text-mocha-text">{{ $shipment->origin_city ?: '—' }} → {{ $shipment->destination_city ?: $shipment->warehouse?->city ?: '—' }}</p>
                                <p class="mt-1 text-[10px] text-mocha-muted">{{ $shipment->cargo_type }} · {{ $shipment->courier?->name ?: 'No courier' }}</p>
                            </td>
                            <td class="px-5 py-4 text-xs text-mocha-subtext">{{ number_format((float) $shipment->total_weight, 2) }} kg</td>
                            <td class="px-5 py-4 text-xs text-mocha-text">Rp {{ number_format((float) $shipment->shipping_cost, 0, ',', '.') }}</td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[10px] font-medium {{ $statusStyles }}">
                                    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>{{ str($shipment->status)->replace('_', ' ')->title() }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-16 text-center text-sm text-mocha-muted">No shipments found. Try changing the filters or create a shipment.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="flex flex-col justify-between gap-3 border-t border-mocha-overlay px-5 py-4 sm:flex-row sm:items-center">
            <p class="text-[11px] text-mocha-muted">Showing {{ $shipments->firstItem() ?? 0 }}–{{ $shipments->lastItem() ?? 0 }} of {{ $shipments->total() }} shipments</p>
            <div class="text-xs">{{ $shipments->links() }}</div>
        </div>
    </section>
</div>
@endsection
