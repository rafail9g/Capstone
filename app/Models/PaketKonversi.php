<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaketKonversi extends Model
{
    protected $fillable = [
        'nama_paket',
        'program_studi_id',
        'target_posisi',
        'jumlah_mk',
        'total_sks_pool',
    ];

    public function programStudi(): BelongsTo
    {
        return $this->belongsTo(ProgramStudi::class);
    }

    public function mataKuliahs(): BelongsToMany
    {
        return $this->belongsToMany(MataKuliah::class, 'paket_konversi_mata_kuliah', 'paket_konversi_id', 'mata_kuliah_id')
            ->withPivot('urutan')
            ->orderBy('paket_konversi_mata_kuliah.urutan')
            ->withTimestamps();
    }

    public function rkms(): HasMany
    {
        return $this->hasMany(Rkm::class);
    }
}
