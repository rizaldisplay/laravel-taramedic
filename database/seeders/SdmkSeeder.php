<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SdmkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        // ==========================================
        // 1. SEED TABEL UTAMA (sdmks)
        // ==========================================
        $sdmkId1 = DB::table('sdmks')->insertGetId([
            'sdmk_id' => 'SDMK-00001',
            'nik' => '3271123456789012',
            'nama_lengkap' => 'Budi Santoso',
            'gelar_depan' => 'dr.',
            'gelar_belakang' => 'Sp.PD',
            'tempat_lahir' => 'Bandung',
            'tanggal_lahir' => '1980-01-10',
            'jenis_kelamin' => 'Laki-laki',
            'kewarganegaraan' => 'Indonesia',
            'no_hp' => '081512345678',
            'email' => 'budi.santoso@example.com',
            'alamat_lengkap' => 'Jl. Janti Barat Perum University No 17',
            'provinsi' => 'Jawa Timur',
            'kabupaten_kota' => 'Malang',
            
            'darurat_nama_kontak' => 'Siti Aisyah',
            'darurat_hubungan' => 'Istri',
            'darurat_no_hp' => '081298765432',

            'jenis_sdmk' => 'Tenaga Medis',
            'profesi' => 'Dokter',
            'pendidikan_terakhir' => 'Spesialis (Sp-1)',
            'kompetensi_utama' => 'Spesialis Penyakit Dalam',
            'status_sdmk' => 'Aktif',

            'str_nomor' => '123456/STR/2022',
            'str_tanggal_terbit' => '2022-12-12',
            'str_berlaku_hingga' => '2027-12-12',
            'str_organisasi_penerbit' => 'KKI',
            'str_status' => 'Valid',

            'jadwal_tipe' => 'Berulang (Mingguan)',
            'jadwal_kapasitas_per_hari' => 30,
            'jadwal_durasi_layanan' => '15 menit',
            'jadwal_status' => 'Aktif',
            'jadwal_jenis_pasien' => json_encode(['Umum', 'BPJS']),

            'ihs_nomor' => 'N10000012345',
            'ihs_nama' => 'Budi Santoso',
            'ihs_kesiapan_data' => 'Siap',

            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // ==========================================
        // 2. SEED TABEL RELASI: sdmk_sips
        // ==========================================
        DB::table('sdmk_sips')->insert([
            [
                'sdmk_id' => $sdmkId1,
                'nomor_sip' => '503/123/SIP/2023',
                'jenis_sip' => 'SIP Kesatu',
                'tanggal_terbit' => '2023-01-15',
                'berlaku_hingga' => '2027-12-12',
                'status_sip' => 'Aktif',
                'nama_fasilitas' => 'Klinik Taramedic Utama',
                'unit_poli' => 'Poli Penyakit Dalam',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        ]);

        // ==========================================
        // 3. SEED TABEL RELASI: sdmk_penempatans
        // ==========================================
        DB::table('sdmk_penempatans')->insert([
            [
                'sdmk_id' => $sdmkId1,
                'fasyankes' => 'Klinik Taramedic Utama',
                'unit_poli' => 'Poli Penyakit Dalam',
                'lokasi_ruangan' => 'Ruang Pemeriksaan 02',
                'peran_di_unit' => 'Dokter Penanggung Jawab',
                'jabatan' => 'Dokter',
                'status_penempatan' => 'Aktif',
                'tanggal_mulai' => '2024-01-01',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        ]);

        // ==========================================
        // 4. SEED TABEL RELASI: sdmk_jadwal_slots
        // ==========================================
        $jadwalSlots = [
            ['hari' => 'Senin', 'jam_mulai' => '08:00:00', 'jam_selesai' => '12:00:00'],
            ['hari' => 'Selasa', 'jam_mulai' => '13:00:00', 'jam_selesai' => '17:00:00'],
            ['hari' => 'Rabu', 'jam_mulai' => '08:00:00', 'jam_selesai' => '12:00:00'],
            ['hari' => 'Kamis', 'jam_mulai' => '13:00:00', 'jam_selesai' => '17:00:00'],
            ['hari' => 'Jumat', 'jam_mulai' => '08:00:00', 'jam_selesai' => '12:00:00'],
        ];

        foreach ($jadwalSlots as $slot) {
            DB::table('sdmk_jadwal_slots')->insert(array_merge($slot, [
                'sdmk_id' => $sdmkId1,
                'kapasitas_kuota' => 30,
                'kuota_online' => 20,
                'status_slot' => 'Aktif',
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }

        // ==========================================
        // 5. SEED TABEL RELASI: sdmk_kompetensi_tambahans
        // ==========================================
        DB::table('sdmk_kompetensi_tambahans')->insert([
            [
                'sdmk_id' => $sdmkId1,
                'nama_sertifikat' => 'Sertifikat ACLS',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        ]);
    }
}