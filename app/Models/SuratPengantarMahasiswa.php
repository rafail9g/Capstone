<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuratPengantarMahasiswa extends Model
{
    protected $fillable = [
        'surat_pengantar_id',
        'pendaftaran_mbkm_id',
        'user_id',
        'nim',
        'nama_lengkap',
        'program_studi',
        'posisi_magang',
    ];

    public function suratPengantar(): BelongsTo
    {
        return $this->belongsTo(SuratPengantar::class);
    }

    public function pendaftaranMbkm(): BelongsTo
    {
        return $this->belongsTo(PendaftaranMbkm::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
