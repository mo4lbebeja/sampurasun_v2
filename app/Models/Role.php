<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Role extends Model
{
    use HasFactory;
    protected $table = 'roles';
    protected $guarded = [];
    protected $fillable = [
        'name',
    ];
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Alias: $role->slug → mengembalikan kolom `name`
     * (karena di migration, kolom name dipakai sebagai slug)
     */
    protected function slug(): Attribute
    {
        return Attribute::get(fn () => $this->name);
    }

    /**
     * Alias: $role->nama → mengembalikan kolom `display_name`
     */
    protected function nama(): Attribute
    {
        return Attribute::get(fn () => $this->display_name);
    }
}
