<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SuratPengantarSeeder extends Seeder
{
    /**
     * 2 surat pengantar:
     *  - Telkom: multi-mahasiswa (Danang + Salsabila)
     *  - BRI   : 1 mahasiswa (Aulia)
     * Nomor surat & pejabat penandatangan = dummy, sesuaikan dengan ketentuan fakultas.
     */
    public function run(): void
    {
        $now = now();
        $ttd = DB::table('dosens')->where('nip', '197805122005011002')->value('user_id');
        $periodeAktif = DB::table('periode_akademiks')->where('is_aktif', true)->value('id');

        $surats = [
            [
                'nomor_surat' => '1245/UN25.1.15/SP/2026', 'tanggal_surat' => '2026-10-06',
                'tujuan_instansi' => 'PT. Telkom Indonesia', 'nama_penerima' => 'HR Director',
                'alamat_instansi' => 'Jl. Jend. Gatot Subroto Kav. 52, Jakarta Selatan', 'kota_instansi' => 'Jakarta',
                'nim' => ['242410102035', '232410102019'],
            ],
            [
                'nomor_surat' => '1301/UN25.1.15/SP/2026', 'tanggal_surat' => '2026-10-13',
                'tujuan_instansi' => 'PT. Bank Rakyat Indonesia (Persero) Tbk', 'nama_penerima' => 'Pimpinan Divisi Human Capital',
                'alamat_instansi' => 'Jl. Jenderal Sudirman Kav. 44-46, Jakarta Pusat', 'kota_instansi' => 'Jakarta',
                'nim' => ['232410103021'],
            ],
        ];

        foreach ($surats as $s) {
            $suratId = DB::table('surat_pengantars')->insertGetId([
                'nomor_surat' => $s['nomor_surat'],
                'tanggal_surat' => $s['tanggal_surat'],
                'perihal' => 'Permohonan Izin Magang MBKM',
                'tujuan_instansi' => $s['tujuan_instansi'],
                'nama_penerima' => $s['nama_penerima'],
                'alamat_instansi' => $s['alamat_instansi'],
                'kota_instansi' => $s['kota_instansi'],
                'periode_magang_mulai' => '2026-11-02',
                'periode_magang_selesai' => '2027-02-26',
                'pejabat_ttd_id' => $ttd,
                'file_pdf_path' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($s['nim'] as $nim) {
                $p = DB::table('pendaftaran_mbkms as p')
                    ->join('program_studis as ps', 'ps.id', '=', 'p.program_studi_id')
                    ->where('p.nim', $nim)
                    ->where('p.periode_akademik_id', $periodeAktif)
                    ->select('p.id', 'p.user_id', 'p.nim', 'p.nama_lengkap', 'p.posisi_magang', 'ps.nama as prodi_nama')
                    ->first();

                DB::table('surat_pengantar_mahasiswas')->insert([
                    'surat_pengantar_id' => $suratId,
                    'pendaftaran_mbkm_id' => $p->id,
                    'user_id' => $p->user_id,
                    'nim' => $p->nim,
                    'nama_lengkap' => $p->nama_lengkap,
                    'program_studi' => $p->prodi_nama,
                    'posisi_magang' => $p->posisi_magang,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}
