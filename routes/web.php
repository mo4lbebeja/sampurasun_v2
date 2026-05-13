<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use App\Http\Controllers\UsulanPengadaanController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\PenyediaController;
use App\Http\Controllers\PengadaanController;
use App\Http\Controllers\DokumenUpbjController;
use App\Http\Controllers\PembayaranController;
use App\Http\Controllers\EvaluasiController;
use App\Http\Controllers\KategoriBarangController;
use App\Http\Controllers\AnggaranController;
use App\Http\Controllers\RealisasiAnggaranController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DpaAnggaranController;
use App\Http\Controllers\SubKegiatanController;
use App\Http\Controllers\UserController;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified', 'tahun.anggaran'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    // Custom routes untuk usulan HARUS sebelum Route::resource
    Route::post('/usulan/{usulan}/decide', [ApprovalController::class, 'decide'])
        ->name('usulan.decide');

    Route::post('/usulan/{usulan}/pengadaan', [PengadaanController::class, 'start'])
        ->name('pengadaan.start');

    // Usulan & Penyedia
    Route::resource('usulan', UsulanPengadaanController::class);
    Route::resource('penyedia', PenyediaController::class);

    // Approval
    Route::get('/approval', [ApprovalController::class, 'index'])
        ->name('approval.index');

    // Pengadaan
    Route::get('/pengadaan', [PengadaanController::class, 'index'])
        ->name('pengadaan.index');

    Route::get('/pengadaan/{pengadaan}', [PengadaanController::class, 'show'])
        ->name('pengadaan.show');

    Route::post('/pengadaan/{pengadaan}/kontrak', [PengadaanController::class, 'updateKontrak'])
        ->name('pengadaan.kontrak');

    Route::post('/pengadaan/{pengadaan}/cancel', [PengadaanController::class, 'cancel'])
        ->name('pengadaan.cancel');

    // Dokumen UPBJ
    Route::get('/dokumen', [DokumenUpbjController::class, 'index'])
        ->name('dokumen.index');

    Route::get('/dokumen/rekap', [DokumenUpbjController::class, 'rekap'])
        ->name('dokumen.rekap');

    Route::get('/dokumen/{pengadaan}/json', [DokumenUpbjController::class, 'dokumenJson'])
        ->name('dokumen.json');

    Route::get('/dokumen/{pengadaan}', [DokumenUpbjController::class, 'edit'])
        ->name('dokumen.edit');

    Route::get('/dokumen/{pengadaan}/cetak/{jenis}', [DokumenUpbjController::class, 'cetak'])
        ->name('dokumen.cetak');

    Route::post('/dokumen/{pengadaan}', [DokumenUpbjController::class, 'update'])
        ->name('dokumen.update');

    Route::post('/dokumen/{pengadaan}/complete', [DokumenUpbjController::class, 'complete'])
        ->name('dokumen.complete');

    Route::post('/dokumen/{pengadaan}/generate-dokumen', [DokumenUpbjController::class, 'generateDokumen'])
        ->name('dokumen.generate-dokumen');

    // Pembayaran
    Route::get('/pembayaran', [PembayaranController::class, 'index'])
        ->name('pembayaran.index');

    Route::get('/pembayaran/rekap', [PembayaranController::class, 'rekap'])
        ->name('pembayaran.rekap');

    Route::get('/pembayaran/{pengadaan}', [PembayaranController::class, 'edit'])
        ->name('pembayaran.edit');

    Route::post('/pembayaran/{pengadaan}', [PembayaranController::class, 'update'])
        ->name('pembayaran.update');

    Route::post('/pembayaran/{pengadaan}/complete', [PembayaranController::class, 'complete'])
        ->name('pembayaran.complete');

    // Evaluasi
    Route::get('/evaluasi', [EvaluasiController::class, 'index'])
        ->name('evaluasi.index');

    Route::get('/evaluasi/{pengadaan}', [EvaluasiController::class, 'edit'])
        ->name('evaluasi.edit');

    Route::post('/evaluasi/{pengadaan}', [EvaluasiController::class, 'store'])
        ->name('evaluasi.store');

    // Rekap Realisasi Anggaran
    Route::get('/realisasi', [RealisasiAnggaranController::class, 'index'])
        ->name('realisasi.index');

    Route::get('/realisasi/{anggaran}/detail', [RealisasiAnggaranController::class, 'detailJson'])
        ->name('realisasi.detail');

    // Master data
    Route::resource('kategori', KategoriBarangController::class);
    Route::resource('anggaran', AnggaranController::class);
    Route::resource('dpa-anggaran', DpaAnggaranController::class);
    Route::resource('sub-kegiatan', SubKegiatanController::class);
    Route::resource('users', UserController::class);
});

require __DIR__.'/settings.php';