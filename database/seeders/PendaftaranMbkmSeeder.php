<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PendaftaranMbkmSeeder extends Seeder
{
    // NIP dosen (lihat UserSeeder)
    private const HABIB = '197805122005011002';

    private const SARI = '198003152006042001';

    private const BAMBANG = '197911202005011003';

    private const RINA = '198205102008122001';

    private const DEWI = '198709252012122001';

    /**
     * Skenario data (periode aktif = 2026/2027 Ganjil):
     *  - Danang    : Terverifikasi, PIC sudah di-plot, RKM belum diajukan
     *  - Aulia     : Terverifikasi, RKM Disetujui, DPL + Penguji sudah di-plot
     *  - Rizky     : Menunggu Verifikasi, role custom ("Lainnya"), mitra non-master, belum di-plot
     *  - Nabila    : Perbaikan (ada catatan)
     *  - Salsabila : riwayat magang 2025/2026 Genap (Disetujui) + magang baru (RKM Diajukan)
     *
     * File transkrip & LoA disimpan sebagai PDF dummy di disk 'local' (storage/app/private)
     * supaya endpoint streaming inline/download bisa langsung dites.
     */
    public function run(): void
    {
        $prodi = DB::table('program_studis')->pluck('id', 'kode');
        $periode = DB::table('periode_akademiks')->get()
            ->mapWithKeys(fn ($p) => [$p->tahun_ajaran.' '.$p->semester => $p->id]);
        $user = DB::table('mahasiswas')->pluck('user_id', 'nim')->union(DB::table('dosens')->pluck('user_id', 'nip'));
        $mitra = DB::table('mitras')->pluck('id', 'nama');

        $rows = [
            [
                'nim' => '242410102035', 'nama' => 'Danang Rizki Ramadhan', 'prodi' => 'PSTI',
                'mitra' => 'PT. Telkom Indonesia', 'posisi' => 'UI/UX Designer', 'custom' => false,
                'periode' => '2026/2027 Ganjil', 'tgl' => '2026-10-01 08:15:00',
                'status' => 'Terverifikasi', 'catatan' => 'Berkas lengkap dan sah. Silakan mulai menyusun RKM.',
                'wa' => 'https://chat.whatsapp.com/MbkmFasilkom2026',
                'verifier' => self::SARI, 'verified_at' => '2026-10-05 09:30:00',
                'pic' => self::SARI, 'dpl' => null, 'penguji' => null,
            ],
            [
                'nim' => '232410103021', 'nama' => 'Aulia Putri Maharani', 'prodi' => 'PSSI',
                'mitra' => 'PT. Bank Rakyat Indonesia (Persero) Tbk', 'posisi' => 'Business Analyst', 'custom' => false,
                'periode' => '2026/2027 Ganjil', 'tgl' => '2026-09-14 10:00:00',
                'status' => 'Terverifikasi', 'catatan' => 'Berkas lengkap dan sah.',
                'wa' => 'https://chat.whatsapp.com/MbkmFasilkom2026',
                'verifier' => self::BAMBANG, 'verified_at' => '2026-09-16 13:20:00',
                'pic' => self::BAMBANG, 'dpl' => self::RINA, 'penguji' => self::DEWI,
            ],
            [
                'nim' => '242410101044', 'nama' => 'Rizky Pratama Wijaya', 'prodi' => 'PSIF',
                'mitra' => null, 'mitra_text' => 'PT. Siber Nusantara', 'posisi' => 'Cyber Security Analyst', 'custom' => true,
                'periode' => '2026/2027 Ganjil', 'tgl' => '2026-10-16 14:45:00',
                'status' => 'Menunggu Verifikasi', 'catatan' => null, 'wa' => null,
                'verifier' => null, 'verified_at' => null,
                'pic' => null, 'dpl' => null, 'penguji' => null,
            ],
            [
                'nim' => '242410103058', 'nama' => 'Nabila Safitri', 'prodi' => 'PSSI',
                'mitra' => 'CV. Jember Digital Kreasi', 'posisi' => 'Data Analyst', 'custom' => false,
                'periode' => '2026/2027 Ganjil', 'tgl' => '2026-10-14 09:10:00',
                'status' => 'Perbaikan',
                'catatan' => 'LoA belum ditandatangani pihak mitra. Mohon unggah ulang LoA yang sudah bertanda tangan dan berstempel.',
                'wa' => null,
                'verifier' => self::SARI, 'verified_at' => '2026-10-15 11:00:00',
                'pic' => self::SARI, 'dpl' => null, 'penguji' => null,
            ],
            [   // Riwayat magang periode lampau (dipakai untuk tes already_converted_in_past)
                'nim' => '232410102019', 'nama' => 'Salsabila Nurhaliza', 'prodi' => 'PSTI',
                'mitra' => 'CV. Jember Digital Kreasi', 'posisi' => 'Frontend Developer', 'custom' => false,
                'periode' => '2025/2026 Genap', 'tgl' => '2026-02-10 08:00:00',
                'status' => 'Terverifikasi', 'catatan' => 'Berkas lengkap dan sah.',
                'wa' => 'https://chat.whatsapp.com/MbkmFasilkom2026',
                'verifier' => self::SARI, 'verified_at' => '2026-02-12 10:00:00',
                'pic' => self::SARI, 'dpl' => self::RINA, 'penguji' => self::DEWI,
            ],
            [   // Magang baru periode aktif
                'nim' => '232410102019', 'nama' => 'Salsabila Nurhaliza', 'prodi' => 'PSTI',
                'mitra' => 'PT. Telkom Indonesia', 'posisi' => 'Frontend Developer', 'custom' => false,
                'periode' => '2026/2027 Ganjil', 'tgl' => '2026-10-02 08:30:00',
                'status' => 'Terverifikasi', 'catatan' => 'Berkas lengkap dan sah. Silakan mulai menyusun RKM.',
                'wa' => 'https://chat.whatsapp.com/MbkmFasilkom2026',
                'verifier' => self::HABIB, 'verified_at' => '2026-10-06 09:00:00',
                'pic' => self::HABIB, 'dpl' => null, 'penguji' => null,
            ],
        ];

        foreach ($rows as $r) {
            $slugPeriode = Str::slug($r['periode']);
            $depan = Str::slug(Str::before($r['nama'], ' '));
            $pdfTranskrip = $this->dummyPdf('Transkrip Nilai - '.$r['nim']);
            $pdfLoa = $this->dummyPdf('Letter of Acceptance - '.$r['nim']);

            $pathTranskrip = "pendaftaran/{$r['nim']}/{$slugPeriode}/transkrip.pdf";
            $pathLoa = "pendaftaran/{$r['nim']}/{$slugPeriode}/loa.pdf";
            Storage::disk('local')->put($pathTranskrip, $pdfTranskrip);
            Storage::disk('local')->put($pathLoa, $pdfLoa);

            DB::table('pendaftaran_mbkms')->insert([
                'user_id' => $user[$r['nim']],
                'nim' => $r['nim'],
                'nama_lengkap' => $r['nama'],
                'program_studi_id' => $prodi[$r['prodi']],
                'mitra_id' => $r['mitra'] ? $mitra[$r['mitra']] : null,
                'mitra_magang_text' => $r['mitra'] ?? $r['mitra_text'],
                'posisi_magang' => $r['posisi'],
                'is_custom_role' => $r['custom'],
                'periode_akademik_id' => $periode[$r['periode']],

                'transkrip_file_name' => "transkrip-{$depan}.pdf",
                'transkrip_file_path' => $pathTranskrip,
                'transkrip_file_size' => number_format(strlen($pdfTranskrip) / 1024, 1).' KB',
                'loa_file_name' => "loa-{$depan}.pdf",
                'loa_file_path' => $pathLoa,
                'loa_file_size' => number_format(strlen($pdfLoa) / 1024, 1).' KB',

                'status' => $r['status'],
                'catatan' => $r['catatan'],
                'link_wa_grup' => $r['wa'],
                'verified_by' => $r['verifier'] ? $user[$r['verifier']] : null,
                'verified_at' => $r['verified_at'],

                'is_plotted_pic' => $r['pic'] !== null,
                'pic_verifikator_id' => $r['pic'] ? $user[$r['pic']] : null,
                'is_plotted_dpl_penguji' => $r['dpl'] !== null,
                'dpl_id' => $r['dpl'] ? $user[$r['dpl']] : null,
                'penguji_semhas_id' => $r['penguji'] ? $user[$r['penguji']] : null,

                'created_at' => $r['tgl'],
                'updated_at' => $r['tgl'],
            ]);
        }
    }

    /** PDF 1 halaman minimal (valid, xref dihitung) untuk keperluan tes preview. */
    private function dummyPdf(string $title): string
    {
        $title = str_replace(['(', ')', '\\'], '', $title);
        $stream = "BT /F1 18 Tf 72 760 Td ({$title}) Tj ET\nBT /F1 11 Tf 72 735 Td (Dokumen dummy hasil seeder) Tj ET";

        $objs = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            '<< /Length '.strlen($stream)." >>\nstream\n{$stream}\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objs as $i => $obj) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n{$obj}\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objs) + 1)."\n0000000000 65535 f \n";
        foreach ($offsets as $off) {
            $pdf .= sprintf("%010d 00000 n \n", $off);
        }
        $pdf .= "trailer\n<< /Size ".(count($objs) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";

        return $pdf;
    }
}
