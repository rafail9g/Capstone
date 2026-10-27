<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaketKonversiSeeder extends Seeder
{
    /**
     * Paket konversi = POOL pilihan MK per peran magang (total SKS pool > 20).
     * Mahasiswa memilih sendiri MK dari pool dengan batas aktif <= 20 SKS.
     * jumlah_mk dan total_sks_pool dihitung otomatis dari isi pool.
     */
    public function run(): void
    {
        $now = now();
        $prodi = DB::table('program_studis')->pluck('id', 'kode');
        $mk = DB::table('mata_kuliahs')->get()->keyBy('kode');

        // [kode prodi, nama paket, target posisi, [kode MK di pool]]
        $pakets = [
            ['PSTI', 'UI/UX DESIGN', 'UI/UX Designer',
                ['KSU1701', 'KSU1702', 'TI2205', 'TI2201', 'TI3201', 'TI3202', 'TI2202', 'TI3203']],
            ['PSTI', 'FRONTEND DEVELOPER', 'Frontend Developer',
                ['KSU1701', 'KSU1702', 'TI2201', 'TI2205', 'TI3201', 'TI3202', 'TI2202', 'TI3204', 'TI3203']],
            ['PSTI', 'NETWORK ENGINEER', 'Network Engineer',
                ['KSU1701', 'KSU1702', 'TI2203', 'TI2204', 'TI3203', 'TI3204', 'TI3206', 'TI3205']],

            ['PSSI', 'BUSINESS ANALYST', 'Business Analyst',
                ['KSU1701', 'KSU1702', 'SI2201', 'SI2203', 'SI2204', 'SI3206', 'SI3205', 'SI3201', 'SI2205']],
            ['PSSI', 'DATA ANALYST', 'Data Analyst',
                ['KSU1701', 'KSU1702', 'SI2202', 'SI3204', 'SI3205', 'SI2201', 'SI3203', 'SI3206']],
            ['PSSI', 'IT AUDITOR', 'IT Auditor',
                ['KSU1701', 'KSU1702', 'SI3202', 'SI3207', 'SI2203', 'SI2201', 'SI3201', 'SI3206']],

            ['PSIF', 'MOBILE DEVELOPER', 'Mobile Developer',
                ['KSU1701', 'KSU1702', 'IF3102', 'IF2103', 'IF2104', 'IF3107', 'IF2102', 'IF3101']],
            ['PSIF', 'AI / MACHINE LEARNING ENGINEER', 'AI/ML Engineer',
                ['KSU1701', 'KSU1702', 'IF3103', 'IF3104', 'IF3108', 'IF2101', 'IF2102', 'IF3105']],
            ['PSIF', 'BACKEND DEVELOPER', 'Backend Developer',
                ['KSU1701', 'KSU1702', 'IF2102', 'IF2103', 'IF2104', 'IF3101', 'IF3105', 'IF3106']],
        ];

        foreach ($pakets as [$kodeProdi, $nama, $target, $kodes]) {
            $totalSks = collect($kodes)->sum(fn ($k) => $mk[$k]->sks);

            $paketId = DB::table('paket_konversis')->insertGetId([
                'nama_paket' => $nama,
                'program_studi_id' => $prodi[$kodeProdi],
                'target_posisi' => $target,
                'jumlah_mk' => count($kodes),
                'total_sks_pool' => $totalSks,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($kodes as $i => $kode) {
                DB::table('paket_konversi_mata_kuliah')->insert([
                    'paket_konversi_id' => $paketId,
                    'mata_kuliah_id' => $mk[$kode]->id,
                    'urutan' => $i + 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}
