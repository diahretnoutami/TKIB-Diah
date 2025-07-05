<?php

// routes/api.php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Ortu\OrtuSiswaController;
use App\Http\Controllers\Ortu\OrtuController;
use App\Http\Controllers\Ortu\LaporanController;
use App\Http\Controllers\Ortu\PenilaianController;
use App\Http\Controllers\Ortu\PertemuanController;
use App\Models\Pertemuan;

Route::prefix('ortu')->group(function () {
    Route::post('/login', [OrtuController::class, 'login']);
});

Route::middleware(['auth:sanctum'])->prefix('ortu')->group(function () {
    Route::post('/logout', [OrtuController::class, 'logout']);
    Route::get('/siswa', [OrtuSiswaController::class, 'index']);
    Route::get('/penilaian/harian/{noinduk}/tanggal', [PenilaianController::class, 'tanggalHarian']);
    Route::get('/penilaian/harian/{noinduk}/tanggal/{tanggal}', [PenilaianController::class, 'harianByTanggal']);
    Route::get('/penilaian/mingguan/{noinduk}/minggu', [PenilaianController::class, 'mingguList']);
    Route::get('/penilaian/mingguan/{noinduk}/minggu/{minggu}', [PenilaianController::class, 'mingguanByMinggu']);
    Route::get('/penilaian/bulanan/{noinduk}/bulan', [PenilaianController::class, 'listBulan']);
    Route::get('/penilaian/bulanan/{noinduk}/bulan/{bulan}', [PenilaianController::class, 'bulananByBulan']);
 Route::get('/laporan/{noinduk}', [LaporanController::class, 'listLaporan']);
 Route::get('/laporan/detail/{noinduk}/{id_ta}/{semester}', [LaporanController::class, 'getLaporanDetail']);

    // Route yang DIPERBARUI untuk preview dan download
    Route::get('/laporan/{noinduk}/preview/{id_ta}/{semester}', [LaporanController::class, 'previewLaporan']);
    Route::get('/laporan/{noinduk}/download/{id_ta}/{semester}', [LaporanController::class, 'downloadLaporan']);

    // pertemuan
     Route::get('/pertemuan/{noinduk}', [PertemuanController::class, 'index']);
    Route::post('/pertemuan', [PertemuanController::class, 'store']);
    Route::post('/save-fcm-token', [PertemuanController::class, 'saveFcmToken']);
});