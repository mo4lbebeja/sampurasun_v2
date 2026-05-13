<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DokumenPengadaan extends Model
{
    protected $table = 'dokumen_pengadaan';

    protected $guarded = [];

    protected $casts = [
        'tanggal' => 'date',
        'tahun' => 'integer',
        'nomor_urut' => 'integer',
    ];

    public function pengadaan(): BelongsTo
    {
        return $this->belongsTo(Pengadaan::class, 'pengadaan_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}