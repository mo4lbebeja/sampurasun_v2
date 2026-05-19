<?php

namespace App\Observers;

use App\Models\Anggaran;
use App\Models\BelanjaLangsung;

class BelanjaLangsungObserver
{
    // Saat DIBUAT langsung dengan status dibayar (keuangan input langsung)
    public function created(BelanjaLangsung $belanja): void
    {
        if ($belanja->status === 'dibayar') {
            Anggaran::find($belanja->anggaran_id)?->recompute();
        }
    }

    // Saat STATUS BERUBAH ke/dari dibayar
    public function updated(BelanjaLangsung $belanja): void
    {
        if (! $belanja->wasChanged('status')) {
            return;
        }

        $lama = $belanja->getOriginal('status');
        $baru = $belanja->status;

        if ($baru === 'dibayar' || $lama === 'dibayar') {
            Anggaran::find($belanja->anggaran_id)?->recompute();
        }
    }

    // Saat DIHAPUS dan sudah dibayar
    public function deleted(BelanjaLangsung $belanja): void
    {
        if ($belanja->status === 'dibayar') {
            Anggaran::find($belanja->anggaran_id)?->recompute();
        }
    }
}