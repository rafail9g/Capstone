<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RkmMataKuliah extends Model
{
    protected $fillable = [
        'rkm_id',
        'mata_kuliah_id',
        'kode_mk',
        'nama_mk',
        'sks',
        'durasi_jam',
        'kompetensi',
        'status_mk',
    ];

    public function rkm(): BelongsTo
    {
        return $this->belongsTo(Rkm::class);
    }

    public function mataKuliah(): BelongsTo
    {
        return $this->belongsTo(MataKuliah::class);
    }

    public function jobdesks(): HasMany
    {
        return $this->hasMany(RkmJobdesk::class)->orderBy('urutan');
    }
}
