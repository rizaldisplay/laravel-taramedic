<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel `polis` dikembangkan menjadi master Unit & Poli.
     *  - name        = nama unit/poli (tetap, sudah unik)
     *  - is_active   = status Aktif/Nonaktif
     *  - facility_id = fasyankes (ditambahkan migration SDMK; dibuat di sini bila belum ada)
     */
    public function up(): void
    {
        Schema::table('polis', function (Blueprint $table) {
            $table->string('kode', 20)->nullable()->unique()->after('id');
            $table->string('jenis', 20)->default('Poli')->after('name')->index();
            $table->string('deskripsi', 255)->nullable()->after('jenis');
            $table->boolean('untuk_antrean')->default(true)->after('deskripsi');
        });

        if (! Schema::hasColumn('polis', 'facility_id') && Schema::hasTable('facilities')) {
            Schema::table('polis', function (Blueprint $table) {
                $table->foreignId('facility_id')->nullable()->constrained('facilities')->nullOnDelete();
            });
        }

        // Isi kode untuk baris lama agar semuanya punya kode unik: POLI-<id>.
        DB::table('polis')->whereNull('kode')->orderBy('id')->each(function ($row) {
            DB::table('polis')->where('id', $row->id)->update(['kode' => 'POLI-' . $row->id]);
        });
    }

    public function down(): void
    {
        Schema::table('polis', function (Blueprint $table) {
            $table->dropUnique(['kode']);
            $table->dropIndex(['jenis']);
            $table->dropColumn(['kode', 'jenis', 'deskripsi', 'untuk_antrean']);
        });
    }
};
