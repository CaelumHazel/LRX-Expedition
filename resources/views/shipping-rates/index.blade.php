@extends('layouts.app')

@section('title', 'Shipping Rates')
@section('breadcrumb', 'Management / Shipping Rates')
@section('page-title', 'Shipping Rates')

@section('content')
<div class="space-y-6">
    <div>
        <p class="mb-2 text-[10px] font-semibold uppercase tracking-[0.2em] text-lrx-cyan">Rate configuration</p>
        <h1 class="text-2xl font-semibold text-mocha-text">Shipping Rates</h1>
        <p class="mt-1 text-xs text-mocha-muted">Active matching rates determine shipping fees and estimated delivery dates.</p>
    </div>

    <form method="POST" action="{{ route('shipping-rates.store') }}" class="rounded-2xl border border-mocha-overlay bg-mocha-surface p-5">
        @csrf
        <h2 class="text-sm font-semibold text-mocha-text">Add a route rate</h2>
        <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @foreach (['origin_city' => 'Origin city', 'destination_city' => 'Destination city', 'cargo_type' => 'Cargo type'] as $name => $label)
                <label class="text-xs text-mocha-subtext">{{ $label }}
                    <input name="{{ $name }}" value="{{ old($name) }}" required maxlength="100" class="mt-2 w-full rounded-xl border border-mocha-border bg-mocha-mantle px-3 py-2.5 text-mocha-text">
                    @error($name)<span class="mt-1 block text-lrx-red">{{ $message }}</span>@enderror
                </label>
            @endforeach
            <label class="text-xs text-mocha-subtext">Courier (optional)
                <select name="courier_id" class="mt-2 w-full rounded-xl border border-mocha-border bg-mocha-mantle px-3 py-2.5 text-mocha-text">
                    <option value="">All couriers</option>
                    @foreach ($couriers as $courier)
                        <option value="{{ $courier->id }}" @selected(old('courier_id') == $courier->id)>{{ $courier->name }}</option>
                    @endforeach
                </select>
                @error('courier_id')<span class="mt-1 block text-lrx-red">{{ $message }}</span>@enderror
            </label>
            <label class="text-xs text-mocha-subtext">Price per kg (Rp)
                <input name="price_per_kg" type="number" step="0.01" min="0.01" value="{{ old('price_per_kg') }}" required class="mt-2 w-full rounded-xl border border-mocha-border bg-mocha-mantle px-3 py-2.5 text-mocha-text">
                @error('price_per_kg')<span class="mt-1 block text-lrx-red">{{ $message }}</span>@enderror
            </label>
            <label class="text-xs text-mocha-subtext">Estimated days
                <input name="estimated_days" type="number" min="1" max="365" value="{{ old('estimated_days', 1) }}" required class="mt-2 w-full rounded-xl border border-mocha-border bg-mocha-mantle px-3 py-2.5 text-mocha-text">
                @error('estimated_days')<span class="mt-1 block text-lrx-red">{{ $message }}</span>@enderror
            </label>
        </div>
        @error('is_active')<p class="mt-2 text-xs text-lrx-red">{{ $message }}</p>@enderror
        <button class="lrx-button-primary mt-4 rounded-xl px-4 py-2.5 text-xs font-semibold">Add rate</button>
    </form>

    <section class="overflow-x-auto rounded-2xl border border-mocha-overlay bg-mocha-surface">
        <table class="w-full min-w-[980px] text-left">
            <thead class="sticky top-0 bg-mocha-mantle text-[10px] uppercase tracking-wider text-mocha-muted">
                <tr><th class="px-4 py-3">Route</th><th class="px-4 py-3">Courier</th><th class="px-4 py-3">Cargo</th><th class="px-4 py-3">Rate / kg</th><th class="px-4 py-3">ETA</th><th class="px-4 py-3">Active</th><th class="px-4 py-3">Save</th></tr>
            </thead>
            <tbody class="divide-y divide-mocha-overlay">
                @forelse ($rates as $rate)
                    <tr class="align-top odd:bg-mocha-mantle/20">
                        <td colspan="7" class="p-0">
                            <form method="POST" action="{{ route('shipping-rates.update', $rate) }}" class="grid grid-cols-7 items-start gap-3 p-3">
                                @csrf
                                @method('PUT')
                                <div class="col-span-2 grid grid-cols-2 gap-2">
                                    <input name="origin_city" value="{{ $rate->origin_city }}" required maxlength="100" class="w-full rounded-lg border border-mocha-border bg-mocha-mantle px-2 py-2 text-xs text-mocha-text">
                                    <input name="destination_city" value="{{ $rate->destination_city }}" required maxlength="100" class="w-full rounded-lg border border-mocha-border bg-mocha-mantle px-2 py-2 text-xs text-mocha-text">
                                </div>
                                <select name="courier_id" class="min-w-0 rounded-lg border border-mocha-border bg-mocha-mantle px-2 py-2 text-xs text-mocha-text">
                                    <option value="">All couriers</option>
                                    @foreach ($couriers as $courier)
                                        <option value="{{ $courier->id }}" @selected($rate->courier_id == $courier->id)>{{ $courier->name }}</option>
                                    @endforeach
                                </select>
                                <input name="cargo_type" value="{{ $rate->cargo_type }}" required maxlength="100" class="min-w-0 rounded-lg border border-mocha-border bg-mocha-mantle px-2 py-2 text-xs text-mocha-text">
                                <input name="price_per_kg" type="number" min="0.01" step="0.01" value="{{ $rate->price_per_kg }}" required class="min-w-0 rounded-lg border border-mocha-border bg-mocha-mantle px-2 py-2 text-xs text-mocha-text">
                                <input name="estimated_days" type="number" min="1" max="365" value="{{ $rate->estimated_days }}" required class="min-w-0 rounded-lg border border-mocha-border bg-mocha-mantle px-2 py-2 text-xs text-mocha-text">
                                <div class="flex items-center gap-2">
                                    <label class="flex items-center gap-2 text-xs text-mocha-subtext">
                                        <input type="checkbox" name="is_active" value="1" @checked($rate->is_active) class="rounded border-mocha-border bg-mocha-mantle text-lrx-cyan">
                                        Active
                                    </label>
                                    <button class="rounded-lg border border-lrx-cyan/30 px-2 py-2 text-[10px] text-lrx-cyan">Save</button>
                                </div>
                            </form>
                            <form method="POST" action="{{ route('shipping-rates.destroy', $rate) }}" class="px-3 pb-3 text-right">
                                @csrf
                                @method('DELETE')
                                <button class="text-[10px] text-lrx-red hover:underline" onclick="return confirm('Delete this rate?')">Delete rate</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-12 text-center text-xs text-mocha-muted">No rate configuration yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t border-mocha-overlay px-5 py-4 text-xs">{{ $rates->links() }}</div>
    </section>
</div>
@endsection
