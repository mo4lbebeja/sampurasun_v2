<?php

namespace App\Notifications;

use App\Models\Pengadaan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DokumenLengkapNotification extends Notification
{
    use Queueable;

    public function __construct(public Pengadaan $pengadaan) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'         => 'dokumen.complete',
            'pengadaan_id' => $this->pengadaan->id,
            'no_pengadaan' => $this->pengadaan->no_pengadaan,
            'judul'        => $this->pengadaan->usulan?->judul,
            'url'          => "/pembayaran/{$this->pengadaan->id}",
            'message'      => "Dokumen {$this->pengadaan->no_pengadaan} lengkap, siap diproses pembayaran.",
        ];
    }
}