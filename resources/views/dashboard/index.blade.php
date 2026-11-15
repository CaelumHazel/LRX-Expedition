@extends('layouts.app')

@section('title', 'Dashboard')
@section('breadcrumb', 'Overview')
@section('page-title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <div>
        <p class="text-sm text-mocha-subtext">Welcome back, {{ auth()->user()->name ?? 'Administrator' }}.</p>
        <p class="mt-1 text-xs text-mocha-muted">A live view of shipment and logistics activity.</p>
    </div>

    <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
        @foreach ([
            ['Total shipments', $totalShipments, 'text-lrx-purple'],
            ['Pending', $pendingShipments, 'text-mocha-muted'],
            ['In transit', $inTransitShipments, 'text-lrx-yellow'],
            ['Delivered', $deliveredShipments, 'text-lrx-green'],
        ] as [$label, $value, $color])
            <article class="lrx-surface rounded-2xl border border-mocha-overlay p-5">
                <p class="text-xs text-mocha-subtext">{{ $label }}</p>
                <p class="mt-5 text-3xl font-semibold tracking-tight {{ $color }}">{{ number_format($value) }}</p>
            </article>
        @endforeach
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <section class="overflow-hidden rounded-2xl border border-mocha-overlay bg-mocha-surface xl:col-span-2">
            <div class="flex items-center justify-between border-b border-mocha-overlay px-5 py-4">
                <div>
                    <h2 class="text-sm font-semibold text-mocha-text">Latest shipments</h2>
                    <p class="mt-1 text-xs text-mocha-muted">Recently created tracking records</p>
                </div>
                <a href="{{ route('shipments.index') }}" class="text-xs text-lrx-cyan hover:text-lrx-purple">View all →</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[600px] text-left">
                    <thead class="bg-mocha-mantle/80 text-[10px] uppercase tracking-wider text-mocha-muted">
                        <tr><th class="px-5 py-3">Tracking number</th><th class="px-5 py-3">Receiver</th><th class="px-5 py-3">Destination</th><th class="px-5 py-3">Status</th></tr>
                    </thead>
                    <tbody class="divide-y divide-mocha-overlay">
                        @forelse ($recentShipments as $shipment)
                            <tr class="odd:bg-mocha-mantle/20 hover:bg-mocha-overlay/30">
                                <td class="px-5 py-3">
                                    <a href="{{ route('shipments.show', $shipment) }}" class="text-xs font-medium text-lrx-cyan hover:text-lrx-purple">{{ $shipment->tracking_number }}</a>
                                    <p class="mt-1 text-[10px] text-mocha-muted">{{ $shipment->created_at->format('d M Y H:i') }}</p>
                                </td>
                                <td class="px-5 py-3 text-xs text-mocha-text">{{ $shipment->receiver_name ?: $shipment->receiver?->name ?: '—' }}</td>
                                <td class="px-5 py-3 text-xs text-mocha-subtext">{{ $shipment->destination_city ?: '—' }}</td>
                                <td class="px-5 py-3 text-xs text-mocha-subtext">{{ str($shipment->status)->replace('_', ' ')->title() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-12 text-center text-xs text-mocha-muted">No shipments yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-2xl border border-mocha-overlay bg-mocha-surface p-5">
            <h2 class="text-sm font-semibold text-mocha-text">Operations</h2>
            <p class="mt-1 text-xs text-mocha-muted">Quick access to shipment workflows</p>
            <div class="mt-5 space-y-3">
                <a href="{{ route('shipments.create') }}" class="block rounded-xl border border-mocha-border bg-mocha-mantle p-4 transition hover:border-lrx-cyan/50">
                    <p class="text-xs font-medium text-mocha-text">Create shipment</p>
                    <p class="mt-1 text-[10px] text-mocha-muted">Register a parcel and issue a tracking number</p>
                </a>
                <a href="{{ route('shipments.index') }}" class="block rounded-xl border border-mocha-border bg-mocha-mantle p-4 transition hover:border-lrx-cyan/50">
                    <p class="text-xs font-medium text-mocha-text">Track shipments</p>
                    <p class="mt-1 text-[10px] text-mocha-muted">Search by tracking number, people, or route</p>
                </a>
                <a href="{{ route('customers.index') }}" class="block rounded-xl border border-mocha-border bg-mocha-mantle p-4 transition hover:border-lrx-purple/50">
                    <p class="text-xs font-medium text-mocha-text">Customers</p>
                    <p class="mt-1 text-[10px] text-mocha-muted">{{ number_format($totalCustomers) }} registered customers</p>
                </a>
            </div>
            <dl class="mt-5 grid grid-cols-3 gap-2 border-t border-mocha-overlay pt-4 text-center">
                <div><dd class="text-base font-semibold text-mocha-text">{{ $totalItems }}</dd><dt class="mt-1 text-[10px] text-mocha-muted">Items</dt></div>
                <div><dd class="text-base font-semibold text-mocha-text">{{ $totalWarehouses }}</dd><dt class="mt-1 text-[10px] text-mocha-muted">Warehouses</dt></div>
                <div><dd class="text-base font-semibold text-mocha-text">{{ $totalCouriers }}</dd><dt class="mt-1 text-[10px] text-mocha-muted">Couriers</dt></div>
            </dl>
        </section>
    </div>
</div>
@endsection
