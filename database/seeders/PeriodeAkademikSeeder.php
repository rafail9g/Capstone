<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PeriodeAkademikSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $rows = [
            ['tahun_ajaran' => '2025/2026', 'semester' => 'Ganjil', 'is_aktif' => false],
            ['tahun_ajaran' => '2025/2026', 'semester' => 'Genap', 'is_aktif' => false],
            ['tahun_ajaran' => '2026/2027', 'semester' => 'Ganjil', 'is_aktif' => true], // periode aktif
        ];

        DB::table('periode_akademiks')->insert(array_map(
            fn (array $r) => $r + ['created_at' => $now, 'updated_at' => $now],
            $rows
        ));
    }
}
