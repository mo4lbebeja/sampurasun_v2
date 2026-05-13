<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsulanItem extends Model
{
    protected $table = 'usulan_items';
    protected $guarded = [];

    protected $casts = [
        'jumlah'                 => 'integer',
        'harga_satuan_estimasi'  => 'decimal:2',
        'subtotal'               => 'decimal:2',
    ];

    public function usulan(): BelongsTo
    {
        return $this->belongsTo(UsulanPengadaan::class, 'usulan_id');
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriBarang::class, 'kategori_id');
    }
}