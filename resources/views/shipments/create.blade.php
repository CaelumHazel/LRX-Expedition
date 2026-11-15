
@extends('layouts.app')

@section('title', 'Create Shipment')

@section('content')
<div
    x-data="{
        rows: @js(old('items', [['name' => '', 'description' => '', 'quantity' => 1]])),
        cities: @js($cities),
        originCity: @js(old('origin_city', '')),
        destinationCity: @js(old('destination_city', '')),
        cargoType: @js(old('cargo_type', '')),
        courierId: @js(old('courier_id', '')),
        weight: @js(old('total_weight', '')),
        shippingCost: @js(old('shipping_cost', '')),
        feeSource: '',
        distanceKm: null,
        estimatedDays: null,
        loadingFee: false,
        feeError: '',
        estimateTimer: null,

        init() {
            ['originCity', 'destinationCity', 'cargoType', 'courierId', 'weight'].forEach((key) => {
                this.$watch(key, () => this.scheduleEstimate());
            });
            this.scheduleEstimate();
        },

        addRow() {
            this.rows.push({ name: '', description: '', quantity: 1 });
        },

        removeRow(index) {
            if (this.rows.length > 1) {
                this.rows.splice(index, 1);
            }
        },

        scheduleEstimate() {
            clearTimeout(this.estimateTimer);
            if (!this.originCity || !this.destinationCity || !this.cargoType || !Number(this.weight)) {
                this.shippingCost = '';
                this.distanceKm = null;
                this.estimatedDays = null;
                this.feeSource = '';
                return;
            }
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
                    courier_id: this.courierId,
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
                this.shippingCost = '';
                this.distanceKm = null;
                this.estimatedDays = null;
                this.feeSource = '';
                this.feeError = error.message || 'Ongkir gagal dihitung.';
            } finally {
                this.loadingFee = false;
            }
        }
    }"
    class="space-y-6"
    @city-changed="if ($event.detail.field === 'origin_city') originCity = $event.detail.value; if ($event.detail.field === 'destination_city') destinationCity = $event.detail.value"
>
    {{-- Page Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="mb-2 flex items-center gap-2 text-xs font-medium uppercase tracking-[0.18em] text-mocha-subtext">
                <span class="h-1.5 w-1.5 rounded-full bg-lrx-cyan shadow-[0_0_10px_var(--color-lrx-cyan)]"></span>
                Logistics Operations
            </div>

            <h1 class="text-2xl font-semibold tracking-tight text-mocha-text">
                Create Shipment
            </h1>

            <p class="mt-1 text-sm text-mocha-subtext">
                Create a new shipment and configure its delivery details.
            </p>
        </div>

        <a
            href="{{ route('shipments.index') }}"
            class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-mocha-border bg-mocha-surface px-4 text-sm font-medium text-mocha-subtext transition hover:border-mocha-overlay hover:bg-mocha-mantle hover:text-mocha-text"
        >
            <span aria-hidden="true">←</span>
            Back to Shipments
        </a>
    </div>

    {{-- Validation Errors --}}
    @if ($errors->any())
        <div class="rounded-xl border border-lrx-red/30 bg-lrx-red/10 p-4">
            <div class="flex items-start gap-3">
                <span class="text-lrx-red">!</span>
                <div>
                    <p class="text-sm font-semibold text-lrx-red">
                        Please check the following fields
                    </p>

                    <ul class="mt-2 list-inside list-disc space-y-1 text-xs text-mocha-subtext">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <form action="{{ route('shipments.store') }}" method="POST" class="space-y-6">
        @csrf

        {{-- Shipment Parties --}}
        <section class="overflow-hidden rounded-2xl border border-mocha-overlay bg-mocha-surface/90 shadow-xl shadow-black/10">
            <div class="border-b border-mocha-overlay px-5 py-4 sm:px-6">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-lrx-cyan/10 text-lrx-cyan">
                        <span class="text-lg">↗</span>
                    </div>

                    <div>
                        <h2 class="text-sm font-semibold text-mocha-text">Shipment Parties</h2>
                        <p class="mt-0.5 text-xs text-mocha-subtext">
                            Define sender, receiver, and delivery assignment.
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid gap-5 p-5 sm:grid-cols-2 sm:p-6">
                {{-- Sender --}}
                <div>
                    <label for="sender_name" class="mb-2 block text-xs font-medium text-mocha-subtext">
                        Sender <span class="text-lrx-red">*</span>
                    </label>
                    <input id="sender_name" name="sender_name" value="{{ old('sender_name') }}" required maxlength="255" placeholder="Sender name" class="w-full rounded-xl border border-mocha-border bg-mocha-mantle px-3.5 py-3 text-sm text-mocha-text outline-none transition focus:border-lrx-cyan focus:ring-2 focus:ring-lrx-cyan/10">
                    @error('sender_name')
                        <p class="mt-1.5 text-xs text-lrx-red">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Receiver --}}
                <div>
                    <label for="receiver_name" class="mb-2 block text-xs font-medium text-mocha-subtext">
                        Receiver <span class="text-lrx-red">*</span>
                    </label>
                    <input id="receiver_name" name="receiver_name" value="{{ old('receiver_name') }}" required maxlength="255" placeholder="Receiver name" class="w-full rounded-xl border border-mocha-border bg-mocha-mantle px-3.5 py-3 text-sm text-mocha-text outline-none transition focus:border-lrx-cyan focus:ring-2 focus:ring-lrx-cyan/10">
                    @error('receiver_name')
                        <p class="mt-1.5 text-xs text-lrx-red">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="sender_phone" class="mb-2 block text-xs font-medium text-mocha-subtext">Sender phone <span class="text-lrx-red">*</span></label>
                    <input id="sender_phone" name="sender_phone" value="{{ old('sender_phone') }}" required maxlength="20" inputmode="tel" placeholder="08xxxxxxxxxx" class="w-full rounded-xl border border-mocha-border bg-mocha-mantle px-3.5 py-3 text-sm text-mocha-text outline-none transition focus:border-lrx-cyan focus:ring-2 focus:ring-lrx-cyan/10">
                    @error('sender_phone')<p class="mt-1.5 text-xs text-lrx-red">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="receiver_phone" class="mb-2 block text-xs font-medium text-mocha-subtext">Receiver phone <span class="text-lrx-red">*</span></label>
                    <input id="receiver_phone" name="receiver_phone" value="{{ old('receiver_phone') }}" required maxlength="20" inputmode="tel" placeholder="08xxxxxxxxxx" class="w-full rounded-xl border border-mocha-border bg-mocha-mantle px-3.5 py-3 text-sm text-mocha-text outline-none transition focus:border-lrx-cyan focus:ring-2 focus:ring-lrx-cyan/10">
                    @error('receiver_phone')<p class="mt-1.5 text-xs text-lrx-red">{{ $message }}</p>@enderror
                </div>

                {{-- Courier --}}
                <div>
                    <label for="courier_id" class="mb-2 block text-xs font-medium text-mocha-subtext">
                        Courier <span class="text-mocha-muted">(Optional)</span>
                    </label>

                    <select
                        id="courier_id"
                        name="courier_id"
                        x-model="courierId"
                        class="w-full rounded-xl border border-mocha-border bg-mocha-mantle px-3.5 py-3 text-sm text-mocha-text outline-none transition focus:border-lrx-cyan focus:ring-2 focus:ring-lrx-cyan/10"
                    >
                        <option value="">Unassigned</option>
                        @foreach ($couriers as $courier)
                            <option value="{{ $courier->id }}" @selected(old('courier_id') == $courier->id)>
                                {{ $courier->name }} — {{ $courier->vehicle_type }}
                            </option>
                        @endforeach
                    </select>

                    @error('courier_id')
                        <p class="mt-1.5 text-xs text-lrx-red">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Warehouse --}}
                <div>
                    <label for="warehouse_id" class="mb-2 block text-xs font-medium text-mocha-subtext">
                        Warehouse <span class="text-mocha-muted">(Optional)</span>
                    </label>

                    <select
                        id="warehouse_id"
                        name="warehouse_id"
                        class="w-full rounded-xl border border-mocha-border bg-mocha-mantle px-3.5 py-3 text-sm text-mocha-text outline-none transition focus:border-lrx-cyan focus:ring-2 focus:ring-lrx-cyan/10"
                    >
                        <option value="">No warehouse assigned</option>
                        @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" @selected(old('warehouse_id') == $warehouse->id)>
                                {{ $warehouse->name }} — {{ $warehouse->city }}
                            </option>
                        @endforeach
                    </select>

                    @error('warehouse_id')
                        <p class="mt-1.5 text-xs text-lrx-red">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </section>

        {{-- Route Details --}}
        <section class="relative z-20 overflow-visible rounded-2xl border border-mocha-overlay bg-mocha-surface/90 shadow-xl shadow-black/10">
            <div class="border-b border-mocha-overlay px-5 py-4 sm:px-6">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-lrx-purple/10 text-lrx-purple">
                        <span class="text-lg">⌖</span>
                    </div>

                    <div>
                        <h2 class="text-sm font-semibold text-mocha-text">Route & Cargo Details</h2>
                        <p class="mt-0.5 text-xs text-mocha-subtext">
                            Specify shipment route and cargo classification.
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid gap-5 p-5 sm:grid-cols-2 sm:p-6">
                {{-- Cargo Type --}}
                <div>
                    <label for="cargo_type" class="mb-2 block text-xs font-medium text-mocha-subtext">
                        Cargo Type <span class="text-lrx-red">*</span>
                    </label>

                    <select
                        id="cargo_type"
                        name="cargo_type"
                        x-model="cargoType"
                        required
                        class="w-full rounded-xl border border-mocha-border bg-mocha-mantle px-3.5 py-3 text-sm text-mocha-text outline-none placeholder:text-mocha-muted transition focus:border-lrx-cyan focus:ring-2 focus:ring-lrx-cyan/10"
                    >
                        <option value="">Select cargo type</option>
                        @foreach ($cargoTypes as $cargoType)
                            <option value="{{ $cargoType }}">{{ str($cargoType)->replace('_', ' ')->title() }}</option>
                        @endforeach
                    </select>

                    @error('cargo_type')
                        <p class="mt-1.5 text-xs text-lrx-red">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Origin --}}
                <div>
                    <label for="origin_city" class="mb-2 block text-xs font-medium text-mocha-subtext">
                        Origin City <span class="text-lrx-red">*</span>
                    </label>

                    <div x-data="cityCombobox(cities, 'origin_city', originCity)" class="relative" @click.outside="open = false">
                        <div class="relative">
                            <svg viewBox="0 0 24 24" fill="none" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-mocha-muted" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <circle cx="11" cy="11" r="7"/>
                                <path d="M16 16L21 21"/>
                            </svg>
                            <input
                                id="origin_city"
                                type="search"
                                x-model="query"
                                @focus="open = true"
                                @input="searchChanged"
                                @keydown.arrow-down.prevent="move(1)"
                                @keydown.arrow-up.prevent="move(-1)"
                                @keydown.enter="handleEnter($event)"
                                @keydown.escape.prevent="open = false; activeIndex = -1"
                                @keydown.tab="open = false; activeIndex = -1"
                                role="combobox"
                                aria-autocomplete="list"
                                aria-haspopup="listbox"
                                aria-label="Search origin city"
                                :aria-expanded="open"
                                aria-controls="origin_city-options"
                                :aria-activedescendant="activeOptionId"
                                :aria-invalid="!selected && query.length > 0"
                                autocomplete="off"
                                required
                                placeholder="Type a city or regency..."
                                class="w-full rounded-xl border border-mocha-border bg-mocha-mantle py-3 pl-10 pr-11 text-sm text-mocha-text outline-none placeholder:text-mocha-muted transition focus:border-lrx-cyan focus:ring-2 focus:ring-lrx-cyan/10"
                            >
                            <input type="hidden" name="origin_city" :value="selected">
                            <button
                                type="button"
                                x-cloak
                                x-show="selected"
                                @click="clear()"
                                class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg p-1.5 text-mocha-muted transition hover:bg-mocha-overlay hover:text-mocha-text"
                                aria-label="Clear origin city"
                            >×</button>
                        </div>
                        <div x-cloak x-show="open" x-transition class="absolute z-20 mt-2 max-h-64 w-full overflow-y-auto rounded-xl border border-mocha-border bg-mocha-mantle p-1.5 shadow-2xl shadow-black/30">
                            <ul id="origin_city-options" role="listbox" aria-label="Origin cities">
                                <template x-for="(city, index) in filteredOptions" :key="city.id">
                                    <li role="presentation">
                                        <button
                                            type="button"
                                            role="option"
                                            :id="`origin_city-option-${index}`"
                                            :aria-selected="selected === city.name"
                                            @mouseenter="activeIndex = index"
                                            @mousedown.prevent
                                            @click="select(city)"
                                            :class="activeIndex === index ? 'bg-mocha-overlay text-lrx-cyan' : 'text-mocha-text hover:bg-mocha-overlay'"
                                            class="flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2.5 text-left text-xs transition"
                                        >
                                            <span x-text="city.label"></span>
                                            <span class="shrink-0 text-[10px] text-mocha-muted" x-text="city.province_label"></span>
                                        </button>
                                    </li>
                                </template>
                                <li x-show="filteredOptions.length === 0" class="px-3 py-3 text-xs text-mocha-muted">No matching cities.</li>
                            </ul>
                        </div>
                        <p class="mt-1.5 text-[10px] text-mocha-muted">Type to search; use ↑ / ↓ and Enter to select.</p>
                    </div>

                    @error('origin_city')
                        <p class="mt-1.5 text-xs text-lrx-red">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Destination --}}
                <div>
                    <label for="destination_city" class="mb-2 block text-xs font-medium text-mocha-subtext">
                        Destination City <span class="text-lrx-red">*</span>
                    </label>

                    <div x-data="cityCombobox(cities, 'destination_city', destinationCity)" class="relative" @click.outside="open = false">
                        <div class="relative">
                            <svg viewBox="0 0 24 24" fill="none" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-mocha-muted" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <circle cx="11" cy="11" r="7"/>
                                <path d="M16 16L21 21"/>
                            </svg>
                            <input
                                id="destination_city"
                                type="search"
                                x-model="query"
                                @focus="open = true"
                                @input="searchChanged"
                                @keydown.arrow-down.prevent="move(1)"
                                @keydown.arrow-up.prevent="move(-1)"
                                @keydown.enter="handleEnter($event)"
                                @keydown.escape.prevent="open = false; activeIndex = -1"
                                @keydown.tab="open = false; activeIndex = -1"
                                role="combobox"
                                aria-autocomplete="list"
                                aria-haspopup="listbox"
                                aria-label="Search destination city"
                                :aria-expanded="open"
                                aria-controls="destination_city-options"
                                :aria-activedescendant="activeOptionId"
                                :aria-invalid="!selected && query.length > 0"
                                autocomplete="off"
                                required
                                placeholder="Type a city or regency..."
                                class="w-full rounded-xl border border-mocha-border bg-mocha-mantle py-3 pl-10 pr-11 text-sm text-mocha-text outline-none placeholder:text-mocha-muted transition focus:border-lrx-cyan focus:ring-2 focus:ring-lrx-cyan/10"
                            >
                            <input type="hidden" name="destination_city" :value="selected">
                            <button
                                type="button"
                                x-cloak
                                x-show="selected"
                                @click="clear()"
                                class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg p-1.5 text-mocha-muted transition hover:bg-mocha-overlay hover:text-mocha-text"
                                aria-label="Clear destination city"
                            >×</button>
                        </div>
                        <div x-cloak x-show="open" x-transition class="absolute z-20 mt-2 max-h-64 w-full overflow-y-auto rounded-xl border border-mocha-border bg-mocha-mantle p-1.5 shadow-2xl shadow-black/30">
                            <ul id="destination_city-options" role="listbox" aria-label="Destination cities">
                                <template x-for="(city, index) in filteredOptions" :key="city.id">
                                    <li role="presentation">
                                        <button
                                            type="button"
                                            role="option"
                                            :id="`destination_city-option-${index}`"
                                            :aria-selected="selected === city.name"
                                            @mouseenter="activeIndex = index"
                                            @mousedown.prevent
                                            @click="select(city)"
                                            :class="activeIndex === index ? 'bg-mocha-overlay text-lrx-cyan' : 'text-mocha-text hover:bg-mocha-overlay'"
                                            class="flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2.5 text-left text-xs transition"
                                        >
                                            <span x-text="city.label"></span>
                                            <span class="shrink-0 text-[10px] text-mocha-muted" x-text="city.province_label"></span>
                                        </button>
                                    </li>
                                </template>
                                <li x-show="filteredOptions.length === 0" class="px-3 py-3 text-xs text-mocha-muted">No matching cities.</li>
                            </ul>
                        </div>
                        <p class="mt-1.5 text-[10px] text-mocha-muted">Type to search; use ↑ / ↓ and Enter to select.</p>
                    </div>

                    @error('destination_city')
                        <p class="mt-1.5 text-xs text-lrx-red">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </section>

        {{-- Receiver Address --}}
        <section class="overflow-hidden rounded-2xl border border-mocha-overlay bg-mocha-surface/90 shadow-xl shadow-black/10">
            <div class="border-b border-mocha-overlay px-5 py-4 sm:px-6">
                <h2 class="text-sm font-semibold text-mocha-text">Detailed Receiver Address</h2>
                <p class="mt-1 text-xs text-mocha-subtext">Include street, number, RT/RW, village, district, postal code, and a nearby landmark.</p>
            </div>
            <div class="p-5 sm:p-6">
                <label for="receiver_address" class="mb-2 block text-xs font-medium text-mocha-subtext">
                    Full delivery address <span class="text-lrx-red">*</span>
                </label>
                <textarea
                    id="receiver_address"
                    name="receiver_address"
                    rows="4"
                    maxlength="2000"
                    required
                    placeholder="Street, building/house number, RT/RW, village, district, city, postal code, landmark..."
                    class="w-full resize-y rounded-xl border border-mocha-border bg-mocha-mantle px-3.5 py-3 text-sm text-mocha-text outline-none placeholder:text-mocha-muted transition focus:border-lrx-cyan focus:ring-2 focus:ring-lrx-cyan/10"
                >{{ old('receiver_address') }}</textarea>
                @error('receiver_address')
                    <p class="mt-1.5 text-xs text-lrx-red">{{ $message }}</p>
                @enderror
            </div>
        </section>

        {{-- Package Items --}}
        <section class="overflow-hidden rounded-2xl border border-mocha-overlay bg-mocha-surface/90 shadow-xl shadow-black/10">
            <div class="flex flex-col gap-3 border-b border-mocha-overlay px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-lrx-green/10 text-lrx-green">
                        <span class="text-lg">▤</span>
                    </div>

                    <div>
                        <h2 class="text-sm font-semibold text-mocha-text">Package Items</h2>
                        <p class="mt-0.5 text-xs text-mocha-subtext">
                            Add items included in this shipment.
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    @click="addRow()"
                    class="inline-flex h-9 items-center justify-center gap-2 rounded-lg border border-lrx-cyan/30 bg-lrx-cyan/10 px-3 text-xs font-semibold text-lrx-cyan transition hover:bg-lrx-cyan/15"
                >
                    <span class="text-base">+</span>
                    Add Item
                </button>
            </div>

            <div class="space-y-4 p-5 sm:p-6">
                <template x-for="(row, index) in rows" :key="index">
                    <div class="rounded-xl border border-mocha-border bg-mocha-mantle/70 p-4">
                        <div class="mb-4 flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-mocha-subtext">
                                Item <span x-text="index + 1"></span>
                            </span>

                            <button
                                type="button"
                                @click="removeRow(index)"
                                :disabled="rows.length === 1"
                                class="text-xs font-medium text-lrx-red transition hover:text-lrx-red/80 disabled:cursor-not-allowed disabled:opacity-30"
                            >
                                Remove
                            </button>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-xs font-medium text-mocha-subtext">
                                    Item name <span class="text-lrx-red">*</span>
                                </label>
                                <input
                                    type="text"
                                    :name="'items[' + index + '][name]'"
                                    x-model="row.name"
                                    required
                                    maxlength="255"
                                    placeholder="e.g. Spare parts"
                                    class="w-full rounded-xl border border-mocha-border bg-mocha-surface px-3.5 py-3 text-sm text-mocha-text outline-none transition focus:border-lrx-cyan focus:ring-2 focus:ring-lrx-cyan/10"
                                >
                            </div>

                            <div>
                                <label class="mb-2 block text-xs font-medium text-mocha-subtext">
                                    Quantity <span class="text-lrx-red">*</span>
                                </label>

                                <input
                                    type="number"
                                    :name="'items[' + index + '][quantity]'"
                                    x-model.number="row.quantity"
                                    min="1"
                                    step="1"
                                    required
                                    class="w-full rounded-xl border border-mocha-border bg-mocha-surface px-3.5 py-3 text-sm text-mocha-text outline-none placeholder:text-mocha-muted transition focus:border-lrx-cyan focus:ring-2 focus:ring-lrx-cyan/10"
                                >
                            </div>

                            <div class="sm:col-span-2">
                                <label class="mb-2 block text-xs font-medium text-mocha-subtext">Description <span class="text-mocha-muted">(Optional)</span></label>
                                <textarea
                                    :name="'items[' + index + '][description]'"
                                    x-model="row.description"
                                    rows="2"
                                    maxlength="2000"
                                    placeholder="Describe the package contents"
                                    class="w-full resize-y rounded-xl border border-mocha-border bg-mocha-surface px-3.5 py-3 text-sm text-mocha-text outline-none transition focus:border-lrx-cyan focus:ring-2 focus:ring-lrx-cyan/10"
                                ></textarea>
                            </div>
                        </div>
                    </div>
                </template>

                <div>
                    <label for="total_weight" class="mb-2 block text-xs font-medium text-mocha-subtext">
                        Measured shipment weight (kg) <span class="text-lrx-red">*</span>
                    </label>
                    <input
                        id="total_weight"
                        name="total_weight"
                        type="number"
                        min="0.01"
                        max="99999999.99"
                        step="0.01"
                        required
                        value="{{ old('total_weight') }}"
                        x-model="weight"
                        placeholder="Enter measured weight"
                        class="w-full rounded-xl border border-mocha-border bg-mocha-mantle px-3.5 py-3 text-sm text-mocha-text outline-none placeholder:text-mocha-muted transition focus:border-lrx-cyan focus:ring-2 focus:ring-lrx-cyan/10"
                    >
                    @error('total_weight')
                        <p class="mt-1.5 text-xs text-lrx-red">{{ $message }}</p>
                    @enderror
                </div>

                @error('items')
                    <p class="text-xs text-lrx-red">{{ $message }}</p>
                @enderror
            </div>
        </section>

        {{-- Shipment Value and Fee --}}
        <section class="overflow-hidden rounded-2xl border border-mocha-overlay bg-mocha-surface/90 shadow-xl shadow-black/10">
            <div class="border-b border-mocha-overlay px-5 py-4 sm:px-6">
                <h2 class="text-sm font-semibold text-mocha-text">Declared Value & Shipping Fee</h2>
                <p class="mt-1 text-xs text-mocha-subtext">Declared value is entered manually. Shipping fee and distance are estimated automatically from the selected route.</p>
            </div>
            <div class="grid gap-5 p-5 sm:grid-cols-2 sm:p-6">
                <div>
                    <label for="declared_value" class="mb-2 block text-xs font-medium text-mocha-subtext">Declared item value (Rp)</label>
                    <input
                        id="declared_value"
                        name="declared_value"
                        type="number"
                        min="0"
                        step="0.01"
                        value="{{ old('declared_value', 0) }}"
                        class="w-full rounded-xl border border-mocha-border bg-mocha-mantle px-3.5 py-3 text-sm text-mocha-text outline-none focus:border-lrx-cyan focus:ring-2 focus:ring-lrx-cyan/10"
                    >
                    @error('declared_value')
                        <p class="mt-1.5 text-xs text-lrx-red">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="shipping_cost" class="mb-2 block text-xs font-medium text-mocha-subtext">Estimated shipping fee (Rp)</label>
                    <input
                        id="shipping_cost"
                        name="shipping_cost"
                        type="number"
                        :value="shippingCost"
                        readonly
                        class="w-full rounded-xl border border-mocha-border bg-mocha-mantle px-3.5 py-3 text-sm text-mocha-text outline-none"
                    >
                    <p class="mt-1.5 text-xs text-mocha-muted" x-show="loadingFee">Calculating route estimate…</p>
                    <p class="mt-1.5 text-xs text-lrx-red" x-show="feeError" x-text="feeError"></p>
                    <p class="mt-1.5 text-xs text-mocha-subtext" x-show="distanceKm !== null && !loadingFee">
                        Approx. <span x-text="distanceKm"></span> km · ETA <span x-text="estimatedDays"></span> days · <span x-text="feeSource === 'configured' ? 'configured rate' : 'dummy rate'"></span>
                    </p>
                </div>
            </div>
        </section>

        {{-- Additional Notes --}}
        <section class="overflow-hidden rounded-2xl border border-mocha-overlay bg-mocha-surface/90 shadow-xl shadow-black/10">
            <div class="border-b border-mocha-overlay px-5 py-4 sm:px-6">
                <h2 class="text-sm font-semibold text-mocha-text">Additional Notes</h2>
                <p class="mt-0.5 text-xs text-mocha-subtext">
                    Add any special instructions for this shipment.
                </p>
            </div>

            <div class="p-5 sm:p-6">
                <textarea
                    id="notes"
                    name="notes"
                    rows="4"
                    placeholder="Write additional shipment instructions..."
                    class="w-full resize-y rounded-xl border border-mocha-border bg-mocha-mantle px-3.5 py-3 text-sm text-mocha-text outline-none placeholder:text-mocha-muted transition focus:border-lrx-cyan focus:ring-2 focus:ring-lrx-cyan/10"
                >{{ old('notes') }}</textarea>

                @error('notes')
                    <p class="mt-1.5 text-xs text-lrx-red">{{ $message }}</p>
                @enderror
            </div>
        </section>

        {{-- Form Actions --}}
        <div class="flex flex-col-reverse gap-3 border-t border-mocha-overlay pt-5 sm:flex-row sm:items-center sm:justify-end">
            <a
                href="{{ route('shipments.index') }}"
                class="inline-flex h-11 items-center justify-center rounded-xl border border-mocha-border bg-mocha-surface px-5 text-sm font-medium text-mocha-subtext transition hover:bg-mocha-mantle hover:text-mocha-text"
            >
                Cancel
            </a>

            <button
                type="submit"
                :disabled="loadingFee || !shippingCost"
                class="lrx-button-primary inline-flex h-11 items-center justify-center gap-2 rounded-xl px-6 text-sm font-semibold transition disabled:cursor-not-allowed disabled:opacity-40"
            >
                <span>Create Shipment</span>
                <span aria-hidden="true">→</span>
            </button>
        </div>
    </form>
</div>
@endsection
