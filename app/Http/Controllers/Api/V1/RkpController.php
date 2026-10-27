<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Rkm;
use App\Models\RkpDokumen;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RkpController extends Controller
{
    public function preview(int $rkmId): JsonResponse
    {
        $rkp = RkpDokumen::with([
            'pengesah',
            'pendaftaranMbkm.programStudi',
            'pendaftaranMbkm.mitra',
            'pendaftaranMbkm.dpl',
            'rkm.items.mataKuliah.cpmks.cpl',
            'rkm.items.jobdesks',
        ])->where('rkm_id', $rkmId)->firstOrFail();

        $p = $rkp->pendaftaranMbkm;
        $rkm = $rkp->rkm;
        $activeItems = $rkm->items->where('status_mk', 'aktif')->values();

        $bagian1Courses = $activeItems->map(fn ($item) => [
            'id' => $item->id,
            'kode' => $item->kode_mk,
            'nama_mk' => $item->nama_mk,
            'sks' => $item->sks,
        ])->all();

        $bagian2Cpl = $activeItems->map(function ($item) {
            $cpls = $item->mataKuliah?->cpmks->pluck('cpl')->filter()->unique('id')->values();
            $cplText = $cpls->isNotEmpty()
                ? $cpls->map(fn ($c) => "{$c->kode} {$c->deskripsi}")->implode('", "')
                : 'CPL-02 Mampu menunjukkan sikap logis, kritis, inovatif serta adaptif terhadap perubahan teknologi dan kemajuan bidang Teknologi Informasi dan Komunikasi dengan pendekatan ilmiah dan etika ilmiah.';

            return [
                'mata_kuliah_id' => $item->mata_kuliah_id,
                'kode' => $item->kode_mk,
                'nama_mk' => $item->nama_mk,
                'cpl_deskripsi' => '"'.$cplText.'"',
            ];
        })->all();

        $bagian3Cpmk = $activeItems->map(function ($item) {
            $cpmkItems = $item->mataKuliah?->cpmks->map(fn ($c) => [
                'kode' => $c->kode,
                'deskripsi' => $c->deskripsi,
            ])->values()->all();

            if (empty($cpmkItems)) {
                $cpmkItems = [
                    ['kode' => 'CPMK-01', 'deskripsi' => 'Mahasiswa mampu menyelesaikan tugas industri secara profesional.'],
                ];
            }

            return [
                'mata_kuliah_id' => $item->mata_kuliah_id,
                'kode' => $item->kode_mk,
                'nama_mk' => $item->nama_mk,
                'sks' => $item->sks,
                'cpmk_items' => $cpmkItems,
            ];
        })->all();

        $bagian4Pengalaman = $activeItems->map(fn ($item) => [
            'mata_kuliah_id' => $item->mata_kuliah_id,
            'kode' => $item->kode_mk,
            'nama_mk' => $item->nama_mk,
            'kompetensi' => $item->kompetensi,
            'jobdesk_list' => $item->jobdesks->map(fn ($j) => [
                'urutan' => $j->urutan,
                'deskripsi' => $j->deskripsi,
            ])->values()->all(),
            'durasi_jam' => $item->durasi_jam ?? ($item->sks * 40),
        ])->all();

        $bagian5Monitoring = [
            [
                'aktivitas' => 'Monitoring setiap aktivitas pembelajaran untuk setiap ketercapaian CPMK melalui kelengkapan dan kesesuaian logbook progress kegiatan',
                'pihak_memonitor' => 'DPL',
            ],
            [
                'aktivitas' => 'Menyusun laporan akhir',
                'pihak_memonitor' => 'DPL',
            ],
            [
                'aktivitas' => 'Publikasi Produk',
                'pihak_memonitor' => 'DPL',
            ],
        ];

        $bagian6Asesmen = [
            'keterangan_asesmen' => 'Logbook, Laporan Akhir, Catatan Mitra, Seminar Hasil',
            'penilai_per_mk' => $activeItems->map(fn ($item) => [
                'mata_kuliah_id' => $item->mata_kuliah_id,
                'kode' => $item->kode_mk,
                'nama_mk' => $item->nama_mk,
                'penilai' => 'DPL, Mitra, dan Penguji',
            ])->all(),
        ];

        $bagian7Pengesahan = [
            'kriteria_evaluasi' => 'Mahasiswa akan mendapatkan nilai jika memenuhi kriteria kelengkapan logbook, laporan akhir, catatan mitra industri, dan kelulusan seminar hasil.',
            'pejabat' => [
                'kaprodi' => [
                    'peran' => 'Koordinator Program Studi',
                    'nama' => $rkp->nama_kaprodi,
                    'nip' => $rkp->nip_kaprodi,
                ],
                'dpl' => [
                    'peran' => 'Dosen Pembimbing Lapangan',
                    'nama' => $rkp->nama_dpl ?? ($p->dpl?->nama ?? '-'),
                    'nip' => $rkp->nip_dpl ?? ($p->dpl?->nim_nip ?? '-'),
                ],
                'mahasiswa' => [
                    'peran' => 'Mahasiswa',
                    'nama' => $p->nama_lengkap,
                    'nim' => $p->nim,
                ],
            ],
        ];

        return response()->json([
            'success' => true,
            'message' => 'Data pratinjau Dokumen RKP berhasil dimuat',
            'data' => [
                'rkp_id' => $rkp->id,
                'nomor_dokumen' => $rkp->nomor_dokumen,
                'status' => $rkp->status,
                'metadata' => [
                    'durasi_pelaksanaan' => $rkp->durasi_pelaksanaan,
                    'semester_tahun_akademik' => $rkp->semester_tahun_akademik,
                    'prasyarat_sks' => $rkp->prasyarat_sks,
                    'nama_dpl' => $rkp->nama_dpl ?? ($p->dpl?->nama ?? '-'),
                    'nip_dpl' => $rkp->nip_dpl ?? ($p->dpl?->nim_nip ?? '-'),
                    'nama_kaprodi' => $rkp->nama_kaprodi,
                    'nip_kaprodi' => $rkp->nip_kaprodi,
                ],
                'student_info' => [
                    'nama' => $p->nama_lengkap,
                    'nim' => $p->nim,
                    'fakultas' => 'Ilmu Komputer',
                    'prodi' => $p->programStudi?->nama,
                    'mitra' => $p->mitra_magang_text,
                    'posisi' => $p->posisi_magang,
                    'total_sks_capstone' => $activeItems->sum('sks'),
                ],
                'bagian_1_identitas' => [
                    'active_courses' => $bagian1Courses,
                ],
                'bagian_2_cpl' => $bagian2Cpl,
                'bagian_3_cpmk' => $bagian3Cpmk,
                'bagian_4_pengalaman_pembelajaran' => $bagian4Pengalaman,
                'bagian_5_monitoring' => $bagian5Monitoring,
                'bagian_6_asesmen' => $bagian6Asesmen,
                'bagian_7_pengesahan' => $bagian7Pengesahan,
            ],
        ]);
    }

    public function updateMetadata(int $rkmId, Request $request): JsonResponse
    {
        $rkp = RkpDokumen::where('rkm_id', $rkmId)->firstOrFail();

        // Normalisasi otomatis jika terdapat input typo "800 SKS"
        if ($request->has('prasyarat_sks') && str_contains($request->input('prasyarat_sks') ?? '', '800')) {
            $request->merge(['prasyarat_sks' => '80 SKS']);
        }

        $data = $request->validate([
            'durasi_pelaksanaan' => ['required', 'string', 'max:50'],
            'semester_tahun_akademik' => ['required', 'string', 'max:100'],
            'prasyarat_sks' => ['required', 'string', 'max:50'],
            'nama_dpl' => ['required', 'string', 'max:150'],
            'nip_dpl' => ['required', 'string', 'max:30'],
            'nama_kaprodi' => ['required', 'string', 'max:150'],
            'nip_kaprodi' => ['required', 'string', 'max:30'],
        ]);

        $rkp->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Metadata informasi Dokumen RKP berhasil diperbarui!',
            'data' => [
                'rkp_id' => $rkp->id,
                'durasi_pelaksanaan' => $rkp->durasi_pelaksanaan,
                'semester_tahun_akademik' => $rkp->semester_tahun_akademik,
                'prasyarat_sks' => $rkp->prasyarat_sks,
                'nama_dpl' => $rkp->nama_dpl,
                'nip_dpl' => $rkp->nip_dpl,
                'nama_kaprodi' => $rkp->nama_kaprodi,
                'nip_kaprodi' => $rkp->nip_kaprodi,
            ],
        ]);
    }

    public function downloadPdf(int $rkmId, Request $request): Response
    {
        $rkm = Rkm::with([
            'items.jobdesks',
            'items.mataKuliah.cpmks',
            'pendaftaranMbkm.user',
            'pendaftaranMbkm.programStudi',
            'pendaftaranMbkm.dpl',
            'rkpDokumen',
        ])->findOrFail($rkmId);

        if ($rkm->status !== 'Disetujui') {
            return response()->json([
                'success' => false,
                'error_code' => 'ERR_RKP_NOT_READY',
                'message' => 'Dokumen RKP belum dapat diunduh karena status RKM belum Disetujui.',
            ], 403);
        }

        $activeCourses = $rkm->items()->where('status_mk', 'aktif')->with(['jobdesks', 'mataKuliah.cpmks'])->get();
        $rkp = $rkm->rkpDokumen;
        $pendaftaran = $rkm->pendaftaranMbkm;
        $student = $pendaftaran->user;

        // Siapkan atribut tambahan pada $activeCourses untuk view
        foreach ($activeCourses as $mk) {
            $cpmkItems = $mk->mataKuliah?->cpmks->map(fn ($c) => [
                'kode' => $c->kode,
                'deskripsi' => $c->deskripsi,
            ])->all();
            $mk->cpmk_items = $cpmkItems;
        }

        $pdf = Pdf::loadView('pdf.rkp', [
            'rkp' => $rkp,
            'rkm' => $rkm,
            'student' => $student,
            'pendaftaran' => $pendaftaran,
            'activeCourses' => $activeCourses,
        ])->setPaper('a4', 'portrait');

        $namaClean = str_replace(' ', '_', $pendaftaran->nama_lengkap);
        $filename = "RKP-{$pendaftaran->nim}-{$namaClean}.pdf";

        return $request->query('mode') === 'download'
            ? $pdf->download($filename)
            : $pdf->stream($filename);
    }
}
