<?php

namespace App\Observers;

use App\Models\Anggaran;
use App\Models\Pembayaran;

class PembayaranObserver
{
    /**
     * Saat pembayaran di-update, cek apakah status berubah ke/dari 'lunas'.
     */
    public function updated(Pembayaran $pembayaran): void
    {
        if (! $pembayaran->wasChanged('status')) {
            return;
        }

        $oldStatus = $pembayaran->getOriginal('status');
        $newStatus = $pembayaran->status;

        $relevantTransition = $newStatus === 'lunas' || $oldStatus === 'lunas';

        if (! $relevantTransition) {
            return;
        }

        $this->recomputeAnggaran($pembayaran);
    }

    public function deleted(Pembayaran $pembayaran): void
    {
        if ($pembayaran->status === 'lunas') {
            $this->recomputeAnggaran($pembayaran);
        }
    }

    private function recomputeAnggaran(Pembayaran $pembayaran): void
    {
        $anggaranId = $pembayaran->pengadaan?->usulan?->anggaran_id;

        if (! $anggaranId) {
            return;
        }

        Anggaran::find($anggaranId)?->recompute();
    }
}