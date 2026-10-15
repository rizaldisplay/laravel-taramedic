<?php

use App\Http\Controllers\Api\Masterdata\Produkobat\ProdukObatController;
use Illuminate\Support\Facades\Route;

// Gabungkan ke routes/api.php (require atau salin).
Route::prefix('farmasi/produk-obat')->group(function () {
    Route::get('/', [ProdukObatController::class, 'index'])->middleware('throttle:120,1');
    Route::post('/', [ProdukObatController::class, 'store']);
    // Tidak ada DELETE: produk dirujuk resep/riwayat, cukup dinonaktifkan.
    Route::put('{produk}', [ProdukObatController::class, 'update'])->where('produk', '[0-9]+');
    Route::put('{produk}/status', [ProdukObatController::class, 'status'])->where('produk', '[0-9]+');
});
