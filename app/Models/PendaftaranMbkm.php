<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PendaftaranMbkm extends Model
{
    protected $fillable = [
        'user_id',
        'nim',
        'nama_lengkap',
        'program_studi_id',
        'mitra_id',
        'mitra_magang_text',
        'posisi_magang',
        'is_custom_role',
        'periode_akademik_id',
        'transkrip_file_name',
        'transkrip_file_path',
        'transkrip_file_size',
        'loa_file_name',
        'loa_file_path',
        'loa_file_size',
        'status',
        'catatan',
        'link_wa_grup',
        'verified_by',
        'verified_at',
        'is_plotted_pic',
        'pic_verifikator_id',
        'is_plotted_dpl_penguji',
        'dpl_id',
        'penguji_semhas_id',
    ];

    protected function casts(): array
    {
        return [
            'is_custom_role' => 'boolean',
            'is_plotted_pic' => 'boolean',
            'is_plotted_dpl_penguji' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function programStudi(): BelongsTo
    {
        return $this->belongsTo(ProgramStudi::class);
    }

    public function mitra(): BelongsTo
    {
        return $this->belongsTo(Mitra::class);
    }

    public function periodeAkademik(): BelongsTo
    {
        return $this->belongsTo(PeriodeAkademik::class);
    }

    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function picVerifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_verifikator_id');
    }

    public function dpl(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dpl_id');
    }

    public function pengujiSemhas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penguji_semhas_id');
    }

    public function rkm(): HasOne
    {
        return $this->hasOne(Rkm::class);
    }

    public function suratPengantarMahasiswas(): HasMany
    {
        return $this->hasMany(SuratPengantarMahasiswa::class);
    }
}
