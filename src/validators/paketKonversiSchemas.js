const { z } = require('zod');

const nama = z.string().trim().min(2, 'Nama plotting/paket minimal 2 karakter').max(100);
const deskripsi = z.string().trim().max(2000).optional();
const mataKuliahIds = z
  .array(z.string().uuid('ID mata kuliah harus UUID valid'))
  .transform((arr) => [...new Set(arr)]); // buang duplikat

const atLeastOne = (v) => Object.values(v).some((val) => val !== undefined);

exports.createPaketKonversi = z.object({
  nama,
  deskripsi: deskripsi.optional(),
  mata_kuliah_ids: mataKuliahIds.default([]),
});

exports.updatePaketKonversi = z
  .object({
    nama: nama.optional(),
    deskripsi: deskripsi.optional(),
    mata_kuliah_ids: mataKuliahIds.optional(),
  })
  .refine(atLeastOne, 'Minimal satu field harus diisi');

exports.listPaketKonversi = z.object({
  page: z.coerce.number().int().min(1).default(1),
  limit: z.coerce.number().int().min(1).max(100).default(10),
  search: z.string().trim().max(100).optional(),
});

exports.paketKonversiIdParam = z.object({
  id: z.string().uuid('ID paket konversi tidak valid'),
});
