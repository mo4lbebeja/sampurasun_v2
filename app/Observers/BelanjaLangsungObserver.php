<?php

namespace App\Observers;

use App\Models\Anggaran;
use App\Models\BelanjaLangsung;

/**
 * File: app/Observers/BelanjaLangsungObserver.php
 *
 * Memicu recompute anggaran setiap kali status belanja langsung
 * berubah ke/dari 'dibayar'.
 */
class BelanjaLangsungObserver
{
    /**
     * Saat DIBUAT langsung dengan status 'dibayar'
     * (keuangan input langsung nominal < threshold)
     */
    public function created(BelanjaLangsung $belanja): void
    {
        if ($belanja->status === 'dibayar') {
            Anggaran::find($belanja->anggaran_id)?->recompute();
        }
    }

    /**
     * Saat status BERUBAH — misalnya dari disetujui → dibayar
     */
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

    /**
     * Saat DIHAPUS dan sudah berstatus dibayar
     */
    public function deleted(BelanjaLangsung $belanja): void
    {
        if ($belanja->status === 'dibayar') {
            Anggaran::find($belanja->anggaran_id)?->recompute();
        }
    }
}