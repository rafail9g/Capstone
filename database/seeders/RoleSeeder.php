<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $roles = [
            ['name' => 'Admin', 'display_name' => 'Admin', 'description' => 'Administrator sistem dengan akses penuh.'],
            ['name' => 'Ketua Tim MBKM', 'display_name' => 'Ketua Tim MBKM', 'description' => 'Melakukan plotting PIC Verifikator, DPL, dan Penguji Semhas.'],
            ['name' => 'Tim MBKM', 'display_name' => 'Tim MBKM', 'description' => 'Dapat menjadi PIC Verifikator berkas pendaftaran dan RKM.'],
            ['name' => 'DPL', 'display_name' => 'Dosen Pembimbing Lapangan', 'description' => 'Membimbing mahasiswa selama magang.'],
            ['name' => 'Penguji Semhas', 'display_name' => 'Penguji Seminar Hasil', 'description' => 'Menguji seminar hasil magang mahasiswa lain.'],
            ['name' => 'Mahasiswa', 'display_name' => 'Mahasiswa', 'description' => 'Peserta magang MBKM.'],
        ];

        DB::table('roles')->insert(array_map(
            fn (array $r) => $r + ['created_at' => $now, 'updated_at' => $now],
            $roles
        ));
    }
}
