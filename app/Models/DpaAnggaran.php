<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DpaAnggaran extends Model
{
    protected $table = 'dpa_anggaran';

    protected $fillable = [
        'tahun_anggaran',
        'no_dpa',
        'tanggal_dpa',
        'nama_dpa',
        'keterangan',
        'is_active',
    ];

    protected $casts = [
        'tahun_anggaran' => 'integer',
        'tanggal_dpa' => 'date',
        'is_active' => 'boolean',
    ];

    public function subKegiatan(): HasMany
    {
        return $this->hasMany(SubKegiatan::class, 'dpa_anggaran_id');
    }
}