<?php

namespace App\Notifications;

use App\Models\Pengadaan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PengadaanKontrakNotification extends Notification
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
            'type'         => 'pengadaan.kontrak',
            'pengadaan_id' => $this->pengadaan->id,
            'no_pengadaan' => $this->pengadaan->no_pengadaan,
            'judul'        => $this->pengadaan->usulan?->judul,
            'url'          => "/dokumen/{$this->pengadaan->id}",
            'message'      => "Kontrak {$this->pengadaan->no_pengadaan} siap, mohon lengkapi dokumen UPBJ.",
        ];
    }
}