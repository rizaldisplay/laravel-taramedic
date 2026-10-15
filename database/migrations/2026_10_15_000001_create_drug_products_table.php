<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drug_products', function (Blueprint $table) {
            $table->id();
            $table->string('kfa_code', 30)->unique();          // identitas sumber, tidak berubah
            $table->string('kode_lokal', 30)->unique();
            $table->string('name', 150)->index();              // nama tampil di resep
            $table->string('generic_name', 150)->nullable();
            $table->string('brand_name', 150)->nullable();     // dari sinkronisasi KFA
            $table->string('active_ingredient', 200);          // beberapa zat aktif dipisah koma
            $table->string('strength', 12)->nullable();        // angka sebagai teks agar tidak berubah format ("500", "0.5")
            $table->string('strength_unit', 20)->nullable();   // mg, mL, mcg/puff, ...
            $table->string('dosage_form', 50)->index();
            $table->json('routes');                            // [{"kode":"PO","label":"Oral"}]
            $table->string('manufacturer', 100)->nullable()->index();
            $table->string('registration_number', 50)->nullable(); // dari sinkronisasi KFA
            $table->string('formulary_status', 30)->default('Formularium')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->string('source', 100)->default('Input manual');
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drug_products');
    }
};
