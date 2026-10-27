<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Cpl;
use App\Models\Cpmk;
use App\Models\MataKuliah;
use App\Models\Mitra;
use App\Models\MkJobdesk;
use App\Models\PaketKonversi;
use App\Models\PeriodeAkademik;
use App\Models\ProgramStudi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MasterDataController extends Controller
{
    // =========================================================================
    // 1. MATA KULIAH
    // =========================================================================

    public function mataKuliah(Request $request): JsonResponse
    {
        $query = MataKuliah::with(['cpmks.cpl', 'cpls', 'jobdesks', 'programStudi']);

        if ($request->filled('prodi')) {
            $prodi = $request->input('prodi');
            $query->where(function ($q) use ($prodi) {
                $q->where('lingkup', 'Fakultas')
                    ->orWhereHas('programStudi', function ($p) use ($prodi) {
                        $p->where('nama', $prodi)->orWhere('kode', $prodi);
                    });
            });
        } elseif ($request->filled('program_studi_id')) {
            $prodiId = $request->input('program_studi_id');
            $query->where(function ($q) use ($prodiId) {
                $q->where('lingkup', 'Fakultas')
                    ->orWhere('program_studi_id', $prodiId);
            });
        }

        $list = $query->get()->map(function ($mk) {
            return [
                'id' => $mk->id,
                'kode' => $mk->kode,
                'nama_mk' => $mk->nama_mk,
                'sks' => $mk->sks,
                'lingkup' => $mk->lingkup,
                'program_studi_id' => $mk->program_studi_id,
                'program_studi' => $mk->programStudi?->nama,
                'kompetensi' => $mk->kompetensi,
                'cpls' => $mk->cpls->map(fn ($c) => [
                    'id' => $c->id,
                    'kode' => $c->kode,
                    'deskripsi' => $c->deskripsi,
                ]),
                'cpmks' => $mk->cpmks->map(fn ($c) => [
                    'id' => $c->id,
                    'kode' => $c->kode,
                    'deskripsi' => $c->deskripsi,
                    'cpl_kode' => $c->cpl?->kode,
                    'cpl_id' => $c->cpl_id,
                ]),
                'jobdesks' => $mk->jobdesks->map(fn ($j) => [
                    'id' => $j->id,
                    'urutan' => $j->urutan,
                    'deskripsi' => $j->deskripsi,
                ]),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $list,
        ]);
    }

    public function showMataKuliah(int $id): JsonResponse
    {
        $mk = MataKuliah::with(['cpmks.cpl', 'cpls', 'jobdesks', 'programStudi'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $mk->id,
                'kode' => $mk->kode,
                'nama_mk' => $mk->nama_mk,
                'sks' => $mk->sks,
                'lingkup' => $mk->lingkup,
                'program_studi_id' => $mk->program_studi_id,
                'program_studi' => $mk->programStudi?->nama,
                'kompetensi' => $mk->kompetensi,
                'cpls' => $mk->cpls->map(fn ($c) => [
                    'id' => $c->id,
                    'kode' => $c->kode,
                    'deskripsi' => $c->deskripsi,
                ]),
                'cpmks' => $mk->cpmks->map(fn ($c) => [
                    'id' => $c->id,
                    'kode' => $c->kode,
                    'deskripsi' => $c->deskripsi,
                    'cpl_kode' => $c->cpl?->kode,
                    'cpl_id' => $c->cpl_id,
                ]),
                'jobdesks' => $mk->jobdesks->map(fn ($j) => [
                    'id' => $j->id,
                    'urutan' => $j->urutan,
                    'deskripsi' => $j->deskripsi,
                ]),
            ],
        ]);
    }

    public function storeMataKuliah(Request $request): JsonResponse
    {
        // Aturan: Wajib pilih prodi dulu baru bisa isi lainnya
        $data = $request->validate([
            'program_studi_id' => ['required', 'exists:program_studis,id'],
            'kode' => ['required', 'string', 'max:20', 'unique:mata_kuliahs,kode'],
            'nama_mk' => ['required', 'string', 'max:150'],
            'sks' => ['required', 'integer', 'min:1', 'max:20'],
            'lingkup' => ['nullable', 'in:Fakultas,Prodi'],
            'kompetensi' => ['nullable', 'string'],
            // Tiap pilih CPL wajib isi CPMK (gaboleh di-skip)
            'cpls' => ['required', 'array', 'min:1'],
            'cpls.*.cpl_id' => ['required', 'exists:cpls,id'],
            'cpls.*.cpmk_deskripsi' => ['required', 'string', 'min:3'],
            'jobdesks' => ['nullable', 'array'],
            'jobdesks.*' => ['string'],
        ], [
            'program_studi_id.required' => 'Pilih Program Studi terlebih dahulu sebelum mengisi data mata kuliah.',
            'cpls.required' => 'Setiap mata kuliah wajib memiliki minimal 1 Capaian Pembelajaran Lulusan (CPL) dan CPMK.',
            'cpls.*.cpmk_deskripsi.required' => 'Deskripsi CPMK wajib diisi untuk setiap CPL yang dipilih.',
        ]);

        return DB::transaction(function () use ($data) {
            $mk = MataKuliah::create([
                'program_studi_id' => $data['program_studi_id'],
                'kode' => strtoupper(trim($data['kode'])),
                'nama_mk' => $data['nama_mk'],
                'sks' => $data['sks'],
                'lingkup' => $data['lingkup'] ?? 'Prodi',
                'kompetensi' => $data['kompetensi'] ?? 'Mampu memahami dan menerapkan konsep keilmuan secara profesional.',
            ]);

            // Sync CPL & CPMK
            $cplIds = [];
            foreach ($data['cpls'] as $idx => $cplItem) {
                $cplId = $cplItem['cpl_id'];
                $cplIds[] = $cplId;
                $cpmkKode = $cplItem['cpmk_kode'] ?? ('CPMK-'.($idx + 1));

                Cpmk::create([
                    'mata_kuliah_id' => $mk->id,
                    'cpl_id' => $cplId,
                    'kode' => $cpmkKode,
                    'deskripsi' => $cplItem['cpmk_deskripsi'],
                ]);
            }
            $mk->cpls()->sync(array_unique($cplIds));

            // Jobdesks default
            if (! empty($data['jobdesks'])) {
                foreach ($data['jobdesks'] as $idx => $jdText) {
                    MkJobdesk::create([
                        'mata_kuliah_id' => $mk->id,
                        'urutan' => $idx + 1,
                        'deskripsi' => $jdText,
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Mata kuliah berhasil ditambahkan.',
                'data' => $mk->load(['cpmks.cpl', 'cpls', 'jobdesks']),
            ], 201);
        });
    }

    public function updateMataKuliah(int $id, Request $request): JsonResponse
    {
        $mk = MataKuliah::findOrFail($id);

        $data = $request->validate([
            'program_studi_id' => ['required', 'exists:program_studis,id'],
            'kode' => ['required', 'string', 'max:20', Rule::unique('mata_kuliahs', 'kode')->ignore($mk->id)],
            'nama_mk' => ['required', 'string', 'max:150'],
            'sks' => ['required', 'integer', 'min:1', 'max:20'],
            'lingkup' => ['nullable', 'in:Fakultas,Prodi'],
            'kompetensi' => ['nullable', 'string'],
            'cpls' => ['required', 'array', 'min:1'],
            'cpls.*.cpl_id' => ['required', 'exists:cpls,id'],
            'cpls.*.cpmk_deskripsi' => ['required', 'string', 'min:3'],
            'jobdesks' => ['nullable', 'array'],
            'jobdesks.*' => ['string'],
        ]);

        return DB::transaction(function () use ($mk, $data) {
            $mk->update([
                'program_studi_id' => $data['program_studi_id'],
                'kode' => strtoupper(trim($data['kode'])),
                'nama_mk' => $data['nama_mk'],
                'sks' => $data['sks'],
                'lingkup' => $data['lingkup'] ?? $mk->lingkup,
                'kompetensi' => $data['kompetensi'] ?? $mk->kompetensi,
            ]);

            // Re-sync CPL & CPMK
            $mk->cpmks()->delete();
            $cplIds = [];
            foreach ($data['cpls'] as $idx => $cplItem) {
                $cplId = $cplItem['cpl_id'];
                $cplIds[] = $cplId;
                $cpmkKode = $cplItem['cpmk_kode'] ?? ('CPMK-'.($idx + 1));

                Cpmk::create([
                    'mata_kuliah_id' => $mk->id,
                    'cpl_id' => $cplId,
                    'kode' => $cpmkKode,
                    'deskripsi' => $cplItem['cpmk_deskripsi'],
                ]);
            }
            $mk->cpls()->sync(array_unique($cplIds));

            if (isset($data['jobdesks'])) {
                $mk->jobdesks()->delete();
                foreach ($data['jobdesks'] as $idx => $jdText) {
                    MkJobdesk::create([
                        'mata_kuliah_id' => $mk->id,
                        'urutan' => $idx + 1,
                        'deskripsi' => $jdText,
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Mata kuliah berhasil diperbarui.',
                'data' => $mk->load(['cpmks.cpl', 'cpls', 'jobdesks']),
            ]);
        });
    }

    public function destroyMataKuliah(int $id): JsonResponse
    {
        $mk = MataKuliah::findOrFail($id);

        // Cek jika digunakan dalam paket konversi atau RKM
        if ($mk->paketKonversis()->exists()) {
            return response()->json([
                'success' => false,
                'error_code' => 'ERR_FOREIGN_KEY_CONSTRAINT',
                'message' => 'Mata kuliah tidak dapat dihapus karena masih terdaftar dalam paket konversi.',
            ], 409);
        }

        $mk->delete();

        return response()->json([
            'success' => true,
            'message' => 'Mata kuliah berhasil dihapus.',
        ]);
    }

    // =========================================================================
    // 2. CPL (CAPAIAN PEMBELAJARAN LULUSAN)
    // =========================================================================

    public function cpl(Request $request): JsonResponse
    {
        $query = Cpl::with('programStudi');

        if ($request->filled('prodi')) {
            $prodi = $request->input('prodi');
            $query->whereHas('programStudi', function ($p) use ($prodi) {
                $p->where('nama', $prodi)->orWhere('kode', $prodi);
            });
        } elseif ($request->filled('program_studi_id')) {
            $query->where('program_studi_id', $request->input('program_studi_id'));
        }

        return response()->json([
            'success' => true,
            'data' => $query->get(),
        ]);
    }

    public function showCpl(int $id): JsonResponse
    {
        $cpl = Cpl::with('programStudi')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $cpl,
        ]);
    }

    public function storeCpl(Request $request): JsonResponse
    {
        $data = $request->validate([
            'program_studi_id' => ['required', 'exists:program_studis,id'],
            'kode' => ['required', 'string', 'max:30'],
            'deskripsi' => ['required', 'string'],
        ], [
            'program_studi_id.required' => 'Pilih Program Studi terlebih dahulu.',
        ]);

        $prodi = ProgramStudi::findOrFail($data['program_studi_id']);

        // Pastikan kode CPL konsisten dengan prefix prodi jika belum disertakan
        $rawKode = strtoupper(trim($data['kode']));
        if (! str_starts_with($rawKode, $prodi->cpl_prefix)) {
            $rawKode = $prodi->cpl_prefix.ltrim($rawKode, '_');
        }

        // Cek duplikasi kode
        if (Cpl::where('kode', $rawKode)->exists()) {
            return response()->json([
                'success' => false,
                'error_code' => 'ERR_DUPLICATE_ENTRY',
                'message' => "Kode CPL [{$rawKode}] sudah terdaftar pada sistem.",
            ], 409);
        }

        $cpl = Cpl::create([
            'program_studi_id' => $prodi->id,
            'kode' => $rawKode,
            'deskripsi' => $data['deskripsi'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'CPL berhasil ditambahkan.',
            'data' => $cpl->load('programStudi'),
        ], 201);
    }

    public function updateCpl(int $id, Request $request): JsonResponse
    {
        $cpl = Cpl::findOrFail($id);

        $data = $request->validate([
            'program_studi_id' => ['required', 'exists:program_studis,id'],
            'kode' => ['required', 'string', 'max:30'],
            'deskripsi' => ['required', 'string'],
        ]);

        $prodi = ProgramStudi::findOrFail($data['program_studi_id']);
        $rawKode = strtoupper(trim($data['kode']));
        if (! str_starts_with($rawKode, $prodi->cpl_prefix)) {
            $rawKode = $prodi->cpl_prefix.ltrim($rawKode, '_');
        }

        $duplicate = Cpl::where('kode', $rawKode)->where('id', '!=', $cpl->id)->exists();
        if ($duplicate) {
            return response()->json([
                'success' => false,
                'error_code' => 'ERR_DUPLICATE_ENTRY',
                'message' => "Kode CPL [{$rawKode}] sudah digunakan oleh CPL lain.",
            ], 409);
        }

        $cpl->update([
            'program_studi_id' => $prodi->id,
            'kode' => $rawKode,
            'deskripsi' => $data['deskripsi'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'CPL berhasil diperbarui.',
            'data' => $cpl->load('programStudi'),
        ]);
    }

    public function destroyCpl(int $id): JsonResponse
    {
        $cpl = Cpl::findOrFail($id);

        if ($cpl->cpmks()->exists() || $cpl->mataKuliahs()->exists()) {
            return response()->json([
                'success' => false,
                'error_code' => 'ERR_CPL_IN_USE',
                'message' => 'CPL tidak dapat dihapus karena masih digunakan oleh satu atau lebih mata kuliah.',
            ], 409);
        }

        $cpl->delete();

        return response()->json([
            'success' => true,
            'message' => 'CPL berhasil dihapus.',
        ]);
    }

    // =========================================================================
    // 3. PAKET KONVERSI
    // =========================================================================

    public function paketKonversi(Request $request): JsonResponse
    {
        $query = PaketKonversi::with(['programStudi', 'mataKuliahs.cpmks.cpl']);

        if ($request->filled('program_studi_id')) {
            $query->where('program_studi_id', $request->input('program_studi_id'));
        }

        return response()->json([
            'success' => true,
            'data' => $query->get(),
        ]);
    }

    public function showPaketKonversi(int $id): JsonResponse
    {
        $paket = PaketKonversi::with(['programStudi', 'mataKuliahs.cpmks.cpl'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $paket,
        ]);
    }

    public function storePaketKonversi(Request $request): JsonResponse
    {
        // Aturan: Wajib pilih prodi dulu baru bisa pilih MK
        $data = $request->validate([
            'nama_paket' => ['required', 'string', 'max:150'],
            'program_studi_id' => ['required', 'exists:program_studis,id'],
            'target_posisi' => ['nullable', 'string', 'max:100'],
            'mata_kuliah_ids' => ['required', 'array', 'min:1'],
            'mata_kuliah_ids.*' => ['integer', 'exists:mata_kuliahs,id'],
        ], [
            'program_studi_id.required' => 'Pilih Program Studi terlebih dahulu sebelum memilih mata kuliah.',
            'mata_kuliah_ids.required' => 'Pilih minimal satu mata kuliah untuk dimasukkan ke dalam paket konversi.',
        ]);

        $mks = MataKuliah::whereIn('id', $data['mata_kuliah_ids'])->get();

        // Validasi: MK harus berasal dari prodi yang sama atau lingkup Fakultas
        foreach ($mks as $mk) {
            if ($mk->lingkup !== 'Fakultas' && (int) $mk->program_studi_id !== (int) $data['program_studi_id']) {
                return response()->json([
                    'success' => false,
                    'error_code' => 'ERR_PAKET_PRODI_MISMATCH',
                    'message' => 'Mata kuliah yang dimasukkan ke dalam paket konversi harus berasal dari program studi yang sama atau lingkup Fakultas.',
                ], 422);
            }
        }

        $jumlahMk = $mks->count();
        $totalSks = $mks->sum('sks');

        return DB::transaction(function () use ($data, $mks, $jumlahMk, $totalSks) {
            $paket = PaketKonversi::create([
                'nama_paket' => strtoupper(trim($data['nama_paket'])),
                'program_studi_id' => $data['program_studi_id'],
                'target_posisi' => $data['target_posisi'] ?? ucwords(strtolower($data['nama_paket'])),
                'jumlah_mk' => $jumlahMk,
                'total_sks_pool' => $totalSks,
            ]);

            $pivotData = [];
            foreach ($data['mata_kuliah_ids'] as $idx => $mkId) {
                $pivotData[$mkId] = ['urutan' => $idx + 1];
            }
            $paket->mataKuliahs()->sync($pivotData);

            return response()->json([
                'success' => true,
                'message' => 'Paket konversi berhasil dibuat.',
                'data' => $paket->load(['programStudi', 'mataKuliahs']),
            ], 201);
        });
    }

    public function updatePaketKonversi(int $id, Request $request): JsonResponse
    {
        $paket = PaketKonversi::findOrFail($id);

        $data = $request->validate([
            'nama_paket' => ['required', 'string', 'max:150'],
            'program_studi_id' => ['required', 'exists:program_studis,id'],
            'target_posisi' => ['nullable', 'string', 'max:100'],
            'mata_kuliah_ids' => ['required', 'array', 'min:1'],
            'mata_kuliah_ids.*' => ['integer', 'exists:mata_kuliahs,id'],
        ]);

        $mks = MataKuliah::whereIn('id', $data['mata_kuliah_ids'])->get();

        foreach ($mks as $mk) {
            if ($mk->lingkup !== 'Fakultas' && (int) $mk->program_studi_id !== (int) $data['program_studi_id']) {
                return response()->json([
                    'success' => false,
                    'error_code' => 'ERR_PAKET_PRODI_MISMATCH',
                    'message' => 'Mata kuliah yang dimasukkan ke dalam paket konversi harus berasal dari program studi yang sama atau lingkup Fakultas.',
                ], 422);
            }
        }

        $jumlahMk = $mks->count();
        $totalSks = $mks->sum('sks');

        return DB::transaction(function () use ($paket, $data, $jumlahMk, $totalSks) {
            $paket->update([
                'nama_paket' => strtoupper(trim($data['nama_paket'])),
                'program_studi_id' => $data['program_studi_id'],
                'target_posisi' => $data['target_posisi'] ?? ucwords(strtolower($data['nama_paket'])),
                'jumlah_mk' => $jumlahMk,
                'total_sks_pool' => $totalSks,
            ]);

            $pivotData = [];
            foreach ($data['mata_kuliah_ids'] as $idx => $mkId) {
                $pivotData[$mkId] = ['urutan' => $idx + 1];
            }
            $paket->mataKuliahs()->sync($pivotData);

            return response()->json([
                'success' => true,
                'message' => 'Paket konversi berhasil diperbarui.',
                'data' => $paket->load(['programStudi', 'mataKuliahs']),
            ]);
        });
    }

    public function destroyPaketKonversi(int $id): JsonResponse
    {
        $paket = PaketKonversi::findOrFail($id);

        if ($paket->rkms()->exists()) {
            return response()->json([
                'success' => false,
                'error_code' => 'ERR_FOREIGN_KEY_CONSTRAINT',
                'message' => 'Paket konversi tidak dapat dihapus karena telah digunakan pada dokumen RKM mahasiswa.',
            ], 409);
        }

        $paket->delete();

        return response()->json([
            'success' => true,
            'message' => 'Paket konversi berhasil dihapus.',
        ]);
    }

    // =========================================================================
    // 4. MITRA (KELOLA MITRA - KHUSUS ADMIN)
    // =========================================================================

    public function mitra(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Mitra::orderBy('nama', 'asc')->get(),
        ]);
    }

    public function showMitra(int $id): JsonResponse
    {
        $mitra = Mitra::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $mitra,
        ]);
    }

    public function storeMitra(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'nomor_pks' => ['nullable', 'string', 'max:100'],
            'alamat' => ['required', 'string'],
            'kontak' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:100'],
            'deskripsi' => ['nullable', 'string'],
            'image_url' => ['nullable', 'string', 'max:255'],
        ]);

        $mitra = Mitra::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Data mitra berhasil ditambahkan.',
            'data' => $mitra,
        ], 201);
    }

    public function updateMitra(int $id, Request $request): JsonResponse
    {
        $mitra = Mitra::findOrFail($id);

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'nomor_pks' => ['nullable', 'string', 'max:100'],
            'alamat' => ['required', 'string'],
            'kontak' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:100'],
            'deskripsi' => ['nullable', 'string'],
            'image_url' => ['nullable', 'string', 'max:255'],
        ]);

        $mitra->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Data mitra berhasil diperbarui.',
            'data' => $mitra,
        ]);
    }

    public function destroyMitra(int $id): JsonResponse
    {
        $mitra = Mitra::findOrFail($id);

        if ($mitra->pendaftaranMbkms()->exists()) {
            return response()->json([
                'success' => false,
                'error_code' => 'ERR_FOREIGN_KEY_CONSTRAINT',
                'message' => 'Mitra tidak dapat dihapus karena masih terhubung dengan pendaftaran MBKM aktif.',
            ], 409);
        }

        $mitra->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data mitra berhasil dihapus.',
        ]);
    }

    // =========================================================================
    // 5. PROGRAM STUDI & PERIODE AKADEMIK (READ-ONLY)
    // =========================================================================

    public function programStudi(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => ProgramStudi::all(),
        ]);
    }

    public function periodeAkademik(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => PeriodeAkademik::orderBy('id', 'desc')->get(),
        ]);
    }

    public function periodeAktif(): JsonResponse
    {
        $aktif = PeriodeAkademik::where('is_aktif', true)->first();

        return response()->json([
            'success' => true,
            'data' => $aktif,
        ]);
    }
}
