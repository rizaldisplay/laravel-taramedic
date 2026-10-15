<?php

namespace App\Http\Requests\Sdmk;

use App\Enums\SdmkReferenceType as T;
use App\Enums\StatusIzin;
use App\Http\Requests\Concerns\ChecksReference;
use App\Http\Requests\Concerns\ChecksRegion;
use Illuminate\Validation\Rule;

/** POST /sdmk/{sdmk}/sip dan PUT (via _method) /sdmk/{sdmk}/sip/{sip}. Multipart; berkas pada kunci "dokumen". */
class SipRequest extends SdmkRequest
{
    use ChecksReference, ChecksRegion;

    public function rules(): array
    {
        return [
            'nomorSip' => ['required', 'string', 'max:80'],
            'jenisSip' => ['required', $this->refRule(T::JenisSip)],
            'tanggalTerbit' => ['required', 'date_format:Y-m-d'],
            'berlakuHingga' => ['required', 'date_format:Y-m-d', 'after:tanggalTerbit'],
            'status' => ['required', Rule::enum(StatusIzin::class)],
            'tempatTerbit' => ['required', 'string', 'max:255'],
            'nomorSk' => ['nullable', 'string', 'max:80'],
            'tanggalSk' => ['nullable', 'date_format:Y-m-d'],
            'fasyankes' => ['required', Rule::exists('facilities', 'id')->where('is_active', true)],
            'poli' => ['required', $this->poliInFacilityRule('fasyankes')],
            'ruangan' => ['nullable', $this->locationRule('poli', 'fasyankes')],
            'alamatPraktik' => ['required', 'string', 'max:500'],
            ...$this->regionRules('provinsi', 'kabupatenKota', 'kecamatan', 'desaKelurahan'),
            'kodePos' => ['nullable', 'digits:5'],
            'telepon' => ['nullable', 'regex:/^[0-9+\-() ]{6,30}$/'],
            'emailFasilitas' => ['nullable', 'email', 'max:255'],
            // Validasi isi berkas berdasarkan MIME hasil deteksi server, bukan ekstensi dari pengguna.
            'dokumen' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:' . config('sdmk.max_file_kb')],
        ];
    }

    public function attributes(): array
    {
        return [
            'nomorSip' => 'Nomor SIP', 'jenisSip' => 'Jenis SIP', 'tanggalTerbit' => 'Tanggal terbit', 'berlakuHingga' => 'Berlaku hingga',
            'status' => 'Status SIP', 'tempatTerbit' => 'Tempat terbit', 'nomorSk' => 'No. SK', 'tanggalSk' => 'Tanggal SK',
            'fasyankes' => 'Fasyankes', 'poli' => 'Unit/poli', 'ruangan' => 'Ruangan', 'alamatPraktik' => 'Alamat praktik',
            'provinsi' => 'Provinsi', 'kabupatenKota' => 'Kabupaten/kota', 'kecamatan' => 'Kecamatan', 'desaKelurahan' => 'Desa/kelurahan',
            'kodePos' => 'Kode pos', 'telepon' => 'Telepon', 'emailFasilitas' => 'Email fasilitas', 'dokumen' => 'Berkas SIP',
        ];
    }

    /** @return array<string, mixed> kolom worker_sips tanpa nama wilayah dan tanpa berkas */
    public function attrs(): array
    {
        $v = $this->validated();

        return [
            'nomor_sip' => $v['nomorSip'], 'jenis_sip' => $v['jenisSip'],
            'tanggal_terbit' => $v['tanggalTerbit'], 'berlaku_hingga' => $v['berlakuHingga'], 'status' => $v['status'],
            'tempat_terbit' => $v['tempatTerbit'], 'nomor_sk' => $v['nomorSk'] ?? null, 'tanggal_sk' => $v['tanggalSk'] ?? null,
            'facility_id' => (int) $v['fasyankes'], 'poli_id' => (int) $v['poli'], 'service_location_id' => isset($v['ruangan']) ? (int) $v['ruangan'] : null,
            'alamat_praktik' => $v['alamatPraktik'],
            'province_code' => $v['provinsi'], 'regency_code' => $v['kabupatenKota'],
            'district_code' => $v['kecamatan'], 'village_code' => $v['desaKelurahan'],
            'kode_pos' => $v['kodePos'] ?? null, 'telepon' => $v['telepon'] ?? null, 'email_fasilitas' => $v['emailFasilitas'] ?? null,
        ];
    }
}
