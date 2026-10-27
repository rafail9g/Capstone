<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PeriodeAkademik extends Model
{
    protected $fillable = [
        'tahun_ajaran',
        'semester',
        'is_aktif',
    ];

    protected function casts(): array
    {
        return [
            'is_aktif' => 'boolean',
        ];
    }

    public function pendaftaranMbkms(): HasMany
    {
        return $this->hasMany(PendaftaranMbkm::class);
    }

    public function scopeAktif($query)
    {
        return $query->where('is_aktif', true);
    }
}
