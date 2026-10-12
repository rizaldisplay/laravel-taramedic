<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('health_workers', function (Blueprint $table) {
            $table->id();
            $table->string('sdmk_code', 20)->unique();

            // Identitas
            $table->char('nik', 16)->unique();
            $table->string('nama_lengkap');
            $table->string('gelar_depan', 30)->nullable();
            $table->string('gelar_belakang', 60)->nullable();
            $table->string('tempat_lahir', 100)->nullable();
            $table->date('tanggal_lahir');
            $table->string('jenis_kelamin', 10);
            $table->string('kewarganegaraan', 3)->default('WNI');
            $table->string('nomor_hp', 15);
            $table->string('email')->nullable();

            // Alamat domisili. *_code = kode Kemendagri (Wilayah.id); *_name = salinan nama saat disimpan.
            $table->text('alamat_lengkap');
            $table->string('province_code', 2);
            $table->string('regency_code', 5);
            $table->string('district_code', 8);
            $table->string('village_code', 13);
            $table->string('province_name');
            $table->string('regency_name');
            $table->string('district_name');
            $table->string('village_name');
            $table->string('rt', 3)->nullable();
            $table->string('rw', 3)->nullable();
            $table->char('kode_pos', 5)->nullable();

            $table->string('foto_path')->nullable();

            // Kontak darurat
            $table->string('nama_kontak_darurat');
            $table->string('hubungan_kontak_darurat', 50);
            $table->string('nomor_hp_kontak_darurat', 15);

            // Profesi (nilai merujuk sdmk_references.value)
            $table->string('jenis_sdmk', 80)->nullable();
            $table->string('profesi', 80)->nullable();
            $table->string('kompetensi_utama', 80)->nullable();
            $table->string('spesialisasi', 80)->nullable();
            $table->string('sub_spesialisasi', 80)->nullable();
            $table->string('tingkat_pendidikan', 80)->nullable();
            $table->string('institusi')->nullable();
            $table->char('tahun_lulus', 4)->nullable();
            $table->string('status', 20)->default('Aktif')->index();

            // STR (satu SDMK satu STR aktif)
            $table->string('str_number', 60)->nullable()->index();
            $table->date('str_issued_at')->nullable();
            $table->date('str_expires_at')->nullable();
            $table->string('str_issuer', 80)->nullable();
            $table->timestamp('str_verified_at')->nullable();
            $table->foreignId('str_verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('other_certificates')->nullable();

            // Status izin hasil hitung (STR + SIP). Dihitung ulang tiap SIP/STR berubah dan oleh sdmk:refresh-license-status.
            $table->string('license_status', 20)->default('Tidak Aktif')->index();

            // Pengaturan jadwal
            $table->string('schedule_type', 40)->nullable();
            $table->unsignedSmallInteger('daily_capacity')->nullable();
            $table->unsignedSmallInteger('service_duration')->nullable();
            $table->json('schedule_patient_types')->nullable();
            $table->string('schedule_status', 20)->default('Aktif');

            // Integrasi SATUSEHAT
            $table->string('ihs_status', 12)->default('belum')->index();
            $table->string('ihs_number', 40)->nullable();
            $table->string('organization_id', 60)->nullable();
            $table->timestamp('ihs_synced_at')->nullable();
            $table->string('mapping_specialty', 80)->nullable();
            $table->string('mapping_practitioner_type', 80)->nullable();

            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('nama_lengkap');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_workers');
    }
};
