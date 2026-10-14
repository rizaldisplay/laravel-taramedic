<?php

namespace Database\Seeders;

use App\Models\Facility;
use App\Models\Poli;
use App\Models\ServiceLocation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

/**
 * DATA CONTOH (pengembangan): hierarki Gedung > Ruang. Ganti dengan lokasi sebenarnya.
 * Idempoten (kunci: kode). Lokasi lama dari RegistrationMasterSeeder tetap ada dan
 * otomatis mendapat kode LOK-<id> dari migration.
 *
 * php artisan db:seed --class=LokasiPelayananSeeder
 */
class LokasiPelayananSeeder extends Seeder
{
    public function run(): void
    {
        $facilityId = Schema::hasTable('facilities')
            ? Facility::firstOrCreate(['name' => 'Klinik Utama (contoh)'])->id
            : null;

        $gedung = ServiceLocation::updateOrCreate(
            ['kode' => 'GEDUNG-UTAMA'],
            ['name' => 'Gedung Utama', 'tipe' => 'Gedung', 'induk_id' => null, 'facility_id' => $facilityId, 'deskripsi' => 'Gedung pelayanan utama', 'is_active' => true],
        );

        foreach ([['RUANG-FARMASI', 'Ruang Farmasi'], ['RUANG-POLI-ANAK', 'Ruang Poli Anak']] as [$kode, $nama]) {
            ServiceLocation::updateOrCreate(
                ['kode' => $kode],
                ['name' => $nama, 'tipe' => 'Ruang', 'induk_id' => $gedung->id, 'facility_id' => $facilityId, 'is_active' => true,
                    // Ruang Poli Anak dikaitkan ke Poli Anak bila poli tersebut sudah ada (RegistrationMasterSeeder).
                    'poli_id' => $kode === 'RUANG-POLI-ANAK' ? Poli::where('name', 'Poli Anak')->value('id') : null],
            );
        }
    }
}
