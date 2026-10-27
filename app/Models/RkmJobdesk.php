<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RkmJobdesk extends Model
{
    protected $fillable = [
        'rkm_mata_kuliah_id',
        'urutan',
        'deskripsi',
        'origin',
    ];

    public function rkmMataKuliah(): BelongsTo
    {
        return $this->belongsTo(RkmMataKuliah::class);
    }

    // Mahasiswa hanya boleh mengedit/menghapus jobdesk bertipe custom
    public function canBeEditedByStudent(): bool
    {
        return $this->origin === 'custom';
    }
}
