@extends('layouts.app')

@section('title', 'Edit Shipment')
@section('breadcrumb', 'Shipment / Edit')
@section('page-title', 'Edit Shipment')

@section('content')
<div
    x-data="{
        rows: @js(old('packages', $shipment->packages->map(fn ($package) => ['name' => $package->name, 'description' => $package->description, 'quantity' => $package->quantity])->values()->all() ?: [['name' => '', 'description' => '', 'quantity' => 1]])),
        originCity: @js(old('origin_city', $shipment->origin_city)),
        destinationCity: @js(old('destination_city', $shipment->destination_city)),
        cargoType: @js(old('cargo_type', $shipment->cargo_type)),
        courierId: @js(old('courier_id', $shipment->courier_id)),
        weight: @js(old('total_weight', $shipment->total_weight)),
        shippingCost: @js(old('shipping_cost', $shipment->shipping_cost)),
        feeSource: '',
        distanceKm: @js($shipment->estimated_distance_km),
        estimatedDays: @js($shipment->estimated_delivery_date?->diffInDays(today())),
        loadingFee: false,
        feeError: '',
        estimateTimer: null,

        init() {
            ['originCity', 'destinationCity', 'cargoType', 'courierId', 'weight'].forEach((key) => {
                this.$watch(key, () => this.scheduleEstimate());
            });
        },

        addRow() {
            this.rows.push({ name: '', description: '', quantity: 1 });
        },

        removeRow(index) {
            this.rows.splice(index, 1);
        },

        scheduleEstimate() {
            clearTimeout(this.estimateTimer);
            if (!this.originCity || !this.destinationCity || !this.cargoType || !Number(this.weight)) return;
            this.estimateTimer = setTimeout(() => this.loadEstimate(), 250);
        },

        async loadEstimate() {
            this.loadingFee = true;
            this.feeError = '';
            try {
                const params = new URLSearchParams({
                    origin_city: this.originCity,
                    destination_city: this.destinationCity,
                    cargo_type: this.cargoType,
                    total_weight: this.weight,
                    courier_id: this.courierId || '',
                });
                const response = await fetch('{{ route('shipments.estimate') }}?' + params.toString(), {
                    headers: { Accept: 'application/json' },
                });
                if (!response.ok) throw new Error('Ongkir belum dapat dihitung. Periksa rute dan berat.');
                const estimate = await response.json();
                this.shippingCost = estimate.shipping_cost;
                this.distanceKm = estimate.distance_km;
                this.estimatedDays = estimate.estimated_days;
                this.feeSource = estimate.source;
            } catch (error) {
                this.feeError = error.message || 'Ongkir gagal dihitung.';
            } finally {
                this.loadingFee = false;
            }
        }
    }"
    class="mx-auto max-w-5xl space-y-6"
>
    <div>
        <a href="{{ route('shipments.show', $shipment) }}" class="text-xs text-lrx-cyan hover:text-lrx-purple">← Shipment details</a>
        <h1 class="mt-2 text-2xl font-semibold text-mocha-text">Edit {{ $shipment->tracking_number }}</h1>
    </div>

    @if ($errors->any())
        <div class="rounded-xl border border-lrx-red/30 bg-lrx-red/10 p-4 text-xs text-lrx-red" role="alert">
            @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('shipments.update', $shipment) }}" class="space-y-5">
        @csrf
        @method('PUT')

        <section class="grid gap-4 rounded-2xl border border-mocha-overlay bg-mocha-surface p-5 sm:grid-cols-2">
            <label class="text-xs text-mocha-subtext">Sender name
                <input name="sender_name" value="{{ old('sender_name', $shipment->sender_name ?: $shipment->sender?->name) }}" required maxlength="255" class="mt-2 w-full rounded-xl border border-mocha-border bg-mocha-mantle px-3 py-2.5 text-mocha-text">
            </label>
            <label class="text-xs text-mocha-subtext">Sender phone
                <input name="sender_phone" value="{{ old('sender_phone', $shipment->sender_phone ?: $shipment->sender?->phone) }}" required maxlength="20" inputmode="tel" class="mt-2 w-full rounded-xl border border-mocha-border bg-mocha-mantle px-3 py-2.5 text-mocha-text">
            </label>
            <label class="text-xs text-mocha-subtext">Receiver name
                <input name="receiver_name" value="{{ old('receiver_name', $shipment->receiver_name ?: $shipment->receiver?->name) }}" required maxlength="255" class="mt-2 w-full rounded-xl border border-mocha-border bg-mocha-mantle px-3 py-2.5 text-mocha-text">
            </label>
            <label class="text-xs text-mocha-subtext">Receiver phone
                <input name="receiver_phone" value="{{ old('receiver_phone', $shipment->receiver_phone ?: $shipment->receiver?->phone) }}" required maxlength="20" inputmode="tel" class="mt-2 w-full rounded-xl border border-mocha-border bg-mocha-mantle px-3 py-2.5 text-mocha-text">
            </label>
            <label class="text-xs text-mocha-subtext">Courier
                <select name="courier_id" x-model="courierId" class="mt-2 w-full rounded-xl border border-mocha-border bg-mocha-mantle px-3 py-2.5 text-mocha-text">
                    <option value="">Unassigned</option>
                    @foreach ($couriers as $courier)
                        <option value="{{ $courier->id }}">{{ $courier->name }} · {{ $courier->phone }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-xs text-mocha-subtext">Current warehouse
                <select name="warehouse_id" class="mt-2 w-full rounded-xl border border-mocha-border bg-mocha-mantle px-3 py-2.5 text-mocha-text">
                    <option value="">No warehouse assigned</option>
                    @foreach ($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" @selected((string) old('warehouse_id', $shipment->warehouse_id) === (string) $warehouse->id)>{{ $warehouse->name }} · {{ $warehouse->city }}</option>
                    @endforeach
                </select>
            </label>
        </section>

        <section class="grid gap-4 rounded-2xl border border-mocha-overlay bg-mocha-surface p-5 sm:grid-cols-2">
            <label class="text-xs text-mocha-subtext">Cargo type
                <select name="cargo_type" x-model="cargoType" required class="mt-2 w-full rounded-xl border border-mocha-border bg-mocha-mantle px-3 py-2.5 text-mocha-text">
                    @foreach ($cargoTypes as $cargoType)
                        <option value="{{ $cargoType }}">{{ str($cargoType)->replace('_', ' ')->title() }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-xs text-mocha-subtext">Measured total weight (kg)
                <input name="total_weight" type="number" step="0.01" min="0.01" max="99999999.99" x-model="weight" required class="mt-2 w-full rounded-xl border border-mocha-border bg-mocha-mantle px-3 py-2.5 text-mocha-text">
            </label>
            <label class="text-xs text-mocha-subtext">Origin city / regency
                <select name="origin_city" x-model="originCity" required class="mt-2 w-full rounded-xl border border-mocha-border bg-mocha-mantle px-3 py-2.5 text-mocha-text">
                    @if (! in_array(old('origin_city', $shipment->origin_city), array_column($cities, 'name'), true))
                        <option value="{{ old('origin_city', $shipment->origin_city) }}">{{ old('origin_city', $shipment->origin_city) }} (existing)</option>
                    @endif
                    @foreach ($cities as $city)
                        <option value="{{ $city['name'] }}">{{ $city['label'] }} · {{ $city['province_label'] }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-xs text-mocha-subtext">Destination city / regency
                <select name="destination_city" x-model="destinationCity" required class="mt-2 w-full rounded-xl border border-mocha-border bg-mocha-mantle px-3 py-2.5 text-mocha-text">
                    @if (! in_array(old('destination_city', $shipment->destination_city), array_column($cities, 'name'), true))
                        <option value="{{ old('destination_city', $shipment->destination_city) }}">{{ old('destination_city', $shipment->destination_city) }} (existing)</option>
                    @endif
                    @foreach ($cities as $city)
                        <option value="{{ $city['name'] }}">{{ $city['label'] }} · {{ $city['province_label'] }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-xs text-mocha-subtext">Declared value (Rp)
                <input name="declared_value" type="number" min="0" step="0.01" value="{{ old('declared_value', $shipment->declared_value) }}" class="mt-2 w-full rounded-xl border border-mocha-border bg-mocha-mantle px-3 py-2.5 text-mocha-text">
            </label>
            <label class="text-xs text-mocha-subtext">Estimated shipping fee (Rp)
                <input name="shipping_cost" type="number" x-model="shippingCost" readonly class="mt-2 w-full rounded-xl border border-mocha-border bg-mocha-mantle px-3 py-2.5 text-mocha-text">
                <span class="mt-1 block text-[10px] text-mocha-muted" x-show="distanceKm !== null">
                    Approx. <span x-text="distanceKm"></span> km · ETA <span x-text="estimatedDays"></span> days · <span x-text="feeSource === 'configured' ? 'configured rate' : 'dummy rate'"></span>
                </span>
                <span class="mt-1 block text-xs text-lrx-red" x-show="feeError" x-text="feeError"></span>
            </label>
            <label class="text-xs text-mocha-subtext sm:col-span-2">Detailed receiver address
                <textarea name="receiver_address" rows="4" required maxlength="2000" placeholder="Street, building/house number, RT/RW, village, district, postal code, and landmark" class="mt-2 w-full rounded-xl border border-mocha-border bg-mocha-mantle px-3 py-2.5 text-mocha-text">{{ old('receiver_address', $shipment->receiver_address) }}</textarea>
            </label>
            <label class="text-xs text-mocha-subtext sm:col-span-2">Notes
                <textarea name="notes" rows="3" maxlength="2000" class="mt-2 w-full rounded-xl border border-mocha-border bg-mocha-mantle px-3 py-2.5 text-mocha-text">{{ old('notes', $shipment->notes) }}</textarea>
            </label>
        </section>

        <section class="rounded-2xl border border-mocha-overlay bg-mocha-surface p-5">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-semibold text-mocha-text">Package details</h2>
                    <p class="mt-1 text-[11px] text-mocha-muted">Edit free-text package descriptions; measured total weight stays manual.</p>
                </div>
                <button type="button" @click="addRow()" class="rounded-lg border border-lrx-cyan/30 bg-lrx-cyan/10 px-3 py-2 text-xs text-lrx-cyan">Add item</button>
            </div>
            <div class="mt-4 space-y-3">
                <template x-for="(row, index) in rows" :key="index">
                    <div class="grid gap-3 rounded-xl border border-mocha-border p-3 sm:grid-cols-[1fr_140px_auto]">
                        <div>
                            <input :name="'packages[' + index + '][name]'" x-model="row.name" maxlength="255" placeholder="Item name" class="w-full rounded-lg border border-mocha-border bg-mocha-mantle px-3 py-2 text-xs text-mocha-text">
                            <textarea :name="'packages[' + index + '][description]'" x-model="row.description" rows="2" maxlength="2000" placeholder="Package description" class="mt-2 w-full rounded-lg border border-mocha-border bg-mocha-mantle px-3 py-2 text-xs text-mocha-text"></textarea>
                        </div>
                        <input :name="'packages[' + index + '][quantity]'" x-model="row.quantity" type="number" min="1" step="1" placeholder="Quantity" class="h-10 rounded-lg border border-mocha-border bg-mocha-mantle px-3 text-xs text-mocha-text">
                        <button type="button" @click="removeRow(index)" class="h-10 text-xs text-lrx-red">Remove</button>
                    </div>
                </template>
                @if ($shipment->items->isNotEmpty())
                    <div class="border-t border-mocha-overlay pt-3 text-xs text-mocha-subtext">
                        Existing inventory-linked items:
                        @foreach ($shipment->items as $item)
                            <span class="ml-1">{{ $item->item?->name ?? 'Item' }} × {{ $item->quantity }}@unless ($loop->last),@endunless</span>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        <div class="flex justify-end gap-3">
            <a href="{{ route('shipments.show', $shipment) }}" class="rounded-xl border border-mocha-border px-4 py-2.5 text-xs text-mocha-subtext">Cancel</a>
            <button :disabled="loadingFee || !shippingCost" class="lrx-button-primary rounded-xl px-5 py-2.5 text-xs font-semibold disabled:opacity-50">Save shipment</button>
        </div>
    </form>
</div>
@endsection
