<?php

namespace App\Notifications;

use App\Models\Pengadaan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PembayaranLunasNotification extends Notification
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
            'type'         => 'pembayaran.lunas',
            'pengadaan_id' => $this->pengadaan->id,
            'no_pengadaan' => $this->pengadaan->no_pengadaan,
            'judul'        => $this->pengadaan->usulan?->judul,
            'url'          => "/evaluasi/{$this->pengadaan->id}",
            'message'      => "Pembayaran {$this->pengadaan->no_pengadaan} lunas, siap dievaluasi.",
        ];
    }
}