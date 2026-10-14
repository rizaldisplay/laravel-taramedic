<?php

use App\Http\Controllers\Api\Masterdata\Sdmk\SdmkController;
use App\Http\Controllers\Api\Masterdata\Sdmk\SdmkFileController;
use App\Http\Controllers\Api\Masterdata\Sdmk\SdmkIntegrationController;
use App\Http\Controllers\Api\Masterdata\Sdmk\SdmkLicenseController;
use App\Http\Controllers\Api\Masterdata\Sdmk\SdmkScheduleController;
use Illuminate\Support\Facades\Route;

// Gabungkan ke routes/api.php (atau muat lewat require). Prefix /api otomatis dari file api.php.

// Unduh berkas: hanya URL bertanda tangan (dibuat oleh SdmkFiles::url), tanpa token.
Route::get('sdmk/files/{type}/{id}', [SdmkFileController::class, 'show'])
    ->whereIn('type', ['foto', 'sip', 'kompetensi'])->whereNumber('id')
    ->middleware('signed')->name('sdmk.files.show');

Route::prefix('sdmk')->group(function () {
    // ---- Dasbor & daftar ----
    Route::get('/', [SdmkController::class, 'index'])->middleware('throttle:120,1');
    Route::get('stats', [SdmkController::class, 'stats']);
    Route::get('options', [SdmkController::class, 'options']);
    Route::get('history', [SdmkController::class, 'historyAll']);
    Route::get('audit', [SdmkController::class, 'auditAll']);

    // Throttle ketat: endpoint ini mengungkap apakah seseorang terdaftar sebagai SDMK.
    Route::get('check-nik/{nik}', [SdmkController::class, 'checkNik'])
        ->where('nik', '[0-9]{16}')->middleware('throttle:30,1');

    Route::post('/', [SdmkController::class, 'store']);

    Route::prefix('{sdmk}')->where(['sdmk' => '[0-9]+'])->group(function () {
        Route::get('/', [SdmkController::class, 'show']);
        Route::get('history', [SdmkController::class, 'history']);
        Route::get('audit', [SdmkController::class, 'audit']);

        // Tab 1-2 & catatan
        Route::put('identitas', [SdmkController::class, 'updateIdentitas']);
        Route::post('foto', [SdmkController::class, 'foto']);
        Route::put('profesi', [SdmkController::class, 'profesi']);
        Route::put('catatan', [SdmkController::class, 'catatan']);

        // Kompetensi tambahan
        Route::post('kompetensi', [SdmkLicenseController::class, 'storeKompetensi']);
        Route::delete('kompetensi/{kompetensi}', [SdmkLicenseController::class, 'destroyKompetensi'])->whereNumber('kompetensi');

        // Tab 3: STR & SIP
        Route::put('str', [SdmkLicenseController::class, 'saveStr']);
        Route::post('str/verify', [SdmkLicenseController::class, 'verifyStr'])->middleware('throttle:20,1');
        Route::post('sip', [SdmkLicenseController::class, 'storeSip']);
        // Frontend mengirim POST + _method=PUT karena multipart; Route::match menerima keduanya.
        Route::match(['put', 'post'], 'sip/{sip}', [SdmkLicenseController::class, 'updateSip'])->whereNumber('sip');
        Route::delete('sip/{sip}', [SdmkLicenseController::class, 'destroySip'])->whereNumber('sip');

        // Tab 4: penempatan
        Route::post('penempatan', [SdmkScheduleController::class, 'storePenempatan']);
        Route::put('penempatan/{penempatan}', [SdmkScheduleController::class, 'updatePenempatan'])->whereNumber('penempatan');
        Route::delete('penempatan/{penempatan}', [SdmkScheduleController::class, 'destroyPenempatan'])->whereNumber('penempatan');

        // Tab 5: jadwal & slot
        Route::put('jadwal', [SdmkScheduleController::class, 'saveJadwal']);
        Route::post('slots', [SdmkScheduleController::class, 'storeSlot']);
        Route::put('slots/{slot}', [SdmkScheduleController::class, 'updateSlot'])->whereNumber('slot');
        Route::delete('slots/{slot}', [SdmkScheduleController::class, 'destroySlot'])->whereNumber('slot');

        // Tab 6: integrasi SATUSEHAT
        Route::put('integrasi', [SdmkIntegrationController::class, 'save']);
        Route::post('integrasi/verify', [SdmkIntegrationController::class, 'verify'])->middleware('throttle:10,1');
    });
});
