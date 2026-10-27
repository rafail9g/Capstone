<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Dokumen RKP - {{ $pendaftaran->nama_lengkap }} ({{ $pendaftaran->nim }})</title>
  <style>
    @page {
      size: A4 portrait;
      margin: 0mm !important;
    }
    * {
      box-sizing: border-box;
      -webkit-print-color-adjust: exact !important;
      print-color-adjust: exact !important;
      color-adjust: exact !important;
    }
    html, body {
      margin: 0 !important;
      padding: 8mm 10mm !important;
      font-family: Arial, Calibri, sans-serif;
      font-size: 10px;
      color: #000000;
      background: #ffffff;
      line-height: 1.22;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      border: 1px solid #000000;
      font-size: 10px;
    }
    th, td {
      border: 1px solid #000000;
      padding: 3px 5px;
      vertical-align: middle;
      font-size: 10px;
      color: #000000;
      line-height: 1.22;
    }
    tr {
      page-break-inside: avoid;
    }
    p, div {
      margin: 0;
      padding: 0;
    }
    .bg-header-gray {
      background-color: #D0CECE !important;
      font-weight: 700 !important;
    }
    .bg-cell-gray {
      background-color: #EDEDED !important;
    }
    .text-center {
      text-align: center !important;
    }
    .text-bold {
      font-weight: 700 !important;
    }
  </style>
</head>
<body>

  <!-- MASTER TABEL UTAMA GRID 4 KOLOM DOKUMEN RKP FASILKOM UNEJ -->
  <table>
    <colgroup>
      <col style="width: 24%;" />
      <col style="width: 26%;" />
      <col style="width: 25%;" />
      <col style="width: 25%;" />
    </colgroup>
    <tbody>

      <!-- =================================================================== -->
      <!-- KOP SURAT RESMI FASILKOM UNEJ (KOLOM 1 LOGO, KOLOM 2-4 TEKS KOP)    -->
      <!-- =================================================================== -->
      <tr>
        <td style="background-color: #ffffff; border: 1px solid #000000; padding: 3px 5px; text-align: center; vertical-align: middle;">
          @if(file_exists(public_path('images/logo-unej.png')))
          <img
            src="{{ public_path('images/logo-unej.png') }}"
            alt="Logo Universitas Jember"
            style="width: 110px; height: 110px; object-fit: contain; margin: 0 auto; display: block;"
          />
          @else
          <div style="font-weight: 700; font-size: 12px;">UNEJ</div>
          @endif
        </td>
        <td colspan="3" style="background-color: #EDEDED; text-align: center; vertical-align: middle; padding: 6px 10px; border: 1px solid #000000;">
          <div style="font-weight: 700; font-size: 11px; line-height: 1.22; margin: 0; color: #000000;">
            <div>KEMENTERIAN PENDIDIKAN TINGGI, SAINS,</div>
            <div>DAN TEKNOLOGI</div>
            <div>UNIVERSITAS JEMBER</div>
            <div>FAKULTAS ILMU KOMPUTER</div>
            <div style="font-size: 10px; font-weight: 700; margin-top: 1px;">
              Jalan Kalimantan No. 37 - Kampus Tegalboto Kotak Pos 159
            </div>
            <div style="font-size: 10px; font-weight: 700;">
              Telp 0331 - 326911 Faksimile 0331 - 332150
            </div>
            <div style="font-size: 10px; font-weight: 700;">
              Email: dekan.ilkom@unej.ac.id Website: www.ilkom.unej.ac.id
            </div>
          </div>
        </td>
      </tr>

      <!-- =================================================================== -->
      <!-- BARIS JUDUL DOKUMEN RKP (COLSPAN 4 BOLD)                            -->
      <!-- =================================================================== -->
      <tr>
        <td colspan="4" style="background-color: #ffffff; border: 1px solid #000000; padding: 4px 6px; text-align: center; font-weight: 700; font-size: 11px; text-transform: uppercase;">
          RENCANA KEGIATAN PEMBELAJARAN (RKP)
        </td>
      </tr>

      <!-- =================================================================== -->
      <!-- BAGIAN 1: IDENTITAS AKTIVITAS PEMBELAJARAN CAPSTONE                 -->
      <!-- =================================================================== -->
      <tr>
        <td colspan="4" style="background-color: #D0CECE; border: 1px solid #000000; padding: 3px 5px; font-weight: 700; font-size: 11px;">
          1. Identitas Aktivitas Pembelajaran Capstone
        </td>
      </tr>

      <!-- Baris Mata Kuliah (Rowspan sesuai jumlah MK aktif) -->
      @foreach($activeCourses as $idx => $mk)
      <tr>
        @if($idx === 0)
        <td rowspan="{{ count($activeCourses) }}" style="background-color: #EDEDED; border: 1px solid #000000; padding: 3px 5px; vertical-align: middle;">
          Nama Mata Kuliah
        </td>
        @endif
        <td colspan="3" style="background-color: #ffffff; border: 1px solid #000000; padding: 3px 5px;">
          {{ $mk->nama_mk }} {{ $mk->kode_mk ? '('.$mk->kode_mk.')' : '' }}
        </td>
      </tr>
      @endforeach

      <!-- Baris Nama Mahasiswa -->
      <tr>
        <td style="background-color: #EDEDED; border: 1px solid #000000; padding: 3px 5px;">Nama</td>
        <td colspan="3" style="background-color: #ffffff; border: 1px solid #000000; padding: 3px 5px;">
          {{ $pendaftaran->nama_lengkap }}
        </td>
      </tr>

      <!-- Baris NIM -->
      <tr>
        <td style="background-color: #EDEDED; border: 1px solid #000000; padding: 3px 5px;">NIM</td>
        <td colspan="3" style="background-color: #ffffff; border: 1px solid #000000; padding: 3px 5px;">
          {{ $pendaftaran->nim }}
        </td>
      </tr>

      <!-- Baris Fakultas & Program Studi -->
      <tr>
        <td style="background-color: #EDEDED; border: 1px solid #000000; padding: 3px 5px;">Fakultas</td>
        <td style="background-color: #ffffff; border: 1px solid #000000; padding: 3px 5px;">Ilmu Komputer</td>
        <td style="background-color: #EDEDED; border: 1px solid #000000; padding: 3px 5px;">Program Studi</td>
        <td style="background-color: #ffffff; border: 1px solid #000000; padding: 3px 5px;">{{ $pendaftaran->programStudi->nama ?? '-' }}</td>
      </tr>

      <!-- Baris Bobot SKS & Durasi Pelaksanaan -->
      <tr>
        <td style="background-color: #EDEDED; border: 1px solid #000000; padding: 3px 5px;">Bobot SKS Capstone</td>
        <td style="background-color: #ffffff; border: 1px solid #000000; padding: 3px 5px;">{{ $activeCourses->sum('sks') }}</td>
        <td style="background-color: #EDEDED; border: 1px solid #000000; padding: 3px 5px;">Durasi Pelaksanaan</td>
        <td style="background-color: #ffffff; border: 1px solid #000000; padding: 3px 5px;">{{ $rkp->durasi_pelaksanaan ?? '4 bulan' }}</td>
      </tr>

      <!-- Baris Prasyarat & Semester/Tahun Akademik -->
      <tr>
        <td style="background-color: #EDEDED; border: 1px solid #000000; padding: 3px 5px;">Prasyarat</td>
        <td style="background-color: #ffffff; border: 1px solid #000000; padding: 3px 5px;">{{ $rkp->prasyarat_sks ?? '80 SKS' }}</td>
        <td style="background-color: #EDEDED; border: 1px solid #000000; padding: 3px 5px;">
          Semester/<br/>Tahun Akademik
        </td>
        <td style="background-color: #ffffff; border: 1px solid #000000; padding: 3px 5px;">{{ $rkp->semester_tahun_akademik ?? '5 / 2026/2027 Ganjil' }}</td>
      </tr>

      <!-- Baris DPL, Mitra, dan Posisi Kegiatan (colSpan 3) -->
      <tr>
        <td style="background-color: #EDEDED; border: 1px solid #000000; padding: 3px 5px;">Dosen Pembimbing Lapangan (DPL)</td>
        <td colspan="3" style="background-color: #ffffff; border: 1px solid #000000; padding: 3px 5px;">
          {{ $rkp->nama_dpl ?? ($pendaftaran->dpl->nama ?? '-') }}
        </td>
      </tr>
      <tr>
        <td style="background-color: #EDEDED; border: 1px solid #000000; padding: 3px 5px;">Nama Mitra</td>
        <td colspan="3" style="background-color: #ffffff; border: 1px solid #000000; padding: 3px 5px;">
          {{ $pendaftaran->mitra_magang_text }}
        </td>
      </tr>
      <tr>
        <td style="background-color: #EDEDED; border: 1px solid #000000; padding: 3px 5px;">Posisi Kegiatan</td>
        <td colspan="3" style="background-color: #ffffff; border: 1px solid #000000; padding: 3px 5px;">
          {{ $pendaftaran->posisi_magang }}
        </td>
      </tr>

      <!-- =================================================================== -->
      <!-- BAGIAN 2: CAPAIAN PEMBELAJARAN LULUSAN (CPL)                        -->
      <!-- =================================================================== -->
      <tr>
        <td colspan="4" style="background-color: #D0CECE; border: 1px solid #000000; padding: 3px 5px; font-weight: 700; font-size: 11px;">
          2. Capaian Pembelajaran Lulusan
        </td>
      </tr>
      @foreach($activeCourses as $mk)
      <tr style="vertical-align: top;">
        <td style="background-color: #EDEDED; border: 1px solid #000000; padding: 3px 5px;">
          {{ $mk->nama_mk }} {{ $mk->kode_mk ? '('.$mk->kode_mk.')' : '' }}
        </td>
        <td colspan="3" style="background-color: #ffffff; border: 1px solid #000000; padding: 3px 5px; text-align: justify;">
          {{ $mk->cpl_text ?? 'CPL-02 Mampu menunjukkan sikap logis, kritis, inovatif serta adaptif terhadap perubahan teknologi dan kemajuan bidang Teknologi Informasi dan Komunikasi dengan pendekatan ilmiah dan etika ilmiah.' }}
        </td>
      </tr>
      @endforeach

      <!-- =================================================================== -->
      <!-- BAGIAN 3: CAPAIAN PEMBELAJARAN MATA KULIAH (CPMK)                   -->
      <!-- =================================================================== -->
      <tr>
        <td colspan="4" style="background-color: #D0CECE; border: 1px solid #000000; padding: 3px 5px; font-weight: 700; font-size: 11px; text-transform: uppercase;">
          3. CAPAIAN PEMBELAJARAN MATA KULIAH
        </td>
      </tr>
      <tr style="font-weight: 700;">
        <td style="background-color: #EDEDED; border: 1px solid #000000; padding: 3px 5px; font-weight: 700;">Mata Kuliah</td>
        <td style="background-color: #EDEDED; border: 1px solid #000000; padding: 3px 5px; text-align: center; width: 60px; font-weight: 700;">SKS</td>
        <td colspan="2" style="background-color: #EDEDED; border: 1px solid #000000; padding: 3px 5px; font-weight: 700;">CPMK</td>
      </tr>
      @foreach($activeCourses as $mk)
      <tr style="vertical-align: top;">
        <td style="background-color: #EDEDED; border: 1px solid #000000; padding: 3px 5px;">
          {{ $mk->nama_mk }} {{ $mk->kode_mk ? '('.$mk->kode_mk.')' : '' }}
        </td>
        <td style="background-color: #ffffff; border: 1px solid #000000; padding: 3px 5px; text-align: center;">
          {{ $mk->sks }}
        </td>
        <td colspan="2" style="background-color: #ffffff; border: 1px solid #000000; padding: 3px 5px; text-align: justify;">
          @if(isset($mk->cpmk_items) && count($mk->cpmk_items) > 0)
            @foreach($mk->cpmk_items as $cpmk)
              <div style="margin-bottom: 2px;"><strong>{{ $cpmk['kode'] }}</strong> {{ $cpmk['deskripsi'] }}</div>
            @endforeach
          @else
            <div><strong>CPMK-01</strong> Mahasiswa mampu menyelesaikan tugas industri secara profesional.</div>
          @endif
        </td>
      </tr>
      @endforeach
      <!-- Baris Total SKS -->
      <tr style="font-weight: 700;">
        <td style="background-color: #EDEDED; border: 1px solid #000000; padding: 3px 5px; font-weight: 700;">TOTAL SKS</td>
        <td style="background-color: #ffffff; border: 1px solid #000000; padding: 3px 5px; text-align: center; font-weight: 700;">
          {{ $activeCourses->sum('sks') }}
        </td>
        <td colspan="2" style="background-color: #ffffff; border: 1px solid #000000; padding: 3px 5px;"></td>
      </tr>

      <!-- =================================================================== -->
      <!-- BAGIAN 4: PENGALAMAN PEMBELAJARAN                                   -->
      <!-- =================================================================== -->
      <tr>
        <td colspan="4" style="background-color: #D0CECE; border: 1px solid #000000; padding: 3px 5px; font-weight: 700; font-size: 11px;">
          4. Pengalaman Pembelajaran
        </td>
      </tr>
      <tr style="font-weight: 700;">
        <td style="background-color: #EDEDED; border: 1px solid #000000; padding: 3px 5px; font-weight: 700;">Mata Kuliah</td>
        <td style="background-color: #EDEDED; border: 1px solid #000000; padding: 3px 5px; font-weight: 700;">Kompetensi yang dikembangkan</td>
        <td style="background-color: #EDEDED; border: 1px solid #000000; padding: 3px 5px; font-weight: 700;">Job Description</td>
        <td style="background-color: #EDEDED; border: 1px solid #000000; padding: 3px 5px; text-align: center; font-weight: 700;">Durasi Pembelajaran (Jam)</td>
      </tr>
      @foreach($activeCourses as $mk)
      <tr style="vertical-align: top;">
        <td style="background-color: #EDEDED; border: 1px solid #000000; padding: 3px 5px;">
          {{ $mk->nama_mk }} {{ $mk->kode_mk ? '('.$mk->kode_mk.')' : '' }}
        </td>
        <td style="background-color: #ffffff; border: 1px solid #000000; padding: 3px 5px;">
          {{ $mk->kompetensi }}
        </td>
        <td style="background-color: #ffffff; border: 1px solid #000000; padding: 3px 5px;">
          @if($mk->jobdesks && count($mk->jobdesks) > 0)
            @foreach($mk->jobdesks as $jIdx => $jd)
              <div>{{ $jIdx + 1 }}. {{ $jd->deskripsi }}</div>
            @endforeach
          @else
            <div>-</div>
          @endif
        </td>
        <td style="background-color: #ffffff; border: 1px solid #000000; padding: 3px 5px; text-align: center;">
          {{ $mk->durasi_jam ?? ($mk->sks * 40) }}
        </td>
      </tr>
      @endforeach

      <!-- =================================================================== -->
      <!-- BAGIAN 5: MONITORING                                                -->
      <!-- =================================================================== -->
      <tr>
        <td colspan="4" style="background-color: #D0CECE; border: 1px solid #000000; padding: 3px 5px; font-weight: 700; font-size: 11px;">
          5. Monitoring
        </td>
      </tr>
      <tr style="font-weight: 700;">
        <td colspan="3" style="background-color: #EDEDED; border: 1px solid #000000; padding: 3px 5px; font-weight: 700;">
          Rancangan Monitoring Proses Pembelajaran
        </td>
        <td style="background-color: #EDEDED; border: 1px solid #000000; padding: 3px 5px; text-align: center; font-weight: 700;">
          Pihak yang Memonitor
        </td>
      </tr>
      <tr>
        <td colspan="3" style="background-color: #ffffff; border: 1px solid #000000; padding: 3px 5px;">
          Monitoring setiap aktivitas pembelajaran untuk setiap ketercapaian CPMK melalui kelengkapan dan kesesuaian logbook progress kegiatan
        </td>
        <td style="background-color: #ffffff; border: 1px solid #000000; padding: 3px 5px; text-align: center;">DPL</td>
      </tr>
      <tr>
        <td colspan="3" style="background-color: #ffffff; border: 1px solid #000000; padding: 3px 5px;">
          Menyusun laporan akhir
        </td>
        <td style="background-color: #ffffff; border: 1px solid #000000; padding: 3px 5px; text-align: center;">DPL</td>
      </tr>
      <tr>
        <td colspan="3" style="background-color: #ffffff; border: 1px solid #000000; padding: 3px 5px;">
          Publikasi Produk
        </td>
        <td style="background-color: #ffffff; border: 1px solid #000000; padding: 3px 5px; text-align: center;">DPL</td>
      </tr>

      <!-- =================================================================== -->
      <!-- BAGIAN 6: ASESMEN HASIL PEMBELAJARAN                                -->
      <!-- =================================================================== -->
      <tr>
        <td colspan="4" style="background-color: #D0CECE; border: 1px solid #000000; padding: 3px 5px; font-weight: 700; font-size: 11px;">
          6. Asesmen Hasil Pembelajaran
        </td>
      </tr>
      <!-- Baris Keterangan Asesmen dan Penilaian -->
      <tr>
        <td style="background-color: #EDEDED; border: 1px solid #000000; padding: 3px 5px;">
          Asesmen dan Penilaian
        </td>
        <td colspan="3" style="background-color: #ffffff; border: 1px solid #000000; padding: 3px 5px;">
          Logbook, Laporan Akhir, Catatan Mitra, Seminar Hasil
        </td>
      </tr>
      <!-- Sub-Header Asesmen: colSpan 3 & colSpan 1 -->
      <tr style="font-weight: 700;">
        <td colspan="3" style="background-color: #EDEDED; border: 1px solid #000000; padding: 3px 5px; font-weight: 700;">
          Mata Kuliah
        </td>
        <td colspan="1" style="background-color: #EDEDED; border: 1px solid #000000; padding: 3px 5px; text-align: center; font-weight: 700;">
          Penilai
        </td>
      </tr>
      @foreach($activeCourses as $mk)
      <tr style="vertical-align: middle;">
        <td colspan="3" style="background-color: #EDEDED; border: 1px solid #000000; padding: 3px 5px;">
          {{ $mk->nama_mk }} {{ $mk->kode_mk ? '('.$mk->kode_mk.')' : '' }}
        </td>
        <td colspan="1" style="background-color: #ffffff; border: 1px solid #000000; padding: 3px 5px; text-align: center;">
          DPL, Mitra, dan Penguji
        </td>
      </tr>
      @endforeach

      <!-- =================================================================== -->
      <!-- BAGIAN 7: EVALUASI & PENGESAHAN (KOTAK TABEL FORMAL)                -->
      <!-- =================================================================== -->
      <tr style="page-break-inside: avoid;">
        <td colspan="4" style="background-color: #D0CECE; border: 1px solid #000000; padding: 3px 5px; font-weight: 700; font-size: 11px;">
          7. Evaluasi
        </td>
      </tr>
      <tr style="page-break-inside: avoid;">
        <td colspan="4" style="background-color: #ffffff; border: 1px solid #000000; padding: 3px 5px;">
          Mahasiswa akan mendapatkan nilai jika memenuhi kriteria sebagai berikut:
        </td>
      </tr>
      <!-- Sub-Tabel Pengesahan 3 Kolom Formal Bergaris Hitam -->
      <tr style="page-break-inside: avoid;">
        <td colspan="4" style="padding: 0; border: none;">
          <table style="width: 100%; border-collapse: collapse; border: 1px solid #000000; border-top: none;">
            <colgroup>
              <col style="width: 33.33%;" />
              <col style="width: 33.33%;" />
              <col style="width: 33.34%;" />
            </colgroup>
            <tbody>
              <!-- Baris Jabatan: padding 10px 8px, vertical-align top -->
              <tr style="page-break-inside: avoid; text-align: center;">
                <td style="background-color: #ffffff; border: 1px solid #000000; padding: 10px 8px; vertical-align: top;">
                  <div style="margin: 0; line-height: 1.3;">Disahkan Oleh</div>
                  <div style="margin: 0; line-height: 1.3;">Koordinator Program Studi</div>
                </td>
                <td style="background-color: #ffffff; border: 1px solid #000000; padding: 10px 8px; vertical-align: top;">
                  <div style="margin: 0; line-height: 1.3;">Diperiksa Oleh</div>
                  <div style="margin: 0; line-height: 1.3;">Dosen Pembimbing Lapangan</div>
                </td>
                <td style="background-color: #ffffff; border: 1px solid #000000; padding: 10px 8px; vertical-align: top;">
                  <div style="margin: 0; line-height: 1.3;">Disiapkan Oleh</div>
                  <div style="margin: 0; line-height: 1.3;">Mahasiswa</div>
                </td>
              </tr>

              <!-- Baris Kotak Tanda Tangan: TINGGI 90px Luas dan Tegas -->
              <tr style="page-break-inside: avoid;">
                <td style="background-color: #ffffff; border: 1px solid #000000; height: 90px;"></td>
                <td style="background-color: #ffffff; border: 1px solid #000000; height: 90px;"></td>
                <td style="background-color: #ffffff; border: 1px solid #000000; height: 90px;"></td>
              </tr>

              <!-- Baris Nama Terang & NIP/NIM: padding 10px 8px, vertical-align bottom -->
              <tr style="page-break-inside: avoid; text-align: center;">
                <td style="background-color: #ffffff; border: 1px solid #000000; padding: 10px 8px; vertical-align: bottom;">
                  <div style="margin: 0; line-height: 1.3;">{{ $rkp->nama_kaprodi ?? 'Priza Pandunata, S.Kom., M.Sc.' }}</div>
                  <div style="margin: 0; line-height: 1.3;">NIP. {{ $rkp->nip_kaprodi ?? '198301312015041001' }}</div>
                </td>
                <td style="background-color: #ffffff; border: 1px solid #000000; padding: 10px 8px; vertical-align: bottom;">
                  <div style="margin: 0; line-height: 1.3;">{{ $rkp->nama_dpl ?? ($pendaftaran->dpl->nama ?? '-') }}</div>
                  <div style="margin: 0; line-height: 1.3;">NIP. {{ $rkp->nip_dpl ?? ($pendaftaran->dpl->nim_nip ?? '-') }}</div>
                </td>
                <td style="background-color: #ffffff; border: 1px solid #000000; padding: 10px 8px; vertical-align: bottom;">
                  <div style="margin: 0; line-height: 1.3;">{{ $pendaftaran->nama_lengkap }}</div>
                  <div style="margin: 0; line-height: 1.3;">NIM. {{ $pendaftaran->nim }}</div>
                </td>
              </tr>
            </tbody>
          </table>
        </td>
      </tr>

    </tbody>
  </table>

</body>
</html>

