<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Referensi generik SDMK: jenis SDMK, profesi, spesialisasi, dst.
        // parent_value dipakai untuk relasi (profesi -> jenis SDMK, sub spesialisasi -> spesialisasi).
        Schema::create('sdmk_references', function (Blueprint $table) {
            $table->id();
            $table->string('type', 40);
            $table->string('value', 80);
            $table->string('label');
            $table->string('parent_value', 80)->nullable();
            $table->string('code', 30)->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['type', 'value']);
            $table->index(['type', 'is_active', 'sort']);
        });

        Schema::create('facilities', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Ruangan memakai tabel service_locations (paket Lokasi Pelayanan), bukan tabel `rooms` tersendiri.
        Schema::table('polis', function (Blueprint $table) {
            $table->foreignId('facility_id')->nullable()->after('name')->constrained('facilities')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('polis', function (Blueprint $table) {
            $table->dropConstrainedForeignId('facility_id');
        });
        Schema::dropIfExists('facilities');
        Schema::dropIfExists('sdmk_references');
    }
};
