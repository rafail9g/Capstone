<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Rkm;
use App\Models\RkmJobdesk;
use App\Models\RkmMataKuliah;
use App\Services\RkpGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerifikasiRkmController extends Controller
{
    public function __construct(private RkpGeneratorService $rkpService) {}

    public function index(Request $request): JsonResponse
    {
        $query = Rkm::with([
            'pendaftaranMbkm.programStudi',
            'pendaftaranMbkm.picVerifikator',
            'items',
        ])
            ->whereIn('status', ['Diajukan', 'Disetujui', 'Perbaikan RKM']);

        if ($request->filled('status') && $request->input('status') !== 'Semua') {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('pic')) {
            $pic = $request->input('pic');
            $query->whereHas('pendaftaranMbkm.picVerifikator', function ($q) use ($pic) {
                $q->where('nama', 'like', "%{$pic}%");
            });
        }

        if ($request->filled('prodi')) {
            $prodi = $request->input('prodi');
            $query->whereHas('pendaftaranMbkm.programStudi', function ($q) use ($prodi) {
                $q->where('nama', $prodi)->orWhere('kode', $prodi);
            });
        }

        $items = $query->orderBy('updated_at', 'desc')->get()->map(function ($rkm) {
            $p = $rkm->pendaftaranMbkm;

            return [
                'rkm_id' => $rkm->id,
                'pendaftaran_id' => $p->id,
                'nim' => $p->nim,
                'nama_lengkap' => $p->nama_lengkap,
                'program_studi' => $p->programStudi?->nama,
                'mitra_magang' => $p->mitra_magang_text,
                'posisi_magang' => $p->posisi_magang,
                'total_sks' => $rkm->total_sks,
                'status_rkm' => $rkm->status,
                'catatan_pic' => $rkm->catatan_pic,
                'pic_verifikator' => $p->picVerifikator?->nama,
                'submitted_at' => $rkm->updated_at->toISOString(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function show(int $rkmId): JsonResponse
    {
        $rkm = Rkm::with([
            'pendaftaranMbkm.programStudi',
            'pendaftaranMbkm.mitra',
            'items.jobdesks' => fn ($q) => $q->orderBy('urutan'),
        ])->findOrFail($rkmId);

        $p = $rkm->pendaftaranMbkm;

        return response()->json([
            'success' => true,
            'data' => [
                'rkm_id' => $rkm->id,
                'status' => $rkm->status,
                'total_sks' => $rkm->total_sks,
                'catatan_pic' => $rkm->catatan_pic,
                'mahasiswa' => [
                    'nim' => $p->nim,
                    'nama_lengkap' => $p->nama_lengkap,
                    'program_studi' => $p->programStudi?->nama,
                    'mitra' => $p->mitra_magang_text,
                    'posisi' => $p->posisi_magang,
                ],
                'items' => $rkm->items->map(fn ($item) => [
                    'id' => $item->id,
                    'mata_kuliah_id' => $item->mata_kuliah_id,
                    'kode_mk' => $item->kode_mk,
                    'nama_mk' => $item->nama_mk,
                    'sks' => $item->sks,
                    'durasi_jam' => $item->durasi_jam,
                    'kompetensi' => $item->kompetensi,
                    'status_mk' => $item->status_mk,
                    'jobdesks' => $item->jobdesks->map(fn ($j) => [
                        'id' => $j->id,
                        'deskripsi' => $j->deskripsi,
                        'origin' => $j->origin,
                    ]),
                ]),
            ],
        ]);
    }

    public function toggleDropItem(int $rkmMkId): JsonResponse
    {
        $item = RkmMataKuliah::with('rkm')->findOrFail($rkmMkId);

        $newStatus = $item->status_mk === 'aktif' ? 'dropped' : 'aktif';
        $item->update(['status_mk' => $newStatus]);

        // Hitung ulang SKS aktif
        $totalActiveSks = $item->rkm->items()->where('status_mk', 'aktif')->sum('sks');
        $item->rkm->update(['total_sks' => $totalActiveSks]);

        return response()->json([
            'success' => true,
            'message' => $newStatus === 'dropped' ? 'Mata kuliah berhasil digugurkan (drop).' : 'Mata kuliah berhasil dipulihkan (aktif).',
            'data' => [
                'id' => $item->id,
                'status_mk' => $newStatus,
                'total_sks' => $totalActiveSks,
            ],
        ]);
    }

    public function updateJobdesk(int $jobdeskId, Request $request): JsonResponse
    {
        $data = $request->validate([
            'deskripsi' => ['required', 'string', 'max:500'],
        ]);

        $jobdesk = RkmJobdesk::findOrFail($jobdeskId);
        $jobdesk->update(['deskripsi' => $data['deskripsi']]);

        return response()->json([
            'success' => true,
            'message' => 'Jobdesk berhasil disesuaikan oleh PIC.',
            'data' => $jobdesk,
        ]);
    }

    public function addJobdesk(int $rkmMkId, Request $request): JsonResponse
    {
        $data = $request->validate([
            'deskripsi' => ['required', 'string', 'max:500'],
        ]);

        $rkmMk = RkmMataKuliah::findOrFail($rkmMkId);
        $nextUrutan = $rkmMk->jobdesks()->max('urutan') + 1;

        $jobdesk = $rkmMk->jobdesks()->create([
            'urutan' => $nextUrutan,
            'deskripsi' => $data['deskripsi'],
            'origin' => 'custom',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Jobdesk tambahan berhasil ditambahkan oleh PIC.',
            'data' => $jobdesk,
        ], 201);
    }

    public function deleteJobdesk(int $jobdeskId): JsonResponse
    {
        $jobdesk = RkmJobdesk::findOrFail($jobdeskId);
        $jobdesk->delete();

        return response()->json([
            'success' => true,
            'message' => 'Jobdesk berhasil dihapus.',
        ]);
    }

    public function keputusan(int $rkmId, Request $request): JsonResponse
    {
        $data = $request->validate([
            'keputusan' => ['required', 'in:Disetujui,Perbaikan RKM'],
            'catatan' => ['nullable', 'string', 'required_if:keputusan,Perbaikan RKM'],
        ]);

        $rkm = Rkm::with('items')->findOrFail($rkmId);

        $updateData = [
            'status' => $data['keputusan'],
            'catatan_pic' => $data['catatan'] ?? null,
            'reviewed_by' => $request->user()->id,
        ];

        if ($data['keputusan'] === 'Disetujui') {
            $updateData['approved_at'] = now();
        }

        $rkm->update($updateData);

        // Otomasi generate dokumen RKP jika disetujui
        if ($data['keputusan'] === 'Disetujui') {
            $this->rkpService->generateFromRkm($rkm, $request->user());
        }

        return response()->json([
            'success' => true,
            'message' => $data['keputusan'] === 'Disetujui'
                ? 'RKM berhasil disetujui dan dokumen RKP telah digenerate otomatis.'
                : 'Catatan perbaikan RKM berhasil dikirimkan ke mahasiswa.',
            'data' => [
                'rkm_id' => $rkm->id,
                'status' => $rkm->status,
                'approved_at' => $rkm->approved_at,
            ],
        ]);
    }
}
