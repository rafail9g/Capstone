<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RkpDokumen extends Model
{
    protected $fillable = [
        'rkm_id',
        'pendaftaran_mbkm_id',
        'nomor_dokumen',
        'judul',
        'durasi_pelaksanaan',
        'semester_tahun_akademik',
        'prasyarat_sks',
        'nama_dpl',
        'nip_dpl',
        'nama_kaprodi',
        'nip_kaprodi',
        'snapshot_data',
        'status',
        'disahkan_oleh',
        'disahkan_at',
        'file_pdf_path',
    ];

    protected function casts(): array
    {
        return [
            'snapshot_data' => 'array',
            'disahkan_at' => 'datetime',
        ];
    }

    public function rkm(): BelongsTo
    {
        return $this->belongsTo(Rkm::class);
    }

    public function pendaftaranMbkm(): BelongsTo
    {
        return $this->belongsTo(PendaftaranMbkm::class);
    }

    public function pengesah(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disahkan_oleh');
    }
}
