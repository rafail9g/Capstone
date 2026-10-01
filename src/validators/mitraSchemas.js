const { z } = require('zod');

const emptyToUndef = (val) =>
  typeof val === 'string' && val.trim() === '' ? undefined : val;

const boolPreprocess = (val) => {
  if (val === 'true' || val === true) return true;
  if (val === 'false' || val === false) return false;
  return undefined;
};

const nama = z.string().trim().min(2, 'Nama minimal 2 karakter').max(200);
const alamat = z.preprocess(emptyToUndef, z.string().trim().max(500).optional());
const email = z.preprocess(emptyToUndef, z.string().trim().toLowerCase().email('Format email tidak valid').max(254).optional());
const deskripsi = z.preprocess(emptyToUndef, z.string().trim().max(2000).optional());
const isActive = z.preprocess(boolPreprocess, z.boolean().optional());

exports.createMitra = z.object({
  nama,
  alamat,
  email,
  deskripsi,
});

exports.updateMitra = z
  .object({
    nama: nama.optional(),
    alamat,
    email,
    deskripsi,
    is_active: isActive,
  })
  .refine(
    (v) => Object.values(v).some((val) => val !== undefined),
    'Minimal satu field harus diisi'
  );

exports.listMitra = z.object({
  page: z.coerce.number().int().min(1).default(1),
  limit: z.coerce.number().int().min(1).max(100).default(10),
  search: z.string().trim().max(100).optional(),
  is_active: z.enum(['true', 'false']).transform((v) => v === 'true').optional(),
});

exports.mitraIdParam = z.object({ id: z.string().uuid('ID mitra tidak valid') });
