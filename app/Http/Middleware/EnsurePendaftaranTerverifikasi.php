<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePendaftaranTerverifikasi
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $pendaftaran = $user->pendaftaranMbkm()
            ->whereHas('periodeAkademik', function ($q) {
                $q->where('is_aktif', true);
            })
            ->latest()
            ->first();

        if (! $pendaftaran || $pendaftaran->status !== 'Terverifikasi') {
            return response()->json([
                'success' => false,
                'error_code' => 'ERR_RKM_PENDAFTARAN_NOT_VERIFIED',
                'message' => 'Fitur RKM Belum Dapat Diakses. Anda baru dapat menyusun Rencana Kegiatan Mahasiswa (RKM) setelah berkas Pendaftaran MBKM Anda dinyatakan Terverifikasi (Layak Magang) oleh Tim MBKM.',
                'data' => [
                    'current_status' => $pendaftaran ? $pendaftaran->status : 'Belum Mendaftar',
                ],
            ], 403);
        }

        return $next($request);
    }
}
