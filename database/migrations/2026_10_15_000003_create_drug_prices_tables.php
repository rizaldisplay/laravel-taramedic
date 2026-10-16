<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Membutuhkan tabel drug_products (paket laravel-backend-produk-obat) sudah ada. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drug_prices', function (Blueprint $table) {
            $table->id();
            // restrict: obat tidak pernah dihapus (hanya dinonaktifkan); tarif ikut menjadi dasar invoice lama.
            $table->foreignId('drug_product_id')->constrained('drug_products')->restrictOnDelete();
            $table->string('penjamin', 30);                       // App\Enums\Penjamin
            $table->string('satuan', 30);                         // tablet, kapsul, botol, ...
            $table->unsignedInteger('tarif');                     // Rupiah bulat per satuan (0 = gratis)
            $table->date('berlaku_mulai');
            $table->date('berlaku_sampai')->nullable();           // null = berlaku seterusnya
            $table->string('sumber', 100)->nullable();
            $table->string('catatan', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Pemeriksaan tumpang tindih: obat + penjamin (+ satuan) yang aktif.
            $table->index(['drug_product_id', 'penjamin', 'is_active'], 'drug_prices_lookup_index');
            $table->index('berlaku_mulai');
        });

        Schema::create('drug_price_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('drug_price_id')->constrained('drug_prices')->cascadeOnDelete();
            $table->string('aksi', 20);                           // dibuat | diubah | diaktifkan | dinonaktifkan
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name', 100)->nullable();         // salinan nama saat kejadian (jejak audit)
            $table->json('perubahan');                            // [{field,label,dari,ke}]
            $table->timestamp('created_at')->nullable();

            $table->index(['drug_price_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drug_price_histories');
        Schema::dropIfExists('drug_prices');
    }
};
