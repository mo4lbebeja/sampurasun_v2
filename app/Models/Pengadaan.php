<?php
// ================================================================
// File: app/Models/Pengadaan.php  — VERSI DIPERBAIKI
//
// Perubahan dari file kemarin:
// - Tambah: pejabat_id, no_pengadaan, status ke $fillable
//   Alasan: ketiganya di-set oleh KODE APLIKASI (bukan dari request user)
//   di PengadaanController::start() dan updateKontrak()
// - Hapus: relasi duplikat dokumen() — hanya tinggal dokumenUpbj()
// ================================================================

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pengadaan extends Model
{
    protected $table = 'pengadaan';

    /**
     * Kolom yang boleh diisi via mass assignment.
     *
     * Prinsip: semua kolom BOLEH kecuali `id` dan kolom yang
     * tidak pernah di-set lewat create()/update() di controller manapun.
     *
     * Kolom `status` aman di $fillable karena selalu di-set oleh
     * kode aplikasi (hardcoded), BUKAN dari $request->validated().
     * Kolom `no_pengadaan` aman karena di-generate oleh DocumentNumberService.
     */
    protected $fillable = [
        'usulan_id',
        'pejabat_id',
        'pejabat_penandatangan_id',
        'kpa_penandatangan_id',
        'penyedia_id',
        'no_pengadaan',
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
        'tanggal_kontrak' => 'date',
        'tanggal_mulai'   => 'date',
        'tanggal_selesai' => 'date',
        'nilai_kontrak'   => 'decimal:2',
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

    /**
     * Relasi dokumen UPBJ — nama tunggal.
     * Relasi duplikat dokumen() sudah dihapus.
     */
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
}