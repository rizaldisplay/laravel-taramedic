<?php

use App\Http\Controllers\Api\Masterdata\UnitPoli\UnitPoliController;
use Illuminate\Support\Facades\Route;

// Gabungkan ke routes/api.php (require atau salin).
Route::prefix('unit-poli')->group(function () {
    Route::get('/', [UnitPoliController::class, 'index'])->middleware('throttle:120,1');
    Route::get('options', [UnitPoliController::class, 'options']);
    Route::post('/', [UnitPoliController::class, 'store']);
    // Tidak ada DELETE: unit/poli dirujuk kunjungan dan penempatan, cukup dinonaktifkan.
    Route::put('{poli}', [UnitPoliController::class, 'update'])->where('poli', '[0-9]+');
    Route::put('{poli}/status', [UnitPoliController::class, 'status'])->where('poli', '[0-9]+');
});
