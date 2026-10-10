<?php

namespace App\Enums\Patient;

/** Nilai enum = nama segmen endpoint Wilayah.id untuk mengambil daftar level tersebut. */
enum RegionLevel: string
{
    case Province = 'provinces';
    case Regency = 'regencies';
    case District = 'districts';
    case Village = 'villages';

    /**
     * Pola kode INDUK yang dibutuhkan untuk mengambil daftar level ini.
     * Juga mencegah input pengguna menyusup ke path URL (mis. "../").
     */
    public function parentPattern(): ?string
    {
        return match ($this) {
            self::Province => null,
            self::Regency => '/^\d{2}$/',
            self::District => '/^\d{2}\.\d{2}$/',
            self::Village => '/^\d{2}\.\d{2}\.\d{2}$/',
        };
    }

    /** Path relatif terhadap base URL Wilayah.id. */
    public function path(?string $parentCode): string
    {
        return $this === self::Province
            ? 'provinces.json'
            : "{$this->value}/{$parentCode}.json";
    }
}
