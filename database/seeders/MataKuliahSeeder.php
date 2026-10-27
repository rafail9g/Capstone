<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MataKuliahSeeder extends Seeder
{
    /**
     * Mengisi: mata_kuliahs, cpmks, cpl_mata_kuliah, mk_jobdesks.
     * Aturan bisnis: 1 CPMK hanya terhubung ke 1 CPL (cpmks.cpl_id).
     * Daftar MK di bawah = contoh; ganti dengan kurikulum resmi Fasilkom.
     */
    private array $cplId = [];

    public function run(): void
    {
        $this->cplId = DB::table('cpls')->pluck('id', 'kode')->all();
        $prodi = DB::table('program_studis')->pluck('id', 'kode');

        // ---------- MK FAKULTAS (program_studi_id = null) ----------
        $this->simpan([
            'kode' => 'KSU1701', 'nama' => 'Praktik Kerja Lapangan (PKL)', 'sks' => 2,
            'kompetensi' => 'Mampu beradaptasi secara profesional dalam dunia kerja industri nyata.',
        ], 'Fakultas', null, [
            ['PSTI_CPL06', 'Mampu beradaptasi dan bekerja profesional di lingkungan industri teknologi informasi.'],
            ['PSSI_CPL06', 'Mampu beradaptasi dan bekerja profesional di lingkungan organisasi berbasis sistem informasi.'],
            ['PSIF_CPL06', 'Mampu beradaptasi dan bekerja profesional di lingkungan pengembangan perangkat lunak.'],
        ], [
            'Melaksanakan tugas kerja magang sesuai arahan supervisor industri.',
            'Menyusun laporan kegiatan magang secara berkala (logbook mingguan).',
            'Mengikuti budaya kerja, SOP, dan standar keamanan informasi di tempat magang.',
        ]);

        $this->simpan([
            'kode' => 'KSU1702', 'nama' => 'Komunikasi Kerja', 'sks' => 2,
            'kompetensi' => 'Mampu berkomunikasi lisan dan tulisan secara efektif di lingkungan kerja profesional.',
        ], 'Fakultas', null, [
            ['PSTI_CPL06', 'Mampu menyampaikan gagasan teknis secara lisan dan tulisan kepada rekan kerja dan atasan.'],
            ['PSSI_CPL06', 'Mampu berkoordinasi dan bernegosiasi dengan pemangku kepentingan di lingkungan kerja.'],
            ['PSIF_CPL06', 'Mampu mempresentasikan hasil pekerjaan dan berkolaborasi dalam tim pengembang.'],
        ], [
            'Menyusun notulensi dan laporan progres kerja kepada supervisor.',
            'Mempresentasikan hasil pekerjaan dalam rapat atau demo internal mitra.',
        ]);

        // ---------- MK PRODI ----------
        foreach ($this->mkProdi() as $kodeProdi => $list) {
            foreach ($list as [$kode, $nama, $sks, $kompetensi, $cplIdx, $jobdesks]) {
                $this->simpan(
                    ['kode' => $kode, 'nama' => $nama, 'sks' => $sks, 'kompetensi' => $kompetensi],
                    'Prodi',
                    $prodi[$kodeProdi],
                    [
                        [sprintf('%s_CPL%02d', $kodeProdi, $cplIdx[0]), "Mampu menjelaskan dan menerapkan konsep inti {$nama} untuk menyelesaikan permasalahan teknis."],
                        [sprintf('%s_CPL%02d', $kodeProdi, $cplIdx[1]), "Mampu mengimplementasikan {$nama} pada pekerjaan nyata di lingkungan industri atau mitra."],
                    ],
                    $jobdesks
                );
            }
        }
    }

    /** Simpan 1 MK + CPMK + pemetaan CPL + template jobdesk. */
    private function simpan(array $mk, string $lingkup, ?int $prodiId, array $cpmks, array $jobdesks): void
    {
        $now = now();

        $mkId = DB::table('mata_kuliahs')->insertGetId([
            'kode' => $mk['kode'],
            'nama_mk' => $mk['nama'],
            'sks' => $mk['sks'],
            'lingkup' => $lingkup,
            'program_studi_id' => $prodiId,
            'kompetensi' => $mk['kompetensi'],
            'job_description_default' => implode("\n", $jobdesks),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        foreach ($cpmks as $i => [$cplKode, $deskripsi]) {
            DB::table('cpmks')->insert([
                'mata_kuliah_id' => $mkId,
                'cpl_id' => $this->cplId[$cplKode],
                'kode' => 'CPMK-'.($i + 1),
                'deskripsi' => $deskripsi,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach (array_unique(array_column($cpmks, 0)) as $cplKode) {
            DB::table('cpl_mata_kuliah')->insert([
                'cpl_id' => $this->cplId[$cplKode],
                'mata_kuliah_id' => $mkId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach ($jobdesks as $i => $deskripsi) {
            DB::table('mk_jobdesks')->insert([
                'mata_kuliah_id' => $mkId,
                'urutan' => $i + 1,
                'deskripsi' => $deskripsi,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /** [kode, nama, sks, kompetensi, [indeks CPL 1, indeks CPL 2], [jobdesk...]] */
    private function mkProdi(): array
    {
        return [
            'PSTI' => [
                ['TI2201', 'Pemrograman Web', 3, 'Mampu membangun aplikasi web dinamis dengan standar pengembangan modern.', [3, 5], [
                    'Mengembangkan halaman dan fitur aplikasi web sesuai kebutuhan mitra.',
                    'Mengintegrasikan antarmuka web dengan REST API milik mitra.']],
                ['TI2202', 'Basis Data', 3, 'Mampu merancang dan mengelola basis data relasional.', [4, 1], [
                    'Merancang skema dan relasi basis data untuk sistem mitra.',
                    'Menulis dan mengoptimalkan query SQL untuk kebutuhan pelaporan.']],
                ['TI2203', 'Jaringan Komputer', 3, 'Mampu mengonfigurasi dan memelihara jaringan komputer skala kecil hingga menengah.', [2, 1], [
                    'Melakukan konfigurasi perangkat jaringan (router, switch, VLAN) sesuai arahan.',
                    'Memantau dan mendokumentasikan topologi serta kondisi jaringan mitra.']],
                ['TI2204', 'Sistem Operasi', 3, 'Mampu mengelola sistem operasi dan sumber daya komputasi pada lingkungan server.', [2, 1], [
                    'Melakukan instalasi dan konfigurasi sistem operasi server atau desktop.',
                    'Menyusun skrip otomasi untuk tugas administrasi sistem.']],
                ['TI2205', 'Interaksi Manusia dan Komputer', 3, 'Mampu merancang wireframe dan antarmuka ramah pengguna.', [5, 3], [
                    'Merancang wireframing dan mockup sistem pada Figma.',
                    'Menyusun user flow dan prototipe interaktif berdasarkan kebutuhan pengguna.']],
                ['TI3201', 'Pemrograman Mobile', 3, 'Mampu membangun aplikasi mobile lintas platform.', [3, 5], [
                    'Mengembangkan fitur aplikasi mobile (Android/iOS/Flutter) sesuai spesifikasi.',
                    'Menguji dan memperbaiki bug aplikasi mobile sebelum rilis.']],
                ['TI3202', 'Rekayasa Perangkat Lunak', 3, 'Mampu menerapkan siklus pengembangan perangkat lunak secara terstruktur.', [3, 5], [
                    'Menyusun dokumen kebutuhan dan desain perangkat lunak.',
                    'Berpartisipasi dalam siklus pengembangan (sprint, code review, pengujian).']],
                ['TI3203', 'Keamanan Informasi', 3, 'Mampu mengidentifikasi ancaman dan menerapkan kontrol keamanan informasi.', [4, 2], [
                    'Melakukan pengecekan kerentanan dasar pada aplikasi atau jaringan mitra.',
                    'Menyusun rekomendasi kebijakan keamanan informasi.']],
                ['TI3204', 'Komputasi Awan', 3, 'Mampu memanfaatkan layanan cloud untuk deployment dan skalabilitas aplikasi.', [2, 4], [
                    'Melakukan deployment aplikasi ke layanan cloud mitra.',
                    'Mengelola konfigurasi server dan monitoring layanan cloud.']],
                ['TI3205', 'Internet of Things', 3, 'Mampu merancang sistem IoT dari sensor hingga platform pemantauan.', [3, 2], [
                    'Merangkai dan memprogram perangkat IoT (mikrokontroler dan sensor).',
                    'Membangun dashboard pemantauan data sensor secara real-time.']],
                ['TI3206', 'Administrasi Jaringan', 3, 'Mampu mengelola layanan jaringan dan server secara berkelanjutan.', [2, 4], [
                    'Mengelola layanan jaringan (DNS, DHCP, proxy) pada infrastruktur mitra.',
                    'Melakukan troubleshooting gangguan jaringan dan membuat laporan insiden.']],
                ['TI3207', 'Penambangan Data', 3, 'Mampu mengolah data menjadi pengetahuan menggunakan teknik data mining.', [4, 1], [
                    'Melakukan pembersihan dan eksplorasi data operasional mitra.',
                    'Membangun model analisis sederhana dan memvisualisasikan hasilnya.']],
            ],
            'PSSI' => [
                ['SI2201', 'Analisis dan Perancangan Sistem Informasi', 3, 'Mampu menganalisis kebutuhan dan merancang sistem informasi organisasi.', [1, 2], [
                    'Menyusun analisis kebutuhan sistem dari hasil wawancara dengan pengguna mitra.',
                    'Membuat pemodelan sistem (use case, ERD, DFD) untuk solusi yang diusulkan.']],
                ['SI2202', 'Basis Data', 3, 'Mampu merancang dan mengelola basis data untuk sistem informasi.', [3, 2], [
                    'Merancang dan memelihara basis data untuk sistem informasi mitra.',
                    'Menyusun query dan laporan data untuk kebutuhan manajerial.']],
                ['SI2203', 'Manajemen Proses Bisnis', 3, 'Mampu memodelkan dan memperbaiki proses bisnis organisasi.', [1, 5], [
                    'Memetakan proses bisnis berjalan (as-is) menggunakan BPMN.',
                    'Mengusulkan perbaikan proses bisnis (to-be) berdasarkan hasil analisis.']],
                ['SI2204', 'Rekayasa Kebutuhan', 3, 'Mampu menggali, mendokumentasikan, dan memvalidasi kebutuhan perangkat lunak.', [1, 2], [
                    'Melakukan elisitasi kebutuhan melalui wawancara dan observasi.',
                    'Menyusun dokumen spesifikasi kebutuhan perangkat lunak (SRS).']],
                ['SI2205', 'Interaksi Manusia dan Komputer', 3, 'Mampu mengevaluasi dan merancang antarmuka sistem informasi yang mudah digunakan.', [2, 1], [
                    'Melakukan evaluasi usability terhadap aplikasi internal mitra.',
                    'Merancang prototipe antarmuka perbaikan sistem informasi.']],
                ['SI3201', 'Enterprise Resource Planning', 3, 'Mampu memahami dan mengimplementasikan modul sistem ERP.', [5, 2], [
                    'Membantu konfigurasi dan pengujian modul ERP pada unit kerja mitra.',
                    'Menyusun dokumentasi panduan penggunaan modul ERP.']],
                ['SI3202', 'Audit Sistem Informasi', 3, 'Mampu merencanakan dan melaksanakan audit sistem informasi.', [4, 1], [
                    'Membantu penyusunan program kerja dan checklist audit sistem informasi.',
                    'Mendokumentasikan temuan audit beserta rekomendasi perbaikan.']],
                ['SI3203', 'Pemrograman Web Lanjut', 3, 'Mampu membangun aplikasi web berskala menengah dengan framework modern.', [2, 3], [
                    'Mengembangkan modul aplikasi web berbasis framework sesuai kebutuhan bisnis mitra.',
                    'Menulis dokumentasi dan pengujian untuk fitur yang dikembangkan.']],
                ['SI3204', 'Data Warehouse', 3, 'Mampu merancang gudang data dan proses ETL untuk analisis organisasi.', [3, 2], [
                    'Merancang skema data warehouse (star/snowflake) dari sumber data mitra.',
                    'Membangun dan menjadwalkan proses ETL sederhana.']],
                ['SI3205', 'Business Intelligence', 3, 'Mampu menyajikan analisis bisnis melalui dashboard dan visualisasi data.', [3, 1], [
                    'Membuat dashboard business intelligence untuk memantau indikator kinerja mitra.',
                    'Menyusun laporan analisis tren dan rekomendasi bisnis berbasis data.']],
                ['SI3206', 'Manajemen Proyek Sistem Informasi', 3, 'Mampu merencanakan dan mengendalikan proyek sistem informasi.', [5, 1], [
                    'Menyusun rencana proyek (jadwal, sumber daya, risiko) bersama tim mitra.',
                    'Memantau progres proyek dan membuat laporan status secara berkala.']],
                ['SI3207', 'Manajemen Risiko dan Tata Kelola TI', 3, 'Mampu menerapkan kerangka tata kelola dan manajemen risiko TI.', [4, 5], [
                    'Membantu identifikasi dan penilaian risiko TI pada layanan mitra.',
                    'Memetakan proses TI terhadap kerangka tata kelola (misal COBIT atau ISO 27001).']],
            ],
            'PSIF' => [
                ['IF2101', 'Struktur Data', 3, 'Mampu memilih dan mengimplementasikan struktur data yang efisien.', [1, 2], [
                    'Mengimplementasikan struktur data dan algoritma pada modul perangkat lunak mitra.',
                    'Menganalisis dan mengoptimalkan kompleksitas kode yang berjalan.']],
                ['IF2102', 'Basis Data', 3, 'Mampu merancang basis data dan mengelola data secara efisien.', [2, 1], [
                    'Merancang dan menormalisasi skema basis data untuk aplikasi mitra.',
                    'Mengoptimalkan query dan indeks pada basis data.']],
                ['IF2103', 'Pemrograman Berorientasi Objek', 3, 'Mampu merancang perangkat lunak dengan paradigma berorientasi objek.', [2, 1], [
                    'Mengembangkan komponen aplikasi menggunakan prinsip OOP dan design pattern.',
                    'Melakukan refactoring kode agar lebih modular dan mudah dipelihara.']],
                ['IF2104', 'Rekayasa Perangkat Lunak', 3, 'Mampu mengelola proses pengembangan perangkat lunak dari kebutuhan hingga pemeliharaan.', [2, 5], [
                    'Menyusun dokumentasi kebutuhan dan desain perangkat lunak.',
                    'Menerapkan version control, code review, dan pengujian dalam tim pengembang.']],
                ['IF3101', 'Pemrograman Web', 3, 'Mampu membangun aplikasi web fullstack.', [5, 2], [
                    'Mengembangkan fitur frontend dan backend aplikasi web mitra.',
                    'Merancang dan mengonsumsi REST API untuk aplikasi web.']],
                ['IF3102', 'Pemrograman Mobile', 3, 'Mampu membangun aplikasi mobile native maupun lintas platform.', [5, 2], [
                    'Mengembangkan fitur aplikasi mobile sesuai desain dan spesifikasi.',
                    'Mengintegrasikan aplikasi mobile dengan layanan backend mitra.']],
                ['IF3103', 'Kecerdasan Buatan', 3, 'Mampu menerapkan konsep dan teknik dasar kecerdasan buatan.', [3, 1], [
                    'Merancang solusi berbasis AI (pencarian, sistem pakar, logika) untuk kasus mitra.',
                    'Mengevaluasi performa solusi AI dan mendokumentasikan hasilnya.']],
                ['IF3104', 'Pembelajaran Mesin', 3, 'Mampu membangun dan mengevaluasi model pembelajaran mesin.', [3, 1], [
                    'Menyiapkan dataset dan melatih model pembelajaran mesin untuk kasus mitra.',
                    'Mengevaluasi model dengan metrik yang sesuai dan menyusun laporan eksperimen.']],
                ['IF3105', 'Komputasi Awan', 3, 'Mampu merancang dan men-deploy aplikasi pada platform komputasi awan.', [4, 2], [
                    'Men-deploy aplikasi ke platform cloud dengan konfigurasi yang sesuai.',
                    'Menerapkan containerisasi dan monitoring layanan terdistribusi.']],
                ['IF3106', 'Keamanan Siber', 3, 'Mampu menerapkan praktik keamanan siber pada aplikasi dan infrastruktur.', [4, 2], [
                    'Melakukan penetration testing dasar pada aplikasi yang diizinkan mitra.',
                    'Menyusun laporan kerentanan beserta rekomendasi mitigasi.']],
                ['IF3107', 'Interaksi Manusia dan Komputer', 3, 'Mampu merancang antarmuka dan pengalaman pengguna yang efektif.', [5, 2], [
                    'Menyusun wireframe dan prototipe antarmuka aplikasi mitra.',
                    'Melakukan pengujian kegunaan dan menindaklanjuti hasil evaluasinya.']],
                ['IF3108', 'Pengolahan Citra Digital', 3, 'Mampu menerapkan teknik pengolahan dan analisis citra digital.', [3, 1], [
                    'Membangun pipeline pra-pemrosesan citra untuk kebutuhan mitra.',
                    'Mengimplementasikan dan menguji algoritma pengolahan citra.']],
            ],
        ];
    }
}
