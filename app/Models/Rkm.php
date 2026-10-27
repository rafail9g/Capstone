<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Rkm extends Model
{
    protected $fillable = [
        'pendaftaran_mbkm_id',
        'user_id',
        'paket_konversi_id',
        'total_sks',
        'status',
        'catatan_pic',
        'reviewed_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
        ];
    }

    public function pendaftaranMbkm(): BelongsTo
    {
        return $this->belongsTo(PendaftaranMbkm::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function paketKonversi(): BelongsTo
    {
        return $this->belongsTo(PaketKonversi::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RkmMataKuliah::class, 'rkm_id');
    }

    public function rkpDokumen(): HasOne
    {
        return $this->hasOne(RkpDokumen::class);
    }
}
