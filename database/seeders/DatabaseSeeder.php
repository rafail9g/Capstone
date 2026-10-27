<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Urutan penting: tabel induk dulu, baru tabel yang bergantung padanya.
     * Jalankan: php artisan migrate:fresh --seed
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,              // roles
            ProgramStudiSeeder::class,      // program_studis
            UserSeeder::class,              // users + role_user
            PeriodeAkademikSeeder::class,   // periode_akademiks
            MitraSeeder::class,             // mitras
            CplSeeder::class,               // cpls
            MataKuliahSeeder::class,        // mata_kuliahs + cpmks + cpl_mata_kuliah + mk_jobdesks
            PaketKonversiSeeder::class,     // paket_konversis + paket_konversi_mata_kuliah
            PendaftaranMbkmSeeder::class,   // pendaftaran_mbkms (+ file PDF dummy)
            RkmSeeder::class,               // rkms + rkm_mata_kuliahs + rkm_jobdesks
            RkpDokumenSeeder::class,        // rkp_dokumens
            SuratPengantarSeeder::class,    // surat_pengantars + surat_pengantar_mahasiswas
        ]);
    }
}
