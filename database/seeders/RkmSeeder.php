<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RkmSeeder extends Seeder
{
    private const HABIB = '197805122005011002';

    private const SARI = '198003152006042001';

    private const BAMBANG = '197911202005011003';

    /**
     * Mengisi: rkms, rkm_mata_kuliahs (snapshot MK), rkm_jobdesks.
     * Hanya mahasiswa dengan pendaftaran Terverifikasi yang punya RKM (sesuai aturan 4.1).
     *
     * Aturan yang tercermin di data:
     *  - total_sks = jumlah SKS MK berstatus 'aktif' (maks 20)
     *  - MK 'dropped' tidak dihitung (contoh: KSU1702 milik Aulia di-drop PIC)
     *  - jobdesk master_mk disalin dari mk_jobdesks; jobdesk custom ditambah mahasiswa/PIC
     *  - MK yang sudah dikonversi di periode lampau tidak dipilih lagi (Salsabila)
     */
    public function run(): void
    {
        $now = now();
        $user = DB::table('mahasiswas')->pluck('user_id', 'nim')->union(DB::table('dosens')->pluck('user_id', 'nip'));
        $prodi = DB::table('program_studis')->pluck('id', 'kode');
        $periode = DB::table('periode_akademiks')->get()
            ->mapWithKeys(fn ($p) => [$p->tahun_ajaran.' '.$p->semester => $p->id]);

        $specs = [
            [   // Danang: draft, belum diajukan
                'nim' => '242410102035', 'periode' => '2026/2027 Ganjil', 'prodi' => 'PSTI', 'paket' => 'UI/UX DESIGN',
                'status' => 'Belum Mengajukan', 'catatan_pic' => null, 'reviewer' => null, 'approved_at' => null,
                'items' => ['KSU1701' => 'aktif', 'TI2205' => 'aktif', 'TI2201' => 'aktif', 'TI3201' => 'aktif', 'TI3202' => 'aktif', 'TI2202' => 'aktif'],
                'custom' => ['TI2205' => ['Menyusun evaluasi usability testing terhadap aplikasi mitra.']],
            ],
            [   // Aulia: disetujui, KSU1702 di-drop PIC
                'nim' => '232410103021', 'periode' => '2026/2027 Ganjil', 'prodi' => 'PSSI', 'paket' => 'BUSINESS ANALYST',
                'status' => 'Disetujui',
                'catatan_pic' => 'Susunan mata kuliah dan rincian penugasan kerja sangat selaras. RKM disetujui.',
                'reviewer' => self::BAMBANG, 'approved_at' => '2026-10-17 15:00:00',
                'items' => ['KSU1701' => 'aktif', 'SI2201' => 'aktif', 'SI2203' => 'aktif', 'SI2204' => 'aktif', 'SI3205' => 'aktif', 'SI3206' => 'aktif', 'KSU1702' => 'dropped'],
                'custom' => ['SI3205' => ['Membuat dashboard penjualan harian untuk unit bisnis mitra pada Power BI.']],
            ],
            [   // Salsabila - periode lampau (riwayat konversi)
                'nim' => '232410102019', 'periode' => '2025/2026 Genap', 'prodi' => 'PSTI', 'paket' => 'FRONTEND DEVELOPER',
                'status' => 'Disetujui', 'catatan_pic' => 'RKM disetujui.',
                'reviewer' => self::SARI, 'approved_at' => '2026-03-10 10:30:00',
                'items' => ['KSU1701' => 'aktif', 'TI2201' => 'aktif', 'TI2205' => 'aktif', 'TI3201' => 'aktif'],
                'custom' => [],
            ],
            [   // Salsabila - periode aktif (tidak mengulang MK yang sudah dikonversi)
                'nim' => '232410102019', 'periode' => '2026/2027 Ganjil', 'prodi' => 'PSTI', 'paket' => 'FRONTEND DEVELOPER',
                'status' => 'Diajukan', 'catatan_pic' => null, 'reviewer' => null, 'approved_at' => null,
                'items' => ['KSU1702' => 'aktif', 'TI3202' => 'aktif', 'TI2202' => 'aktif', 'TI3204' => 'aktif', 'TI3203' => 'aktif'],
                'custom' => ['TI3202' => ['Menulis unit test untuk komponen antarmuka yang dikembangkan.']],
            ],
        ];

        foreach ($specs as $s) {
            $pendaftaranId = DB::table('pendaftaran_mbkms')
                ->where('nim', $s['nim'])
                ->where('periode_akademik_id', $periode[$s['periode']])
                ->value('id');

            $paketId = DB::table('paket_konversis')
                ->where('nama_paket', $s['paket'])
                ->where('program_studi_id', $prodi[$s['prodi']])
                ->value('id');

            $mks = DB::table('mata_kuliahs')->whereIn('kode', array_keys($s['items']))->get()->keyBy('kode');

            $totalSks = 0;
            foreach ($s['items'] as $kode => $status) {
                if ($status === 'aktif') {
                    $totalSks += $mks[$kode]->sks;
                }
            }

            $rkmId = DB::table('rkms')->insertGetId([
                'pendaftaran_mbkm_id' => $pendaftaranId,
                'user_id' => $user[$s['nim']],
                'paket_konversi_id' => $paketId,
                'total_sks' => $totalSks,
                'status' => $s['status'],
                'catatan_pic' => $s['catatan_pic'],
                'reviewed_by' => $s['reviewer'] ? $user[$s['reviewer']] : null,
                'approved_at' => $s['approved_at'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($s['items'] as $kode => $statusMk) {
                $mk = $mks[$kode];

                $rkmMkId = DB::table('rkm_mata_kuliahs')->insertGetId([
                    'rkm_id' => $rkmId,
                    'mata_kuliah_id' => $mk->id,
                    'kode_mk' => $mk->kode,
                    'nama_mk' => $mk->nama_mk,
                    'sks' => $mk->sks,
                    'durasi_jam' => $mk->sks * 40, // PKL (2 SKS) = 80 jam
                    'kompetensi' => $mk->kompetensi,
                    'status_mk' => $statusMk,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                // Salin jobdesk master (terkunci bagi mahasiswa)
                $urutan = 0;
                foreach (DB::table('mk_jobdesks')->where('mata_kuliah_id', $mk->id)->orderBy('urutan')->get() as $jd) {
                    DB::table('rkm_jobdesks')->insert([
                        'rkm_mata_kuliah_id' => $rkmMkId,
                        'urutan' => ++$urutan,
                        'deskripsi' => $jd->deskripsi,
                        'origin' => 'master_mk',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                // Jobdesk custom (bebas diedit/dihapus)
                foreach ($s['custom'][$kode] ?? [] as $deskripsi) {
                    DB::table('rkm_jobdesks')->insert([
                        'rkm_mata_kuliah_id' => $rkmMkId,
                        'urutan' => ++$urutan,
                        'deskripsi' => $deskripsi,
                        'origin' => 'custom',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }
}
