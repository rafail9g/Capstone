<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::with(['roles', 'dosen.programStudi', 'mahasiswa.programStudi']);

        if ($request->filled('role')) {
            $role = $request->input('role');
            $query->whereHas('roles', fn ($q) => $q->where('name', $role));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('dosen', fn ($d) => $d->where('nip', 'like', "%{$search}%"))
                    ->orWhereHas('mahasiswa', fn ($m) => $m->where('nim', 'like', "%{$search}%"));
            });
        }

        $users = $query->orderBy('id', 'desc')->get()->map(function ($u) {
            return [
                'id' => $u->id,
                'nama' => $u->nama,
                'email' => $u->email,
                'nim_nip' => $u->nim_nip,
                'roles' => $u->roles->pluck('name'),
                'avatar_url' => $u->avatar_url,
                'profil_tipe' => $u->dosen ? 'dosen' : ($u->mahasiswa ? 'mahasiswa' : 'admin'),
                'dosen' => $u->dosen ? [
                    'nip' => $u->dosen->nip,
                    'program_studi' => $u->dosen->programStudi?->nama,
                    'program_studi_id' => $u->dosen->program_studi_id,
                ] : null,
                'mahasiswa' => $u->mahasiswa ? [
                    'nim' => $u->mahasiswa->nim,
                    'program_studi' => $u->mahasiswa->programStudi?->nama,
                    'program_studi_id' => $u->mahasiswa->program_studi_id,
                    'angkatan' => $u->mahasiswa->angkatan,
                ] : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $user = User::with(['roles', 'dosen.programStudi', 'mahasiswa.programStudi'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'nama' => $user->nama,
                'email' => $user->email,
                'nim_nip' => $user->nim_nip,
                'alamat' => $user->alamat,
                'no_hp' => $user->no_hp,
                'roles' => $user->roles->pluck('name'),
                'avatar_url' => $user->avatar_url,
                'profil_tipe' => $user->dosen ? 'dosen' : ($user->mahasiswa ? 'mahasiswa' : 'admin'),
                'dosen' => $user->dosen,
                'mahasiswa' => $user->mahasiswa,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['nullable', 'string', 'min:6'],
            'tipe_akun' => ['required', 'in:dosen,mahasiswa,admin'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', 'exists:roles,name'],
            'avatar_url' => ['nullable', 'string'],
            'no_hp' => ['nullable', 'string', 'max:20'],
            'alamat' => ['nullable', 'string'],

            // Khusus dosen
            'nip' => ['nullable', 'string', 'max:30', 'required_if:tipe_akun,dosen', 'unique:dosens,nip'],
            'program_studi_id' => ['nullable', 'exists:program_studis,id', 'required_if:tipe_akun,mahasiswa'],

            // Khusus mahasiswa
            'nim' => ['nullable', 'string', 'max:30', 'required_if:tipe_akun,mahasiswa', 'unique:mahasiswas,nim'],
            'angkatan' => ['nullable', 'integer', 'required_if:tipe_akun,mahasiswa'],
        ]);

        return DB::transaction(function () use ($data) {
            $user = User::create([
                'nama' => $data['nama'],
                'email' => $data['email'],
                'password' => Hash::make($data['password'] ?? 'password123'),
                'avatar_url' => $data['avatar_url'] ?? null,
                'no_hp' => $data['no_hp'] ?? null,
                'alamat' => $data['alamat'] ?? null,
            ]);

            // Sync roles
            $roleIds = Role::whereIn('name', $data['roles'])->pluck('id');
            $user->roles()->sync($roleIds);

            // Simpan profil spesifik
            if ($data['tipe_akun'] === 'dosen') {
                Dosen::create([
                    'user_id' => $user->id,
                    'nip' => $data['nip'],
                    'program_studi_id' => $data['program_studi_id'] ?? null,
                ]);
            } elseif ($data['tipe_akun'] === 'mahasiswa') {
                Mahasiswa::create([
                    'user_id' => $user->id,
                    'nim' => $data['nim'],
                    'program_studi_id' => $data['program_studi_id'],
                    'angkatan' => $data['angkatan'] ?? (int) date('Y'),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Akun pengguna berhasil dibuat.',
                'data' => $user->load(['roles', 'dosen', 'mahasiswa']),
            ], 201);
        });
    }

    public function update(int $id, Request $request): JsonResponse
    {
        $user = User::with(['dosen', 'mahasiswa'])->findOrFail($id);

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:6'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
            'avatar_url' => ['nullable', 'string'],
            'no_hp' => ['nullable', 'string', 'max:20'],
            'alamat' => ['nullable', 'string'],
            'nip' => ['nullable', 'string', 'max:30'],
            'nim' => ['nullable', 'string', 'max:30'],
            'program_studi_id' => ['nullable', 'exists:program_studis,id'],
            'angkatan' => ['nullable', 'integer'],
        ]);

        return DB::transaction(function () use ($user, $data) {
            $userUpdate = [
                'nama' => $data['nama'],
                'email' => $data['email'],
                'avatar_url' => $data['avatar_url'] ?? $user->avatar_url,
                'no_hp' => $data['no_hp'] ?? $user->no_hp,
                'alamat' => $data['alamat'] ?? $user->alamat,
            ];

            if (! empty($data['password'])) {
                $userUpdate['password'] = Hash::make($data['password']);
            }

            $user->update($userUpdate);

            if (isset($data['roles'])) {
                $roleIds = Role::whereIn('name', $data['roles'])->pluck('id');
                $user->roles()->sync($roleIds);
            }

            // Update profil dosen jika ada
            if ($user->dosen && isset($data['nip'])) {
                $user->dosen->update([
                    'nip' => $data['nip'],
                    'program_studi_id' => $data['program_studi_id'] ?? $user->dosen->program_studi_id,
                ]);
            }

            // Update profil mahasiswa jika ada
            if ($user->mahasiswa && isset($data['nim'])) {
                $user->mahasiswa->update([
                    'nim' => $data['nim'],
                    'program_studi_id' => $data['program_studi_id'] ?? $user->mahasiswa->program_studi_id,
                    'angkatan' => $data['angkatan'] ?? $user->mahasiswa->angkatan,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Data pengguna berhasil diperbarui.',
                'data' => $user->load(['roles', 'dosen', 'mahasiswa']),
            ]);
        });
    }

    public function destroy(int $id, Request $request): JsonResponse
    {
        $user = User::findOrFail($id);

        if ($request->user()->id === $user->id) {
            return response()->json([
                'success' => false,
                'error_code' => 'ERR_FORBIDDEN_ROLE',
                'message' => 'Anda tidak dapat menghapus akun Anda sendiri.',
            ], 403);
        }

        if ($user->pendaftaranMbkm()->exists()) {
            return response()->json([
                'success' => false,
                'error_code' => 'ERR_FOREIGN_KEY_CONSTRAINT',
                'message' => 'Pengguna tidak dapat dihapus karena memiliki transaksi pendaftaran MBKM.',
            ], 409);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pengguna berhasil dihapus.',
        ]);
    }
}

