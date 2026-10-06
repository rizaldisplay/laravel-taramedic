<?php

use App\Events\QueueDisplayUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Queue\KioskController;
use App\Http\Controllers\Api\Queue\CounterController;
use App\Http\Controllers\Api\AuthController;


// Endpoint publik
Route::post('/login', [AuthController::class, 'login']);

// Endpoint yang membutuhkan autentikasi
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Rute API lain untuk SaaS kita nantinya...
});

/* Digunakan Untuk Mendaftar Nomor Antrian Di Kiosk */
Route::prefix('antrean')->group(function () {
    Route::post('/daftar', [KioskController::class, 'store']);
});

/* Digunakan Untuk Mendapatkan Data Antrian dan Mengubah Status Antrian */
Route::prefix('counter/{counter}')->group(function () {
    // Rute untuk mengambil data dashboard (API)
    Route::get('/data', [CounterController::class, 'dashboard'])->name('counter.data');
    
    // Rute aksi antrean
    Route::post('/call-next', [CounterController::class, 'callNext'])->name('counter.call-next');
    Route::post('/call-next-skip', [CounterController::class, 'callNextSkip'])->name('counter.call-next-skip');
    Route::post('/tickets/{ticket}/recall', [CounterController::class, 'recall'])->name('counter.recall');
    Route::patch('/tickets/{ticket}/start-serving', [CounterController::class, 'startServing'])->name('counter.start-serving');
    Route::patch('/tickets/{ticket}/defer', [CounterController::class, 'defer'])->name('counter.defer');
    Route::patch('/tickets/{ticket}/complete', [CounterController::class, 'complete'])->name('counter.complete');
});


/* Ini Hanya Route Test Realtime */
Route::post('/queue/call', function (Request $request) {
    // Simulasi data antrean disesuaikan dengan interface AnnouncementPayload TS
    $announcementData = [
        'message'       => 'Nomor antrean Anda B-042, silakan menuju Ruang 3',
        'ticket_number' => 'B-050',
        'service_name'  => 'Poli Gigi',
        'service_code'  => 'PG',       // Opsional
        'counter_name'  => 'Ruang 2',
        'counter_code'  => 'R2',       // Opsional
        'call_id'       => 3,        // Opsional
        'called_at'     => now()->toIso8601String(), // Opsional (format ISO 8601)
    ];

    // Trigger event ke Reverb
    QueueDisplayUpdated::dispatch($announcementData);

    return response()->json([
        'status' => 'success',
        'message' => 'Layar berhasil diupdate'
    ]);
});