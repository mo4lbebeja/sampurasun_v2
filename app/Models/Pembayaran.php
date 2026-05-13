<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pembayaran extends Model
{
    protected $table = 'pembayaran';
    protected $guarded = [];

    protected $casts = [
        'tanggal_spm'  => 'date',
        'tanggal_sp2d' => 'date',
        'nilai_bruto'  => 'decimal:2',
        'pph'          => 'decimal:2',
        'ppn'          => 'decimal:2',
        'nilai_bersih' => 'decimal:2',
    ];

    public function pengadaan(): BelongsTo
    {
        return $this->belongsTo(Pengadaan::class, 'pengadaan_id');
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }
}