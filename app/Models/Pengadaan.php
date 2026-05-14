<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Pengadaan extends Model
{
    protected $table = 'pengadaan';

    protected $fillable = [
        'usulan_id',
        'pejabat_id',
        'pejabat_penandatangan_id',
        'kpa_penandatangan_id',
        'penyedia_id',
        'no_pengadaan',
        'nama_paket',       // ← kolom baru
        'estimasi_paket',   // ← kolom baru
        'metode',
        'tanggal_mulai',
        'tanggal_selesai',
        'no_kontrak',
        'tanggal_kontrak',
        'nilai_kontrak',
        'file_kontrak',
        'file_hps',
        'status',
        'catatan',
    ];

    protected $casts = [
        'tanggal_kontrak'  => 'date',
        'tanggal_mulai'    => 'date',
        'tanggal_selesai'  => 'date',
        'nilai_kontrak'    => 'decimal:2',
        'estimasi_paket'   => 'decimal:2',
    ];

    // ── Relasi ──────────────────────────────────────────────────

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

    public function pejabatPenandatangan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pejabat_penandatangan_id');
    }

    public function kpaPenandatangan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kpa_penandatangan_id');
    }

    public function dokumenUpbj(): HasOne
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

    /**
     * Item assignments — relasi ke tabel pivot pengadaan_item_assignments.
     */
    public function itemAssignments(): HasMany
    {
        return $this->hasMany(PengadaanItemAssignment::class, 'pengadaan_id');
    }

    /**
     * Item usulan yang masuk ke paket pengadaan ini,
     * melalui pivot pengadaan_item_assignments.
     */
    public function usulanItems(): BelongsToMany
    {
        return $this->belongsToMany(
            UsulanItem::class,
            'pengadaan_item_assignments',
            'pengadaan_id',
            'usulan_item_id'
        )->withTimestamps();
    }

    // ── Helper methods ───────────────────────────────────────────

    /**
     * Label paket untuk ditampilkan di UI.
     * Jika nama_paket diisi, tampilkan itu. Kalau tidak, fallback ke no_pengadaan.
     */
    public function labelPaket(): string
    {
        return $this->nama_paket ?: $this->no_pengadaan;
    }
}