<?php

namespace App\Notifications;

use App\Models\UsulanPengadaan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class UsulanBaruNotification extends Notification
{
    use Queueable;

    public function __construct(public UsulanPengadaan $usulan) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'      => 'usulan.submit',
            'usulan_id' => $this->usulan->id,
            'no_usulan' => $this->usulan->no_usulan,
            'judul'     => $this->usulan->judul,
            'pemohon'   => $this->usulan->pemohon?->name,
            'url'       => "/usulan/{$this->usulan->id}",
            'message'   => "Usulan baru masuk: {$this->usulan->no_usulan} — {$this->usulan->judul}",
        ];
    }
}