<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `doctors` = "SDMK X bertugas di poli Y". Satu SDMK dapat bertugas di beberapa poli,
     * sehingga unik per (health_worker_id, poli_id), bukan per health_worker_id saja.
     * Baris lama dibiarkan (health_worker_id NULL) agar kunjungan lama tidak rusak.
     */
    public function up(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            $table->foreignId('health_worker_id')->nullable()->after('id')
                ->constrained('health_workers')->nullOnDelete();
            $table->unique(['health_worker_id', 'poli_id'], 'doctors_worker_poli_unique');
        });
    }

    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            $table->dropUnique('doctors_worker_poli_unique');
            $table->dropConstrainedForeignId('health_worker_id');
        });
    }
};
