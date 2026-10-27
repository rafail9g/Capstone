<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nim_nip' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::with(['roles', 'dosen.programStudi', 'mahasiswa.programStudi'])
            ->whereIdentifier($data['nim_nip'])
            ->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'error_code' => 'ERR_AUTH_INVALID_CREDENTIALS',
                'message' => 'NIM/NIP atau password salah.',
                'errors' => ['nim_nip' => ['Kredensial tidak valid.']],
            ], 401);
        }

        $token = $user->createToken('mbkm-web')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'data' => ['token' => $token, 'user' => $this->userPayload($user)],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->userPayload($request->user()->load(['roles', 'dosen.programStudi', 'mahasiswa.programStudi'])),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['success' => true, 'message' => 'Logout berhasil']);
    }

    private function userPayload(User $user): array
    {
        $roles = $user->roles->pluck('name')->values();

        $payload = [
            'id' => $user->id,
            'nama' => $user->nama,
            'nim_nip' => $user->nim_nip,
            'email' => $user->email,
            'roles' => $roles,
            'active_role' => $roles->first(),
            'avatar_url' => $user->avatar_url,
            'profil_tipe' => $user->dosen ? 'dosen' : ($user->mahasiswa ? 'mahasiswa' : 'admin'),
        ];

        if ($user->dosen) {
            $payload['dosen'] = [
                'id' => $user->dosen->id,
                'nip' => $user->dosen->nip,
                'program_studi_id' => $user->dosen->program_studi_id,
                'program_studi' => $user->dosen->programStudi?->nama,
            ];
        }

        if ($user->mahasiswa) {
            $payload['mahasiswa'] = [
                'id' => $user->mahasiswa->id,
                'nim' => $user->mahasiswa->nim,
                'angkatan' => $user->mahasiswa->angkatan,
                'program_studi_id' => $user->mahasiswa->program_studi_id,
                'program_studi' => $user->mahasiswa->programStudi?->nama,
            ];
        }

        return $payload;
    }
}
