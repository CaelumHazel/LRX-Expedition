@csrf
<div class="grid gap-4 rounded-2xl border border-mocha-overlay bg-mocha-surface p-5 sm:grid-cols-2">
    <label class="text-xs text-mocha-subtext sm:col-span-2">Warehouse name
        <input name="name" value="{{ old('name', $warehouse->name) }}" required maxlength="255" placeholder="Warehouse A" class="mt-2 w-full rounded-xl border border-mocha-border bg-mocha-mantle px-3 py-2.5 text-sm text-mocha-text">
        @error('name')<span class="mt-1 block text-lrx-red">{{ $message }}</span>@enderror
    </label>
    <label class="text-xs text-mocha-subtext">Code
        <input name="code" value="{{ old('code', $warehouse->code) }}" required maxlength="20" placeholder="WH-A" class="mt-2 w-full rounded-xl border border-mocha-border bg-mocha-mantle px-3 py-2.5 text-sm text-mocha-text">
        @error('code')<span class="mt-1 block text-lrx-red">{{ $message }}</span>@enderror
    </label>
    <label class="text-xs text-mocha-subtext">City / regency
        <select name="city" required class="mt-2 w-full rounded-xl border border-mocha-border bg-mocha-mantle px-3 py-2.5 text-sm text-mocha-text">
            <option value="">Select location</option>
            @if ($warehouse->city && ! in_array($warehouse->city, array_column($cities, 'name'), true))
                <option value="{{ $warehouse->city }}" @selected(old('city', $warehouse->city) === $warehouse->city)>{{ $warehouse->city }} (existing)</option>
            @endif
            @foreach ($cities as $city)
                <option value="{{ $city['name'] }}" @selected(old('city', $warehouse->city) === $city['name'])>{{ $city['label'] }} · {{ $city['province_label'] }}</option>
            @endforeach
        </select>
        @error('city')<span class="mt-1 block text-lrx-red">{{ $message }}</span>@enderror
    </label>
    <label class="text-xs text-mocha-subtext sm:col-span-2">Full address
        <textarea name="address" rows="3" required maxlength="2000" placeholder="Street, building number, RT/RW, village, district, postal code" class="mt-2 w-full rounded-xl border border-mocha-border bg-mocha-mantle px-3 py-2.5 text-sm text-mocha-text">{{ old('address', $warehouse->address) }}</textarea>
        @error('address')<span class="mt-1 block text-lrx-red">{{ $message }}</span>@enderror
    </label>
    <label class="flex items-center gap-2 text-xs text-mocha-subtext sm:col-span-2">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $warehouse->is_active ?? true)) class="rounded border-mocha-border bg-mocha-mantle text-lrx-cyan">
        Active warehouse (available for new shipment assignments)
    </label>
</div>
<div class="flex justify-end gap-3">
    <a href="{{ route('warehouses.index') }}" class="rounded-xl border border-mocha-border px-4 py-2.5 text-xs text-mocha-subtext">Cancel</a>
    <button class="lrx-button-primary rounded-xl px-5 py-2.5 text-xs font-semibold">{{ $submitLabel }}</button>
</div>
