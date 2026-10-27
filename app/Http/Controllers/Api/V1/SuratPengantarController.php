<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SuratPengantar;
use App\Services\PdfDocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class SuratPengantarController extends Controller
{
    public function __construct(private PdfDocumentService $pdfService) {}

    public function index(Request $request): JsonResponse
    {
        $query = SuratPengantar::with(['pejabatTtd', 'mahasiswas']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nomor_surat', 'like', "%{$search}%")
                    ->orWhere('tujuan_instansi', 'like', "%{$search}%");
            });
        }

        $items = $query->orderBy('id', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $surat = SuratPengantar::with(['pejabatTtd', 'mahasiswas.pendaftaranMbkm'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $surat,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tanggal_surat' => ['required', 'date'],
            'perihal' => ['nullable', 'string', 'max:200'],
            'tujuan_instansi' => ['required', 'string', 'max:200'],
            'nama_penerima' => ['nullable', 'string', 'max:150'],
            'alamat_instansi' => ['required', 'string'],
            'kota_instansi' => ['required', 'string', 'max:100'],
            'periode_magang_mulai' => ['nullable', 'date'],
            'periode_magang_selesai' => ['nullable', 'date', 'after_or_equal:periode_magang_mulai'],
            'pejabat_ttd_id' => ['nullable', 'exists:users,id'],
            'mahasiswas' => ['required', 'array', 'min:1'],
            'mahasiswas.*.pendaftaran_mbkm_id' => ['nullable', 'exists:pendaftaran_mbkms,id'],
            'mahasiswas.*.user_id' => ['required', 'exists:users,id'],
            'mahasiswas.*.nim' => ['required', 'string'],
            'mahasiswas.*.nama_lengkap' => ['required', 'string'],
            'mahasiswas.*.program_studi' => ['required', 'string'],
            'mahasiswas.*.posisi_magang' => ['required', 'string'],
        ]);

        return DB::transaction(function () use ($data) {
            $year = now()->year;
            $countThisYear = SuratPengantar::whereYear('created_at', $year)->count() + 1;
            $nomorSurat = sprintf('%04d/UN25.1.15/SP/%d', $countThisYear + 1240, $year);

            $surat = SuratPengantar::create([
                'nomor_surat' => $nomorSurat,
                'tanggal_surat' => $data['tanggal_surat'],
                'perihal' => $data['perihal'] ?? 'Permohonan Izin Magang MBKM',
                'tujuan_instansi' => $data['tujuan_instansi'],
                'nama_penerima' => $data['nama_penerima'] ?? null,
                'alamat_instansi' => $data['alamat_instansi'],
                'kota_instansi' => $data['kota_instansi'],
                'periode_magang_mulai' => $data['periode_magang_mulai'] ?? null,
                'periode_magang_selesai' => $data['periode_magang_selesai'] ?? null,
                'pejabat_ttd_id' => $data['pejabat_ttd_id'] ?? null,
            ]);

            foreach ($data['mahasiswas'] as $mhs) {
                $surat->mahasiswas()->create([
                    'pendaftaran_mbkm_id' => $mhs['pendaftaran_mbkm_id'] ?? null,
                    'user_id' => $mhs['user_id'],
                    'nim' => $mhs['nim'],
                    'nama_lengkap' => $mhs['nama_lengkap'],
                    'program_studi' => $mhs['program_studi'],
                    'posisi_magang' => $mhs['posisi_magang'],
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Surat Pengantar Magang berhasil dibuat.',
                'data' => $surat->load('mahasiswas'),
            ], 201);
        });
    }

    public function streamPdf(int $id, Request $request): Response
    {
        $surat = SuratPengantar::with('mahasiswas')->findOrFail($id);
        $mode = $request->query('mode', 'inline');

        $filename = 'Surat-Pengantar-'.str_replace('/', '-', $surat->nomor_surat).'.pdf';
        $content = $this->pdfService->generateDummyPdf(
            'SURAT PENGANTAR MAGANG MBKM FASILKOM',
            "Nomor: {$surat->nomor_surat} | Tujuan: {$surat->tujuan_instansi}"
        );

        return $this->pdfService->streamPdfContent($content, $filename, $mode);
    }
}
