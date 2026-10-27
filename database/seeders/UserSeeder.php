<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Struktur akun:
     *   users      -> data umum (nama, email, password, kontak) + role_user
     *   dosens     -> profil dosen/tendik (NIP, prodi)
     *   mahasiswas -> profil mahasiswa (NIM, prodi, angkatan)
     *
     * Semua akun memakai password: password123
     * ID hasil seed: 1 Admin, 2 Habib, 3 Sari, 4 Bambang, 5 Rina, 6 Fauzi, 7 Dewi, 8 Hendra, 9-13 Mahasiswa.
     * Admin tidak punya profil dosen/mahasiswa -> login memakai email.
     */
    public function run(): void
    {
        $now = now();
        $password = Hash::make('password123'); // di-hash sekali, dipakai semua akun
        $prodi = DB::table('program_studis')->pluck('id', 'kode');
        $roles = DB::table('roles')->pluck('id', 'name');

        // Admin (tanpa profil)
        $this->buatUser('Administrator Fasilkom', 'admin@fasilkom.ac.id', '081234560001', ['Admin'], $password, $roles);

        // Dosen: [nama, nip, email, kode_prodi, [roles], no_hp]
        $dosens = [
            ['Dr. Habib, M.Kom', '197805122005011002', 'habib@fasilkom.ac.id', 'PSTI', ['Ketua Tim MBKM', 'Tim MBKM', 'DPL', 'Penguji Semhas'], '081234560002'],
            ['Dr. Sari Wulandari, S.Kom., M.T.', '198003152006042001', 'sari.wulandari@fasilkom.ac.id', 'PSSI', ['Tim MBKM', 'DPL', 'Penguji Semhas'], '081234560003'],
            ['Ir. Bambang Hartono, M.Cs.', '197911202005011003', 'bambang.hartono@fasilkom.ac.id', 'PSIF', ['Tim MBKM', 'DPL', 'Penguji Semhas'], '081234560004'],
            ['Dr. Rina Kusumawati, M.T.', '198205102008122001', 'rina.kusumawati@fasilkom.ac.id', 'PSTI', ['DPL', 'Penguji Semhas'], '081234560005'],
            ['Ahmad Fauzi, S.Kom., M.Kom.', '198506142010121002', 'ahmad.fauzi@fasilkom.ac.id', 'PSSI', ['DPL'], '081234560006'],
            ['Dewi Anggraini, S.Si., M.Kom.', '198709252012122001', 'dewi.anggraini@fasilkom.ac.id', 'PSIF', ['DPL', 'Penguji Semhas'], '081234560007'],
            ['Hendra Saputra, S.Kom., M.T.', '198811302014041001', 'hendra.saputra@fasilkom.ac.id', 'PSTI', ['Penguji Semhas'], '081234560008'],
        ];

        foreach ($dosens as [$nama, $nip, $email, $kodeProdi, $roleNames, $noHp]) {
            $userId = $this->buatUser($nama, $email, $noHp, $roleNames, $password, $roles);

            DB::table('dosens')->insert([
                'user_id' => $userId,
                'nip' => $nip,
                'program_studi_id' => $prodi[$kodeProdi],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Mahasiswa: [nama, nim, kode_prodi, angkatan, no_hp]
        $mahasiswas = [
            ['Danang Rizki Ramadhan', '242410102035', 'PSTI', 2024, '082111110001'],
            ['Aulia Putri Maharani', '232410103021', 'PSSI', 2023, '082111110002'],
            ['Rizky Pratama Wijaya', '242410101044', 'PSIF', 2024, '082111110003'],
            ['Nabila Safitri', '242410103058', 'PSSI', 2024, '082111110004'],
            ['Salsabila Nurhaliza', '232410102019', 'PSTI', 2023, '082111110005'],
        ];

        foreach ($mahasiswas as [$nama, $nim, $kodeProdi, $angkatan, $noHp]) {
            $userId = $this->buatUser($nama, "{$nim}@mhs.fasilkom.ac.id", $noHp, ['Mahasiswa'], $password, $roles);

            DB::table('mahasiswas')->insert([
                'user_id' => $userId,
                'nim' => $nim,
                'program_studi_id' => $prodi[$kodeProdi],
                'angkatan' => $angkatan,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /** Buat baris users + role_user, kembalikan id user. */
    private function buatUser(string $nama, string $email, string $noHp, array $roleNames, string $password, $roles): int
    {
        $now = now();

        $userId = DB::table('users')->insertGetId([
            'nama' => $nama,
            'email' => $email,
            'password' => $password,
            'alamat' => 'Jember, Jawa Timur',
            'no_hp' => $noHp,
            'avatar_url' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        foreach ($roleNames as $roleName) {
            DB::table('role_user')->insert([
                'user_id' => $userId,
                'role_id' => $roles[$roleName],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        return $userId;
    }
}
