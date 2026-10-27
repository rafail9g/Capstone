<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProgramStudiSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // Urutan: id 1 = TI, 2 = SI, 3 = IF (sesuai contoh program_studi_id: 1 di blueprint)
        $rows = [
            ['kode' => 'PSTI', 'nama' => 'Teknologi Informasi', 'cpl_prefix' => 'PSTI_', 'jenjang' => 'S1'],
            ['kode' => 'PSSI', 'nama' => 'Sistem Informasi', 'cpl_prefix' => 'PSSI_', 'jenjang' => 'S1'],
            ['kode' => 'PSIF', 'nama' => 'Informatika', 'cpl_prefix' => 'PSIF_', 'jenjang' => 'S1'],
        ];

        DB::table('program_studis')->insert(array_map(
            fn (array $r) => $r + ['created_at' => $now, 'updated_at' => $now],
            $rows
        ));
    }
}
