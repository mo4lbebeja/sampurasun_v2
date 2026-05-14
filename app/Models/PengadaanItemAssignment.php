<?php
// ================================================================
// File: app/Models/PengadaanItemAssignment.php  — MODEL BARU
// ================================================================

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengadaanItemAssignment extends Model
{
    protected $table = 'pengadaan_item_assignments';

    protected $fillable = [
        'pengadaan_id',
        'usulan_item_id',
    ];

    public function pengadaan(): BelongsTo
    {
        return $this->belongsTo(Pengadaan::class);
    }

    public function usulanItem(): BelongsTo
    {
        return $this->belongsTo(UsulanItem::class, 'usulan_item_id');
    }
}