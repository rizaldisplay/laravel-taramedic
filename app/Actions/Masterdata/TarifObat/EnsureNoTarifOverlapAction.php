<?php

namespace App\Actions\Masterdata\TarifObat;

use App\Enums\Patient\Penjamin;
use App\Exceptions\TarifObatConflictException;
use App\Models\DrugPrice;

/**
 * Tarif AKTIF untuk obat + penjamin + satuan (tanpa membedakan huruf besar/kecil) yang sama tidak boleh
 * punya masa berlaku yang beririsan. Rentang bersifat inklusif; berlaku_sampai null = tanpa batas.
 *
 * Panggil di dalam DB::transaction setelah baris obat dikunci (lockForUpdate), supaya dua permintaan
 * serentak untuk obat yang sama tidak sama-sama lolos.
 */
final class EnsureNoTarifOverlapAction
{
    public function execute(
        int $obatId,
        Penjamin $penjamin,
        string $satuan,
        string $mulai,
        ?string $sampai,
        ?int $exceptId = null,
        string $field = 'berlakuMulai',
    ): void {
        $bentrok = DrugPrice::query()
            ->where('drug_product_id', $obatId)
            ->where('penjamin', $penjamin->value)
            ->where('is_active', true)
            ->whereRaw('LOWER(TRIM(satuan)) = ?', [mb_strtolower(trim($satuan))])
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            // Irisan: lain.mulai <= baru.sampai  DAN  lain.sampai >= baru.mulai
            ->where(fn ($q) => $q->whereNull('berlaku_sampai')->orWhere('berlaku_sampai', '>=', $mulai))
            ->when($sampai !== null, fn ($q) => $q->where('berlaku_mulai', '<=', $sampai))
            ->orderBy('berlaku_mulai')
            ->first();

        if ($bentrok) {
            $rp = number_format((int) $bentrok->tarif, 0, ',', '.');
            $akhir = $bentrok->berlaku_sampai ? substr((string) $bentrok->berlaku_sampai, 0, 10) : 'seterusnya';

            throw new TarifObatConflictException(
                $field,
                "Bertabrakan dengan tarif aktif Rp {$rp} (" . substr((string) $bentrok->berlaku_mulai, 0, 10) . " – {$akhir})",
            );
        }
    }
}
