<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KategoriBarang extends Model
{
    protected $table = 'kategori_barang';
    protected $guarded = [];

    public function items(): HasMany
    {
        return $this->hasMany(UsulanItem::class);
    }
    public function usulanItems()
    {
        return $this->hasMany(\App\Models\UsulanItem::class, 'kategori_id');
    }
}
