<?php

namespace App\Http\Requests\Concerns;

use App\Enums\SdmkReferenceType;
use App\Models\Poli;
use App\Models\ServiceLocation;
use App\Models\SdmkReference;
use Closure;

trait ChecksReference
{
    /** Nilai harus ada di sdmk_references (aktif) dengan tipe tertentu, dan (opsional) turunan dari nilai pada field induk. */
    protected function refRule(SdmkReferenceType $type, ?string $parentField = null): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($type, $parentField) {
            $q = SdmkReference::where('type', $type->value)->where('value', $value)->where('is_active', true);

            if ($parentField) {
                $q->where('parent_value', $this->input($parentField));
            }

            if (! is_string($value) || ! $q->exists()) {
                $fail(':attribute tidak valid' . ($parentField ? ' atau tidak sesuai pilihan sebelumnya.' : '.'));
            }
        };
    }

    /** Poli harus milik fasyankes yang dipilih (bila poli tersebut terikat fasyankes). */
    protected function poliInFacilityRule(string $facilityField): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($facilityField) {
            $poli = Poli::where('is_active', true)->find($value);

            if (! $poli || ($poli->facility_id && (string) $poli->facility_id !== (string) $this->input($facilityField))) {
                $fail(':attribute tidak sesuai fasyankes yang dipilih.');
            }
        };
    }

    /**
     * Ruangan = lokasi pelayanan bertipe Ruang yang aktif. Bila ruangan terikat poli, harus poli yang dipilih;
     * bila terikat fasyankes, harus fasyankes yang dipilih.
     */
    protected function locationRule(string $poliField, string $facilityField): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($poliField, $facilityField) {
            $loc = ServiceLocation::where('is_active', true)->where('tipe', 'Ruang')->find($value);

            if (! $loc) {
                $fail(':attribute tidak ditemukan atau bukan ruangan aktif.');
            } elseif ($loc->poli_id && (string) $loc->poli_id !== (string) $this->input($poliField)) {
                $fail(':attribute tidak sesuai unit/poli yang dipilih.');
            } elseif ($loc->facility_id && (string) $loc->facility_id !== (string) $this->input($facilityField)) {
                $fail(':attribute tidak sesuai fasyankes yang dipilih.');
            }
        };
    }
}

