<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Approval extends Model
{
    protected $table = 'approvals';

    protected $fillable = [
        'usulan_id',
        'approver_id',
        'keputusan',
        'catatan',
        'tanggal_keputusan',
    ];

    protected $casts = [
        'tanggal_keputusan' => 'datetime',
    ];

    public function usulan(): BelongsTo
    {
        return $this->belongsTo(UsulanPengadaan::class, 'usulan_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}