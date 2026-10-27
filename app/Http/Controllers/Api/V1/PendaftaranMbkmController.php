<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PendaftaranMbkm;
use App\Models\PeriodeAkademik;
use App\Services\PdfDocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class PendaftaranMbkmController extends Controller
{
    public function __construct(private PdfDocumentService $pdfService) {}

    public function index(Request $request): JsonResponse
    {
        $query = PendaftaranMbkm::with([
            'programStudi',
            'mitra',
            'periodeAkademik',
            'picVerifikator',
            'dpl',
            'pengujiSemhas',
            'rkm',
        ]);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('prodi_id')) {
            $query->where('program_studi_id', $request->input('prodi_id'));
        }

        if ($request->filled('periode_id')) {
            $query->where('periode_akademik_id', $request->input('periode_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('nim', 'like', "%{$search}%")
                    ->orWhere('posisi_magang', 'like', "%{$search}%")
                    ->orWhere('mitra_magang_text', 'like', "%{$search}%");
            });
        }

        $items = $query->orderBy('id', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function myPendaftaran(Request $request): JsonResponse
    {
        $user = $request->user();

        $pendaftaran = PendaftaranMbkm::with([
            'programStudi',
            'mitra',
            'periodeAkademik',
            'picVerifikator',
            'dpl',
            'pengujiSemhas',
            'rkm',
        ])
            ->where('user_id', $user->id)
            ->latest()
            ->first();

        if (! $pendaftaran) {
            return response()->json([
                'success' => false,
                'message' => 'Belum ada data pendaftaran MBKM.',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $pendaftaran,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $pendaftaran = PendaftaranMbkm::with([
            'programStudi',
            'mitra',
            'periodeAkademik',
            'picVerifikator',
            'dpl',
            'pengujiSemhas',
            'rkm.items.jobdesks',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $pendaftaran,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'program_studi_id' => ['required', 'exists:program_studis,id'],
            'mitra_id' => ['nullable', 'exists:mitras,id'],
            'mitra_magang_text' => ['required', 'string', 'max:150'],
            'posisi_magang' => ['required', 'string', 'max:100'],
            'is_custom_role' => ['nullable', 'boolean'],
            'transkrip_file' => ['required', 'file', 'mimes:pdf', 'max:5120'],
            'loa_file' => ['required', 'file', 'mimes:pdf', 'max:5120'],
        ]);

        $periodeAktif = PeriodeAkademik::where('is_aktif', true)->firstOrFail();

        $existing = PendaftaranMbkm::where('user_id', $user->id)
            ->where('periode_akademik_id', $periodeAktif->id)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'error_code' => 'ERR_PENDAFTARAN_ALREADY_EXISTS',
                'message' => 'Anda sudah memiliki pendaftaran magang aktif pada periode akademik ini.',
            ], 409);
        }

        $nim = $user->nim_nip ?? $user->mahasiswa?->nim ?? 'MHS-'.$user->id;
        $slugPeriode = Str::slug($periodeAktif->tahun_ajaran.'-'.$periodeAktif->semester);

        $transkrip = $request->file('transkrip_file');
        $transkripName = 'transkrip-'.Str::slug(Str::before($user->nama, ' ')).'.pdf';
        $transkripPath = "pendaftaran/{$nim}/{$slugPeriode}/{$transkripName}";
        Storage::disk('local')->putFileAs("pendaftaran/{$nim}/{$slugPeriode}", $transkrip, $transkripName);

        $loa = $request->file('loa_file');
        $loaName = 'loa-'.Str::slug(Str::before($user->nama, ' ')).'.pdf';
        $loaPath = "pendaftaran/{$nim}/{$slugPeriode}/{$loaName}";
        Storage::disk('local')->putFileAs("pendaftaran/{$nim}/{$slugPeriode}", $loa, $loaName);

        $pendaftaran = PendaftaranMbkm::create([
            'user_id' => $user->id,
            'nim' => $nim,
            'nama_lengkap' => $user->nama,
            'program_studi_id' => $data['program_studi_id'],
            'mitra_id' => $data['mitra_id'] ?? null,
            'mitra_magang_text' => $data['mitra_magang_text'],
            'posisi_magang' => $data['posisi_magang'],
            'is_custom_role' => $data['is_custom_role'] ?? false,
            'periode_akademik_id' => $periodeAktif->id,
            'transkrip_file_name' => $transkripName,
            'transkrip_file_path' => $transkripPath,
            'transkrip_file_size' => number_format($transkrip->getSize() / 1024, 1).' KB',
            'loa_file_name' => $loaName,
            'loa_file_path' => $loaPath,
            'loa_file_size' => number_format($loa->getSize() / 1024, 1).' KB',
            'status' => 'Menunggu Verifikasi',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pendaftaran MBKM berhasil diajukan. Menunggu verifikasi berkas oleh Tim MBKM.',
            'data' => [
                'id' => $pendaftaran->id,
                'status' => $pendaftaran->status,
                'rkm_status' => 'Belum Mengajukan',
            ],
        ], 201);
    }

    public function streamFile(int $id, string $jenis, Request $request): Response
    {
        $pendaftaran = PendaftaranMbkm::findOrFail($id);
        $mode = $request->query('mode', 'inline');

        if ($jenis === 'transkrip') {
            $path = $pendaftaran->transkrip_file_path;
            $filename = $pendaftaran->transkrip_file_name;
            $title = 'Transkrip Nilai - '.$pendaftaran->nama_lengkap;
        } elseif ($jenis === 'loa') {
            $path = $pendaftaran->loa_file_path;
            $filename = $pendaftaran->loa_file_name;
            $title = 'Letter of Acceptance - '.$pendaftaran->nama_lengkap;
        } else {
            return response()->json(['success' => false, 'message' => 'Jenis berkas tidak valid.'], 400);
        }

        if ($path && Storage::disk('local')->exists($path)) {
            $content = Storage::disk('local')->get($path);
        } else {
            $content = $this->pdfService->generateDummyPdf($title);
        }

        return $this->pdfService->streamPdfContent($content, $filename, $mode);
    }

    public function verifikasi(int $id, Request $request): JsonResponse
    {
        $pendaftaran = PendaftaranMbkm::findOrFail($id);

        $data = $request->validate([
            'status' => ['required', 'in:Terverifikasi,Perbaikan,Ditolak'],
            'link_wa_grup' => ['nullable', 'string', 'url', 'required_if:status,Terverifikasi'],
            'catatan' => ['nullable', 'string', 'required_if:status,Perbaikan', 'required_if:status,Ditolak'],
        ]);

        $pendaftaran->update([
            'status' => $data['status'],
            'link_wa_grup' => $data['link_wa_grup'] ?? $pendaftaran->link_wa_grup,
            'catatan' => $data['catatan'] ?? null,
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status verifikasi berkas pendaftaran berhasil disimpan.',
            'data' => [
                'id' => $pendaftaran->id,
                'status' => $pendaftaran->status,
                'link_wa_grup' => $pendaftaran->link_wa_grup,
                'catatan' => $pendaftaran->catatan,
            ],
        ]);
    }
}
