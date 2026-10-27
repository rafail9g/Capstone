<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MataKuliah extends Model
{
    protected $fillable = [
        'kode',
        'nama_mk',
        'sks',
        'lingkup',
        'program_studi_id',
        'kompetensi',
        'job_description_default',
    ];

    public function programStudi(): BelongsTo
    {
        return $this->belongsTo(ProgramStudi::class);
    }

    public function cpmks(): HasMany
    {
        return $this->hasMany(Cpmk::class);
    }

    public function jobdesks(): HasMany
    {
        return $this->hasMany(MkJobdesk::class)->orderBy('urutan');
    }

    public function cpls(): BelongsToMany
    {
        return $this->belongsToMany(Cpl::class, 'cpl_mata_kuliah', 'mata_kuliah_id', 'cpl_id')
            ->withTimestamps();
    }

    public function paketKonversis(): BelongsToMany
    {
        return $this->belongsToMany(PaketKonversi::class, 'paket_konversi_mata_kuliah', 'mata_kuliah_id', 'paket_konversi_id')
            ->withPivot('urutan')
            ->withTimestamps();
    }
}
