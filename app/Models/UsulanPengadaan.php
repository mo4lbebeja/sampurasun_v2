<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class UsulanPengadaan extends Model
{
    use SoftDeletes;

    protected $table = 'usulan_pengadaan';

    protected $fillable = [
        'no_usulan',
        'pemohon_id',
        'anggaran_id',
        'tanggal_usulan',
        'judul',
        'latar_belakang',
        'keterangan',
        'total_estimasi',
        'status',
        'catatan_pemohon',
        'file_pendukung',
        'submitted_at',
    ];

    protected $casts = [
        'tanggal_usulan' => 'date',
        'submitted_at'   => 'datetime',
        'total_estimasi' => 'decimal:2',
    ];

    public function pemohon(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pemohon_id');
    }

    public function anggaran(): BelongsTo
    {
        return $this->belongsTo(Anggaran::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(UsulanItem::class, 'usulan_id');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(Approval::class, 'usulan_id');
    }

    public function pengadaan(): HasOne
    {
        return $this->hasOne(Pengadaan::class, 'usulan_id');
    }
}