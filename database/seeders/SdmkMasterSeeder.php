<?php

namespace Database\Seeders;

use App\Models\Facility;
use App\Models\Poli;
use App\Models\Room;
use App\Models\SdmkReference;
use Illuminate\Database\Seeder;

/**
 * DATA CONTOH untuk pengembangan. Ganti dengan referensi resmi (KKI/Kemenkes, Permenkes)
 * dan nama fasyankes/poli/ruangan milik Anda sebelum dipakai produksi.
 */
class SdmkMasterSeeder extends Seeder
{
    public function run(): void
    {
        $refs = [
            'jenis_sdmk' => [['tenaga_medis', 'Tenaga Medis'], ['tenaga_keperawatan', 'Tenaga Keperawatan'], ['tenaga_kefarmasian', 'Tenaga Kefarmasian'], ['tenaga_kesehatan_lain', 'Tenaga Kesehatan Lainnya']],
            'kompetensi_utama' => [['pelayanan_umum', 'Pelayanan Umum'], ['pelayanan_spesialistik', 'Pelayanan Spesialistik'], ['asuhan_keperawatan', 'Asuhan Keperawatan'], ['pelayanan_kefarmasian', 'Pelayanan Kefarmasian']],
            'spesialisasi' => [['umum', 'Umum'], ['penyakit_dalam', 'Penyakit Dalam'], ['anak', 'Anak'], ['kandungan', 'Obstetri & Ginekologi']],
            'tingkat_pendidikan' => [['d3', 'D3'], ['s1', 'S1 / Profesi'], ['s2', 'S2'], ['sp1', 'Spesialis (Sp-1)']],
            'organisasi_str' => [['kki', 'Konsil Kesehatan Indonesia (KKI)'], ['mtki', 'MTKI (lama)']],
            'jenis_sip' => [['sip_dokter', 'SIP Dokter'], ['sip_perawat', 'SIP Perawat'], ['sip_apoteker', 'SIPA']],
            'peran_unit' => [['dpjp', 'DPJP'], ['pelaksana', 'Pelaksana'], ['koordinator', 'Koordinator']],
            'jabatan' => [['kepala_unit', 'Kepala Unit'], ['staf', 'Staf']],
            'tipe_jadwal' => [['tetap_mingguan', 'Tetap Mingguan'], ['bergilir', 'Bergilir']],
            'jenis_pasien' => [['umum', 'Umum'], ['bpjs', 'BPJS'], ['asuransi', 'Asuransi']],
            'pola_jadwal' => [['setiap_minggu', 'Setiap Minggu'], ['minggu_ganjil', 'Minggu Ganjil'], ['minggu_genap', 'Minggu Genap']],
            'mapping_spesialisasi' => [['umum', 'Dokter Umum'], ['sp_pd', 'Sp.PD'], ['sp_a', 'Sp.A']],
            'tipe_praktisi' => [['dokter', 'Dokter'], ['perawat', 'Perawat'], ['apoteker', 'Apoteker']],
        ];

        foreach ($refs as $type => $rows) {
            foreach ($rows as $i => [$value, $label]) {
                SdmkReference::updateOrCreate(['type' => $type, 'value' => $value], ['label' => $label, 'sort' => $i]);
            }
        }

        // Profesi bergantung pada jenis SDMK; sub spesialisasi pada spesialisasi.
        foreach ([
            ['dokter_umum', 'Dokter Umum', 'tenaga_medis', 'DR-UM'],
            ['dokter_spesialis', 'Dokter Spesialis', 'tenaga_medis', 'DR-SP'],
            ['perawat', 'Perawat', 'tenaga_keperawatan', 'PRW'],
            ['apoteker', 'Apoteker', 'tenaga_kefarmasian', 'APT'],
        ] as $i => [$v, $l, $parent, $code]) {
            SdmkReference::updateOrCreate(['type' => 'profesi', 'value' => $v], ['label' => $l, 'parent_value' => $parent, 'code' => $code, 'sort' => $i]);
        }
        foreach ([['kardiologi', 'Kardiologi', 'penyakit_dalam'], ['gastro', 'Gastroenterohepatologi', 'penyakit_dalam'], ['neonatologi', 'Neonatologi', 'anak']] as $i => [$v, $l, $parent]) {
            SdmkReference::updateOrCreate(['type' => 'sub_spesialisasi', 'value' => $v], ['label' => $l, 'parent_value' => $parent, 'sort' => $i]);
        }

        $facility = Facility::firstOrCreate(['name' => 'Klinik Utama (contoh)']);
        foreach (['Poli Umum', 'Poli Anak', 'Poli Penyakit Dalam'] as $name) {
            $poli = Poli::updateOrCreate(['name' => $name], ['facility_id' => $facility->id, 'is_active' => true]);
            Room::firstOrCreate(['name' => 'Ruang ' . $name, 'poli_id' => $poli->id], ['facility_id' => $facility->id]);
        }
    }
}
