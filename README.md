# MBKM API — Manajemen User & Role

Node.js + Express + Supabase (PostgreSQL). Admin mengelola **akun & role dosen** (Tim MBKM, GPM, Penguji Semhas, DPL, dll — satu dosen boleh menjabat beberapa role sekaligus). Mahasiswa **daftar & kelola profil sendiri**. Ada Swagger UI buat coba-coba endpoint tanpa Postman.

## Setup

1. Skema database **tidak berubah** dari sebelumnya — kalau `supabase/schema.sql` sudah dijalankan, tidak perlu jalankan apa-apa lagi. Perubahan kali ini murni di sisi API (siapa boleh akses apa + endpoint baru), bukan di tabel.
2. `cp .env.example .env`, isi `SUPABASE_URL`, `SUPABASE_SERVICE_ROLE_KEY` (Project Settings → API), `JWT_SECRET`.
3. `npm install`
4. `npm run seed:admin` (bikin admin pertama dari `ADMIN_EMAIL` / `ADMIN_PASSWORD`, kalau belum ada)
5. `npm run dev`
6. Buka **http://localhost:3000/api-docs** → Swagger UI. Login dulu lewat `/auth/login`, copy token-nya, klik tombol **Authorize** (kanan atas) → tempel token → sekarang bisa "Try it out" endpoint yang butuh login langsung dari browser.

## Apa yang berubah dari versi sebelumnya

Tidak ada tabel/kolom yang dihapus atau ditambah di database — struktur `users`, `roles`, `user_roles`, `mahasiswa_profiles`, `dosen_profiles` tetap sama. Yang berubah:

- **Ruang lingkup admin dipersempit**: `/api/users` sekarang CRUD & kelola role **khusus akun dosen**. Admin tidak bisa lagi membuat/mengubah/menghapus akun mahasiswa lewat endpoint ini (kalau dicoba ke akun non-dosen → `403`).
- **Mahasiswa daftar sendiri**: `POST /api/auth/register` (publik, tanpa token) — otomatis dapat role dasar `mahasiswa` dan langsung dapat token (auto-login).
- **Edit profil sendiri**: `PUT /api/auth/me` — berlaku untuk semua tipe akun (admin/dosen/mahasiswa), hanya boleh ubah nama/alamat/password, bukan email/role/status.
- **Dosen tetap bisa menjabat banyak role sekaligus** — ini sudah didukung sejak awal lewat tabel pivot `user_roles` (bukan kolom tunggal), jadi tidak ada perubahan skema; `PUT/POST /api/users/:id/roles` dipakai untuk itu.
- **Landing/menu beda per role**: `GET /api/dashboard` — satu endpoint, isinya (daftar menu) menyesuaikan role akun yang login. Frontend tinggal render menu itu, tidak perlu hardcode logic per role.
- **Swagger UI** di `/api-docs` (spec: `openapi.json`).

## Model data (tidak berubah)

| Tabel | Isi |
|---|---|
| `users` | nama, alamat, email, password_hash, `user_type` (admin/dosen/mahasiswa), is_active |
| `mahasiswa_profiles` | `nim` (unik), prodi, angkatan |
| `dosen_profiles` | `nip` (unik) |
| `roles` | master role + `allowed_user_types` (siapa yang boleh memegang role) |
| `user_roles` | pivot user↔role (satu dosen bisa punya banyak role), `assigned_by`, `assigned_at` |

Aturan (dijaga di API **dan** trigger database):
- Role dosen (`tim_mbkm`, `gpm`, `penguji_semhas`, `dpl`, `wakil_dekan_1`) hanya untuk akun dosen.
- Mahasiswa otomatis dapat role `mahasiswa`, admin otomatis `admin`; role dasar ini tidak bisa dicabut.
- Admin tidak bisa menghapus/menonaktifkan akunnya sendiri atau mencabut role admin dari dirinya.

## Endpoint

Semua respons: `{ success, data, meta?, message? }`. Header: `Authorization: Bearer <token>`.

| Method | Path | Akses | Keterangan |
|---|---|---|---|
| POST | `/api/auth/login` | publik | `{email, password}` → token |
| POST | `/api/auth/register` | publik | mahasiswa daftar sendiri → token |
| GET | `/api/auth/me` | login | profil + role sendiri |
| PUT | `/api/auth/me` | login | edit nama/alamat/password sendiri |
| GET | `/api/dashboard` | login | menu/landing sesuai role |
| GET | `/api/roles?user_type=dosen` | login | daftar role (untuk checkbox) |
| POST/PUT/DELETE | `/api/roles[/:id]` | admin | kelola master role (role bawaan terlindungi) |
| GET | `/api/users?page&limit&role&search&is_active` | admin | list dosen + filter (search: nama/email/NIP) |
| POST | `/api/users` | admin | buat akun **dosen** (`nip` + `role_ids` opsional, boleh banyak) |
| GET/PUT/DELETE | `/api/users/:id` | admin | detail/edit/hapus akun **dosen** (403 kalau target bukan dosen) |
| GET | `/api/users/:id/roles` | admin | role milik dosen |
| **PUT** | `/api/users/:id/roles` | admin | **ganti semua role** `{role_ids:[3,6]}` (tombol Simpan form checkbox) |
| POST | `/api/users/:id/roles` | admin | tambah role (dosen bisa menjabat >1 role) |
| DELETE | `/api/users/:id/roles/:roleId` | admin | cabut satu role |

## Contoh cepat (kalau tidak lewat Swagger)

```bash
# login admin
curl -X POST localhost:3000/api/auth/login -H 'Content-Type: application/json' \
  -d '{"email":"admin@example.com","password":"GantiPasswordIni123"}'

# admin buat dosen + langsung kasih role Tim MBKM (id 3) & DPL (id 6)
curl -X POST localhost:3000/api/users -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' \
  -d '{"nama":"Dr. Budi","alamat":"Jember","email":"budi@kampus.ac.id","password":"rahasia123","nip":"198501012010011001","role_ids":[3,6]}'

# mahasiswa daftar sendiri (tanpa token)
curl -X POST localhost:3000/api/auth/register -H 'Content-Type: application/json' \
  -d '{"nama":"Danang Rizki","alamat":"Jember","email":"danang@gmail.com","password":"rahasia123","nim":"2101010001"}'

# lihat menu sesuai role yang login
curl localhost:3000/api/dashboard -H "Authorization: Bearer $TOKEN"
```

Role baru (mis. "Koordinator Prodi"): `POST /api/roles` `{ "code":"koordinator_prodi", "name":"Koordinator Prodi", "allowed_user_types":["dosen"] }` — otomatis muncul sebagai pilihan role dosen.

## Catatan keamanan
- `SUPABASE_SERVICE_ROLE_KEY` hanya di server (bypass RLS). RLS aktif tanpa policy, jadi akses langsung dari client Supabase ditolak.
- Password di-hash bcrypt; JWT hanya memuat id, role dibaca ulang dari DB tiap request (perubahan role langsung berlaku tanpa perlu login ulang).

## Akademik: Program Studi, CPL, Mata Kuliah, CPMK

Jalankan ulang `supabase/schema.sql` di SQL Editor (aman diulang, bagian baru ada di paling bawah). Semua endpoint di bawah **khusus admin**.

| Tabel | Isi |
|---|---|
| `program_studi` | 3 data awal: Teknologi Informasi, Sistem Informasi, Informatika |
| `cpl` | `kode`, `program_studi_id`, `deskripsi` (kode unik per program studi) |
| `mata_kuliah` | `program_studi_id`, `kode`, `nama`, `cpl_id`, `sks` (1-6) (kode unik per program studi) |
| `cpmk` | `mata_kuliah_id`, `urutan`, `deskripsi` (jumlah bebas per mata kuliah) |

Aturan: CPL yang dipilih di mata kuliah harus milik program studi yang sama (dijaga di API dan trigger). CPL yang sudah dipakai mata kuliah tidak bisa dihapus (`409`).

| Method | Path | Keterangan |
|---|---|---|
| GET | `/api/prodi` | isi dropdown Program Studi |
| GET/POST | `/api/cpl` | list (`?program_studi_id&search&page&limit`) / tambah `{kode, program_studi_id, deskripsi}` |
| GET/PUT/DELETE | `/api/cpl/:id` | detail / edit / hapus |
| GET/POST | `/api/mata-kuliah` | list / tambah `{program_studi_id, kode, nama, cpl_id, sks, cpmk: ["...", "..."]}` |
| GET/PUT/DELETE | `/api/mata-kuliah/:id` | detail (lengkap dengan CPL & CPMK) / edit / hapus |

Dropdown CPL di form mata kuliah: nonaktif sampai prodi dipilih, lalu panggil `GET /api/cpl?program_studi_id=<id>&limit=100`. Kalau prodi diganti saat edit, kirim juga `cpl_id` baru. `cpmk` di PUT: dikirim = ganti semua, tidak dikirim = tidak berubah.

## Pemaketan / Paket Konversi (Role & Skill MBKM)

Fitur pemaketan mata kuliah yang eligible untuk role / skill MBKM (seperti UI/UX Designer, Data Analyst, Web Developer, dsb.). Di form "Buat Paket Konversi", admin/tim MBKM memasukkan nama plotting dan memilih mata kuliah secara dropdown (+ tambah item). Akses: **Admin & Tim MBKM**.

| Tabel | Isi |
|---|---|
| `paket_konversi` | `nama` (nama plotting/skill MBKM), `deskripsi` |
| `paket_konversi_item` | pivot `paket_konversi_id`, `mata_kuliah_id` |

| Method | Path | Akses | Keterangan |
|---|---|---|---|
| GET | `/api/paket-konversi` | admin, tim_mbkm | list paket konversi (`?search&page&limit`) beserta mata kuliah & total SKS |
| POST | `/api/paket-konversi` | admin, tim_mbkm | buat paket `{nama, deskripsi, mata_kuliah_ids: ["uuid1", "uuid2"]}` |
| GET | `/api/paket-konversi/:id` | admin, tim_mbkm | detail paket konversi lengkap dengan mata kuliah |
| PUT | `/api/paket-konversi/:id` | admin, tim_mbkm | edit nama/deskripsi & sinkronisasi `mata_kuliah_ids` |
| DELETE | `/api/paket-konversi/:id` | admin, tim_mbkm | hapus paket konversi |

