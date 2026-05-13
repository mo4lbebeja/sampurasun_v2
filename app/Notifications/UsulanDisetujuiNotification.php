<?php

namespace App\Notifications;

use App\Models\UsulanPengadaan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class UsulanDisetujuiNotification extends Notification
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
            'type'      => 'approval.approve',
            'usulan_id' => $this->usulan->id,
            'no_usulan' => $this->usulan->no_usulan,
            'judul'     => $this->usulan->judul,
            'url'       => "/usulan/{$this->usulan->id}",
            'message'   => "Usulan {$this->usulan->no_usulan} disetujui, siap diproses pengadaan.",
        ];
    }
}