<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// OPSIONAL. Dipakai oleh listener LinkPatientToQueueTicket.
// Sesuaikan nama tabel antrean Anda; dilewati otomatis jika tabelnya tidak ada.
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tickets') && ! Schema::hasColumn('tickets', 'patient_id')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->foreignId('patient_id')->nullable()->constrained('patients')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tickets') && Schema::hasColumn('tickets', 'patient_id')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->dropConstrainedForeignId('patient_id');
            });
        }
    }
};
