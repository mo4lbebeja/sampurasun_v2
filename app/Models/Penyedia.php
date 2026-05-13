<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Penyedia extends Model
{
    protected $table = 'penyedia';
    protected $guarded = [];

    public function pengadaan(): HasMany
    {
        return $this->hasMany(Pengadaan::class);
    }
}