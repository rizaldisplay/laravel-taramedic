<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CHILD_TABLES = ['worker_sips', 'worker_placements'];

    /**
     * 1) service_locations.poli_id: ruang dapat dikaitkan ke satu poli (opsional).
     * 2) Hanya bila tabel lama `rooms` (versi awal paket SDMK) masih ada: pindahkan isinya ke
     *    service_locations (tipe "Ruang"), arahkan worker_sips & worker_placements ke
     *    kolom baru service_location_id, lalu hapus `rooms`.
     * Pada instalasi baru (tanpa `rooms`) hanya langkah 1 yang berjalan.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('service_locations', 'poli_id')) {
            Schema::table('service_locations', function (Blueprint $table) {
                $table->foreignId('poli_id')->nullable()->after('induk_id')->constrained('polis')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('rooms')) {
            return;
        }

        $hasFacility = Schema::hasColumn('service_locations', 'facility_id');

        DB::transaction(function () use ($hasFacility) {
            // 1. Kolom baru pada tabel anak (nullable; nilai diisi dari pemetaan di bawah).
            foreach (self::CHILD_TABLES as $tbl) {
                if (Schema::hasTable($tbl) && ! Schema::hasColumn($tbl, 'service_location_id')) {
                    Schema::table($tbl, function (Blueprint $table) {
                        $table->foreignId('service_location_id')->nullable()->constrained('service_locations')->nullOnDelete();
                    });
                }
            }

            // 2. Salin rooms -> service_locations, catat pemetaan id lama -> id baru.
            $map = [];
            foreach (DB::table('rooms')->orderBy('id')->get() as $room) {
                $map[$room->id] = $this->copyRoom($room, $hasFacility);
            }

            // 3. Isi kolom baru. Ke kolom BARU (bukan menimpa room_id) agar id lama & baru yang kebetulan sama tidak bertabrakan.
            foreach (self::CHILD_TABLES as $tbl) {
                if (! Schema::hasTable($tbl) || ! Schema::hasColumn($tbl, 'room_id')) {
                    continue;
                }
                foreach ($map as $oldId => $newId) {
                    DB::table($tbl)->where('room_id', $oldId)->update(['service_location_id' => $newId]);
                }
            }
        });

        // 4. Buang kolom & kunci asing lama, lalu tabel rooms.
        foreach (self::CHILD_TABLES as $tbl) {
            if (Schema::hasTable($tbl) && Schema::hasColumn($tbl, 'room_id')) {
                Schema::table($tbl, function (Blueprint $table) {
                    $table->dropForeign(['room_id']);
                    $table->dropColumn('room_id');
                });
            }
        }

        Schema::drop('rooms');
    }

    /** @return int id service_locations hasil salinan/penggabungan. */
    private function copyRoom(object $room, bool $hasFacility): int
    {
        $existing = DB::table('service_locations')->where('name', $room->name)->first();

        // Nama sama dengan lokasi yang sudah ada dan bukan Gedung/Lantai -> dianggap ruang yang sama.
        if ($existing && in_array($existing->tipe, ['Ruang', 'Area Lainnya'], true)) {
            $update = ['tipe' => 'Ruang', 'poli_id' => $existing->poli_id ?? $room->poli_id, 'updated_at' => now()];
            if ($hasFacility) {
                $update['facility_id'] = $existing->facility_id ?? $room->facility_id;
            }
            DB::table('service_locations')->where('id', $existing->id)->update($update);

            return $existing->id;
        }

        // Nama bentrok dengan Gedung/Lantai, atau belum ada: buat baris baru dengan nama unik.
        $name = $existing ? $room->name . ' (Ruang)' : $room->name;
        while (DB::table('service_locations')->where('name', $name)->exists()) {
            $name = $room->name . ' (Ruang ' . $room->id . ')';
        }

        return DB::table('service_locations')->insertGetId([
            'kode' => 'RM-' . $room->id,
            'name' => $name,
            'tipe' => 'Ruang',
            'poli_id' => $room->poli_id,
            ...($hasFacility ? ['facility_id' => $room->facility_id] : []),
            'is_active' => $room->is_active,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        throw new RuntimeException('Migration ini tidak dapat dibalik: tabel rooms sudah digabung ke service_locations. Pulihkan dari cadangan database bila perlu.');
    }
};
