<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProgramStudi extends Model
{
    protected $fillable = [
        'kode',
        'nama',
        'cpl_prefix',
        'jenjang',
    ];

    public function dosens(): HasMany
    {
        return $this->hasMany(Dosen::class);
    }

    public function mahasiswas(): HasMany
    {
        return $this->hasMany(Mahasiswa::class);
    }

    public function cpls(): HasMany
    {
        return $this->hasMany(Cpl::class);
    }

    public function mataKuliahs(): HasMany
    {
        return $this->hasMany(MataKuliah::class);
    }

    public function paketKonversis(): HasMany
    {
        return $this->hasMany(PaketKonversi::class);
    }
}
