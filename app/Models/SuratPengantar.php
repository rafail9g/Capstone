<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SuratPengantar extends Model
{
    protected $fillable = [
        'nomor_surat',
        'tanggal_surat',
        'perihal',
        'tujuan_instansi',
        'nama_penerima',
        'alamat_instansi',
        'kota_instansi',
        'periode_magang_mulai',
        'periode_magang_selesai',
        'pejabat_ttd_id',
        'file_pdf_path',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_surat' => 'date',
            'periode_magang_mulai' => 'date',
            'periode_magang_selesai' => 'date',
        ];
    }

    public function pejabatTtd(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pejabat_ttd_id');
    }

    public function mahasiswas(): HasMany
    {
        return $this->hasMany(SuratPengantarMahasiswa::class);
    }
}
