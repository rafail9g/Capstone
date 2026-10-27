<?php

namespace App\Services;

use App\Models\Rkm;
use App\Models\RkpDokumen;
use App\Models\User;
use Carbon\Carbon;

class RkpGeneratorService
{
    /**
     * Generate atau perbarui dokumen RKP saat RKM disetujui.
     */
    public function generateFromRkm(Rkm $rkm, ?User $approver = null): RkpDokumen
    {
        $pendaftaran = $rkm->pendaftaranMbkm()->with(['programStudi', 'periodeAkademik', 'mitra'])->firstOrFail();

        $activeItems = $rkm->items()
            ->where('status_mk', 'aktif')
            ->with(['mataKuliah.cpmks.cpl', 'jobdesks' => fn ($q) => $q->orderBy('urutan')])
            ->get();

        $mataKuliahSnapshot = $activeItems->map(function ($item) {
            $cpmkCpl = $item->mataKuliah->cpmks->map(function ($cpmk) {
                return [
                    'cpmk_kode' => $cpmk->kode,
                    'cpmk_deskripsi' => $cpmk->deskripsi,
                    'cpl_kode' => $cpmk->cpl?->kode,
                    'cpl_deskripsi' => $cpmk->cpl?->deskripsi,
                ];
            })->values()->all();

            $jobdesk = $item->jobdesks->map(function ($j) {
                return [
                    'deskripsi' => $j->deskripsi,
                    'origin' => $j->origin,
                ];
            })->values()->all();

            return [
                'kode' => $item->kode_mk,
                'nama_mk' => $item->nama_mk,
                'sks' => $item->sks,
                'durasi_jam' => $item->durasi_jam,
                'kompetensi' => $item->kompetensi,
                'cpmk_cpl' => $cpmkCpl,
                'jobdesk' => $jobdesk,
            ];
        })->values()->all();

        $tgl = $rkm->approved_at ? Carbon::parse($rkm->approved_at) : now();
        $romawi = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'];

        $countThisMonth = RkpDokumen::whereYear('created_at', $tgl->year)
            ->whereMonth('created_at', $tgl->month)
            ->count() + 1;

        $nomorDokumen = sprintf('RKP/FASILKOM/%d/%s/%03d', $tgl->year, $romawi[$tgl->month], $countThisMonth);

        $snapshot = [
            'nomor_sk' => 'SK/Dekan/Fasilkom/'.$tgl->year.'/MBKM-'.sprintf('%03d', $rkm->id),
            'periode' => $pendaftaran->periodeAkademik->tahun_ajaran.' '.$pendaftaran->periodeAkademik->semester,
            'profil' => [
                'nim' => $pendaftaran->nim,
                'nama' => $pendaftaran->nama_lengkap,
                'program_studi' => $pendaftaran->programStudi->nama,
            ],
            'mitra' => [
                'nama' => $pendaftaran->mitra_magang_text,
                'alamat' => $pendaftaran->mitra?->alamat,
                'posisi' => $pendaftaran->posisi_magang,
            ],
            'total_sks' => $rkm->total_sks,
            'mata_kuliah' => $mataKuliahSnapshot,
        ];

        $prodiKode = $pendaftaran->programStudi->kode ?? '';
        $kaprodiMap = [
            'PSIF' => ['nama' => 'Fajrillah, S.Kom., M.Kom.', 'nip' => '198501012010121001'],
            'PSSI' => ['nama' => 'Windi Eka Yulia Retnani, S.Kom., M.Kom.', 'nip' => '198607142012122001'],
            'PSTI' => ['nama' => 'Priza Pandunata, S.Kom., M.Sc.', 'nip' => '198301312015041001'],
        ];
        $kaprodi = $kaprodiMap[$prodiKode] ?? ['nama' => 'Priza Pandunata, S.Kom., M.Sc.', 'nip' => '198301312015041001'];

        $semesterThnAka = '5 / '.$pendaftaran->periodeAkademik->tahun_ajaran.' '.$pendaftaran->periodeAkademik->semester;

        return RkpDokumen::updateOrCreate(
            ['rkm_id' => $rkm->id],
            [
                'pendaftaran_mbkm_id' => $pendaftaran->id,
                'nomor_dokumen' => $nomorDokumen,
                'judul' => 'Rencana Kegiatan Pembelajaran MBKM - '.$pendaftaran->nama_lengkap,
                'durasi_pelaksanaan' => '4 bulan',
                'semester_tahun_akademik' => $semesterThnAka,
                'prasyarat_sks' => '80 SKS',
                'nama_dpl' => $pendaftaran->dpl?->nama,
                'nip_dpl' => $pendaftaran->dpl?->nim_nip,
                'nama_kaprodi' => $kaprodi['nama'],
                'nip_kaprodi' => $kaprodi['nip'],
                'snapshot_data' => $snapshot,
                'status' => 'Draf',
                'disahkan_oleh' => $approver?->id,
                'disahkan_at' => null,
                'file_pdf_path' => null,
            ]
        );
    }
}
