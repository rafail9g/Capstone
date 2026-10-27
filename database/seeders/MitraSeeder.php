<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MitraSeeder extends Seeder
{
    /** Data dummy: ganti dengan data mitra resmi (nomor PKS, kontak) sebelum dipakai sungguhan. */
    public function run(): void
    {
        $now = now();

        $rows = [
            [
                'nama' => 'PT. Telkom Indonesia',
                'nomor_pks' => '045/PKS/FASILKOM/2025',
                'alamat' => 'Jl. Jend. Gatot Subroto Kav. 52, Jakarta Selatan',
                'kontak' => 'Bpk. Rian Hidayat (0812-3456-7890)',
                'email' => 'magang@telkom.example',
                'deskripsi' => 'Perusahaan penyedia layanan telekomunikasi dan jaringan digital.',
            ],
            [
                'nama' => 'PT. Bank Rakyat Indonesia (Persero) Tbk',
                'nomor_pks' => '051/PKS/FASILKOM/2025',
                'alamat' => 'Jl. Jenderal Sudirman Kav. 44-46, Jakarta Pusat',
                'kontak' => 'Ibu Maya Lestari (0813-1111-2222)',
                'email' => 'talenta@bri.example',
                'deskripsi' => 'Lembaga perbankan dengan unit teknologi informasi dan analisis bisnis.',
            ],
            [
                'nama' => 'Dinas Komunikasi dan Informatika Provinsi Jawa Timur',
                'nomor_pks' => '062/PKS/FASILKOM/2025',
                'alamat' => 'Jl. Ahmad Yani No. 242-244, Surabaya',
                'kontak' => 'Bpk. Teguh Prasetyo (0815-2222-3333)',
                'email' => 'diskominfo@jatimprov.example',
                'deskripsi' => 'Instansi pemerintah daerah yang mengelola layanan TIK dan e-government.',
            ],
            [
                'nama' => 'CV. Jember Digital Kreasi',
                'nomor_pks' => '078/PKS/FASILKOM/2026',
                'alamat' => 'Jl. Kalimantan No. 15, Jember',
                'kontak' => 'Bpk. Andi Firmansyah (0821-3333-4444)',
                'email' => 'hello@jemberdigital.example',
                'deskripsi' => 'Software house lokal untuk pengembangan web, mobile, dan analitik data.',
            ],
        ];

        DB::table('mitras')->insert(array_map(
            fn (array $r) => $r + ['image_url' => null, 'created_at' => $now, 'updated_at' => $now],
            $rows
        ));
    }
}
