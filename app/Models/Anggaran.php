<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Anggaran extends Model
{
    protected $table = 'anggaran';

    protected $guarded = ['id', 'sisa', 'created_at', 'updated_at'];

    protected $casts = [
        'tahun'    => 'integer',
        'pagu'     => 'decimal:2',
        'terpakai' => 'decimal:2',
        'sisa'     => 'decimal:2',
        'is_active' => 'boolean',
    ];
    public function subKegiatan(): BelongsTo
    {
        return $this->belongsTo(SubKegiatan::class, 'sub_kegiatan_id');
    }
    public function usulanPengadaan()
    {
        return $this->hasMany(\App\Models\UsulanPengadaan::class, 'anggaran_id');
    }

    /**
     * Hitung ulang nilai terpakai dari sumber data:
     * - Komitmen: sum nilai_kontrak dari pengadaan dengan status 'kontrak'
     * - Realisasi: sum nilai_bayar dari pembayaran dengan status 'lunas'
     *
     * Dipanggil oleh observer setiap ada perubahan status pengadaan/pembayaran.
     */
    public function recompute(): void
    {
        // Komitmen: pengadaan yang sudah kontrak (status='kontrak')
        // Filter via relasi usulan -> anggaran_id
        $komitmen = \App\Models\Pengadaan::query()
            ->where('status', 'kontrak')
            ->whereHas('usulan', fn ($q) => $q->where('anggaran_id', $this->id))
            ->sum('nilai_kontrak');

        // Realisasi: pembayaran yang sudah lunas
        // Realisasi formal: pembayaran lunas
        $realisasiFormal = \App\Models\Pembayaran::query()
            ->where('status', 'lunas')
            ->whereHas('pengadaan.usulan', fn ($q) => $q->where('anggaran_id', $this->id))
            ->sum('nilai_bayar');

        // Realisasi langsung: belanja langsung yang sudah dibayar
        $realisasiLangsung = \App\Models\BelanjaLangsung::query()
            ->where('status', 'dibayar')
            ->where('anggaran_id', $this->id)
            ->sum('nominal');

        $realisasi = $realisasiFormal + $realisasiLangsung;

        // Total terpakai = komitmen + realisasi
        // Catatan: ini bisa double-count kalau pengadaan SAMA punya kontrak DAN pembayaran lunas.
        // Solusi: kurangi nilai kontrak yang sudah di-realisasi
        $kontrakSudahLunas = \App\Models\Pengadaan::query()
            ->where('status', 'kontrak')
            ->whereHas('pembayaran', fn ($q) => $q->where('status', 'lunas'))
            ->whereHas('usulan', fn ($q) => $q->where('anggaran_id', $this->id))
            ->sum('nilai_kontrak');

        $terpakai = $komitmen + $realisasi - $kontrakSudahLunas;

        // Simpan tanpa trigger event (avoid infinite loop kalau ada observer di Anggaran)
        $this->updateQuietly(['terpakai' => $terpakai]);

        // Catatan: 'sisa' adalah generated column, otomatis ter-update di DB
    }

    /**
     * Helper: hitung breakdown komitmen vs realisasi (untuk halaman rekap).
     */
    public function getKomitmenAttribute(): float
    {
        return (float) \App\Models\Pengadaan::query()
            ->where('status', 'kontrak')
            ->whereDoesntHave('pembayaran', fn ($q) => $q->where('status', 'lunas'))
            ->whereHas('usulan', fn ($q) => $q->where('anggaran_id', $this->id))
            ->sum('nilai_kontrak');
    }

    public function getRealisasiAttribute(): float
    {
        return (float) \App\Models\Pembayaran::query()
            ->where('status', 'lunas')
            ->whereHas('pengadaan.usulan', fn ($q) => $q->where('anggaran_id', $this->id))
            ->sum('nilai_bayar');
    }
}