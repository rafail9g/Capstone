const { z } = require('zod');

const USER_TYPES = ['admin', 'dosen', 'mahasiswa'];

const nama = z.string().trim().min(2).max(150);
const alamat = z.string().trim().max(500).nullable().optional();
const email = z.string().trim().toLowerCase().email().max(254);
const password = z.string().min(8, 'Minimal 8 karakter').max(72);
const roleIds = z.array(z.number().int().positive()).max(20);
const roleCode = z.string().trim().toLowerCase().regex(/^[a-z0-9_]+$/, 'Hanya huruf kecil, angka, underscore').min(2).max(50);
const userTypes = z.array(z.enum(USER_TYPES)).min(1);

const base = { nama, alamat, email, password };

exports.idParam = z.object({ id: z.string().uuid('ID tidak valid') });
exports.userRoleParams = z.object({
  id: z.string().uuid('ID tidak valid'),
  roleId: z.coerce.number().int().positive(),
});
exports.roleIdParam = z.object({ id: z.coerce.number().int().positive() });

exports.login = z.object({ email, password: z.string().min(1) });

exports.createUser = z.discriminatedUnion('user_type', [
  z.object({
    ...base,
    user_type: z.literal('mahasiswa'),
    nim: z.string().trim().min(3).max(30),
    prodi: z.string().trim().max(100).optional(),
    angkatan: z.number().int().min(1990).max(2100).optional(),
  }),
  z.object({
    ...base,
    user_type: z.literal('dosen'),
    nip: z.string().trim().min(3).max(30),
    role_ids: roleIds.optional(), // role dipilih admin (tim_mbkm, gpm, dpl, ...)
  }),
  z.object({ ...base, user_type: z.literal('admin') }),
]);

// Dipakai admin: bikin akun dosen (admin sekarang hanya boleh kelola dosen)
exports.createDosenByAdmin = z.object({
  ...base,
  nip: z.string().trim().min(3).max(30),
  role_ids: roleIds.optional(), // opsional: tim_mbkm, gpm, penguji_semhas, dpl, dst (boleh lebih dari satu)
});

// Mahasiswa daftar sendiri (publik, tanpa token)
exports.registerMahasiswa = z.object({
  ...base,
  nim: z.string().trim().min(3).max(30),
  prodi: z.string().trim().max(100).optional(),
  angkatan: z.number().int().min(1990).max(2100).optional(),
});

// Edit profil sendiri (siapa saja yang login) - tidak boleh ganti email/user_type/role/status
exports.updateSelf = z
  .object({ nama, alamat, password })
  .partial()
  .refine((v) => Object.keys(v).length > 0, 'Minimal satu field harus diisi');

exports.updateUser = z
  .object({
    nama,
    alamat,
    email,
    password,
    is_active: z.boolean(),
    nim: z.string().trim().min(3).max(30),
    prodi: z.string().trim().max(100).nullable(),
    angkatan: z.number().int().min(1990).max(2100).nullable(),
    nip: z.string().trim().min(3).max(30),
  })
  .partial()
  .refine((v) => Object.keys(v).length > 0, 'Minimal satu field harus diisi');

exports.listUsers = z.object({
  page: z.coerce.number().int().min(1).default(1),
  limit: z.coerce.number().int().min(1).max(100).default(10),
  user_type: z.enum(USER_TYPES).optional(),
  role: z.string().regex(/^[a-z0-9_]+$/).optional(),
  search: z.string().trim().max(100).optional(),
  is_active: z.enum(['true', 'false']).transform((v) => v === 'true').optional(),
});

exports.setRoles = z.object({ role_ids: roleIds });
exports.addRoles = z.object({ role_ids: roleIds.min(1) });

exports.createRole = z.object({
  code: roleCode,
  name: z.string().trim().min(2).max(100),
  description: z.string().trim().max(300).nullable().optional(),
  allowed_user_types: userTypes.default(['dosen']),
});
exports.updateRole = z
  .object({
    code: roleCode,
    name: z.string().trim().min(2).max(100),
    description: z.string().trim().max(300).nullable(),
    allowed_user_types: userTypes,
  })
  .partial()
  .refine((v) => Object.keys(v).length > 0, 'Minimal satu field harus diisi');
exports.listRoles = z.object({ user_type: z.enum(USER_TYPES).optional() });
