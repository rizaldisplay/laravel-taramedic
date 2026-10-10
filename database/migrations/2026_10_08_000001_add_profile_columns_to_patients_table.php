<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            // Nama wilayah disimpan saat pendaftaran/ubah data (snapshot), supaya
            // tampilan alamat tidak bergantung pada ketersediaan Wilayah.id.
            $table->string('province_name', 100)->nullable()->after('province_code');
            $table->string('regency_name', 100)->nullable()->after('regency_code');
            $table->string('district_name', 100)->nullable()->after('district_code');
            $table->string('village_name', 100)->nullable()->after('village_code');

            $table->boolean('is_active')->default(true);
            $table->string('registration_source', 50)->default('Petugas Pendaftaran');

            // Diisi oleh integrasi SATUSEHAT / verifikasi NIK (belum ada di backend ini)
            $table->string('ihs_number', 50)->nullable()->unique();
            $table->timestamp('satusehat_synced_at')->nullable();
            $table->timestamp('nik_verified_at')->nullable();

            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index('nomor_hp');
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropConstrainedForeignId('updated_by');
            $table->dropIndex(['nomor_hp']);
            $table->dropColumn([
                'province_name', 'regency_name', 'district_name', 'village_name',
                'is_active', 'registration_source',
                'ihs_number', 'satusehat_synced_at', 'nik_verified_at',
            ]);
        });
    }
};
