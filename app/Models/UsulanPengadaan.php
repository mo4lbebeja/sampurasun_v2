<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    // ── Relasi ──────────────────────────────────────────────────

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

    /**
     * SEBELUM: hasOne(Pengadaan::class, 'usulan_id')
     * SESUDAH: hasMany — satu usulan bisa punya banyak paket pengadaan
     */
    public function pengadaan(): HasMany
    {
        return $this->hasMany(Pengadaan::class, 'usulan_id');
    }

    /**
     * Hanya paket yang masih aktif (bukan batal).
     */
    public function pengadaanAktif(): HasMany
    {
        return $this->hasMany(Pengadaan::class, 'usulan_id')
            ->where('status', '!=', 'batal');
    }

    // ── Helper methods untuk multi-paket ────────────────────────

    /**
     * Berapa paket yang sudah dibuat dari usulan ini.
     */
    public function jumlahPaket(): int
    {
        return $this->pengadaanAktif()->count();
    }

    /**
     * Berapa paket yang sudah selesai.
     */
    public function paketSelesai(): int
    {
        return $this->pengadaanAktif()->where('status', 'selesai')->count();
    }

    /**
     * Apakah semua paket sudah selesai.
     */
    public function semuaPaketSelesai(): bool
    {
        $total   = $this->jumlahPaket();
        $selesai = $this->paketSelesai();

        return $total > 0 && $total === $selesai;
    }

    /**
     * ID item yang sudah di-assign ke paket pengadaan.
     * Berguna untuk menampilkan item mana yang masih "belum dipaketkan".
     */
    public function itemSudahDiassign(): array
    {
        return PengadaanItemAssignment::query()
            ->whereHas('pengadaan', fn ($q) => $q->where('usulan_id', $this->id))
            ->pluck('usulan_item_id')
            ->toArray();
    }

    /**
     * Item yang belum masuk ke paket manapun.
     */
    public function itemBelumDipaketkan(): HasMany
    {
        $assigned = $this->itemSudahDiassign();

        return $this->items()->whereNotIn('id', $assigned);
    }

    /**
     * Summary progress untuk ditampilkan di UI.
     * Contoh output: "2 dari 3 paket selesai"
     */
    public function progressLabel(): string
    {
        $total   = $this->jumlahPaket();
        $selesai = $this->paketSelesai();

        if ($total === 0) return 'Belum ada paket';
        if ($selesai === $total) return 'Semua paket selesai';
        return "{$selesai} dari {$total} paket selesai";
    }

    /**
     * Refresh status usulan berdasarkan kondisi semua paket.
     * Dipanggil oleh PengadaanObserver saat ada perubahan status paket.
     *
     * Aturan:
     * - Jika belum ada paket aktif → status tetap 'disetujui'
     * - Jika ada paket aktif tapi belum semua selesai → 'dalam_pengadaan'
     * - Jika semua paket selesai → 'selesai'
     *
     * Catatan: status dokumen/pembayaran/evaluasi kini di-track per paket,
     * bukan per usulan. Usulan hanya tahu 3 state: disetujui, dalam_pengadaan, selesai.
     */
    public function refreshStatus(): void
    {
        // Jangan ubah status kalau masih di tahap sebelum pengadaan
        if (! in_array($this->status, [
            'disetujui', 'dalam_pengadaan', 'dokumen',
            'pembayaran', 'evaluasi', 'selesai',
        ])) {
            return;
        }

        $total   = $this->jumlahPaket();
        $selesai = $this->paketSelesai();

        if ($total === 0) {
            $newStatus = 'disetujui';
        } elseif ($total === $selesai) {
            $newStatus = 'selesai';
        } else {
            $newStatus = 'dalam_pengadaan';
        }

        if ($this->status !== $newStatus) {
            $this->updateQuietly(['status' => $newStatus]);
        }
    }
}