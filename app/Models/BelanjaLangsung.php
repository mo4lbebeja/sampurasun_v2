<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BelanjaLangsung extends Model
{
    protected $table = 'belanja_langsung';

    protected $fillable = [
        'anggaran_id', 'pembelanja_id', 'approver_id', 'dibayar_oleh',
        'no_nota', 'tanggal_belanja', 'uraian', 'jenis', 'nominal',
        'file_nota', 'status', 'catatan', 'catatan_penolakan',
        'tanggal_dibayar', 'tahun_anggaran',
    ];

    protected $casts = [
        'nominal'         => 'decimal:2',
        'tanggal_belanja' => 'date',
        'tanggal_dibayar' => 'date',
        'tahun_anggaran'  => 'integer',
    ];

    // ── Label ─────────────────────────────────────────────────────

    public static array $jenisLabel = [
        'atk'        => 'ATK',
        'konsumsi'   => 'Konsumsi/Snack',
        'transport'  => 'Transport',
        'materai'    => 'Materai',
        'fotokopi'   => 'Fotokopi/Print',
        'kebersihan' => 'Kebersihan',
        'lainnya'    => 'Lainnya',
    ];

    public function getJenisLabelAttribute(): string
    {
        return static::$jenisLabel[$this->jenis] ?? $this->jenis;
    }

    // ── Relations ─────────────────────────────────────────────────

    public function anggaran(): BelongsTo
    {
        return $this->belongsTo(Anggaran::class);
    }

    public function pembelanja(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pembelanja_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function dibayarOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibayar_oleh');
    }
}