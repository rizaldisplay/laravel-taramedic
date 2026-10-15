<?php

use App\Events\QueueDisplayUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Queue\KioskController;
use App\Http\Controllers\Api\Queue\CounterController;
use App\Http\Controllers\Api\Registrasi\PatientController;
use App\Http\Controllers\Api\Registrasi\RegionController;
use App\Http\Controllers\Api\Registrasi\RegistrationOptionsController;
use App\Http\Controllers\Api\Registrasi\VisitController;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DeviceController;

require __DIR__.'/unit-poli.php';
require __DIR__.'/lokasi-pelayanan.php';
require __DIR__.'/sdmk.php';
require __DIR__.'/loket-counter.php';
require __DIR__.'/produk-obat.php';


// Public Auth Routes
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    // Protected Routes (Harus Login)
    Route::middleware('auth:api')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });

    // Realtime Test
    Route::post('/send-message', function (Request $request) {
        QueueDisplayUpdated::dispatch($request->input('message', 'Halo dari Laravel 12!'));
        return response()->json(['status' => 'Pesan terkirim']);
    });
});

// Protected API Routes Berdasarkan Role
Route::middleware(['auth:api'])->group(function () {

    // Khusus Super Admin
    Route::middleware('role:super_admin')->prefix('admin')->group(function () {
        Route::get('/dashboard-stats', function () {
            return response()->json(['message' => 'Akses khusus Super Admin']);
        });
        // CRUD User / Setting Klinik
    });

    // Akses Dokter & Super Admin
    Route::middleware('role:super_admin,dokter')->prefix('medis')->group(function () {
        Route::get('/rekam-medis', function () {
            return response()->json(['message' => 'Akses Rekam Medis Pasien']);
        });
        // Input Diagnosa, Resep Obat, dll
    });

    // Akses Staff / Resepsionis / Admin
   Route::middleware('role:super_admin,admin,staff')
    ->prefix('pendaftaran')
    ->group(function () {

        // Antrean
        // Route::get('/antrean', [QueueController::class, 'index']);

        // Registrasi kunjungan
        // Route::post('/registrasi', [RegistrationController::class, 'store']);
        // Route::get('/registrasi/{registration}', [RegistrationController::class, 'show']);

        // Cetak kartu pasien
        // Route::get('/pasien/{patient}/kartu', [PatientsController::class, 'show']);

    });

    // Device Management
    Route::prefix('devices')->group(function () {
        Route::get('/', [DeviceController::class, 'index']);
        Route::delete('/{id}', [DeviceController::class, 'revoke']);
    });

    // Audit Logs (Khusus Super Admin)
    Route::middleware('role:super_admin')->get('/audit-logs', function () {
        $logs = \App\Models\AuditLog::with('user:id,name,username')
            ->latest()
            ->paginate(20);

        return response()->json(['status' => true, 'data' => $logs]);
    });

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

// ---- Pasien ----
Route::get('patients', [PatientController::class, 'index']);
    // ->middleware('throttle:120,1');

// Throttle ketat: endpoint ini mengungkap apakah seseorang terdaftar sebagai pasien.
Route::get('patients/check-nik/{nik}', [PatientController::class, 'checkNik'])
    ->where('nik', '[0-9]{16}');
    // ->middleware('throttle:30,1');

Route::post('patients', [PatientController::class, 'store']);
Route::get('patients/{patient}', [PatientController::class, 'show'])->whereNumber('patient');
Route::get('patients/{patient}/summary', [PatientController::class, 'summary'])->whereNumber('patient');
Route::put('patients/{patient}', [PatientController::class, 'update'])->whereNumber('patient');

// ---- Pendaftaran kunjungan ----
Route::get('registration/options', RegistrationOptionsController::class);
Route::post('visits', [VisitController::class, 'store']);

// ---- Wilayah (diteruskan dari Wilayah.id, di-cache) ----
// Format kode dibatasi agar input tidak bisa menyusup ke URL permintaan keluar.
Route::prefix('regions')->group(function () {
    Route::get('provinces', [RegionController::class, 'provinces']);
    Route::get('provinces/{province}/regencies', [RegionController::class, 'regencies'])
        ->where('province', '[0-9]{2}');
    Route::get('regencies/{regency}/districts', [RegionController::class, 'districts'])
        ->where('regency', '[0-9]{2}\.[0-9]{2}');
    Route::get('districts/{district}/villages', [RegionController::class, 'villages'])
        ->where('district', '[0-9]{2}\.[0-9]{2}\.[0-9]{2}');
});

// Data wilayah diteruskan dari Wilayah.id (di-cache). Format kode dibatasi
// agar input tidak bisa menyusup ke URL permintaan keluar.
Route::prefix('regions')->group(function () {
    Route::get('provinces', [RegionController::class, 'provinces']);
    Route::get('provinces/{province}/regencies', [RegionController::class, 'regencies'])
        ->where('province', '[0-9]{2}');
    Route::get('regencies/{regency}/districts', [RegionController::class, 'districts'])
        ->where('regency', '[0-9]{2}\.[0-9]{2}');
    Route::get('districts/{district}/villages', [RegionController::class, 'villages'])
        ->where('district', '[0-9]{2}\.[0-9]{2}\.[0-9]{2}');
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