<?php

namespace App\Notifications;

use App\Models\BelanjaLangsung;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BelanjaLangsungTembusanNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly BelanjaLangsung $belanja
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $nominal = 'Rp ' . number_format($this->belanja->nominal, 0, ',', '.');
        $nama    = $this->belanja->pembelanja?->name ?? '—';

        return [
            'type'    => 'belanja.tembusan',
            'message' => "Tembusan: {$nama} mencatat belanja langsung {$nominal} — {$this->belanja->uraian}",
            'url'     => '/belanja-langsung',
        ];
    }
}