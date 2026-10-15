<?php

namespace Database\Seeders;

use App\Enums\Masterdata\ProdukObat\StatusFormularium;
use App\Models\DrugProduct;
use Illuminate\Database\Seeder;

/**
 * DATA CONTOH untuk pengembangan. Kode KFA di bawah adalah PLACEHOLDER, bukan kode resmi KFA;
 * ganti dengan hasil sinkronisasi/impor KFA yang sebenarnya sebelum dipakai klinis.
 */
class ProdukObatSeeder extends Seeder
{
    public function run(): void
    {
        $oral = [['kode' => 'PO', 'label' => 'Oral']];

        $data = [
            ['92000001', 'OBT-PCT-500', 'Paracetamol 500 mg Tablet', 'Paracetamol', '500', 'mg', 'Tablet', $oral, StatusFormularium::Formularium],
            ['92000002', 'OBT-AMX-500', 'Amoxicillin 500 mg Capsule', 'Amoxicillin', '500', 'mg', 'Kapsul', $oral, StatusFormularium::Formularium],
            ['92000003', 'OBT-IBU-400', 'Ibuprofen 400 mg Tablet', 'Ibuprofen', '400', 'mg', 'Tablet', $oral, StatusFormularium::Formularium],
            ['92000004', 'OBT-ORS-200', 'Oral Rehydration Salt 200 mL', 'Sodium chloride, potassium chloride, trisodium citrate, glucose', '200', 'mL', 'Larutan oral', $oral, StatusFormularium::Formularium],
            ['92000005', 'OBT-SALB-100', 'Salbutamol 100 mcg Inhaler', 'Salbutamol', '100', 'mcg/puff', 'Metered dose inhaler', [['kode' => 'INH', 'label' => 'Inhalasi']], StatusFormularium::PerluPersetujuan],
        ];

        foreach ($data as [$kfa, $lokal, $nama, $zat, $kekuatan, $satuan, $bentuk, $rute, $formularium]) {
            DrugProduct::updateOrCreate(['kfa_code' => $kfa], [
                'kode_lokal' => $lokal,
                'name' => $nama,
                'generic_name' => $nama === 'Oral Rehydration Salt 200 mL' ? null : explode(' ', $nama)[0],
                'active_ingredient' => $zat,
                'strength' => $kekuatan,
                'strength_unit' => $satuan,
                'dosage_form' => $bentuk,
                'routes' => $rute,
                'manufacturer' => 'Katalog KFA',
                'formulary_status' => $formularium,
                'is_active' => true,
                'source' => 'KFA snapshot · preliminary',
            ]);
        }
    }
}
