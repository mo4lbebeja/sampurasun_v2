<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DokumenUpbj extends Model
{
    protected $table = 'dokumen_upbj';
    protected $guarded = [];

    protected $casts = [
        'tanggal_bast'     => 'date',
        'tanggal_invoice'  => 'date',
        'lampiran_lain'    => 'array',
    ];

    public function pengadaan(): BelongsTo
    {
        return $this->belongsTo(Pengadaan::class, 'pengadaan_id');
        //return $this->belongsTo(\App\Models\Pengadaan::class, 'pengadaan_id');
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_id');
        //return $this->belongsTo(\App\Models\User::class, 'petugas_id');
    }
}