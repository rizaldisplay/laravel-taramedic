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

// use App\Http\Controllers\Api\Masterdata\Sdmk\SdmkController;
// use App\Http\Controllers\Api\Masterdata\Sdmk\SdmkFileController;
// use App\Http\Controllers\Api\Masterdata\Sdmk\SdmkIntegrationController;
// use App\Http\Controllers\Api\Masterdata\Sdmk\SdmkLicenseController;
// use App\Http\Controllers\Api\Masterdata\Sdmk\SdmkScheduleController;
use App\Http\Controllers\Api\AuthController;

require __DIR__.'/unit-poli.php';
require __DIR__.'/lokasi-pelayanan.php';
require __DIR__.'/sdmk.php';


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



/* SDMK Route */
// Route::get('sdmk/files/{type}/{id}', [SdmkFileController::class, 'show'])
//     ->whereIn('type', ['foto', 'sip', 'kompetensi'])->whereNumber('id')
//     ->middleware('signed')->name('sdmk.files.show');

// Route::prefix('sdmk')->group(function () {
//     // ---- Dasbor & daftar ----
//     Route::get('/', [SdmkController::class, 'index'])->middleware('throttle:120,1');
//     Route::get('stats', [SdmkController::class, 'stats']);
//     Route::get('options', [SdmkController::class, 'options']);
//     Route::get('history', [SdmkController::class, 'historyAll']);
//     Route::get('audit', [SdmkController::class, 'auditAll']);

//     // Throttle ketat: endpoint ini mengungkap apakah seseorang terdaftar sebagai SDMK.
//     Route::get('check-nik/{nik}', [SdmkController::class, 'checkNik'])
//         ->where('nik', '[0-9]{16}')->middleware('throttle:30,1');

//     Route::post('/', [SdmkController::class, 'store']);

//     Route::prefix('{sdmk}')->where(['sdmk' => '[0-9]+'])->group(function () {
//         Route::get('/', [SdmkController::class, 'show']);
//         Route::get('history', [SdmkController::class, 'history']);
//         Route::get('audit', [SdmkController::class, 'audit']);

//         // Tab 1-2 & catatan
//         Route::put('identitas', [SdmkController::class, 'updateIdentitas']);
//         Route::post('foto', [SdmkController::class, 'foto']);
//         Route::put('profesi', [SdmkController::class, 'profesi']);
//         Route::put('catatan', [SdmkController::class, 'catatan']);

//         // Kompetensi tambahan
//         Route::post('kompetensi', [SdmkLicenseController::class, 'storeKompetensi']);
//         Route::delete('kompetensi/{kompetensi}', [SdmkLicenseController::class, 'destroyKompetensi'])->whereNumber('kompetensi');

//         // Tab 3: STR & SIP
//         Route::put('str', [SdmkLicenseController::class, 'saveStr']);
//         Route::post('str/verify', [SdmkLicenseController::class, 'verifyStr'])->middleware('throttle:20,1');
//         Route::post('sip', [SdmkLicenseController::class, 'storeSip']);
//         // Frontend mengirim POST + _method=PUT karena multipart; Route::match menerima keduanya.
//         Route::match(['put', 'post'], 'sip/{sip}', [SdmkLicenseController::class, 'updateSip'])->whereNumber('sip');
//         Route::delete('sip/{sip}', [SdmkLicenseController::class, 'destroySip'])->whereNumber('sip');

//         // Tab 4: penempatan
//         Route::post('penempatan', [SdmkScheduleController::class, 'storePenempatan']);
//         Route::put('penempatan/{penempatan}', [SdmkScheduleController::class, 'updatePenempatan'])->whereNumber('penempatan');
//         Route::delete('penempatan/{penempatan}', [SdmkScheduleController::class, 'destroyPenempatan'])->whereNumber('penempatan');

//         // Tab 5: jadwal & slot
//         Route::put('jadwal', [SdmkScheduleController::class, 'saveJadwal']);
//         Route::post('slots', [SdmkScheduleController::class, 'storeSlot']);
//         Route::put('slots/{slot}', [SdmkScheduleController::class, 'updateSlot'])->whereNumber('slot');
//         Route::delete('slots/{slot}', [SdmkScheduleController::class, 'destroySlot'])->whereNumber('slot');

//         // Tab 6: integrasi SATUSEHAT
//         Route::put('integrasi', [SdmkIntegrationController::class, 'save']);
//         Route::post('integrasi/verify', [SdmkIntegrationController::class, 'verify'])->middleware('throttle:10,1');
//     });
// });

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