<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('worker_competencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('health_worker_id')->constrained()->cascadeOnDelete();
            $table->string('nama');
            $table->string('penyelenggara');
            $table->date('tanggal_peroleh');
            $table->date('berlaku_hingga')->nullable();
            $table->string('document_path')->nullable();
            $table->timestamps();
        });

        Schema::create('worker_sips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('health_worker_id')->constrained()->cascadeOnDelete();
            $table->string('nomor_sip', 80);
            $table->string('jenis_sip', 80);
            $table->date('tanggal_terbit');
            $table->date('berlaku_hingga');
            $table->string('status', 20)->default('Aktif'); // pilihan petugas; status efektif dihitung dari tanggal
            $table->string('tempat_terbit');
            $table->string('nomor_sk', 80)->nullable();
            $table->date('tanggal_sk')->nullable();
            $table->foreignId('facility_id')->constrained('facilities');
            $table->foreignId('poli_id')->constrained('polis');
            $table->foreignId('room_id')->nullable()->constrained('rooms')->nullOnDelete();
            $table->text('alamat_praktik');
            $table->string('province_code', 2);
            $table->string('regency_code', 5);
            $table->string('district_code', 8);
            $table->string('village_code', 13);
            $table->string('province_name');
            $table->string('regency_name');
            $table->string('district_name');
            $table->string('village_name');
            $table->char('kode_pos', 5)->nullable();
            $table->string('telepon', 30)->nullable();
            $table->string('email_fasilitas')->nullable();
            $table->string('document_path');
            $table->timestamps();

            $table->unique(['health_worker_id', 'nomor_sip']);
        });

        Schema::create('worker_placements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('health_worker_id')->constrained()->cascadeOnDelete();
            $table->foreignId('facility_id')->constrained('facilities');
            $table->foreignId('poli_id')->constrained('polis');
            $table->foreignId('room_id')->constrained('rooms');
            $table->string('peran', 80);
            $table->string('jabatan', 80)->nullable();
            $table->string('status', 20)->default('Aktif');
            $table->date('tanggal_mulai');
            $table->date('tanggal_berakhir')->nullable();
            $table->boolean('is_unit_head')->default(false);
            $table->string('catatan', 255)->nullable();
            $table->timestamps();

            $table->index(['health_worker_id', 'status']);
        });

        Schema::create('worker_schedule_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('health_worker_id')->constrained()->cascadeOnDelete();
            $table->string('hari', 10);
            $table->boolean('aktif')->default(false);
            $table->time('jam_mulai')->nullable();
            $table->time('jam_selesai')->nullable();
            $table->unsignedSmallInteger('durasi_slot')->nullable();
            $table->unsignedSmallInteger('kuota')->nullable();
            $table->unsignedSmallInteger('kuota_online')->nullable();
            $table->time('istirahat_mulai')->nullable();
            $table->time('istirahat_selesai')->nullable();
            $table->json('jenis_pasien')->nullable();
            $table->string('status', 20)->default('Aktif');
            $table->timestamps();

            $table->unique(['health_worker_id', 'hari']);
        });

        Schema::create('worker_schedule_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('health_worker_id')->constrained()->cascadeOnDelete();
            $table->string('hari', 10);
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->time('istirahat_mulai')->nullable();
            $table->time('istirahat_selesai')->nullable();
            $table->unsignedSmallInteger('durasi_layanan');
            $table->unsignedSmallInteger('kuota');
            $table->unsignedSmallInteger('kuota_online');
            $table->json('jenis_pasien');
            $table->date('berlaku_mulai');
            $table->date('berlaku_hingga')->nullable();
            $table->string('pola', 30);
            $table->string('status', 20)->default('Aktif');
            $table->timestamps();

            $table->index(['health_worker_id', 'hari']);
        });

        Schema::create('worker_api_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('health_worker_id')->constrained()->cascadeOnDelete();
            $table->string('aksi', 80);
            $table->string('status', 10); // Berhasil | Gagal
            $table->string('pesan')->nullable(); // tidak boleh memuat NIK / data pribadi
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('worker_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('health_worker_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category', 10); // riwayat | audit
            $table->string('aksi', 120);
            $table->string('keterangan')->nullable(); // tidak boleh memuat NIK / data pribadi
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['health_worker_id', 'category', 'created_at']);
        });
    }

    public function down(): void
    {
        foreach (['worker_activities', 'worker_api_logs', 'worker_schedule_slots', 'worker_schedule_days', 'worker_placements', 'worker_sips', 'worker_competencies'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
