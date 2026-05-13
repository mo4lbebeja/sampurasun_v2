<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DokumenUpbj extends Model
{
    protected $table = 'dokumen_upbj';

    protected $fillable = [
        'pengadaan_id',
        'petugas_id',
        'no_bast',
        'tanggal_bast',
        'file_bast',
        'file_invoice',
        'file_faktur_pajak',
        'file_kuitansi',
        'file_spp',
        'file_lainnya',
        'is_complete',
        'completed_at',
        'keterangan',
    ];

    protected $casts = [
        'tanggal_bast'    => 'date',
        'tanggal_invoice' => 'date',
        'file_lainnya'    => 'array',
        'is_complete'     => 'boolean',
        'completed_at'    => 'datetime',
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