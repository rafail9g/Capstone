<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cpl extends Model
{
    protected $fillable = [
        'program_studi_id',
        'kode',
        'deskripsi',
    ];

    public function programStudi(): BelongsTo
    {
        return $this->belongsTo(ProgramStudi::class);
    }

    public function cpmks(): HasMany
    {
        return $this->hasMany(Cpmk::class);
    }

    public function mataKuliahs(): BelongsToMany
    {
        return $this->belongsToMany(MataKuliah::class, 'cpl_mata_kuliah', 'cpl_id', 'mata_kuliah_id')
            ->withTimestamps();
    }
}
