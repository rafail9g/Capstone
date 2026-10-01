const { z } = require('zod');

const prodiId = z.coerce.number().int().positive('Program studi tidak valid');
const kode = z.string().trim().min(1, 'Kode wajib diisi').max(50);
const deskripsi = z.string().trim().min(1, 'Deskripsi wajib diisi').max(2000);

const listBase = {
  page: z.coerce.number().int().min(1).default(1),
  limit: z.coerce.number().int().min(1).max(100).default(10),
  search: z.string().trim().max(100).optional(),
  program_studi_id: prodiId.optional(),
};

const atLeastOne = (v) => Object.values(v).some((val) => val !== undefined);

// ---------- CPL ----------
exports.createCpl = z.object({ kode, program_studi_id: prodiId, deskripsi });

exports.updateCpl = z
  .object({ kode: kode.optional(), program_studi_id: prodiId.optional(), deskripsi: deskripsi.optional() })
  .refine(atLeastOne, 'Minimal satu field harus diisi');

exports.listCpl = z.object(listBase);
exports.cplIdParam = z.object({ id: z.string().uuid('ID CPL tidak valid') });

// ---------- Mata kuliah ----------
// CPMK dikirim sebagai array deskripsi; isian kosong dibuang, jumlahnya bebas
const cpmk = z
  .array(z.string().max(2000, 'CPMK maksimal 2000 karakter'))
  .max(50, 'CPMK maksimal 50')
  .transform((arr) => arr.map((s) => s.trim()).filter(Boolean));

const namaMk = z.string().trim().min(2, 'Nama mata kuliah minimal 2 karakter').max(200);
const sks = z.coerce.number().int('SKS harus bilangan bulat').min(1, 'SKS minimal 1').max(6, 'SKS maksimal 6');
const cplId = z.string().uuid('ID CPL tidak valid');

exports.createMataKuliah = z.object({
  program_studi_id: prodiId,
  kode,
  nama: namaMk,
  cpl_id: cplId,
  sks,
  cpmk: cpmk.default([]),
});

exports.updateMataKuliah = z
  .object({
    program_studi_id: prodiId.optional(),
    kode: kode.optional(),
    nama: namaMk.optional(),
    cpl_id: cplId.optional(),
    sks: sks.optional(),
    cpmk: cpmk.optional(),
  })
  .refine(atLeastOne, 'Minimal satu field harus diisi');

exports.listMataKuliah = z.object(listBase);
exports.mataKuliahIdParam = z.object({ id: z.string().uuid('ID mata kuliah tidak valid') });
