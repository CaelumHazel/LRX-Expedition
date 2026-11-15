<?php

namespace App\Support;

use RuntimeException;

class IndonesianCityCatalog
{
    private const LEGACY_ALIASES = [
        'JAKARTA' => 'KOTA JAKARTA SELATAN',
        'BANDUNG' => 'KOTA BANDUNG',
        'DEPOK' => 'KOTA DEPOK',
    ];

    private ?array $cities = null;

    public function all(): array
    {
        if ($this->cities !== null) {
            return $this->cities;
        }

        $path = database_path('data/indonesia-cities.json');
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException('The Indonesia city catalog could not be read.');
        }

        $cities = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($cities)) {
            throw new RuntimeException('The Indonesia city catalog has an invalid format.');
        }

        $this->cities = array_map(fn (array $city) => $city + [
            'label' => mb_convert_case(mb_strtolower($city['name']), MB_CASE_TITLE, 'UTF-8'),
            'province_label' => mb_convert_case(mb_strtolower($city['province_name']), MB_CASE_TITLE, 'UTF-8'),
        ], $cities);

        return $this->cities;
    }

    public function names(): array
    {
        return array_merge(array_column($this->all(), 'name'), array_keys(self::LEGACY_ALIASES));
    }

    public function equivalentNames(string $name): array
    {
        $canonicalName = $this->canonicalName($name);
        $aliases = array_keys(array_filter(
            self::LEGACY_ALIASES,
            fn (string $target) => $target === $canonicalName
        ));

        return array_unique([$canonicalName, ...$aliases]);
    }

    public function canonicalName(string $name): string
    {
        $normalizedName = mb_strtoupper($name);

        return self::LEGACY_ALIASES[$normalizedName] ?? $name;
    }

    public function estimateDistanceKm(string $origin, string $destination): int
    {
        $originCity = $this->find($origin);
        $destinationCity = $this->find($destination);

        if (! $originCity || ! $destinationCity) {
            throw new RuntimeException('A city is missing from the Indonesia city catalog.');
        }

        if ($originCity['id'] === $destinationCity['id']) {
            return 25;
        }

        if ($originCity['province_id'] === $destinationCity['province_id']) {
            return 120;
        }

        if ($this->islandGroup($originCity['province_id']) === $this->islandGroup($destinationCity['province_id'])) {
            return 650;
        }

        return 1700;
    }

    private function find(string $name): ?array
    {
        $name = $this->canonicalName($name);

        foreach ($this->all() as $city) {
            if (mb_strtolower($city['name']) === mb_strtolower($name)) {
                return $city;
            }
        }

        return null;
    }

    private function islandGroup(string $provinceId): string
    {
        return match (true) {
            in_array($provinceId, ['11', '12', '13', '14', '15', '16', '17', '18', '19', '21'], true) => 'sumatra',
            in_array($provinceId, ['31', '32', '33', '34', '35', '36'], true) => 'java',
            in_array($provinceId, ['51', '52', '53'], true) => 'bali-nusa',
            in_array($provinceId, ['61', '62', '63', '64', '65'], true) => 'kalimantan',
            in_array($provinceId, ['71', '72', '73', '74', '75', '76'], true) => 'sulawesi',
            in_array($provinceId, ['81', '82'], true) => 'maluku',
            default => 'papua',
        };
    }
}
