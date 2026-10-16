<?php

namespace App\Actions\Masterdata\TarifObat;

use App\Models\DrugPrice;
use App\Models\DrugPriceHistory;
use App\Models\User;
use BackedEnum;

/**
 * Menulis jejak audit tarif. Dipanggil di dalam transaksi yang sama dengan perubahan datanya.
 * Bentuk `perubahan` = PerubahanTarif[] di frontend: { field, label, dari, ke } (semua nilai berupa string/null;
 * tarif sebagai string digit, tanggal sebagai YYYY-MM-DD).
 */
final class RecordTarifHistoryAction
{
    /** field frontend => [kolom, label] — urutan = urutan tampil. */
    private const FIELDS = [
        'tarif' => ['tarif', 'Tarif'],
        'penjamin' => ['penjamin', 'Penjamin'],
        'satuan' => ['satuan', 'Satuan'],
        'berlakuMulai' => ['berlaku_mulai', 'Berlaku mulai'],
        'berlakuSampai' => ['berlaku_sampai', 'Berlaku sampai'],
        'sumber' => ['sumber', 'Sumber tarif'],
        'catatan' => ['catatan', 'Catatan'],
    ];

    /** @param list<array{field: string, label: string, dari: ?string, ke: ?string}> $perubahan */
    public function execute(DrugPrice $tarif, string $aksi, ?int $userId, array $perubahan): DrugPriceHistory
    {
        return DrugPriceHistory::create([
            'drug_price_id' => $tarif->id,
            'aksi' => $aksi,
            'user_id' => $userId,
            'user_name' => $userId ? User::whereKey($userId)->value('name') : null,
            'perubahan' => $perubahan,
        ]);
    }

    /** Tarif baru: semua nilai terisi (dari = null), ditambah status Aktif. */
    public function forCreate(DrugPrice $tarif): array
    {
        $rows = [];
        foreach (self::FIELDS as $field => [$kolom, $label]) {
            $ke = $this->scalar($tarif->getAttribute($kolom));
            if ($ke === null) {
                continue; // berlakuSampai / sumber / catatan kosong tidak perlu ditampilkan
            }
            $rows[] = ['field' => $field, 'label' => $label, 'dari' => null, 'ke' => $ke];
        }
        $rows[] = ['field' => 'status', 'label' => 'Status', 'dari' => null, 'ke' => 'Aktif'];

        return $rows;
    }

    /** Perbedaan antara nilai asli dan nilai terisi (belum disimpan) pada model. Kosong bila tidak ada perubahan. */
    public function diff(DrugPrice $tarif): array
    {
        $rows = [];
        foreach (self::FIELDS as $field => [$kolom, $label]) {
            $dari = $this->scalar($tarif->getOriginal($kolom));
            $ke = $this->scalar($tarif->getAttribute($kolom));
            if ($dari !== $ke) {
                $rows[] = ['field' => $field, 'label' => $label, 'dari' => $dari, 'ke' => $ke];
            }
        }

        return $rows;
    }

    public function forStatus(bool $aktif): array
    {
        return [['field' => 'status', 'label' => 'Status', 'dari' => $aktif ? 'Nonaktif' : 'Aktif', 'ke' => $aktif ? 'Aktif' : 'Nonaktif']];
    }

    private function scalar(mixed $v): ?string
    {
        if ($v === null || $v === '') {
            return null;
        }
        if ($v instanceof BackedEnum) {
            return (string) $v->value;
        }
        if ($v instanceof \DateTimeInterface) {
            return $v->format('Y-m-d');
        }
        $s = (string) $v;

        // "2026-01-01 00:00:00" (beberapa driver) -> "2026-01-01"
        return preg_match('/^\d{4}-\d{2}-\d{2}[ T]/', $s) ? substr($s, 0, 10) : $s;
    }
}
