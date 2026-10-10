<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_kunjungan', 20)->unique();

            $table->foreignId('patient_id')->constrained('patients');
            $table->foreignId('poli_id')->constrained('polis');
            $table->foreignId('doctor_id')->constrained('doctors');
            $table->foreignId('service_location_id')->constrained('service_locations');

            $table->string('jenis_layanan', 30);
            $table->string('cara_masuk', 50);
            $table->string('penjamin', 30);
            $table->string('jenis_pembayaran', 30);
            $table->string('prioritas', 20)->default('Normal');
            $table->unsignedSmallInteger('counter_number'); // loket pendaftaran
            $table->string('ticket_id', 50)->nullable();    // tiket antrean asal
            $table->string('status', 20)->default('Terdaftar');

            $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('registered_at');
            $table->date('visit_date');
            $table->timestamps();

            // Satu pasien hanya boleh didaftarkan sekali per poli per hari
            // (juga melindungi dari klik ganda / pendaftaran serentak).
            $table->unique(['patient_id', 'poli_id', 'visit_date']);
            $table->index(['patient_id', 'registered_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
