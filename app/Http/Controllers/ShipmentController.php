<?php

namespace App\Http\Controllers;

use App\Http\Requests\Shipment\StoreShipmentRequest;
use App\Http\Requests\Shipment\UpdateShipmentRequest;
use App\Models\Courier;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\ShipmentPackage;
use App\Models\ShippingRate;
use App\Models\TrackingLog;
use App\Models\Warehouse;
use App\Support\TrackingBarcode;
use App\Support\IndonesianCityCatalog;
use App\Services\ShipmentInventoryService;
use App\Services\ShippingFeeEstimator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ShipmentController extends Controller
{
    private const STATUSES = [
        'pending',
        'picked_up',
        'in_transit',
        'at_warehouse',
        'out_for_delivery',
        'delivered',
        'cancelled',
    ];

    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:'.implode(',', self::STATUSES)],
            'cargo_type' => ['nullable', 'string', 'max:100'],
            'origin_city' => ['nullable', 'string', 'max:100'],
            'destination_city' => ['nullable', 'string', 'max:100'],
            'courier_id' => ['nullable', 'integer', 'exists:couriers,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $counts = Shipment::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $summary = [
            'total' => (int) $counts->sum(),
            'pending' => (int) ($counts['pending'] ?? 0),
            'in_transit' => (int) ($counts['in_transit'] ?? 0),
            'delivered' => (int) ($counts['delivered'] ?? 0),
        ];

        $query = Shipment::with(['sender', 'receiver', 'courier', 'warehouse']);

        if (! empty($filters['search'])) {
            $search = mb_strtolower($filters['search']);
            $pattern = '%'.$search.'%';

            $query->where(function (Builder $builder) use ($pattern) {
                $builder->whereRaw('LOWER(tracking_number) LIKE ?', [$pattern])
                    ->orWhereRaw('LOWER(sender_name) LIKE ?', [$pattern])
                    ->orWhereRaw('LOWER(receiver_name) LIKE ?', [$pattern])
                    ->orWhereHas('sender', fn (Builder $customers) => $customers
                        ->whereRaw('LOWER(name) LIKE ?', [$pattern]))
                    ->orWhereHas('receiver', fn (Builder $customers) => $customers
                        ->whereRaw('LOWER(name) LIKE ?', [$pattern]));
            });
        }

        foreach (['status', 'cargo_type', 'origin_city', 'destination_city', 'courier_id'] as $filter) {
            if (isset($filters[$filter]) && $filters[$filter] !== '') {
                $query->where($filter, $filters[$filter]);
            }
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $shipments = $query->latest()->paginate(15)->withQueryString();
        $cargoTypes = Shipment::query()->whereNotNull('cargo_type')
            ->distinct()->orderBy('cargo_type')->pluck('cargo_type');
        $originCities = Shipment::query()->whereNotNull('origin_city')
            ->distinct()->orderBy('origin_city')->pluck('origin_city');
        $destinationCities = Shipment::query()->whereNotNull('destination_city')
            ->distinct()->orderBy('destination_city')->pluck('destination_city');
        $couriers = Courier::query()->orderBy('name')->get(['id', 'name']);

        return view('shipments.index', compact(
            'shipments',
            'summary',
            'cargoTypes',
            'originCities',
            'destinationCities',
            'couriers'
        ));
    }

    public function create()
    {
        $couriers = Courier::where('is_active', true)->orderBy('name')->get();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();
        $cargoTypes = ShippingRate::where('is_active', true)
            ->orderBy('cargo_type')
            ->pluck('cargo_type')
            ->unique()
            ->values();
        $cargoTypes = $cargoTypes->merge(['regular', 'express', 'cargo'])->unique()->sort()->values();
        $cities = app(IndonesianCityCatalog::class)->all();

        return view('shipments.create', compact(
            'couriers',
            'warehouses',
            'cities',
            'cargoTypes'
        ));
    }

    public function edit(Shipment $shipment)
    {
        $shipment->load(['items.item', 'packages']);
        $couriers = Courier::where('is_active', true)->orderBy('name')->get();
        $warehouses = Warehouse::where('is_active', true)
            ->orWhereKey($shipment->warehouse_id)
            ->orderBy('name')
            ->get();
        $cities = app(IndonesianCityCatalog::class)->all();
        $cargoTypes = ShippingRate::where('is_active', true)
            ->orderBy('cargo_type')
            ->pluck('cargo_type')
            ->merge(['regular', 'express', 'cargo'])
            ->merge([$shipment->cargo_type])
            ->unique()
            ->sort()
            ->values();

        return view('shipments.edit', compact('shipment', 'couriers', 'warehouses', 'cities', 'cargoTypes'));
    }

    public function stock(Request $request)
    {
        $filters = $request->validate([
            'status' => ['nullable', 'in:active,completed'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $query = Shipment::with(['sender', 'receiver', 'items.item', 'packages'])
            ->where('status', '!=', 'cancelled');

        if (($filters['status'] ?? null) === 'active') {
            $query->where('status', '!=', 'delivered');
        } elseif (($filters['status'] ?? null) === 'completed') {
            $query->where('status', 'delivered');
        }

        if (! empty($filters['search'])) {
            $pattern = '%'.mb_strtolower($filters['search']).'%';
            $query->where(function (Builder $builder) use ($pattern) {
                $builder->whereRaw('LOWER(tracking_number) LIKE ?', [$pattern])
                    ->orWhereHas('items.item', fn (Builder $items) => $items
                        ->whereRaw('LOWER(name) LIKE ?', [$pattern]))
                    ->orWhereHas('packages', fn (Builder $packages) => $packages
                        ->whereRaw('LOWER(name) LIKE ?', [$pattern])
                        ->orWhereRaw('LOWER(description) LIKE ?', [$pattern]));
            });
        }

        $shipments = $query->latest()->paginate(20)->withQueryString();

        return view('shipments.stock', [
            'shipments' => $shipments,
            'activeCount' => Shipment::whereNotIn('status', ['delivered', 'cancelled'])->count(),
            'completedCount' => Shipment::where('status', 'delivered')->count(),
        ]);
    }

    public function estimate(Request $request, ShippingFeeEstimator $fees)
    {
        $cityNames = app(IndonesianCityCatalog::class)->names();
        $validated = $request->validate([
            'origin_city' => ['required', Rule::in($cityNames)],
            'destination_city' => ['required', Rule::in($cityNames)],
            'cargo_type' => ['required', 'string', 'max:100'],
            'total_weight' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'courier_id' => ['nullable', 'integer', 'exists:couriers,id'],
        ]);

        return response()->json($fees->estimate(
            $validated['origin_city'],
            $validated['destination_city'],
            $validated['cargo_type'],
            (float) $validated['total_weight'],
            isset($validated['courier_id']) ? (int) $validated['courier_id'] : null,
        ));
    }

    public function store(
        StoreShipmentRequest $request,
        ShipmentInventoryService $inventory,
        ShippingFeeEstimator $fees,
    )
    {
        $validated = $request->validated();

        $shipment = DB::transaction(function () use ($validated, $inventory, $fees) {
            $trackingNumber = $this->generateTrackingNumber();
            $estimate = $fees->estimate(
                $validated['origin_city'],
                $validated['destination_city'],
                $validated['cargo_type'],
                (float) $validated['total_weight'],
                isset($validated['courier_id']) ? (int) $validated['courier_id'] : null,
            );
            $sender = $this->resolveCustomer($validated, 'sender', '');
            $receiver = $this->resolveCustomer($validated, 'receiver', $validated['receiver_address']);
            $shippingCost = $estimate['shipping_cost'];

            if ($shippingCost > 9999999999.99) {
                throw ValidationException::withMessages([
                    'shipping_cost' => 'Ongkos kirim melebihi batas nilai yang dapat disimpan.',
                ]);
            }

            $warehouse = isset($validated['warehouse_id'])
                ? Warehouse::findOrFail($validated['warehouse_id'])
                : null;
            $position = $warehouse?->name ?? $validated['origin_city'];
            $shipment = Shipment::create([
                'tracking_number' => $trackingNumber,
                'sender_id' => $sender->id,
                'receiver_id' => $receiver->id,
                'sender_name' => $validated['sender_name'] ?? $sender->name,
                'sender_phone' => $validated['sender_phone'] ?? $sender->phone,
                'receiver_name' => $validated['receiver_name'] ?? $receiver->name,
                'receiver_phone' => $validated['receiver_phone'] ?? $receiver->phone,
                'courier_id' => $validated['courier_id'] ?? null,
                'warehouse_id' => $validated['warehouse_id'] ?? null,
                'cargo_type' => $validated['cargo_type'],
                'origin_city' => $validated['origin_city'],
                'destination_city' => $validated['destination_city'],
                'receiver_address' => $validated['receiver_address'],
                'current_position' => $position,
                'total_weight' => $validated['total_weight'],
                'shipping_cost' => $shippingCost,
                'declared_value' => $validated['declared_value'] ?? 0,
                'estimated_distance_km' => $estimate['distance_km'],
                'estimated_delivery_date' => now()->addDays($estimate['estimated_days'])->toDateString(),
                'status' => 'pending',
                'notes' => $validated['notes'] ?? null,
            ]);

            $quantities = [];
            foreach ($validated['items'] as $shipmentItem) {
                if (! empty($shipmentItem['item_id'])) {
                    $itemId = (int) $shipmentItem['item_id'];
                    $quantities[$itemId] = ($quantities[$itemId] ?? 0) + (int) $shipmentItem['quantity'];

                    continue;
                }

                ShipmentPackage::create([
                    'shipment_id' => $shipment->id,
                    'name' => $shipmentItem['name'],
                    'description' => $shipmentItem['description'] ?? null,
                    'quantity' => $shipmentItem['quantity'],
                ]);
            }
            ksort($quantities);

            foreach ($quantities as $itemId => $quantity) {
                $item = Item::query()
                    ->lockForUpdate()
                    ->findOrFail($itemId);

                ShipmentItem::create([
                    'shipment_id' => $shipment->id,
                    'item_id' => $item->id,
                    'quantity' => $quantity,
                    'weight' => (float) $item->weight * $quantity,
                ]);
            }

            if ($quantities !== []) {
                $inventory->reserve($shipment);
            }

            TrackingLog::create([
                'shipment_id' => $shipment->id,
                'status' => 'pending',
                'location' => $position,
                'description' => 'Shipment berhasil dibuat.',
                'tracked_at' => now(),
                'user_id' => auth()->id(),
                'source' => auth()->check() ? 'manual' : 'system',
            ]);

            return $shipment;
        });

        Cache::forget('dashboard.statistics');

        return redirect()
            ->route('shipments.show', $shipment)
            ->with('success', 'Shipment berhasil dibuat. Tracking Number: '.$shipment->tracking_number);
    }

    public function show(Shipment $shipment)
    {
        $shipment->load([
            'sender',
            'receiver',
            'courier',
            'warehouse',
            'items.item',
            'packages',
            'trackingLogs' => fn ($query) => $query->with('user')->latest('tracked_at'),
            'scheduledTrackingUpdates' => fn ($query) => $query->latest('scheduled_at'),
        ]);

        $barcode = app(TrackingBarcode::class)->render($shipment->tracking_number);

        return view('shipments.show', compact('shipment', 'barcode'));
    }

    public function update(UpdateShipmentRequest $request, Shipment $shipment, ShippingFeeEstimator $fees)
    {
        $validated = $request->validated();
        $estimate = $fees->estimate(
            $validated['origin_city'],
            $validated['destination_city'],
            $validated['cargo_type'],
            (float) $validated['total_weight'],
            isset($validated['courier_id']) ? (int) $validated['courier_id'] : null,
        );
        $sender = $this->resolveCustomer($validated, 'sender', '');
        $receiver = $this->resolveCustomer($validated, 'receiver', $validated['receiver_address']);
        $shippingCost = $estimate['shipping_cost'];
        $previousWarehouseId = $shipment->warehouse_id;
        $nextWarehouse = isset($validated['warehouse_id'])
            ? Warehouse::findOrFail($validated['warehouse_id'])
            : null;

        if ($shippingCost > 9999999999.99) {
            throw ValidationException::withMessages([
                'shipping_cost' => 'Ongkos kirim melebihi batas nilai yang dapat disimpan.',
            ]);
        }

        $shipment->update([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'sender_name' => $validated['sender_name'] ?? $sender->name,
            'sender_phone' => $validated['sender_phone'] ?? $sender->phone,
            'receiver_name' => $validated['receiver_name'] ?? $receiver->name,
            'receiver_phone' => $validated['receiver_phone'] ?? $receiver->phone,
            'courier_id' => $validated['courier_id'] ?? null,
            'warehouse_id' => $validated['warehouse_id'] ?? null,
            'cargo_type' => $validated['cargo_type'],
            'origin_city' => $validated['origin_city'],
            'destination_city' => $validated['destination_city'],
            'receiver_address' => $validated['receiver_address'],
            'total_weight' => $validated['total_weight'],
            'declared_value' => $validated['declared_value'] ?? 0,
            'shipping_cost' => $shippingCost,
            'estimated_distance_km' => $estimate['distance_km'],
            'estimated_delivery_date' => now()->addDays($estimate['estimated_days'])->toDateString(),
            'notes' => $validated['notes'] ?? null,
        ]);

        if ($previousWarehouseId !== $nextWarehouse?->id) {
            $location = $nextWarehouse?->name ?? $validated['origin_city'];
            $shipment->update(['current_position' => $location]);
            TrackingLog::create([
                'shipment_id' => $shipment->id,
                'status' => $shipment->status,
                'location' => $location,
                'description' => $nextWarehouse
                    ? 'Shipment dipindahkan ke '.$nextWarehouse->name.'.'
                    : 'Penugasan warehouse shipment dihapus.',
                'tracked_at' => now(),
                'user_id' => auth()->id(),
                'source' => 'manual',
            ]);
        }

        if (array_key_exists('packages', $validated)) {
            $shipment->packages()->delete();

            foreach ($validated['packages'] as $package) {
                if (filled($package['name'] ?? null)) {
                    $shipment->packages()->create([
                        'name' => $package['name'],
                        'description' => $package['description'] ?? null,
                        'quantity' => $package['quantity'],
                    ]);
                }
            }
        }

        Cache::forget('dashboard.statistics');

        return redirect()->route('shipments.show', $shipment)->with('success', 'Shipment berhasil diperbarui.');
    }

    public function destroy(Shipment $shipment, ShipmentInventoryService $inventory)
    {
        DB::transaction(function () use ($shipment, $inventory) {
            $shipment = Shipment::query()->lockForUpdate()->findOrFail($shipment->id);

            if ($shipment->inventory_reserved_at && $shipment->status !== 'cancelled') {
                $inventory->restore($shipment, 'deleting');
            }

            $shipment->delete();
        });

        Cache::forget('dashboard.statistics');

        return redirect()->route('shipments.index')->with('success', 'Shipment berhasil dihapus.');
    }

    private function generateTrackingNumber(): string
    {
        $now = now();
        $date = $now->toDateString();
        $dateCode = $now->format('Ymd');

        if (! DB::table('tracking_number_sequences')->where('sequence_date', $date)->exists()) {
            $lastExistingNumber = Shipment::query()
                ->where('tracking_number', 'like', 'LRX'.$dateCode.'%')
                ->orderByDesc('tracking_number')
                ->value('tracking_number');
            $lastSequence = 0;

            if ($lastExistingNumber
                && preg_match('/^LRX'.$dateCode.'(\d{4})$/', $lastExistingNumber, $matches)) {
                $lastSequence = (int) $matches[1];
            }

            DB::table('tracking_number_sequences')->insertOrIgnore([
                'sequence_date' => $date,
                'last_sequence' => $lastSequence,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $sequence = DB::table('tracking_number_sequences')
            ->where('sequence_date', $date)
            ->lockForUpdate()
            ->value('last_sequence');

        if ($sequence === null || (int) $sequence >= 9999) {
            throw new RuntimeException('Nomor resi harian telah mencapai batas maksimum.');
        }

        $next = (int) $sequence + 1;

        DB::table('tracking_number_sequences')
            ->where('sequence_date', $date)
            ->update([
                'last_sequence' => $next,
                'updated_at' => now(),
            ]);

        return 'LRX'.$dateCode.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function resolveCustomer(array $data, string $party, string $address): Customer
    {
        if (! empty($data["{$party}_id"])) {
            return Customer::findOrFail($data["{$party}_id"]);
        }

        return Customer::firstOrCreate(
            ['phone' => $data["{$party}_phone"]],
            [
                'name' => $data["{$party}_name"],
                'address' => $address,
            ]
        );
    }
}
