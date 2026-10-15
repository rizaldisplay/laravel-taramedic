<?php

namespace App\Services\Sdmk;

use App\Enums\SdmkReferenceType as T;
use App\Models\Facility;
use App\Models\Poli;
use App\Models\ServiceLocation;
use App\Models\SdmkReference;
use Illuminate\Support\Collection;

/** Semua pilihan dropdown SDMK (bentuk sesuai SdmkOptions di frontend). */
final class SdmkOptionsService
{
    /** @return array<string, mixed> */
    public function all(): array
    {
        $refs = SdmkReference::where('is_active', true)->orderBy('sort')->orderBy('label')->get()->groupBy('type');
        $plain = fn (T $t) => $this->plain($refs->get($t->value, collect()));

        return [
            'jenisSdmk' => $plain(T::JenisSdmk),
            'profesi' => ($refs->get(T::Profesi->value) ?? collect())->map(fn ($r) => [
                'value' => $r->value, 'label' => $r->label, 'jenisSdmk' => (string) $r->parent_value, 'kode' => (string) $r->code,
            ])->values(),
            'kompetensiUtama' => $plain(T::KompetensiUtama),
            'spesialisasi' => $plain(T::Spesialisasi),
            'subSpesialisasi' => ($refs->get(T::SubSpesialisasi->value) ?? collect())->map(fn ($r) => [
                'value' => $r->value, 'label' => $r->label, 'spesialisasi' => (string) $r->parent_value,
            ])->values(),
            'tingkatPendidikan' => $plain(T::TingkatPendidikan),
            'kewarganegaraan' => config('sdmk.kewarganegaraan'),
            'hubungan' => collect(config('sdmk.hubungan'))->map(fn ($v) => ['value' => $v, 'label' => $v])->values(),
            'organisasiPenerbitStr' => $plain(T::OrganisasiStr),
            'jenisSip' => $plain(T::JenisSip),
            'fasyankes' => Facility::where('is_active', true)->orderBy('name')->get()
                ->map(fn ($f) => ['value' => (string) $f->id, 'label' => $f->name])->values(),
            'poli' => Poli::where('is_active', true)->orderBy('name')->get()
                ->map(fn ($p) => ['value' => (string) $p->id, 'label' => $p->name, 'fasyankes' => $p->facility_id ? (string) $p->facility_id : null])->values(),
            // Ruangan = lokasi pelayanan bertipe Ruang (service_locations); poli bersifat opsional.
            'ruangan' => ServiceLocation::where('is_active', true)->where('tipe', 'Ruang')->orderBy('name')->get()
                ->map(fn ($l) => ['value' => (string) $l->id, 'label' => $l->name, 'poli' => $l->poli_id ? (string) $l->poli_id : null])->values(),
            'peranUnit' => $plain(T::PeranUnit),
            'jabatan' => $plain(T::Jabatan),
            'tipeJadwal' => $plain(T::TipeJadwal),
            'durasiLayanan' => collect(config('sdmk.durasi_layanan'))->map(fn ($m) => ['value' => (string) $m, 'label' => "{$m} menit"])->values(),
            'jenisPasien' => $plain(T::JenisPasien),
            'polaJadwal' => $plain(T::PolaJadwal),
            'mappingSpesialisasi' => $plain(T::MappingSpesialisasi),
            'tipePraktisi' => $plain(T::TipePraktisi),
        ];
    }

    /** @param Collection<int, SdmkReference> $rows */
    private function plain(Collection $rows): Collection
    {
        return $rows->map(fn ($r) => ['value' => $r->value, 'label' => $r->label])->values();
    }
}
