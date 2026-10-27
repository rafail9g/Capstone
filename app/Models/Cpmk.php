<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cpmk extends Model
{
    protected $fillable = [
        'mata_kuliah_id',
        'cpl_id',
        'kode',
        'deskripsi',
    ];

    public function mataKuliah(): BelongsTo
    {
        return $this->belongsTo(MataKuliah::class);
    }

    public function cpl(): BelongsTo
    {
        return $this->belongsTo(Cpl::class);
    }
}
