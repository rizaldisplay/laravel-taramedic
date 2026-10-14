<?php

use App\Http\Controllers\Api\Masterdata\Lokasi\LokasiPelayananController;
use Illuminate\Support\Facades\Route;

// Gabungkan ke routes/api.php (require atau salin).
Route::prefix('lokasi-pelayanan')->group(function () {
    Route::get('/', [LokasiPelayananController::class, 'index'])->middleware('throttle:120,1');
    Route::get('options', [LokasiPelayananController::class, 'options']);
    Route::post('/', [LokasiPelayananController::class, 'store']);
    // Tidak ada DELETE: lokasi dirujuk kunjungan, cukup dinonaktifkan.
    Route::put('{lokasi}', [LokasiPelayananController::class, 'update'])->where('lokasi', '[0-9]+');
    Route::put('{lokasi}/status', [LokasiPelayananController::class, 'status'])->where('lokasi', '[0-9]+');
});
