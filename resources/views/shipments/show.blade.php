@extends('layouts.app')

@section('title', $shipment->tracking_number)
@section('breadcrumb', 'Shipment / Tracking')
@section('page-title', 'Shipment Details')

@section('content')
@php
    $statusLabel = str($shipment->status)->replace('_', ' ')->title();
    $statusColor = match ($shipment->status) {
        'delivered' => 'text-lrx-green border-lrx-green/30 bg-lrx-green/10',
        'in_transit', 'picked_up', 'at_warehouse', 'out_for_delivery' => 'text-lrx-yellow border-lrx-yellow/30 bg-lrx-yellow/10',
        'cancelled' => 'text-lrx-red border-lrx-red/30 bg-lrx-red/10',
        default => 'text-mocha-muted border-mocha-border bg-mocha-overlay/30',
    };
@endphp

<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <a href="{{ route('shipments.index') }}" class="text-xs text-lrx-cyan hover:text-lrx-purple">← Shipments</a>
            <h1 class="mt-2 text-2xl font-semibold text-mocha-text">{{ $shipment->tracking_number }}</h1>
            <p class="mt-1 text-xs text-mocha-subtext">Created {{ $shipment->created_at->format('d M Y, H:i') }}</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('shipments.edit', $shipment) }}" class="rounded-xl border border-mocha-border px-3 py-2 text-xs text-mocha-subtext transition hover:border-lrx-cyan/40 hover:text-lrx-cyan">Edit</a>
            <span class="inline-flex w-fit items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-medium {{ $statusColor }}">
                <span class="h-2 w-2 rounded-full bg-current"></span>{{ $statusLabel }}
            </span>
        </div>
    </div>

    <section class="lrx-surface grid gap-6 rounded-2xl border border-mocha-overlay p-5 sm:grid-cols-2 lg:grid-cols-5">
        <div>
            <p class="text-[11px] uppercase tracking-wider text-mocha-muted">Current position</p>
            <p class="mt-2 text-sm font-medium text-mocha-text">{{ $shipment->current_position ?: 'Not updated' }}</p>
        </div>
        <div>
            <p class="text-[11px] uppercase tracking-wider text-mocha-muted">Route</p>
            <p class="mt-2 text-sm text-mocha-text">{{ $shipment->origin_city ?: '—' }} → {{ $shipment->destination_city ?: '—' }}</p>
        </div>
        <div>
            <p class="text-[11px] uppercase tracking-wider text-mocha-muted">Courier</p>
            <p class="mt-2 text-sm text-mocha-text">{{ $shipment->courier?->name ?: 'Unassigned' }}</p>
        </div>
        <div>
            <p class="text-[11px] uppercase tracking-wider text-mocha-muted">Warehouse</p>
            <p class="mt-2 text-sm text-mocha-text">{{ $shipment->warehouse?->name ?: 'Unassigned' }}</p>
        </div>
        <div>
            <p class="text-[11px] uppercase tracking-wider text-mocha-muted">Estimated delivery</p>
            <p class="mt-2 text-sm text-mocha-text">{{ $shipment->estimated_delivery_date?->format('d M Y') ?: 'Not available' }}</p>
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-3">
        <section class="space-y-6 xl:col-span-2">
            <div class="grid gap-4 sm:grid-cols-2">
                <article class="rounded-2xl border border-mocha-overlay bg-mocha-surface p-5">
                    <h2 class="text-sm font-semibold text-mocha-text">Sender</h2>
                    <p class="mt-4 text-sm text-mocha-text">{{ $shipment->sender_name ?: $shipment->sender?->name ?: '—' }}</p>
                    <p class="mt-1 text-xs text-mocha-subtext">{{ $shipment->sender_phone ?: $shipment->sender?->phone ?: '—' }}</p>
                    <p class="mt-3 text-xs leading-5 text-mocha-subtext">{{ $shipment->sender?->address ?: '—' }}</p>
                </article>
                <article class="rounded-2xl border border-mocha-overlay bg-mocha-surface p-5">
                    <h2 class="text-sm font-semibold text-mocha-text">Receiver</h2>
                    <p class="mt-4 text-sm text-mocha-text">{{ $shipment->receiver_name ?: $shipment->receiver?->name ?: '—' }}</p>
                    <p class="mt-1 text-xs text-mocha-subtext">{{ $shipment->receiver_phone ?: $shipment->receiver?->phone ?: '—' }}</p>
                    <p class="mt-3 whitespace-pre-line text-xs leading-5 text-mocha-subtext">{{ $shipment->receiver_address ?: $shipment->receiver?->address ?: '—' }}</p>
                </article>
            </div>

            <article class="rounded-2xl border border-mocha-overlay bg-mocha-surface p-5">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-sm font-semibold text-mocha-text">Tracking history</h2>
                    <span class="text-[11px] text-mocha-muted">{{ $shipment->trackingLogs->count() }} updates</span>
                </div>
                <ol class="mt-5 space-y-5">
                    @forelse ($shipment->trackingLogs as $log)
                        <li class="relative flex gap-3">
                            <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full {{ $loop->first ? 'bg-lrx-cyan' : 'bg-mocha-muted' }}"></span>
                            @if (! $loop->last)
                                <span class="absolute left-[4px] top-5 h-[calc(100%+0.5rem)] w-px bg-mocha-border"></span>
                            @endif
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1">
                                    <p class="text-sm font-medium text-mocha-text">{{ str($log->status)->replace('_', ' ')->title() }}</p>
                                    <time class="text-[11px] text-mocha-muted">{{ $log->tracked_at->format('d M Y, H:i') }}</time>
                                </div>
                                <p class="mt-1 text-xs text-lrx-cyan">{{ $log->location }}</p>
                                @if ($log->description)
                                    <p class="mt-1 text-xs leading-5 text-mocha-subtext">{{ $log->description }}</p>
                                @endif
                                <p class="mt-1 text-[10px] text-mocha-muted">
                                    {{ $log->source === 'system' ? 'System' : ($log->user?->name ?: 'Admin') }}
                                </p>
                            </div>
                        </li>
                    @empty
                        <li class="text-xs text-mocha-muted">No tracking updates recorded.</li>
                    @endforelse
                </ol>
            </article>

            @if (auth()->user()?->role === 'admin')
                <form method="POST" action="{{ route('tracking.store', $shipment) }}" class="rounded-2xl border border-mocha-overlay bg-mocha-surface p-5">
                    @csrf
                    <h2 class="text-sm font-semibold text-mocha-text">Update tracking</h2>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <label class="text-xs text-mocha-subtext">
                            Status
                            <select name="status" required class="mt-2 w-full rounded-xl border border-mocha-border bg-mocha-mantle px-3 py-2.5 text-mocha-text">
                                @foreach (['pending', 'picked_up', 'in_transit', 'at_warehouse', 'out_for_delivery', 'delivered', 'cancelled'] as $status)
                                    <option value="{{ $status }}" @selected(old('status', $shipment->status) === $status)>{{ str($status)->replace('_', ' ')->title() }}</option>
                                @endforeach
                            </select>
                            @error('status')<span class="mt-1 block text-lrx-red">{{ $message }}</span>@enderror
                        </label>
                        <label class="text-xs text-mocha-subtext">
                            Current position
                            <input name="location" value="{{ old('location', $shipment->current_position) }}" required maxlength="255" class="mt-2 w-full rounded-xl border border-mocha-border bg-mocha-mantle px-3 py-2.5 text-mocha-text">
                            @error('location')<span class="mt-1 block text-lrx-red">{{ $message }}</span>@enderror
                        </label>
                        <label class="text-xs text-mocha-subtext sm:col-span-2">
                            Update note
                            <textarea name="description" rows="2" maxlength="2000" class="mt-2 w-full rounded-xl border border-mocha-border bg-mocha-mantle px-3 py-2.5 text-mocha-text">{{ old('description') }}</textarea>
                            @error('description')<span class="mt-1 block text-lrx-red">{{ $message }}</span>@enderror
                        </label>
                    </div>
                    <button class="lrx-button-primary mt-4 rounded-xl px-4 py-2.5 text-xs font-semibold">Save update</button>
                </form>

                <form method="POST" action="{{ route('tracking.schedule', $shipment) }}" class="mt-4 rounded-2xl border border-mocha-overlay bg-mocha-surface p-5">
                    @csrf
                    <h2 class="text-sm font-semibold text-mocha-text">Schedule tracking update</h2>
                    <p class="mt-1 text-[11px] text-mocha-muted">A newer manual update automatically supersedes this schedule.</p>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <label class="text-xs text-mocha-subtext">
                            Status
                            <select name="status" required class="mt-2 w-full rounded-xl border border-mocha-border bg-mocha-mantle px-3 py-2.5 text-mocha-text">
                                @foreach (['pending', 'picked_up', 'in_transit', 'at_warehouse', 'out_for_delivery', 'delivered', 'cancelled'] as $status)
                                    <option value="{{ $status }}">{{ str($status)->replace('_', ' ')->title() }}</option>
                                @endforeach
                            </select>
                            @error('status')<span class="mt-1 block text-lrx-red">{{ $message }}</span>@enderror
                        </label>
                        <label class="text-xs text-mocha-subtext">
                            Scheduled time
                            <input type="datetime-local" name="scheduled_at" min="{{ now()->addMinute()->format('Y-m-d\TH:i') }}" value="{{ old('scheduled_at') }}" required class="mt-2 w-full rounded-xl border border-mocha-border bg-mocha-mantle px-3 py-2.5 text-mocha-text">
                            @error('scheduled_at')<span class="mt-1 block text-lrx-red">{{ $message }}</span>@enderror
                        </label>
                        <label class="text-xs text-mocha-subtext sm:col-span-2">
                            Position
                            <input name="location" value="{{ old('location') }}" required maxlength="255" class="mt-2 w-full rounded-xl border border-mocha-border bg-mocha-mantle px-3 py-2.5 text-mocha-text">
                            @error('location')<span class="mt-1 block text-lrx-red">{{ $message }}</span>@enderror
                        </label>
                        <label class="text-xs text-mocha-subtext sm:col-span-2">
                            Note
                            <textarea name="description" rows="2" maxlength="2000" class="mt-2 w-full rounded-xl border border-mocha-border bg-mocha-mantle px-3 py-2.5 text-mocha-text">{{ old('description') }}</textarea>
                            @error('description')<span class="mt-1 block text-lrx-red">{{ $message }}</span>@enderror
                        </label>
                    </div>
                    <button class="mt-4 rounded-xl border border-lrx-purple/30 bg-lrx-purple/10 px-4 py-2.5 text-xs font-semibold text-lrx-purple">Schedule update</button>
                    @if ($shipment->scheduledTrackingUpdates->isNotEmpty())
                        <ul class="mt-4 space-y-2 border-t border-mocha-overlay pt-3">
                            @foreach ($shipment->scheduledTrackingUpdates as $scheduled)
                                <li class="flex flex-wrap justify-between gap-2 text-[10px] text-mocha-subtext">
                                    <span>{{ $scheduled->scheduled_at->format('d M Y, H:i') }} · {{ str($scheduled->status)->replace('_', ' ')->title() }} · {{ $scheduled->location }}</span>
                                    <span class="{{ $scheduled->result === 'applied' ? 'text-lrx-green' : ($scheduled->result === 'skipped' ? 'text-lrx-red' : 'text-lrx-yellow') }}">{{ $scheduled->result }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </form>
            @endif
        </section>

        <aside class="space-y-6">
            <section id="shipment-barcode" class="rounded-2xl border border-mocha-overlay bg-mocha-surface p-5">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-sm font-semibold text-mocha-text">Shipment barcode</h2>
                    <button type="button" onclick="window.print()" class="text-xs text-lrx-cyan hover:text-lrx-purple print:hidden">Print</button>
                </div>
                <div class="mt-5 overflow-x-auto rounded-lg bg-white p-3 text-black">
                    {!! $barcode !!}
                </div>
                <p class="mt-3 text-center font-mono text-xs tracking-widest text-mocha-subtext">{{ $shipment->tracking_number }}</p>
            </section>

            <section class="rounded-2xl border border-mocha-overlay bg-mocha-surface p-5">
                <h2 class="text-sm font-semibold text-mocha-text">Package details</h2>
                <dl class="mt-4 space-y-3 text-xs">
                    <div class="flex justify-between gap-3"><dt class="text-mocha-muted">Cargo</dt><dd class="text-right text-mocha-text">{{ $shipment->cargo_type }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-mocha-muted">Weight</dt><dd class="text-right text-mocha-text">{{ number_format((float) $shipment->total_weight, 2) }} kg</dd></div>
                    @if ($shipment->estimated_distance_km)
                        <div class="flex justify-between gap-3"><dt class="text-mocha-muted">Estimated distance</dt><dd class="text-right text-mocha-text">{{ number_format($shipment->estimated_distance_km) }} km</dd></div>
                    @endif
                    <div class="flex justify-between gap-3"><dt class="text-mocha-muted">Shipping fee</dt><dd class="text-right text-mocha-text">Rp {{ number_format((float) $shipment->shipping_cost, 0, ',', '.') }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-mocha-muted">Declared value</dt><dd class="text-right text-mocha-text">Rp {{ number_format((float) $shipment->declared_value, 0, ',', '.') }}</dd></div>
                    @if ($shipment->notes)
                        <div class="border-t border-mocha-overlay pt-3"><dt class="text-mocha-muted">Notes</dt><dd class="mt-1 whitespace-pre-line text-mocha-text">{{ $shipment->notes }}</dd></div>
                    @endif
                </dl>
                @if ($shipment->items->isNotEmpty())
                    <div class="mt-4 border-t border-mocha-overlay pt-4">
                        <p class="mb-2 text-[11px] font-medium text-mocha-subtext">Items</p>
                        @foreach ($shipment->items as $shipmentItem)
                            <div class="flex justify-between gap-2 py-1.5 text-xs">
                                <span class="text-mocha-text">{{ $shipmentItem->item?->name ?: 'Item' }} × {{ $shipmentItem->quantity }}</span>
                                <span class="text-mocha-muted">{{ number_format((float) $shipmentItem->weight, 2) }} kg</span>
                            </div>
                        @endforeach
                    </div>
                @endif
                @if ($shipment->packages->isNotEmpty())
                    <div class="mt-4 border-t border-mocha-overlay pt-4">
                        <p class="mb-2 text-[11px] font-medium text-mocha-subtext">Package details</p>
                        @foreach ($shipment->packages as $package)
                            <div class="py-2 text-xs">
                                <p class="text-mocha-text">{{ $package->name }} × {{ $package->quantity }}</p>
                                @if ($package->description)
                                    <p class="mt-1 whitespace-pre-line text-mocha-muted">{{ $package->description }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        </aside>
    </div>
</div>
@endsection
