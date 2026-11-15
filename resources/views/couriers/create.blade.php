@extends('layouts.app')

@section('title', 'Add Courier')
@section('breadcrumb', 'Management / Couriers / Add')
@section('page-title', 'Add Courier')

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="mb-2 text-[10px] font-semibold uppercase tracking-[0.2em] text-lrx-cyan">Courier management</p>
            <h1 class="text-2xl font-semibold text-mocha-text">Add a courier</h1>
            <p class="mt-1 text-xs text-mocha-muted">New active couriers will be available for shipment assignment.</p>
        </div>
        <a href="{{ route('couriers.index') }}" class="rounded-xl border border-mocha-border px-4 py-2 text-xs text-mocha-subtext transition hover:bg-mocha-overlay hover:text-mocha-text">
            ← Back to Couriers
        </a>
    </div>

    @if ($errors->any())
        <div class="rounded-xl border border-lrx-red/30 bg-lrx-red/10 p-4 text-xs text-lrx-red">
            <p class="font-semibold">Please check the following fields</p>
            <ul class="mt-2 list-inside list-disc space-y-1 text-mocha-subtext">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('couriers.store') }}" method="POST" class="space-y-5 rounded-2xl border border-mocha-overlay bg-mocha-surface p-5 shadow-xl shadow-black/10 sm:p-7">
        @csrf

        <div class="grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="name" class="mb-2 block text-xs font-medium text-mocha-subtext">Courier name <span class="text-lrx-red">*</span></label>
                <input id="name" name="name" value="{{ old('name') }}" required maxlength="255" autocomplete="name" placeholder="Name or courier service" class="w-full rounded-xl border border-mocha-border bg-mocha-mantle px-3.5 py-3 text-sm text-mocha-text outline-none transition placeholder:text-mocha-muted focus:border-lrx-cyan focus:ring-2 focus:ring-lrx-cyan/10">
                @error('name')<p class="mt-1.5 text-xs text-lrx-red">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="phone" class="mb-2 block text-xs font-medium text-mocha-subtext">Phone <span class="text-lrx-red">*</span></label>
                <input id="phone" name="phone" value="{{ old('phone') }}" required maxlength="20" inputmode="tel" autocomplete="tel" placeholder="08xxxxxxxxxx" class="w-full rounded-xl border border-mocha-border bg-mocha-mantle px-3.5 py-3 text-sm text-mocha-text outline-none transition placeholder:text-mocha-muted focus:border-lrx-cyan focus:ring-2 focus:ring-lrx-cyan/10">
                @error('phone')<p class="mt-1.5 text-xs text-lrx-red">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="vehicle_type" class="mb-2 block text-xs font-medium text-mocha-subtext">Vehicle type <span class="text-mocha-muted">(Optional)</span></label>
                <input id="vehicle_type" name="vehicle_type" value="{{ old('vehicle_type') }}" maxlength="100" placeholder="Motorcycle, van, truck..." class="w-full rounded-xl border border-mocha-border bg-mocha-mantle px-3.5 py-3 text-sm text-mocha-text outline-none transition placeholder:text-mocha-muted focus:border-lrx-cyan focus:ring-2 focus:ring-lrx-cyan/10">
                @error('vehicle_type')<p class="mt-1.5 text-xs text-lrx-red">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="vehicle_number" class="mb-2 block text-xs font-medium text-mocha-subtext">Vehicle plate <span class="text-mocha-muted">(Optional)</span></label>
                <input id="vehicle_number" name="vehicle_number" value="{{ old('vehicle_number') }}" maxlength="50" placeholder="B 1234 XYZ" class="w-full rounded-xl border border-mocha-border bg-mocha-mantle px-3.5 py-3 text-sm text-mocha-text outline-none transition placeholder:text-mocha-muted focus:border-lrx-cyan focus:ring-2 focus:ring-lrx-cyan/10">
                @error('vehicle_number')<p class="mt-1.5 text-xs text-lrx-red">{{ $message }}</p>@enderror
            </div>
        </div>

        <label for="is_active" class="flex items-start gap-3 rounded-xl border border-mocha-overlay bg-mocha-mantle/60 p-4">
            <input type="hidden" name="is_active" value="0">
            <input id="is_active" type="checkbox" name="is_active" value="1" @checked(old('is_active', '1') === '1') class="mt-0.5 h-4 w-4 rounded border-mocha-border bg-mocha-overlay text-lrx-cyan focus:ring-lrx-cyan">
            <span>
                <span class="block text-xs font-medium text-mocha-text">Active courier</span>
                <span class="mt-1 block text-[10px] text-mocha-muted">Active couriers can be selected when creating or editing a shipment.</span>
            </span>
        </label>

        <div class="flex flex-wrap justify-end gap-3 border-t border-mocha-overlay pt-5">
            <a href="{{ route('couriers.index') }}" class="rounded-xl border border-mocha-border px-4 py-2.5 text-xs text-mocha-subtext transition hover:bg-mocha-overlay hover:text-mocha-text">Cancel</a>
            <button type="submit" class="lrx-button-primary rounded-xl px-5 py-2.5 text-xs font-semibold transition">Save Courier</button>
        </div>
    </form>
</div>
@endsection
