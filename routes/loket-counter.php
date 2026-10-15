<?php

use App\Http\Controllers\Api\Masterdata\Loket\LoketCounterController;
use Illuminate\Support\Facades\Route;

// Gabungkan ke routes/api.php (require atau salin).
Route::prefix('loket-counter')->group(function () {
    Route::get('/', [LoketCounterController::class, 'index'])->middleware('throttle:120,1');
    Route::get('options', [LoketCounterController::class, 'options']);
    Route::post('/', [LoketCounterController::class, 'store']);
    // Tidak ada DELETE: nomor loket tercatat pada kunjungan, cukup dinonaktifkan.
    Route::put('{loket}', [LoketCounterController::class, 'update'])->where('loket', '[0-9]+');
    Route::put('{loket}/status', [LoketCounterController::class, 'status'])->where('loket', '[0-9]+');
});
