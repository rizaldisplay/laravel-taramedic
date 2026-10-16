<?php

use App\Http\Controllers\Api\Masterdata\TarifObat\TarifObatController;
use Illuminate\Support\Facades\Route;

// Gabungkan ke routes/api.php (require atau salin).
Route::prefix('farmasi/tarif-obat')->group(function () {
    Route::get('/', [TarifObatController::class, 'index'])->middleware('throttle:120,1');
    Route::get('options', [TarifObatController::class, 'options']);
    Route::post('/', [TarifObatController::class, 'store']);
    // Tidak ada DELETE: tarif menjadi dasar invoice, cukup dinonaktifkan.
    Route::put('{tarif}', [TarifObatController::class, 'update'])->where('tarif', '[0-9]+');
    Route::put('{tarif}/status', [TarifObatController::class, 'status'])->where('tarif', '[0-9]+');
    Route::get('{tarif}/riwayat', [TarifObatController::class, 'riwayat'])->where('tarif', '[0-9]+');
});
