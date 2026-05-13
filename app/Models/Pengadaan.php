<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pengadaan extends Model
{
    protected $table = 'pengadaan';
    protected $guarded = [];

    protected $casts = [
        'tanggal_kontrak'      => 'date',
        'tanggal_mulai'        => 'date',
        'tanggal_selesai'      => 'date',
        'nilai_kontrak'        => 'decimal:2',
    ];

    public function usulan(): BelongsTo
    {
        return $this->belongsTo(UsulanPengadaan::class, 'usulan_id');
    }

    public function penyedia(): BelongsTo
    {
        return $this->belongsTo(Penyedia::class);
    }

    public function pejabat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pejabat_id');
    }
    public function dokumenUpbj()
    {
        return $this->hasOne(\App\Models\DokumenUpbj::class, 'pengadaan_id');
    }
    public function dokumen(): HasOne
    {
        return $this->hasOne(DokumenUpbj::class, 'pengadaan_id');
    }

    public function pembayaran(): HasOne
    {
        return $this->hasOne(Pembayaran::class, 'pengadaan_id');
    }
    public function dokumenPengadaan(): HasMany
    {
        return $this->hasMany(DokumenPengadaan::class, 'pengadaan_id');
    }
    public function evaluasi(): HasOne
    {
        return $this->hasOne(Evaluasi::class, 'pengadaan_id');
    }
    public function pejabatPenandatangan()
    {
        return $this->belongsTo(User::class, 'pejabat_penandatangan_id');
    }
    public function kpaPenandatangan()
    {
        return $this->belongsTo(User::class, 'kpa_penandatangan_id');
    }
}