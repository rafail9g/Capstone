<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mitra extends Model
{
    protected $fillable = [
        'nama',
        'nomor_pks',
        'alamat',
        'kontak',
        'email',
        'deskripsi',
        'image_url',
    ];

    public function pendaftaranMbkms(): HasMany
    {
        return $this->hasMany(PendaftaranMbkm::class);
    }
}
