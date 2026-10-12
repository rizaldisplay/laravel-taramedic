<?php

namespace App\Http\Requests\Sdmk;

use App\Enums\SdmkReferenceType as T;
use App\Enums\StatusAktif;
use App\Http\Requests\Concerns\ChecksReference;
use Illuminate\Validation\Rule;

class PenempatanRequest extends SdmkRequest
{
    use ChecksReference;

    public function rules(): array
    {
        return [
            'fasyankes' => ['required', Rule::exists('facilities', 'id')->where('is_active', true)],
            'poli' => ['required', $this->poliInFacilityRule('fasyankes')],
            'ruangan' => ['required', $this->roomInPoliRule('poli')],
            'peran' => ['required', $this->refRule(T::PeranUnit)],
            'jabatan' => ['nullable', $this->refRule(T::Jabatan)],
            'status' => ['required', Rule::enum(StatusAktif::class)],
            'tanggalMulai' => ['required', 'date_format:Y-m-d'],
            'tanggalBerakhir' => ['nullable', 'date_format:Y-m-d', 'after:tanggalMulai'],
            'penanggungJawabUnit' => ['required', 'boolean'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return ['fasyankes' => 'Fasyankes', 'poli' => 'Unit/poli', 'ruangan' => 'Lokasi/ruangan', 'peran' => 'Peran',
            'jabatan' => 'Jabatan', 'status' => 'Status', 'tanggalMulai' => 'Tanggal mulai', 'tanggalBerakhir' => 'Tanggal berakhir',
            'penanggungJawabUnit' => 'Penanggung jawab unit', 'catatan' => 'Catatan'];
    }

    /** @return array<string, mixed> */
    public function attrs(): array
    {
        $v = $this->validated();

        return [
            'facility_id' => (int) $v['fasyankes'], 'poli_id' => (int) $v['poli'], 'room_id' => (int) $v['ruangan'],
            'peran' => $v['peran'], 'jabatan' => $v['jabatan'] ?? null, 'status' => $v['status'],
            'tanggal_mulai' => $v['tanggalMulai'], 'tanggal_berakhir' => $v['tanggalBerakhir'] ?? null,
            'is_unit_head' => (bool) $v['penanggungJawabUnit'], 'catatan' => $v['catatan'] ?? null,
        ];
    }
}
