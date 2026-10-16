<?php

namespace Database\Seeders;

use App\Enums\Patient\Penjamin;
use App\Models\DrugPrice;
use App\Models\DrugPriceHistory;
use App\Models\DrugProduct;
use Illuminate\Database\Seeder;

/**
 * DATA CONTOH untuk pengembangan; BUKAN tarif klinik yang disahkan. Membutuhkan ProdukObatSeeder
 * (paket produk-obat) dijalankan lebih dulu. Idempoten: kunci = obat + penjamin + satuan + berlaku_mulai.
 *
 * php artisan db:seed --class=ProdukObatSeeder
 * php artisan db:seed --class=TarifObatSeeder
 */
class TarifObatSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            // kode lokal, satuan, tarif (Rp)
            ['OBT-PCT-500', 'tablet', 500],
            ['OBT-AMX-500', 'kapsul', 1000],
            ['OBT-IBU-400', 'tablet', 750],
            ['OBT-ORS-200', 'botol', 3000],
            ['OBT-SALB-100', 'inhaler', 2000],
        ];

        foreach ($data as [$kode, $satuan, $tarif]) {
            $obat = DrugProduct::where('kode_lokal', $kode)->first();
            if (! $obat) {
                $this->command?->warn("Obat {$kode} tidak ditemukan; jalankan ProdukObatSeeder dahulu.");
                continue;
            }

            $harga = DrugPrice::firstOrCreate(
                ['drug_product_id' => $obat->id, 'penjamin' => Penjamin::Umum->value, 'satuan' => $satuan, 'berlaku_mulai' => '2026-01-01'],
                [
                    'tarif' => $tarif,
                    'berlaku_sampai' => null,
                    'sumber' => 'TARAMEDIC development demo',
                    'catatan' => 'Tarif contoh untuk pengujian lokal; bukan tarif klinik yang disahkan.',
                    'is_active' => true,
                ],
            );

            if ($harga->wasRecentlyCreated) {
                DrugPriceHistory::create([
                    'drug_price_id' => $harga->id,
                    'aksi' => 'dibuat',
                    'user_name' => 'Seeder',
                    'perubahan' => [
                        ['field' => 'tarif', 'label' => 'Tarif', 'dari' => null, 'ke' => (string) $tarif],
                        ['field' => 'penjamin', 'label' => 'Penjamin', 'dari' => null, 'ke' => Penjamin::Umum->value],
                        ['field' => 'satuan', 'label' => 'Satuan', 'dari' => null, 'ke' => $satuan],
                        ['field' => 'berlakuMulai', 'label' => 'Berlaku mulai', 'dari' => null, 'ke' => '2026-01-01'],
                        ['field' => 'status', 'label' => 'Status', 'dari' => null, 'ke' => 'Aktif'],
                    ],
                ]);
            }
        }
    }
}
