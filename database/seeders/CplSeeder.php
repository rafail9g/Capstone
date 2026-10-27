<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CplSeeder extends Seeder
{
    /**
     * 6 CPL per prodi, kode wajib berprefix prodi: PSTI_CPL01, PSSI_CPL01, PSIF_CPL01, dst.
     * Isi deskripsi = contoh; sesuaikan dengan dokumen kurikulum OBE resmi.
     * CPL06 (komunikasi/etika/kerja tim) dipakai MK Fakultas (PKL & Komunikasi Kerja).
     */
    public function run(): void
    {
        $now = now();
        $prodi = DB::table('program_studis')->pluck('id', 'kode');

        $data = [
            'PSTI' => [
                'Mampu menerapkan pengetahuan matematika, sains, dan teknologi informasi untuk menyelesaikan permasalahan rekayasa sistem.',
                'Mampu merancang, membangun, dan mengelola infrastruktur serta jaringan komputer yang andal dan aman.',
                'Mampu mengembangkan aplikasi web, mobile, dan perangkat cerdas (IoT) sesuai kebutuhan pengguna.',
                'Mampu mengelola, menganalisis, dan mengamankan data serta informasi organisasi.',
                'Mampu merancang solusi teknologi informasi yang berorientasi pengguna dan memenuhi standar kualitas.',
                'Mampu berkomunikasi, bekerja sama dalam tim, menjunjung etika profesi, dan belajar sepanjang hayat.',
            ],
            'PSSI' => [
                'Mampu menganalisis kebutuhan bisnis dan proses organisasi untuk menentukan solusi sistem informasi.',
                'Mampu merancang dan mengembangkan sistem informasi yang mendukung operasional dan strategi organisasi.',
                'Mampu mengelola data dan memanfaatkannya untuk pengambilan keputusan (data warehouse dan business intelligence).',
                'Mampu menerapkan tata kelola, audit, dan manajemen risiko teknologi informasi.',
                'Mampu mengelola proyek dan implementasi sistem informasi perusahaan secara efektif.',
                'Mampu berkomunikasi, bekerja sama dalam tim, menjunjung etika profesi, dan belajar sepanjang hayat.',
            ],
            'PSIF' => [
                'Mampu menerapkan konsep matematika, algoritma, dan struktur data untuk menyelesaikan masalah komputasi.',
                'Mampu merancang dan mengembangkan perangkat lunak dengan metodologi rekayasa perangkat lunak.',
                'Mampu merancang dan mengimplementasikan model kecerdasan buatan dan pembelajaran mesin.',
                'Mampu membangun sistem terdistribusi, komputasi awan, dan menerapkan keamanan siber.',
                'Mampu mengembangkan aplikasi web dan mobile dengan antarmuka yang berorientasi pengguna.',
                'Mampu berkomunikasi, bekerja sama dalam tim, menjunjung etika profesi, dan belajar sepanjang hayat.',
            ],
        ];

        foreach ($data as $kodeProdi => $list) {
            foreach ($list as $i => $deskripsi) {
                DB::table('cpls')->insert([
                    'program_studi_id' => $prodi[$kodeProdi],
                    'kode' => sprintf('%s_CPL%02d', $kodeProdi, $i + 1),
                    'deskripsi' => $deskripsi,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}
