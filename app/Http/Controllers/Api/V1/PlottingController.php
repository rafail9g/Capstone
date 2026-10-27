<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\PendaftaranMbkm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlottingController extends Controller
{
    public function dosenList(): JsonResponse
    {
        $dosens = Dosen::with(['user.roles', 'programStudi'])->get()->map(function ($dosen) {
            $user = $dosen->user;
            $picCount = PendaftaranMbkm::where('pic_verifikator_id', $user->id)->count();
            $dplCount = PendaftaranMbkm::where('dpl_id', $user->id)->count();
            $pengujiCount = PendaftaranMbkm::where('penguji_semhas_id', $user->id)->count();

            return [
                'id' => $user->id,
                'dosen_id' => $dosen->id,
                'nama' => $user->nama,
                'nip' => $dosen->nip,
                'program_studi' => $dosen->programStudi?->nama,
                'program_studi_id' => $dosen->program_studi_id,
                'roles' => $user->roles->pluck('name'),
                'beban_bimbingan' => [
                    'pic_verifikator' => $picCount,
                    'dpl' => $dplCount,
                    'penguji_semhas' => $pengujiCount,
                    'total' => $picCount + $dplCount + $pengujiCount,
                ],
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $dosens,
        ]);
    }

    public function tahap1Pic(Request $request): JsonResponse
    {
        $query = PendaftaranMbkm::with(['programStudi', 'picVerifikator']);

        if ($request->filled('status_plotting') && $request->input('status_plotting') !== 'Semua') {
            if ($request->input('status_plotting') === 'Sudah Di-plot') {
                $query->where('is_plotted_pic', true);
            } elseif ($request->input('status_plotting') === 'Belum Di-plot') {
                $query->where('is_plotted_pic', false);
            }
        }

        if ($request->filled('prodi') && $request->input('prodi') !== 'Semua Prodi') {
            $prodi = $request->input('prodi');
            $query->whereHas('programStudi', function ($p) use ($prodi) {
                $p->where('nama', $prodi)->orWhere('kode', $prodi);
            });
        }

        $items = $query->orderBy('is_plotted_pic', 'asc')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'nim' => $p->nim,
                    'nama_lengkap' => $p->nama_lengkap,
                    'program_studi' => $p->programStudi?->nama,
                    'mitra_magang' => $p->mitra_magang_text,
                    'posisi_magang' => $p->posisi_magang,
                    'status_pendaftaran' => $p->status,
                    'is_plotted_pic' => (bool) $p->is_plotted_pic,
                    'pic_verifikator' => $p->picVerifikator ? [
                        'id' => $p->picVerifikator->id,
                        'nama' => $p->picVerifikator->nama,
                        'nip' => $p->picVerifikator->nim_nip,
                    ] : null,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function storeTahap1Pic(Request $request): JsonResponse
    {
        $data = $request->validate([
            'pendaftaran_mbkm_id' => ['required', 'exists:pendaftaran_mbkms,id'],
            'pic_verifikator_id' => ['required', 'exists:users,id'],
        ]);

        $pendaftaran = PendaftaranMbkm::findOrFail($data['pendaftaran_mbkm_id']);
        $pendaftaran->update([
            'pic_verifikator_id' => $data['pic_verifikator_id'],
            'is_plotted_pic' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Plotting PIC Verifikator berhasil disimpan!',
        ]);
    }

    public function tahap2DplPenguji(Request $request): JsonResponse
    {
        // Hanya mahasiswa dengan Pendaftaran Terverifikasi DAN RKM Disetujui
        $query = PendaftaranMbkm::with(['programStudi', 'dpl', 'pengujiSemhas', 'rkm'])
            ->where('status', 'Terverifikasi')
            ->whereHas('rkm', fn ($q) => $q->where('status', 'Disetujui'));

        if ($request->filled('status_plotting') && $request->input('status_plotting') !== 'Semua') {
            if ($request->input('status_plotting') === 'Sudah Di-plot') {
                $query->where('is_plotted_dpl_penguji', true);
            } elseif ($request->input('status_plotting') === 'Belum Di-plot') {
                $query->where('is_plotted_dpl_penguji', false);
            }
        }

        if ($request->filled('prodi') && $request->input('prodi') !== 'Semua Prodi') {
            $prodi = $request->input('prodi');
            $query->whereHas('programStudi', function ($p) use ($prodi) {
                $p->where('nama', $prodi)->orWhere('kode', $prodi);
            });
        }

        $items = $query->orderBy('is_plotted_dpl_penguji', 'asc')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'nim' => $p->nim,
                    'nama_lengkap' => $p->nama_lengkap,
                    'program_studi' => $p->programStudi?->nama,
                    'mitra_magang' => $p->mitra_magang_text,
                    'posisi_magang' => $p->posisi_magang,
                    'status_pendaftaran' => $p->status,
                    'status_rkm' => $p->rkm?->status,
                    'is_plotted_dpl_penguji' => (bool) $p->is_plotted_dpl_penguji,
                    'dpl' => $p->dpl ? [
                        'id' => $p->dpl->id,
                        'nama' => $p->dpl->nama,
                        'nip' => $p->dpl->nim_nip,
                    ] : null,
                    'penguji_semhas' => $p->pengujiSemhas ? [
                        'id' => $p->pengujiSemhas->id,
                        'nama' => $p->pengujiSemhas->nama,
                        'nip' => $p->pengujiSemhas->nim_nip,
                    ] : null,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function storeTahap2DplPenguji(Request $request): JsonResponse
    {
        $data = $request->validate([
            'pendaftaran_mbkm_id' => ['required', 'exists:pendaftaran_mbkms,id'],
            'dpl_id' => ['required', 'exists:users,id'],
            'penguji_semhas_id' => ['required', 'exists:users,id'],
        ]);

        if ($data['dpl_id'] === $data['penguji_semhas_id']) {
            return response()->json([
                'success' => false,
                'error_code' => 'ERR_PLOTTING_DPL_EQUALS_PENGUJI',
                'message' => 'Dosen Pembimbing Lapangan (DPL) tidak boleh merangkap sebagai Dosen Penguji Semhas untuk mahasiswa bimbingannya sendiri.',
                'errors' => [
                    'penguji_semhas_id' => ['Penguji Semhas harus dosen yang berbeda dari DPL.'],
                ],
            ], 422);
        }

        $pendaftaran = PendaftaranMbkm::with('rkm')->findOrFail($data['pendaftaran_mbkm_id']);

        if ($pendaftaran->status !== 'Terverifikasi' || $pendaftaran->rkm?->status !== 'Disetujui') {
            return response()->json([
                'success' => false,
                'error_code' => 'ERR_PLOTTING_TAHAP2_PRECONDITION_FAILED',
                'message' => 'Plotting Tahap 2 hanya dapat diproses setelah pendaftaran Terverifikasi dan RKM Disetujui.',
            ], 422);
        }

        $pendaftaran->update([
            'dpl_id' => $data['dpl_id'],
            'penguji_semhas_id' => $data['penguji_semhas_id'],
            'is_plotted_dpl_penguji' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Plotting DPL & Penguji Semhas berhasil disimpan!',
        ]);
    }
}
