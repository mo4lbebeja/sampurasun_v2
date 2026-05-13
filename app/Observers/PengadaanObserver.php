<?php

namespace App\Observers;

use App\Models\Anggaran;
use App\Models\Pengadaan;

class PengadaanObserver
{
    /**
     * Saat pengadaan di-update, cek apakah status berubah ke/dari 'kontrak'.
     * Kalau iya, recompute anggaran.terpakai.
     */
    public function updated(Pengadaan $pengadaan): void
    {
        // Hanya bereaksi kalau kolom 'status' berubah
        if (! $pengadaan->wasChanged('status')) {
            return;
        }

        $oldStatus = $pengadaan->getOriginal('status');
        $newStatus = $pengadaan->status;

        // Trigger recompute kalau:
        // - Naik ke kontrak (komitmen baru)
        // - Turun dari kontrak (komitmen dibatalkan)
        $relevantTransition = $newStatus === 'kontrak' || $oldStatus === 'kontrak';

        if (! $relevantTransition) {
            return;
        }

        $this->recomputeAnggaran($pengadaan);
    }

    /**
     * Saat pengadaan dihapus, juga recompute (jaga konsistensi).
     */
    public function deleted(Pengadaan $pengadaan): void
    {
        if ($pengadaan->status === 'kontrak') {
            $this->recomputeAnggaran($pengadaan);
        }
    }

    /**
     * Helper: cari anggaran terkait & recompute.
     */
    private function recomputeAnggaran(Pengadaan $pengadaan): void
    {
        $anggaranId = $pengadaan->usulan?->anggaran_id;

        if (! $anggaranId) {
            return;
        }

        Anggaran::find($anggaranId)?->recompute();
    }
}