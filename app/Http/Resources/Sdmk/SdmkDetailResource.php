<?php

namespace App\Http\Resources\Sdmk;

use App\Models\HealthWorker;
use App\Models\SdmkReference;
use App\Models\WorkerSip;
use App\Support\SdmkFiles;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin HealthWorker  (kontrak: SdmkDetail). Relasi dimuat oleh SdmkService::detail(). */
class SdmkDetailResource extends JsonResource
{
    public static $wrap = null;

    private const HARI = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

    public function toArray(Request $request): array
    {
        $w = $this->resource;
        $tz = config('sdmk.timezone');
        $d = fn ($date) => $date?->format('Y-m-d');
        $t = fn (?string $time) => $time ? substr($time, 0, 5) : null;

        return [
            'id' => $w->id,
            'sdmkId' => $w->sdmk_code,
            'identitas' => [
                'nik' => $w->nik,
                'namaLengkap' => $w->nama_lengkap,
                'gelarDepan' => $w->gelar_depan,
                'gelarBelakang' => $w->gelar_belakang,
                'tempatLahir' => $w->tempat_lahir,
                'tanggalLahir' => $d($w->tanggal_lahir),
                'jenisKelamin' => $w->jenis_kelamin->value,
                'kewarganegaraan' => $w->kewarganegaraan,
                'nomorHP' => $w->nomor_hp,
                'email' => $w->email,
                'alamatLengkap' => $w->alamat_lengkap,
                'provinsi' => $w->province_code,
                'kabupatenKota' => $w->regency_code,
                'kecamatan' => $w->district_code,
                'desaKelurahan' => $w->village_code,
                'rt' => $w->rt,
                'rw' => $w->rw,
                'kodePos' => $w->kode_pos,
                'namaKontakDarurat' => $w->nama_kontak_darurat,
                'hubunganKontakDarurat' => $w->hubungan_kontak_darurat,
                'nomorHPKontakDarurat' => $w->nomor_hp_kontak_darurat,
                'fotoUrl' => SdmkFiles::url('foto', $w->id, $w->foto_path),
            ],
            'profesi' => [
                'jenisSdmk' => $w->jenis_sdmk,
                'profesi' => $w->profesi,
                'kompetensiUtama' => $w->kompetensi_utama,
                'spesialisasi' => $w->spesialisasi,
                'subSpesialisasi' => $w->sub_spesialisasi,
                'tingkatPendidikan' => $w->tingkat_pendidikan,
                'institusi' => $w->institusi,
                'tahunLulus' => $w->tahun_lulus,
                'status' => $w->status->value,
                'kodeProfesi' => $w->profesi
                    ? (string) SdmkReference::where('type', 'profesi')->where('value', $w->profesi)->value('code')
                    : '',
            ],
            'kompetensi' => $w->competencies->map(fn ($k) => [
                'id' => $k->id,
                'nama' => $k->nama,
                'penyelenggara' => $k->penyelenggara,
                'tanggalPeroleh' => $d($k->tanggal_peroleh),
                'berlakuHingga' => $d($k->berlaku_hingga),
                'dokumenUrl' => SdmkFiles::url('kompetensi', $k->id, $k->document_path),
            ])->values(),
            'str' => $w->str_number ? [
                'nomorStr' => $w->str_number,
                'tanggalTerbit' => $d($w->str_issued_at),
                'berlakuHingga' => $d($w->str_expires_at),
                'organisasiPenerbit' => $w->str_issuer,
                'status' => $w->str_expires_at?->endOfDay()->isPast() ? 'Kedaluwarsa' : ($w->str_verified_at ? 'Valid' : 'Belum Diverifikasi'),
                'tanggalVerifikasi' => $w->str_verified_at ? $w->str_verified_at->timezone($tz)->format('d-m-Y H:i') . ' WIB' : null,
            ] : null,
            'sip' => $w->sips->map(fn (WorkerSip $s) => [
                'id' => $s->id,
                'nomorSip' => $s->nomor_sip,
                'jenisSip' => $s->jenis_sip,
                'tanggalTerbit' => $d($s->tanggal_terbit),
                'berlakuHingga' => $d($s->berlaku_hingga),
                'status' => $this->sipStatus($s),
                'tempatTerbit' => $s->tempat_terbit,
                'nomorSk' => $s->nomor_sk,
                'tanggalSk' => $d($s->tanggal_sk),
                'fasyankes' => (string) $s->facility_id,
                'poli' => (string) $s->poli_id,
                'ruangan' => $s->service_location_id ? (string) $s->service_location_id : null,
                'alamatPraktik' => $s->alamat_praktik,
                'provinsi' => $s->province_code,
                'kabupatenKota' => $s->regency_code,
                'kecamatan' => $s->district_code,
                'desaKelurahan' => $s->village_code,
                'kodePos' => $s->kode_pos,
                'telepon' => $s->telepon,
                'emailFasilitas' => $s->email_fasilitas,
                'dokumenUrl' => SdmkFiles::url('sip', $s->id, $s->document_path),
                'tempatPraktik' => trim(($s->facility?->name ?? '') . ' - ' . ($s->poli?->name ?? ''), ' -'),
                'wilayah' => $s->regency_name . ', ' . $s->province_name,
            ])->values(),
            'sertifikatLain' => (string) $w->other_certificates,
            'penempatan' => $w->placements->map(fn ($p) => [
                'id' => $p->id,
                'fasyankes' => (string) $p->facility_id,
                'poli' => (string) $p->poli_id,
                'ruangan' => (string) $p->service_location_id,
                'peran' => $p->peran,
                'jabatan' => $p->jabatan,
                'status' => $p->status,
                'tanggalMulai' => $d($p->tanggal_mulai),
                'tanggalBerakhir' => $d($p->tanggal_berakhir),
                'penanggungJawabUnit' => $p->is_unit_head,
                'catatan' => $p->catatan,
                'fasyankesNama' => (string) $p->facility?->name,
                'poliNama' => (string) $p->poli?->name,
                'ruanganNama' => (string) $p->location?->name,
            ])->values(),
            'jadwalSetting' => $w->schedule_type ? [
                'tipeJadwal' => $w->schedule_type,
                'kapasitasPerHari' => (string) $w->daily_capacity,
                'durasiLayanan' => (string) $w->service_duration,
                'jenisPasien' => $w->schedule_patient_types ?? [],
                'status' => $w->schedule_status,
            ] : null,
            'jadwalHari' => $w->scheduleDays
                ->sortBy(fn ($r) => array_search($r->hari, self::HARI, true))
                ->map(fn ($r) => [
                    'hari' => $r->hari,
                    'aktif' => $r->aktif,
                    'jamMulai' => $t($r->jam_mulai),
                    'jamSelesai' => $t($r->jam_selesai),
                    'durasiSlot' => $r->durasi_slot !== null ? (string) $r->durasi_slot : null,
                    'kuota' => $r->kuota !== null ? (string) $r->kuota : null,
                    'kuotaOnline' => $r->kuota_online !== null ? (string) $r->kuota_online : null,
                    'istirahatMulai' => $t($r->istirahat_mulai),
                    'istirahatSelesai' => $t($r->istirahat_selesai),
                    'jenisPasien' => $r->jenis_pasien ?? [],
                    'status' => $r->status,
                ])->values(),
            'slots' => $w->scheduleSlots->map(fn ($s) => [
                'id' => $s->id,
                'hari' => $s->hari,
                'jamMulai' => $t($s->jam_mulai),
                'jamSelesai' => $t($s->jam_selesai),
                'adaIstirahat' => $s->istirahat_mulai !== null,
                'istirahatMulai' => $t($s->istirahat_mulai),
                'istirahatSelesai' => $t($s->istirahat_selesai),
                'durasiLayanan' => (string) $s->durasi_layanan,
                'kuota' => (string) $s->kuota,
                'kuotaOnline' => (string) $s->kuota_online,
                'jenisPasien' => $s->jenis_pasien,
                'berlakuMulai' => $d($s->berlaku_mulai),
                'berlakuHingga' => $d($s->berlaku_hingga),
                'pola' => $s->pola,
                'status' => $s->status,
            ])->values(),
            'integrasi' => [
                'mappingSpesialisasi' => $w->mapping_specialty,
                'mappingTipePraktisi' => $w->mapping_practitioner_type,
                'statusIhs' => $w->ihs_status->value,
                'ihsNumber' => $w->ihs_number,
                'organizationId' => $w->organization_id,
                'terakhirSinkron' => $w->ihs_synced_at ? $w->ihs_synced_at->timezone($tz)->format('d-m-Y H:i') . ' WIB' : null,
                'logs' => $w->apiLogs->map(fn ($l) => [
                    'id' => $l->id,
                    'waktu' => $l->created_at->timezone($tz)->format('d-m-Y H:i') . ' WIB',
                    'aksi' => $l->aksi,
                    'status' => $l->status,
                    'pesan' => $l->pesan,
                ])->values(),
            ],
            'catatan' => (string) $w->notes,
            'statusIzin' => $w->license_status->value,
            'createdAt' => $w->created_at?->toIso8601String(),
            'updatedAt' => $w->updated_at?->toIso8601String(),
        ];
    }

    /** SIP lewat tanggal otomatis Tidak Aktif; yang hampir habis ditampilkan Akan Berakhir. */
    private function sipStatus(WorkerSip $s): string
    {
        if ($s->status === 'Tidak Aktif' || $s->berlaku_hingga->endOfDay()->isPast()) {
            return 'Tidak Aktif';
        }

        return $s->berlaku_hingga->lte(now()->addDays((int) config('sdmk.expiring_days'))) ? 'Akan Berakhir' : $s->status;
    }
}
