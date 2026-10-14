<?php

namespace Database\Seeders;

use App\Actions\Doctor\SyncDoctorsForWorkerAction;
use App\Actions\Masterdata\Sdmk\GenerateSdmkCodeAction;
use App\Actions\Masterdata\Sdmk\RecomputeLicenseStatusAction;
use App\Models\Doctor;
use App\Models\Facility;
use App\Models\HealthWorker;
use App\Models\Poli;
use App\Models\SdmkReference;
use App\Models\ServiceLocation;
use App\Models\WorkerPlacement;
use App\Models\WorkerScheduleDay;
use App\Models\WorkerSip;
use App\Services\RegionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

/**
 * DATA CONTOH (pengembangan saja) — SDMK lengkap (identitas, STR, SIP, penempatan, jadwal)
 * yang dihubungkan ke baris `doctors` dari RegistrationMasterSeeder.
 *
 * NIK di sini PALSU (diawali 99, bukan kode provinsi yang ada). Jangan dipakai di produksi.
 * Seeder ini sengaja menyertakan dua kasus untuk menguji aturan ketersediaan dokter:
 *   - dr. Sari Lestari   : SIP berakhir 20 hari lagi  -> izin "Akan Berakhir", MASIH bisa dipilih
 *   - dr. Maya Putri     : STR sudah kedaluwarsa       -> izin "Tidak Aktif", TIDAK muncul di pendaftaran
 * Idempoten: aman dijalankan berulang (kunci: NIK).
 *
 * php artisan db:seed --class=SdmkDoctorSeeder
 */
class SdmkDoctorSeeder extends Seeder
{
    private const FACILITY = 'Klinik Utama (contoh)';

    /** @return list<array<string, mixed>> */
    private function data(): array
    {
        return [
            ['nik' => '9900000000000001', 'nama' => 'Andi Wijaya', 'depan' => 'dr.', 'belakang' => null, 'jk' => 'Laki-laki',
                'profesi' => 'dokter_umum', 'jenis' => 'tenaga_medis', 'spes' => null, 'poli' => 'Poli Umum'],
            ['nik' => '9900000000000002', 'nama' => 'Sari Lestari', 'depan' => 'dr.', 'belakang' => null, 'jk' => 'Perempuan',
                'profesi' => 'dokter_umum', 'jenis' => 'tenaga_medis', 'spes' => null, 'poli' => 'Poli Umum', 'sipDays' => 20],
            ['nik' => '9900000000000003', 'nama' => 'Budi Santoso', 'depan' => 'dr.', 'belakang' => 'Sp.A', 'jk' => 'Laki-laki',
                'profesi' => 'dokter_spesialis', 'jenis' => 'tenaga_medis', 'spes' => 'anak', 'poli' => 'Poli Anak'],
            ['nik' => '9900000000000004', 'nama' => 'Maya Putri', 'depan' => 'dr.', 'belakang' => 'Sp.A', 'jk' => 'Perempuan',
                'profesi' => 'dokter_spesialis', 'jenis' => 'tenaga_medis', 'spes' => 'anak', 'poli' => 'Poli Anak', 'strExpired' => true],
            ['nik' => '9900000000000005', 'nama' => 'Rina Kusuma', 'depan' => 'drg.', 'belakang' => null, 'jk' => 'Perempuan',
                'profesi' => 'dokter_gigi', 'jenis' => 'tenaga_medis', 'spes' => null, 'poli' => 'Poli Gigi'],
            ['nik' => '9900000000000006', 'nama' => 'Dewi Anggraini', 'depan' => 'dr.', 'belakang' => 'Sp.OG', 'jk' => 'Perempuan',
                'profesi' => 'dokter_spesialis', 'jenis' => 'tenaga_medis', 'spes' => 'kandungan', 'poli' => 'Poli KIA'],
            // Bukan dokter: tidak dibuatkan baris `doctors` (jenis SDMK tidak termasuk config doctors.eligible_jenis_sdmk).
            ['nik' => '9900000000000007', 'nama' => 'Joko Prasetyo', 'depan' => null, 'belakang' => 'S.Kep., Ns.', 'jk' => 'Laki-laki',
                'profesi' => 'perawat', 'jenis' => 'tenaga_keperawatan', 'spes' => null, 'poli' => 'Poli Umum'],
        ];
    }

    public function run(): void
    {
        $this->call([RegistrationMasterSeeder::class, SdmkMasterSeeder::class]);

        // Profesi tambahan yang belum ada di SdmkMasterSeeder.
        SdmkReference::updateOrCreate(
            ['type' => 'profesi', 'value' => 'dokter_gigi'],
            ['label' => 'Dokter Gigi', 'parent_value' => 'tenaga_medis', 'code' => 'DR-GG', 'sort' => 5],
        );

        $facility = Facility::firstOrCreate(['name' => self::FACILITY]);
        $region = $this->regionNames();

        foreach ($this->data() as $i => $d) {
            $poli = $this->preparePoli($d['poli'], $facility);
            $room = ServiceLocation::updateOrCreate(
                ['name' => 'Ruang ' . $poli->name],
                [
                    'kode' => substr('RUANG-' . Str::upper(Str::slug($poli->name)), 0, 30),
                    'tipe' => 'Ruang',
                    'poli_id' => $poli->id,
                    'facility_id' => $facility->id,
                    'is_active' => true,
                ]
            );

            $worker = $this->seedWorker($d, $i + 1, $region);
            $this->seedStrAndSip($worker, $d, $facility, $poli, $room, $region);
            $this->seedPlacement($worker, $facility, $poli, $room);
            $this->seedSchedule($worker);
            $this->linkLegacyDoctor($worker, $poli);

            // Hitung status izin (memicu sinkronisasi doctors bila berubah), lalu sinkron eksplisit (idempoten).
            app(RecomputeLicenseStatusAction::class)->execute($worker->refresh());
            app(SyncDoctorsForWorkerAction::class)->execute($worker->refresh());
        }

        $this->command?->info('SDMK contoh dibuat & dihubungkan ke doctors. Cek: Doctor::available()->count().');
    }

    /** Poli dari seeder pendaftaran dilengkapi fasyankes (dan kode bila kolom Unit & Poli sudah ada). */
    private function preparePoli(string $name, Facility $facility): Poli
    {
        $poli = Poli::firstOrCreate(['name' => $name]);
        $poli->facility_id ??= $facility->id;

        if (Schema::hasColumn('polis', 'kode') && ! $poli->kode) {
            $poli->kode = 'POLI-' . $poli->id;
        }

        $poli->save();

        return $poli;
    }

    /** @param array<string, mixed> $d */
    private function seedWorker(array $d, int $n, array $region): HealthWorker
    {
        $existing = HealthWorker::where('nik', $d['nik'])->first();
        if ($existing) {
            return $existing;
        }

        // withoutEvents: observer sinkronisasi baru dijalankan setelah semua data lengkap (urutan terkendali).
        return HealthWorker::withoutEvents(fn () => DB::transaction(fn () => HealthWorker::create([
            'sdmk_code' => app(GenerateSdmkCodeAction::class)->execute(),
            'nik' => $d['nik'],
            'nama_lengkap' => $d['nama'],
            'gelar_depan' => $d['depan'],
            'gelar_belakang' => $d['belakang'],
            'tempat_lahir' => 'Jakarta',
            'tanggal_lahir' => now()->subYears(35 + $n)->toDateString(),
            'jenis_kelamin' => $d['jk'],
            'kewarganegaraan' => 'WNI',
            'nomor_hp' => '08120000' . str_pad((string) $n, 4, '0', STR_PAD_LEFT),
            'alamat_lengkap' => 'Jl. Contoh No. ' . $n,
            'province_code' => '31', 'regency_code' => '31.74', 'district_code' => '31.74.09', 'village_code' => '31.74.09.1002',
            ...$region,
            'nama_kontak_darurat' => 'Kontak Contoh ' . $n,
            'hubungan_kontak_darurat' => 'Kerabat',
            'nomor_hp_kontak_darurat' => '08130000' . str_pad((string) $n, 4, '0', STR_PAD_LEFT),
            'jenis_sdmk' => $d['jenis'],
            'profesi' => $d['profesi'],
            'kompetensi_utama' => $d['spes'] ? 'pelayanan_spesialistik' : ($d['jenis'] === 'tenaga_keperawatan' ? 'asuhan_keperawatan' : 'pelayanan_umum'),
            'spesialisasi' => $d['spes'],
            'tingkat_pendidikan' => $d['spes'] ? 'sp1' : ($d['jenis'] === 'tenaga_keperawatan' ? 'd3' : 's1'),
            'institusi' => 'Universitas Contoh',
            'tahun_lulus' => (string) (now()->year - 10),
            'status' => 'Aktif',
            'ihs_status' => 'belum',
        ])));
    }

    /** @param array<string, mixed> $d */
    private function seedStrAndSip(HealthWorker $w, array $d, Facility $facility, Poli $poli, ServiceLocation $room, array $region): void
    {
        $today = now();
        $strExpired = (bool) ($d['strExpired'] ?? false);

        $w->forceFill([
            'str_number' => 'STR-CONTOH-' . $w->sdmk_code,
            'str_issued_at' => $today->copy()->subYears($strExpired ? 6 : 2)->toDateString(),
            'str_expires_at' => $strExpired ? $today->copy()->subMonths(2)->toDateString() : $today->copy()->addYears(3)->toDateString(),
            'str_issuer' => 'kki',
            'str_verified_at' => $strExpired ? null : $today,
        ])->saveQuietly();

        $path = 'sdmk/seed/contoh-sip.txt';
        Storage::disk(config('sdmk.disk'))->put($path, 'DATA CONTOH untuk pengembangan - bukan dokumen resmi.');

        WorkerSip::firstOrCreate(
            ['health_worker_id' => $w->id, 'nomor_sip' => 'SIP/CONTOH/' . $w->sdmk_code],
            [
                'jenis_sip' => $d['jenis'] === 'tenaga_keperawatan' ? 'sip_perawat' : 'sip_dokter',
                'tanggal_terbit' => $today->copy()->subYear()->toDateString(),
                'berlaku_hingga' => $today->copy()->addDays($d['sipDays'] ?? 730)->toDateString(),
                'status' => 'Aktif',
                'tempat_terbit' => 'Dinas Kesehatan Kota (contoh)',
                'facility_id' => $facility->id, 'poli_id' => $poli->id, 'service_location_id' => $room->id,
                'alamat_praktik' => 'Jl. Klinik Contoh No. 1',
                'province_code' => '31', 'regency_code' => '31.74', 'district_code' => '31.74.09', 'village_code' => '31.74.09.1002',
                ...$region,
                'document_path' => $path,
            ],
        );
    }

    private function seedPlacement(HealthWorker $w, Facility $facility, Poli $poli, ServiceLocation $room): void
    {
        WorkerPlacement::withoutEvents(fn () => WorkerPlacement::firstOrCreate(
            ['health_worker_id' => $w->id, 'poli_id' => $poli->id],
            [
                'facility_id' => $facility->id, 'service_location_id' => $room->id,
                'peran' => $w->jenis_sdmk === 'tenaga_medis' ? 'dpjp' : 'pelaksana',
                'status' => 'Aktif',
                'tanggal_mulai' => now()->subYear()->toDateString(),
                'is_unit_head' => false,
            ],
        ));
    }

    private function seedSchedule(HealthWorker $w): void
    {
        $w->forceFill([
            'schedule_type' => 'tetap_mingguan', 'daily_capacity' => 30, 'service_duration' => 15,
            'schedule_patient_types' => ['umum', 'bpjs'], 'schedule_status' => 'Aktif',
        ])->saveQuietly();

        foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'] as $i => $hari) {
            $aktif = $i < 5;
            WorkerScheduleDay::updateOrCreate(['health_worker_id' => $w->id, 'hari' => $hari], [
                'aktif' => $aktif,
                'jam_mulai' => $aktif ? '08:00' : null, 'jam_selesai' => $aktif ? '14:00' : null,
                'durasi_slot' => $aktif ? 15 : null, 'kuota' => $aktif ? 30 : null, 'kuota_online' => $aktif ? 10 : null,
                'jenis_pasien' => $aktif ? ['umum', 'bpjs'] : [], 'status' => $aktif ? 'Aktif' : 'Tidak Aktif',
            ]);
        }
    }

    /**
     * Hubungkan dengan baris doctors dari RegistrationMasterSeeder. Pencocokan memakai nama
     * tampilan + poli HANYA karena kedua seeder berasal dari data contoh yang sama.
     * Untuk data nyata, gunakan `doctors:link-csv` (pemetaan eksplisit), bukan nama.
     */
    private function linkLegacyDoctor(HealthWorker $w, Poli $poli): void
    {
        if ($w->jenis_sdmk !== 'tenaga_medis' || Doctor::where('health_worker_id', $w->id)->where('poli_id', $poli->id)->exists()) {
            return;
        }

        Doctor::whereNull('health_worker_id')
            ->where('poli_id', $poli->id)
            ->where('name', $w->displayName())
            ->orderBy('id')
            ->first()
            ?->update(['health_worker_id' => $w->id]);
    }

    /** @return array{province_name: string, regency_name: string, district_name: string, village_name: string} */
    private function regionNames(): array
    {
        $names = [];
        try {
            $names = app(RegionService::class)->resolveNames('31', '31.74', '31.74.09', '31.74.09.1002');
        } catch (\Throwable) {
            // Wilayah.id tidak terjangkau saat seeding: pakai nama contoh (data tetap valid untuk uji).
        }

        return [
            'province_name' => $names['province_name'] ?? 'Provinsi (contoh)',
            'regency_name' => $names['regency_name'] ?? 'Kabupaten/Kota (contoh)',
            'district_name' => $names['district_name'] ?? 'Kecamatan (contoh)',
            'village_name' => $names['village_name'] ?? 'Kelurahan (contoh)',
        ];
    }
}
