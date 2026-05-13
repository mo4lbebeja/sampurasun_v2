<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubKegiatan extends Model
{
    protected $table = 'sub_kegiatan';

    protected $fillable = [
        'dpa_anggaran_id',
        'kode_sub_kegiatan',
        'nama_kegiatan',
        'tahun_anggaran',
        'is_active',
    ];

    protected $casts = [
        'dpa_anggaran_id' => 'integer',
        'tahun_anggaran' => 'integer',
        'is_active' => 'boolean',
    ];

    public function dpaAnggaran(): BelongsTo
    {
        return $this->belongsTo(DpaAnggaran::class, 'dpa_anggaran_id');
    }

    public function anggaran(): HasMany
    {
        return $this->hasMany(Anggaran::class, 'sub_kegiatan_id');
    }
}