<?php

namespace App\Services;

use App\DTOs\Registrasi\RegionData;
use App\Enums\Patient\RegionLevel;
use App\Exceptions\RegionApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Data wilayah dari Wilayah.id (https://wilayah.id), dengan cache.
 *
 * - Cache segar (config wilayah.cache_ttl) mengurangi request ke API publik.
 * - Salinan "stale" tanpa kedaluwarsa dipakai bila Wilayah.id tidak bisa dihubungi,
 *   jadi pendaftaran pasien tetap jalan untuk wilayah yang pernah dimuat.
 */
final class RegionService
{
    /** @return Collection<int, RegionData> */
    public function provinces(): Collection
    {
        return $this->children(RegionLevel::Province, null);
    }

    /** @return Collection<int, RegionData> */
    public function regencies(string $provinceCode): Collection
    {
        return $this->children(RegionLevel::Regency, $provinceCode);
    }

    /** @return Collection<int, RegionData> */
    public function districts(string $regencyCode): Collection
    {
        return $this->children(RegionLevel::District, $regencyCode);
    }

    /** @return Collection<int, RegionData> */
    public function villages(string $districtCode): Collection
    {
        return $this->children(RegionLevel::Village, $districtCode);
    }

    /**
     * Apakah $code adalah anak sah dari $parentCode pada level ini?
     * Dipakai validasi agar kecamatan harus benar-benar milik kabupaten yang dipilih, dst.
     */
    public function has(RegionLevel $level, string $code, ?string $parentCode = null): bool
    {
        return $this->children($level, $parentCode)
            ->contains(fn (RegionData $region) => $region->code === $code);
    }

    /**
     * Nama keempat level untuk disimpan sebagai snapshot di data pasien.
     * Kode sudah divalidasi sebelumnya, jadi daftar yang dipakai biasanya sudah ada di cache.
     *
     * @return array{province_name: ?string, regency_name: ?string, district_name: ?string, village_name: ?string}
     */
    public function resolveNames(string $province, string $regency, string $district, string $village): array
    {
        return [
            'province_name' => $this->nameOf(RegionLevel::Province, $province, null),
            'regency_name' => $this->nameOf(RegionLevel::Regency, $regency, $province),
            'district_name' => $this->nameOf(RegionLevel::District, $district, $regency),
            'village_name' => $this->nameOf(RegionLevel::Village, $village, $district),
        ];
    }

    private function nameOf(RegionLevel $level, string $code, ?string $parentCode): ?string
    {
        return $this->children($level, $parentCode)->firstWhere('code', $code)?->name;
    }

    /** @return Collection<int, RegionData> */
    private function children(RegionLevel $level, ?string $parentCode): Collection
    {
        $pattern = $level->parentPattern();

        if ($pattern !== null && ($parentCode === null || ! preg_match($pattern, $parentCode))) {
            return collect();
        }

        $rows = $this->load($level->path($parentCode), 'regions:' . $level->value . ':' . ($parentCode ?? 'all'));

        return collect($rows)
            ->map(fn (array $row) => RegionData::fromArray($row))
            ->sortBy(fn (RegionData $region) => $region->name, SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    /** @return list<array{code: string, name: string}> */
    private function load(string $path, string $cacheKey): array
    {
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $items = $this->request($path);
        } catch (RegionApiException $e) {
            $stale = Cache::get("{$cacheKey}:stale");
            if ($stale !== null) {
                Log::warning('Wilayah.id tidak tersedia, memakai cache lama', ['path' => $path]);

                return $stale;
            }

            throw $e;
        }

        // Hasil kosong (kode tidak dikenal) tidak di-cache agar cache tidak bisa
        // dipenuhi oleh kode sembarang.
        if ($items !== []) {
            Cache::put($cacheKey, $items, config('wilayah.cache_ttl'));
            Cache::forever("{$cacheKey}:stale", $items);
        }

        return $items;
    }

    /** @return list<array{code: string, name: string}> */
    private function request(string $path): array
    {
        try {
            $response = Http::baseUrl(rtrim(config('wilayah.base_url'), '/') . '/')
                ->acceptJson()
                ->timeout(config('wilayah.timeout'))
                ->retry(2, 200, function (Throwable $e) {
                    // 404 = kode tidak ada; tidak perlu diulang.
                    return ! ($e instanceof RequestException && $e->response->status() === 404);
                }, throw: false)
                ->get($path);
        } catch (ConnectionException $e) {
            throw RegionApiException::unavailable($e);
        }

        if ($response->status() === 404) {
            return [];
        }

        if ($response->failed()) {
            throw RegionApiException::unavailable();
        }

        return $response->json('data', []);
    }
}
