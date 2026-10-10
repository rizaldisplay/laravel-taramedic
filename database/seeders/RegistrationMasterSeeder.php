<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\Poli;
use App\Models\ServiceLocation;
use Illuminate\Database\Seeder;

/** DATA CONTOH: ganti dengan poli, dokter, dan lokasi layanan yang sebenarnya. */
class RegistrationMasterSeeder extends Seeder
{
    public function run(): void
    {
        $doctors = [
            'Poli Umum' => ['dr. Andi Wijaya', 'dr. Sari Lestari'],
            'Poli Anak' => ['dr. Budi Santoso, Sp.A', 'dr. Maya Putri, Sp.A'],
            'Poli Gigi' => ['drg. Rina Kusuma'],
            'Poli KIA' => ['dr. Dewi Anggraini, Sp.OG'],
        ];

        foreach ($doctors as $poliName => $names) {
            $poli = Poli::firstOrCreate(['name' => $poliName]);
            foreach ($names as $name) {
                Doctor::firstOrCreate(['name' => $name, 'poli_id' => $poli->id]);
            }
        }

        foreach (['Gedung A Lantai 1', 'Gedung A Lantai 2', 'Gedung B Lantai 1'] as $name) {
            ServiceLocation::firstOrCreate(['name' => $name]);
        }
    }
}
