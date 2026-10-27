<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\MasterDataController;
use App\Http\Controllers\Api\V1\PendaftaranMbkmController;
use App\Http\Controllers\Api\V1\PlottingController;
use App\Http\Controllers\Api\V1\RkmController;
use App\Http\Controllers\Api\V1\RkpController;
use App\Http\Controllers\Api\V1\SuratPengantarController;
use App\Http\Controllers\Api\V1\VerifikasiRkmController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // ----------------------------------------------------
    // Modul 1: Autentikasi
    // ----------------------------------------------------
    Route::post('auth/login', [AuthController::class, 'login']);

    // ----------------------------------------------------
    // Modul 2: Master Data & Kurikulum OBE
    // ----------------------------------------------------
    Route::get('mata-kuliah', [MasterDataController::class, 'mataKuliah']);
    Route::get('program-studi', [MasterDataController::class, 'programStudi']);
    Route::get('periode-akademik', [MasterDataController::class, 'periodeAkademik']);
    Route::get('periode-akademik/aktif', [MasterDataController::class, 'periodeAktif']);
    Route::get('mitra', [MasterDataController::class, 'mitra']);
    Route::get('cpl', [MasterDataController::class, 'cpl']);
    Route::get('paket-konversi', [MasterDataController::class, 'paketKonversi']);

    // File streaming PDF
    Route::get('pendaftaran-mbkm/{id}/file/{jenis}', [PendaftaranMbkmController::class, 'streamFile']);
    Route::get('rkp/{rkm_id}/pdf', [RkpController::class, 'downloadPdf']);
    Route::get('surat-pengantar/{id}/pdf', [SuratPengantarController::class, 'streamPdf']);

    // ----------------------------------------------------
    // Rute Terproteksi Sanctum (Bearer Token)
    // ----------------------------------------------------
    Route::middleware('auth:sanctum')->group(function () {
        // Auth profile & logout
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);

        // Modul 3: Pendaftaran MBKM
        Route::get('pendaftaran-mbkm', [PendaftaranMbkmController::class, 'index']);
        Route::get('pendaftaran-mbkm/my', [PendaftaranMbkmController::class, 'myPendaftaran']);
        Route::get('pendaftaran-mbkm/{id}', [PendaftaranMbkmController::class, 'show']);
        Route::post('pendaftaran-mbkm', [PendaftaranMbkmController::class, 'store']);
        Route::post('pendaftaran-mbkm/{id}/verifikasi', [PendaftaranMbkmController::class, 'verifikasi']);

        // Modul 4 & 7: Plotting (Tahap 1 PIC & Tahap 2 DPL / Penguji)
        Route::prefix('plotting')->group(function () {
            Route::get('dosen-list', [PlottingController::class, 'dosenList']);
            Route::get('tahap-1-pic', [PlottingController::class, 'tahap1Pic']);
            Route::post('tahap-1-pic', [PlottingController::class, 'storeTahap1Pic']);
            Route::get('tahap-2-dpl-penguji', [PlottingController::class, 'tahap2DplPenguji']);
            Route::post('tahap-2-dpl-penguji', [PlottingController::class, 'storeTahap2DplPenguji']);
        });

        // Modul 5: RKM Mahasiswa (Wajib Pendaftaran Terverifikasi)
        Route::middleware('pendaftaran.terverifikasi')->prefix('rkm')->group(function () {
            Route::get('available-courses', [RkmController::class, 'availableCourses']);
            Route::get('my-rkm', [RkmController::class, 'myRkm']);

            // Mutasi RKM Terkunci saat status 'Disetujui' (EnsureRkmNotLocked)
            Route::middleware('ensure.rkm.unlocked')->group(function () {
                Route::post('save-selection', [RkmController::class, 'saveSelection']);
                Route::post('items/{rkm_mk_id}/jobdesk', [RkmController::class, 'addJobdesk']);
                Route::put('jobdesks/{jobdesk_id}', [RkmController::class, 'updateJobdesk']);
                Route::delete('jobdesks/{jobdesk_id}', [RkmController::class, 'deleteJobdesk']);
                Route::delete('items/{rkm_mk_id}', [RkmController::class, 'deleteItem']);
                Route::post('submit', [RkmController::class, 'submit']);
            });
        });

        // Modul 6: Verifikasi RKM oleh PIC Verifikator
        Route::prefix('tim-mbkm/verifikasi-rkm')->group(function () {
            Route::get('/', [VerifikasiRkmController::class, 'index']);
            Route::get('{rkm_id}', [VerifikasiRkmController::class, 'show']);
            Route::patch('items/{rkm_mk_id}/toggle-drop', [VerifikasiRkmController::class, 'toggleDropItem']);
            Route::put('jobdesks/{jobdesk_id}', [VerifikasiRkmController::class, 'updateJobdesk']);
            Route::post('items/{rkm_mk_id}/jobdesk', [VerifikasiRkmController::class, 'addJobdesk']);
            Route::delete('jobdesks/{jobdesk_id}', [VerifikasiRkmController::class, 'deleteJobdesk']);
            Route::post('{rkm_id}/keputusan', [VerifikasiRkmController::class, 'keputusan']);
        });

        // Modul 8: Dokumen RKP & Surat Pengantar
        Route::get('rkp/{rkm_id}/preview', [RkpController::class, 'preview']);
        Route::put('rkp/{rkm_id}/metadata', [RkpController::class, 'updateMetadata']);

        Route::get('surat-pengantar', [SuratPengantarController::class, 'index']);
        Route::get('surat-pengantar/{id}', [SuratPengantarController::class, 'show']);
        Route::post('surat-pengantar', [SuratPengantarController::class, 'store']);
    });
});
