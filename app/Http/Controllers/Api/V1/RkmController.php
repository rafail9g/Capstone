<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MataKuliah;
use App\Models\PaketKonversi;
use App\Models\PendaftaranMbkm;
use App\Models\Rkm;
use App\Models\RkmJobdesk;
use App\Models\RkmMataKuliah;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RkmController extends Controller
{
    public function availableCourses(Request $request): JsonResponse
    {
        $user = $request->user();

        $pendaftaran = PendaftaranMbkm::with(['programStudi', 'periodeAkademik'])
            ->where('user_id', $user->id)
            ->latest()
            ->firstOrFail();

        // 1. Cari riwayat konversi MK di periode lampau yang berstatus Disetujui
        $pastConverted = DB::table('rkm_mata_kuliahs as rmk')
            ->join('rkms as r', 'r.id', '=', 'rmk.rkm_id')
            ->join('pendaftaran_mbkms as p', 'p.id', '=', 'r.pendaftaran_mbkm_id')
            ->join('periode_akademiks as pa', 'pa.id', '=', 'p.periode_akademik_id')
            ->where('p.user_id', $user->id)
            ->where('p.id', '!=', $pendaftaran->id)
            ->where('r.status', 'Disetujui')
            ->where('rmk.status_mk', 'aktif')
            ->select('rmk.mata_kuliah_id', 'pa.tahun_ajaran', 'pa.semester')
            ->get()
            ->keyBy('mata_kuliah_id');

        // 2. Ambil pool katalog paket konversi
        $paket = null;
        if (! $pendaftaran->is_custom_role) {
            $paket = PaketKonversi::with(['mataKuliahs.jobdesks'])
                ->where('program_studi_id', $pendaftaran->program_studi_id)
                ->where('target_posisi', 'like', "%{$pendaftaran->posisi_magang}%")
                ->first();

            if (! $paket) {
                $paket = PaketKonversi::with(['mataKuliahs.jobdesks'])
                    ->where('program_studi_id', $pendaftaran->program_studi_id)
                    ->first();
            }
        }

        if ($paket) {
            $courses = $paket->mataKuliahs;
            $namaPaket = $paket->nama_paket;
            $totalSksPool = $paket->total_sks_pool;
        } else {
            // Role custom: pool dari prodi + fakultas
            $courses = MataKuliah::with('jobdesks')
                ->where(function ($q) use ($pendaftaran) {
                    $q->where('lingkup', 'Fakultas')
                        ->orWhere('program_studi_id', $pendaftaran->program_studi_id);
                })
                ->get();
            $namaPaket = 'Semua Mata Kuliah Prodi & Fakultas';
            $totalSksPool = $courses->sum('sks');
        }

        $formattedCourses = $courses->map(function ($mk) use ($pastConverted) {
            $past = $pastConverted->get($mk->id);
            $durasiJam = $mk->sks * 40;
            if ($mk->kode === 'KSU1701') {
                $durasiJam = 80;
            }

            return [
                'mata_kuliah_id' => $mk->id,
                'kode' => $mk->kode,
                'nama_mk' => $mk->nama_mk,
                'sks' => $mk->sks,
                'durasi_jam' => $durasiJam,
                'kompetensi' => $mk->kompetensi,
                'already_converted_in_past' => (bool) $past,
                'converted_period' => $past ? "{$past->tahun_ajaran} {$past->semester}" : null,
                'default_jobdesks' => $mk->jobdesks->pluck('deskripsi')->values()->all(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'paket_konversi' => $namaPaket,
                'total_sks_pool' => $totalSksPool,
                'courses' => $formattedCourses,
            ],
        ]);
    }

    public function myRkm(Request $request): JsonResponse
    {
        $user = $request->user();

        $pendaftaran = PendaftaranMbkm::where('user_id', $user->id)->latest()->firstOrFail();

        $rkm = Rkm::with(['items.jobdesks' => fn ($q) => $q->orderBy('urutan')])
            ->where('pendaftaran_mbkm_id', $pendaftaran->id)
            ->first();

        if (! $rkm) {
            return response()->json([
                'success' => true,
                'data' => [
                    'rkm_id' => null,
                    'status' => 'Belum Mengajukan',
                    'total_sks' => 0,
                    'items' => [],
                ],
            ]);
        }

        $items = $rkm->items->map(function ($item) {
            return [
                'id' => $item->id,
                'mata_kuliah_id' => $item->mata_kuliah_id,
                'kode_mk' => $item->kode_mk,
                'nama_mk' => $item->nama_mk,
                'sks' => $item->sks,
                'durasi_jam' => $item->durasi_jam,
                'kompetensi' => $item->kompetensi,
                'status_mk' => $item->status_mk,
                'jobdesk_list' => $item->jobdesks->map(fn ($j) => [
                    'id' => $j->id,
                    'deskripsi' => $j->deskripsi,
                    'origin' => $j->origin,
                ]),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'rkm_id' => $rkm->id,
                'status' => $rkm->status,
                'total_sks' => $rkm->total_sks,
                'catatan_pic' => $rkm->catatan_pic,
                'items' => $items,
            ],
        ]);
    }

    public function saveSelection(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'selected_mata_kuliah_ids' => ['required', 'array', 'min:1'],
            'selected_mata_kuliah_ids.*' => ['integer', 'exists:mata_kuliahs,id'],
        ]);

        $pendaftaran = PendaftaranMbkm::where('user_id', $user->id)->latest()->firstOrFail();

        // 1. Validasi Total SKS <= 20
        $selectedMks = MataKuliah::with('jobdesks')
            ->whereIn('id', $data['selected_mata_kuliah_ids'])
            ->get();

        $totalSks = $selectedMks->sum('sks');
        if ($totalSks > 20) {
            return response()->json([
                'success' => false,
                'error_code' => 'ERR_RKM_SKS_LIMIT_EXCEEDED',
                'message' => 'Total SKS mata kuliah aktif yang dipilih melebihi batas maksimal konversi (Maksimal 20 SKS).',
                'errors' => [
                    'selected_mata_kuliah_ids' => ["Maksimal konversi MBKM adalah 20 SKS. Anda memilih {$totalSks} SKS."],
                ],
            ], 422);
        }

        // 2. Validasi Anti-Duplikasi Riwayat Konversi Lampau
        $pastConverted = DB::table('rkm_mata_kuliahs as rmk')
            ->join('rkms as r', 'r.id', '=', 'rmk.rkm_id')
            ->join('pendaftaran_mbkms as p', 'p.id', '=', 'r.pendaftaran_mbkm_id')
            ->where('p.user_id', $user->id)
            ->where('p.id', '!=', $pendaftaran->id)
            ->where('r.status', 'Disetujui')
            ->where('rmk.status_mk', 'aktif')
            ->whereIn('rmk.mata_kuliah_id', $data['selected_mata_kuliah_ids'])
            ->pluck('rmk.nama_mk', 'rmk.mata_kuliah_id');

        if ($pastConverted->isNotEmpty()) {
            $nama = $pastConverted->first();

            return response()->json([
                'success' => false,
                'error_code' => 'ERR_RKM_COURSE_ALREADY_CONVERTED',
                'message' => "Mata kuliah [{$nama}] tidak dapat dipilih karena sudah pernah dikonversi pada periode magang sebelumnya.",
                'errors' => [
                    'selected_mata_kuliah_ids' => ["Mata kuliah {$nama} sudah pernah dikonversi."],
                ],
            ], 422);
        }

        // 3. Simpan atau perbarui RKM
        return DB::transaction(function () use ($pendaftaran, $user, $selectedMks) {
            $rkm = Rkm::firstOrCreate(
                ['pendaftaran_mbkm_id' => $pendaftaran->id],
                [
                    'user_id' => $user->id,
                    'status' => 'Belum Mengajukan',
                    'total_sks' => 0,
                ]
            );

            // Hapus MK yang tidak dipilih lagi
            $rkm->items()->whereNotIn('mata_kuliah_id', $selectedMks->pluck('id'))->delete();

            // Tambah atau perbarui MK yang dipilih
            foreach ($selectedMks as $mk) {
                $durasiJam = $mk->sks * 40;
                if ($mk->kode === 'KSU1701') {
                    $durasiJam = 80;
                }

                $rkmMk = $rkm->items()->firstOrCreate(
                    ['mata_kuliah_id' => $mk->id],
                    [
                        'kode_mk' => $mk->kode,
                        'nama_mk' => $mk->nama_mk,
                        'sks' => $mk->sks,
                        'durasi_jam' => $durasiJam,
                        'kompetensi' => $mk->kompetensi,
                        'status_mk' => 'aktif',
                    ]
                );

                // Clone default jobdesks jika baru
                if ($rkmMk->wasRecentlyCreated) {
                    foreach ($mk->jobdesks as $j) {
                        $rkmMk->jobdesks()->create([
                            'urutan' => $j->urutan,
                            'deskripsi' => $j->deskripsi,
                            'origin' => 'master_mk',
                        ]);
                    }
                }
            }

            // Hitung total SKS aktif
            $totalActiveSks = $rkm->items()->where('status_mk', 'aktif')->sum('sks');
            $rkm->update(['total_sks' => $totalActiveSks]);

            return response()->json([
                'success' => true,
                'message' => 'Pemilihan mata kuliah RKM berhasil disimpan.',
                'data' => [
                    'rkm_id' => $rkm->id,
                    'total_sks' => $totalActiveSks,
                    'items_count' => $rkm->items()->count(),
                ],
            ]);
        });
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
            'message' => 'Jobdesk custom berhasil ditambahkan.',
            'data' => $jobdesk,
        ], 201);
    }

    public function updateJobdesk(int $jobdeskId, Request $request): JsonResponse
    {
        $data = $request->validate([
            'deskripsi' => ['required', 'string', 'max:500'],
        ]);

        $jobdesk = RkmJobdesk::findOrFail($jobdeskId);

        if (! $jobdesk->canBeEditedByStudent()) {
            return response()->json([
                'success' => false,
                'error_code' => 'ERR_JOBDESK_MASTER_LOCKED',
                'message' => 'Jobdesk master mata kuliah bersifat permanen bagi mahasiswa dan hanya dapat disesuaikan oleh PIC Verifikator.',
            ], 403);
        }

        $jobdesk->update(['deskripsi' => $data['deskripsi']]);

        return response()->json([
            'success' => true,
            'message' => 'Jobdesk berhasil diperbarui.',
            'data' => $jobdesk,
        ]);
    }

    public function deleteJobdesk(int $jobdeskId): JsonResponse
    {
        $jobdesk = RkmJobdesk::findOrFail($jobdeskId);

        if (! $jobdesk->canBeEditedByStudent()) {
            return response()->json([
                'success' => false,
                'error_code' => 'ERR_JOBDESK_MASTER_LOCKED',
                'message' => 'Jobdesk master mata kuliah bersifat permanen bagi mahasiswa dan hanya dapat disesuaikan oleh PIC Verifikator.',
            ], 403);
        }

        $jobdesk->delete();

        return response()->json([
            'success' => true,
            'message' => 'Jobdesk berhasil dihapus.',
        ]);
    }

    public function deleteItem(int $rkmMkId): JsonResponse
    {
        $rkmMk = RkmMataKuliah::with('rkm')->findOrFail($rkmMkId);
        $rkm = $rkmMk->rkm;

        $rkmMk->delete();

        // Hitung ulang SKS aktif
        $totalActiveSks = $rkm->items()->where('status_mk', 'aktif')->sum('sks');
        $rkm->update(['total_sks' => $totalActiveSks]);

        return response()->json([
            'success' => true,
            'message' => 'Mata kuliah berhasil dihapus dari RKM.',
            'data' => ['total_sks' => $totalActiveSks],
        ]);
    }

    public function submit(Request $request): JsonResponse
    {
        $user = $request->user();

        $pendaftaran = PendaftaranMbkm::where('user_id', $user->id)->latest()->firstOrFail();

        $rkm = Rkm::with('items')->where('pendaftaran_mbkm_id', $pendaftaran->id)->firstOrFail();

        if ($rkm->items()->where('status_mk', 'aktif')->count() === 0) {
            return response()->json([
                'success' => false,
                'message' => 'RKM harus memiliki minimal satu mata kuliah aktif sebelum diajukan.',
            ], 422);
        }

        $rkm->update(['status' => 'Diajukan']);

        return response()->json([
            'success' => true,
            'message' => 'RKM berhasil diajukan ke PIC Verifikator.',
            'data' => [
                'rkm_id' => $rkm->id,
                'status' => $rkm->status,
            ],
        ]);
    }
}
