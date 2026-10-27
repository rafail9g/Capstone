<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRkmNotLocked
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $rkm = $user->pendaftaranMbkm()
            ->whereHas('periodeAkademik', fn ($q) => $q->where('is_aktif', true))
            ->latest()
            ->first()
            ?->rkm;

        if ($rkm && $rkm->status === 'Disetujui') {
            return response()->json([
                'success' => false,
                'error_code' => 'ERR_RKM_LOCKED_APPROVED',
                'message' => 'Rencana Kegiatan Mahasiswa (RKM) Anda telah disetujui secara resmi oleh PIC Verifikator dan berada dalam status Terkunci (Read-Only). Anda tidak diperkenankan mengubah susunan mata kuliah, kompetensi, atau rincian jobdesk.',
                'errors' => null,
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                    'path' => $request->path(),
                    'rkm_status' => 'Disetujui',
                ],
            ], 403);
        }

        return $next($request);
    }
}

