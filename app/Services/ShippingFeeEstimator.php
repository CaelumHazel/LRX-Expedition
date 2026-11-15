<?php

namespace App\Services;

use App\Models\ShippingRate;
use App\Support\IndonesianCityCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ShippingFeeEstimator
{
    public function __construct(private readonly IndonesianCityCatalog $cities) {}

    public function estimate(
        string $origin,
        string $destination,
        string $cargoType,
        float $weight,
        ?int $courierId = null,
    ): array {
        $distanceKm = $this->cities->estimateDistanceKm($origin, $destination);
        $rate = $this->findRate($origin, $destination, $cargoType, $courierId);

        if ($rate) {
            return [
                'shipping_cost' => round(ceil($weight) * (float) $rate->price_per_kg, 2),
                'distance_km' => $distanceKm,
                'estimated_days' => $rate->estimated_days,
                'source' => 'configured',
            ];
        }

        return [
            'shipping_cost' => round(ceil($weight) * (5000 + ($distanceKm * 5)), 2),
            'distance_km' => $distanceKm,
            'estimated_days' => max(1, (int) ceil($distanceKm / 500)),
            'source' => 'dummy',
        ];
    }

    private function findRate(string $origin, string $destination, string $cargoType, ?int $courierId): ?ShippingRate
    {
        $query = ShippingRate::query()
            ->whereIn(DB::raw('LOWER(origin_city)'), array_map('mb_strtolower', $this->cities->equivalentNames($origin)))
            ->whereIn(DB::raw('LOWER(destination_city)'), array_map('mb_strtolower', $this->cities->equivalentNames($destination)))
            ->whereRaw('LOWER(cargo_type) = ?', [mb_strtolower($cargoType)])
            ->where('is_active', true);

        if ($courierId) {
            $query->where(function (Builder $builder) use ($courierId) {
                $builder->where('courier_id', $courierId)->orWhereNull('courier_id');
            })->orderByRaw('courier_id IS NULL');
        } else {
            $query->whereNull('courier_id');
        }

        return $query->first();
    }
}
