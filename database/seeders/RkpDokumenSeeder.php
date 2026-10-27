<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RkpDokumenSeeder extends Seeder
{
    /**
     * RKP di-generate otomatis dari RKM yang berstatus Disetujui
     * (di aplikasi nyata dikerjakan RkpGeneratorService; di sini meniru hasilnya).
     */
    public function run(): void
    {
        $now = now();
        $habib = DB::table('dosens')->where('nip', '197805122005011002')->value('user_id');
        $romawi = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'];
        $urut = [];

        $rkms = DB::table('rkms')->where('status', 'Disetujui')->orderBy('approved_at')->get();

        foreach ($rkms as $rkm) {
            $p = DB::table('pendaftaran_mbkms as p')
                ->join('program_studis as ps', 'ps.id', '=', 'p.program_studi_id')
                ->join('periode_akademiks as pa', 'pa.id', '=', 'p.periode_akademik_id')
                ->leftJoin('mitras as m', 'm.id', '=', 'p.mitra_id')
                ->where('p.id', $rkm->pendaftaran_mbkm_id)
                ->select('p.*', 'ps.nama as prodi_nama', 'pa.tahun_ajaran', 'pa.semester', 'm.alamat as mitra_alamat')
                ->first();

            $mataKuliah = DB::table('rkm_mata_kuliahs')
                ->where('rkm_id', $rkm->id)->where('status_mk', 'aktif')->orderBy('id')->get()
                ->map(function ($mk) {
                    $cpmk = DB::table('cpmks as c')
                        ->join('cpls as l', 'l.id', '=', 'c.cpl_id')
                        ->where('c.mata_kuliah_id', $mk->mata_kuliah_id)
                        ->orderBy('c.kode')
                        ->get(['c.kode as cpmk_kode', 'c.deskripsi as cpmk_deskripsi', 'l.kode as cpl_kode', 'l.deskripsi as cpl_deskripsi'])
                        ->map(fn ($x) => (array) $x)->all();

                    $jobdesk = DB::table('rkm_jobdesks')
                        ->where('rkm_mata_kuliah_id', $mk->id)->orderBy('urutan')
                        ->get(['deskripsi', 'origin'])
                        ->map(fn ($x) => (array) $x)->all();

                    return [
                        'kode' => $mk->kode_mk,
                        'nama_mk' => $mk->nama_mk,
                        'sks' => $mk->sks,
                        'durasi_jam' => $mk->durasi_jam,
                        'kompetensi' => $mk->kompetensi,
                        'cpmk_cpl' => $cpmk,
                        'jobdesk' => $jobdesk,
                    ];
                })->values()->all();

            $tgl = Carbon::parse($rkm->approved_at);
            $key = $tgl->format('Y-m');
            $urut[$key] = ($urut[$key] ?? 0) + 1;
            $nomor = sprintf('RKP/FASILKOM/%d/%s/%03d', $tgl->year, $romawi[$tgl->month], $urut[$key]);

            $snapshot = [
                'nomor_sk' => 'SK/Dekan/Fasilkom/2026/MBKM-'.sprintf('%03d', $rkm->id), // dummy
                'periode' => $p->tahun_ajaran.' '.$p->semester,
                'profil' => ['nim' => $p->nim, 'nama' => $p->nama_lengkap, 'program_studi' => $p->prodi_nama],
                'mitra' => ['nama' => $p->mitra_magang_text, 'alamat' => $p->mitra_alamat, 'posisi' => $p->posisi_magang],
                'total_sks' => $rkm->total_sks,
                'mata_kuliah' => $mataKuliah,
            ];

            // RKP yang RKM-nya disetujui sebelum 16 Okt 2026 sudah Disahkan; yang lebih baru masih Draf
            $disahkan = $tgl->lt(Carbon::parse('2026-10-16'));

            DB::table('rkp_dokumens')->insert([
                'rkm_id' => $rkm->id,
                'pendaftaran_mbkm_id' => $rkm->pendaftaran_mbkm_id,
                'nomor_dokumen' => $nomor,
                'judul' => 'Rencana Kegiatan Pembelajaran MBKM - '.$p->nama_lengkap,
                'snapshot_data' => json_encode($snapshot, JSON_UNESCAPED_UNICODE),
                'status' => $disahkan ? 'Disahkan' : 'Draf',
                'disahkan_oleh' => $disahkan ? $habib : null,
                'disahkan_at' => $disahkan ? $tgl->copy()->addDay() : null,
                'file_pdf_path' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
