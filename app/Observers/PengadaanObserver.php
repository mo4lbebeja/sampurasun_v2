<?php

namespace App\Observers;

use App\Models\Pengadaan;

/**
 * Observer yang memicu recompute anggaran dan refresh status usulan
 * setiap kali status pengadaan (paket) berubah.
 *
 * SEBELUM: langsung update usulan->status secara hardcode per kondisi
 * SESUDAH: delegasikan ke usulan->refreshStatus() yang menghitung
 *          berdasarkan kondisi SEMUA paket aktif dari usulan tersebut
 */
class PengadaanObserver
{
    public function updated(Pengadaan $pengadaan): void
    {
        if (! $pengadaan->wasChanged('status')) {
            return;
        }

        // Recompute anggaran (komitmen/realisasi) saat status paket berubah
        // ke/dari 'kontrak' atau 'selesai'
        $statusYangMempengaruhiAnggaran = ['kontrak', 'selesai'];
        $statusLama  = $pengadaan->getOriginal('status');
        $statusBaru  = $pengadaan->status;

        $perluRecompute = in_array($statusLama, $statusYangMempengaruhiAnggaran)
            || in_array($statusBaru, $statusYangMempengaruhiAnggaran);

        if ($perluRecompute) {
            $pengadaan->usulan?->anggaran?->recompute();
        }

        // Refresh status usulan secara agregat dari semua paket aktif.
        // SEBELUM: $pengadaan->usulan->update(['status' => 'dalam_pengadaan']) hardcode
        // SESUDAH: delegasikan ke method yang aware multi-paket
        $pengadaan->usulan?->refreshStatus();
    }

    public function deleted(Pengadaan $pengadaan): void
    {
        // Paket dihapus (batal) — refresh status usulan
        $pengadaan->usulan?->refreshStatus();
        $pengadaan->usulan?->anggaran?->recompute();
    }
}