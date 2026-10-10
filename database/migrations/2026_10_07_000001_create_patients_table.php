<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Penghitung berurutan (dipakai untuk nomor rekam medis)
        Schema::create('sequences', function (Blueprint $table) {
            $table->string('name')->primary();
            $table->unsignedBigInteger('value')->default(0);
        });

        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_rm', 20)->unique();

            // Identitas. NIK dan paspor dipisah agar masing-masing bisa unik.
            $table->string('jenis_identitas', 20);
            $table->char('nik', 16)->nullable()->unique();
            $table->string('nomor_paspor', 20)->nullable()->unique();
            $table->string('nama_lengkap');
            $table->string('tempat_lahir', 100)->nullable();
            $table->date('tanggal_lahir');
            $table->string('jenis_kelamin', 10);

            // Alamat sesuai identitas. *_code = kode Kemendagri dari Wilayah.id (tanpa tabel wilayah lokal)
            $table->text('alamat_lengkap');
            $table->string('province_code', 2);
            $table->string('regency_code', 5);
            $table->string('district_code', 8);
            $table->string('village_code', 13);
            $table->string('rt', 3)->nullable();
            $table->string('rw', 3)->nullable();
            $table->char('kode_pos', 5)->nullable();

            // Kontak
            $table->string('nomor_hp', 15);
            $table->string('email')->nullable();

            // Administrasi
            $table->char('nomor_kk', 16)->nullable()->index();
            $table->string('status_perkawinan', 20)->nullable();
            $table->string('kewarganegaraan', 3)->default('WNI');
            $table->string('bahasa', 50)->nullable();

            // Kontak darurat
            $table->string('nama_kontak_darurat')->nullable();
            $table->string('hubungan_kontak_darurat', 50)->nullable();
            $table->string('nomor_hp_kontak_darurat', 15)->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['nama_lengkap', 'tanggal_lahir']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
        Schema::dropIfExists('sequences');
    }
};
