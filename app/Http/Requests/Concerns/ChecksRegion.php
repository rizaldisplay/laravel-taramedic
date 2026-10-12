<?php

namespace App\Http\Requests\Concerns;

use App\Enums\Patient\RegionLevel;
use App\Services\RegionService;
use Closure;

trait ChecksRegion
{
    /** Kode wilayah harus ada di Wilayah.id dan menjadi anak dari kode induk pada field $parentField. */
    protected function regionRule(RegionLevel $level, ?string $parentField = null): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($level, $parentField) {
            $parent = $parentField ? $this->input($parentField) : null;

            $valid = is_string($value)
                && ($parentField === null || is_string($parent))
                && app(RegionService::class)->has($level, $value, $parent);

            if (! $valid) {
                $fail(':attribute tidak valid atau tidak sesuai wilayah di atasnya.');
            }
        };
    }

    /** @return array<string, array<int, mixed>> aturan 4 level wilayah dengan nama field input. */
    protected function regionRules(string $prov, string $kab, string $kec, string $desa): array
    {
        return [
            $prov => ['required', $this->regionRule(RegionLevel::Province)],
            $kab => ['required', $this->regionRule(RegionLevel::Regency, $prov)],
            $kec => ['required', $this->regionRule(RegionLevel::District, $kab)],
            $desa => ['required', $this->regionRule(RegionLevel::Village, $kec)],
        ];
    }
}
