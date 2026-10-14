<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `service_locations` (dipakai visits.service_location_id) dikembangkan menjadi master Lokasi Pelayanan berjenjang.
     *  - name      = nama lokasi (tetap, sudah unik)
     *  - is_active = status Aktif/Nonaktif
     *  Baris & kunjungan lama tidak berubah: kode diisi otomatis (LOK-<id>), tipe = "Area Lainnya", tanpa induk.
     */
    public function up(): void
    {
        Schema::table('service_locations', function (Blueprint $table) {
            $table->string('kode', 30)->nullable()->unique()->after('id');
            $table->string('tipe', 20)->default('Area Lainnya')->after('name')->index();
            $table->foreignId('induk_id')->nullable()->after('tipe')->constrained('service_locations')->nullOnDelete();
            $table->string('deskripsi', 255)->nullable()->after('induk_id');
        });

        if (! Schema::hasColumn('service_locations', 'facility_id') && Schema::hasTable('facilities')) {
            Schema::table('service_locations', function (Blueprint $table) {
                $table->foreignId('facility_id')->nullable()->constrained('facilities')->nullOnDelete();
            });
        }

        DB::table('service_locations')->whereNull('kode')->orderBy('id')->each(function ($row) {
            DB::table('service_locations')->where('id', $row->id)->update(['kode' => 'LOK-' . $row->id]);
        });
    }

    public function down(): void
    {
        Schema::table('service_locations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('induk_id');
            if (Schema::hasColumn('service_locations', 'facility_id')) {
                $table->dropConstrainedForeignId('facility_id');
            }
            $table->dropUnique(['kode']);
            $table->dropIndex(['tipe']);
            $table->dropColumn(['kode', 'tipe', 'deskripsi']);
        });
    }
};
