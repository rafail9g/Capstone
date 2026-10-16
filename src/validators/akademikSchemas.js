const { z } = require('zod');

const prodiIdRequired = z.preprocess(
  (val) => (val === undefined || val === null || val === '' ? undefined : Number(val)),
  z
    .number({
      required_error: 'Program studi wajib dipilih terlebih dahulu',
      invalid_type_error: 'Program studi harus berupa ID valid',
    })
    .int('ID Program studi tidak valid')
    .positive('Program studi wajib dipilih terlebih dahulu')
);

const prodiIdOptional = z.preprocess(
  (val) => (val === undefined || val === null || val === '' ? undefined : Number(val)),
  z
    .number({
      invalid_type_error: 'Program studi harus berupa ID valid',
    })
    .int('ID Program studi tidak valid')
    .positive('Program studi tidak valid')
);

const kodeCpl = z
  .string({ required_error: 'Kode CPL wajib diisi' })
  .trim()
  .min(1, 'Kode CPL wajib diisi')
  .max(50, 'Kode CPL maksimal 50 karakter');

const deskripsiCpl = z
  .string({ required_error: 'Deskripsi CPL wajib diisi' })
  .trim()
  .min(1, 'Deskripsi CPL wajib diisi')
  .max(2000, 'Deskripsi CPL maksimal 2000 karakter');

const listBase = {
  page: z.coerce.number().int().min(1).default(1),
  limit: z.coerce.number().int().min(1).max(100).default(10),
  search: z.string().trim().max(100).optional(),
  program_studi_id: prodiIdOptional.optional(),
};

const atLeastOne = (v) => Object.values(v).some((val) => val !== undefined);

exports.createCpl = z.object({
  program_studi_id: prodiIdRequired,
  kode: kodeCpl,
  deskripsi: deskripsiCpl,
});

exports.updateCpl = z
  .object({
    program_studi_id: prodiIdOptional.optional(),
    kode: kodeCpl.optional(),
    deskripsi: deskripsiCpl.optional(),
  })
  .refine(atLeastOne, 'Minimal satu field harus diisi');

exports.listCpl = z.object(listBase);
exports.cplIdParam = z.object({ id: z.string().uuid('ID CPL tidak valid') });

const singleCpmk = z
  .string({ required_error: 'Deskripsi CPMK wajib diisi' })
  .trim()
  .min(1, 'Deskripsi CPMK tidak boleh kosong')
  .max(2000, 'Deskripsi CPMK maksimal 2000 karakter');

const cpmkArray = z
  .array(singleCpmk, {
    required_error: 'Setiap CPL yang dipilih wajib diisi minimal 1 CPMK terlebih dahulu',
    invalid_type_error: 'Format CPMK harus berupa daftar/array',
  })
  .min(1, 'Setiap CPL yang dipilih wajib diisi minimal 1 CPMK terlebih dahulu')
  .max(50, 'CPMK maksimal 50 per mata kuliah')
  .refine((arr) => {
    const nonDupe = new Set(arr.map((s) => s.trim().toLowerCase()));
    return nonDupe.size === arr.length;
  }, 'Terdapat deskripsi CPMK yang duplikat, setiap CPMK harus unik');

const kodeMk = z
  .string({ required_error: 'Kode mata kuliah wajib diisi' })
  .trim()
  .min(1, 'Kode mata kuliah wajib diisi')
  .max(50, 'Kode mata kuliah maksimal 50 karakter');

const namaMk = z
  .string({ required_error: 'Nama mata kuliah wajib diisi' })
  .trim()
  .min(2, 'Nama mata kuliah minimal 2 karakter')
  .max(200, 'Nama mata kuliah maksimal 200 karakter');

const sks = z.preprocess(
  (val) => (val === undefined || val === null || val === '' ? undefined : Number(val)),
  z
    .number({
      required_error: 'SKS wajib diisi',
      invalid_type_error: 'SKS harus berupa bilangan bulat',
    })
    .int('SKS harus berupa bilangan bulat')
    .min(1, 'SKS minimal 1')
    .max(6, 'SKS maksimal 6')
);

const cplId = z
  .string({ required_error: 'CPL wajib dipilih terlebih dahulu dari program studi yang dipilih' })
  .uuid('ID CPL tidak valid');

exports.createMataKuliah = z.object({
  program_studi_id: prodiIdRequired,
  cpl_id: cplId,
  kode: kodeMk,
  nama: namaMk,
  sks,
  cpmk: cpmkArray,
});

exports.updateMataKuliah = z
  .object({
    program_studi_id: prodiIdOptional.optional(),
    cpl_id: cplId.optional(),
    kode: kodeMk.optional(),
    nama: namaMk.optional(),
    sks: sks.optional(),
    cpmk: cpmkArray.optional(),
  })
  .refine(atLeastOne, 'Minimal satu field harus diisi');

exports.listMataKuliah = z.object(listBase);
exports.mataKuliahIdParam = z.object({ id: z.string().uuid('ID mata kuliah tidak valid') });
