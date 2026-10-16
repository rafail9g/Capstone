class AppError extends Error {
  constructor(status, message, details) {
    super(message);
    this.status = status;
    this.details = details;
  }
}

const asyncHandler = (fn) => (req, res, next) =>
  Promise.resolve(fn(req, res, next)).catch(next);

const FIELD_LABEL = {
  email: 'Email',
  nim: 'NIM',
  nip: 'NIP',
  code: 'Kode role',
  kode: 'Kode',
  nama: 'Nama',
};

function fromDb(error) {
  if (error instanceof AppError) return error;

  switch (error.code) {
    case '23505': {
      const details = error.details || '';
      const table = error.table || '';

      if (table === 'cpl' || details.includes('cpl_program_studi_id_kode_key') || (details.includes('program_studi_id') && details.includes('kode') && !table.includes('mata_kuliah'))) {
        return new AppError(409, 'Kode CPL sudah digunakan pada program studi ini');
      }
      if (table === 'mata_kuliah' || details.includes('mata_kuliah_program_studi_id_kode_key')) {
        return new AppError(409, 'Kode mata kuliah sudah digunakan pada program studi ini');
      }
      if (table === 'cpmk' || details.includes('cpmk_mata_kuliah_id_urutan_key')) {
        return new AppError(409, 'Urutan CPMK duplikat pada mata kuliah ini');
      }
      if (table === 'paket_konversi' || details.includes('paket_konversi_nama_key')) {
        return new AppError(409, 'Nama paket konversi sudah terdaftar');
      }
      if (table === 'program_studi') {
        return new AppError(409, 'Kode atau nama program studi sudah terdaftar');
      }

      const key = /Key \((.+?)\)=/.exec(details)?.[1] || '';
      const field = Object.keys(FIELD_LABEL).find((f) => key.includes(f));
      return new AppError(409, `${field ? FIELD_LABEL[field] : 'Data'} sudah terdaftar`);
    }

    case '23503': {
      const constraint = error.constraint || '';
      const details = error.details || '';

      if (constraint.includes('mata_kuliah_cpl_id_fkey') || details.includes('cpl')) {
        return new AppError(409, 'CPL tidak dapat dihapus karena masih digunakan oleh mata kuliah');
      }
      if (constraint.includes('paket_konversi_item_mata_kuliah_id_fkey') || details.includes('paket_konversi_item')) {
        return new AppError(409, 'Mata kuliah tidak dapat dihapus karena masih terdaftar di paket konversi');
      }
      if (constraint.includes('cpmk_mata_kuliah_id_fkey')) {
        return new AppError(409, 'Mata kuliah masih memiliki data CPMK terkait');
      }
      if (constraint.includes('program_studi')) {
        return new AppError(409, 'Program studi tidak dapat dihapus karena masih digunakan oleh CPL atau mata kuliah terkait');
      }
      if (constraint.includes('user_roles') || constraint.includes('roles')) {
        return new AppError(409, 'Role masih digunakan oleh pengguna aktif');
      }
      return new AppError(409, 'Data tidak dapat diubah/dihapus karena masih terkait dengan data lain');
    }

    case '23514': {
      const constraint = error.constraint || '';
      if (constraint.includes('sks')) {
        return new AppError(400, 'SKS harus bernilai antara 1 sampai 6');
      }
      if (constraint.includes('nama')) {
        return new AppError(400, 'Nama minimal harus 2 karakter');
      }
      if (constraint.includes('kode')) {
        return new AppError(400, 'Kode tidak boleh kosong');
      }
      if (constraint.includes('deskripsi')) {
        return new AppError(400, 'Deskripsi tidak boleh kosong');
      }
      return new AppError(400, 'Data yang dimasukkan tidak memenuhi ketentuan validasi');
    }

    case '22P02':
      return new AppError(400, 'Format data atau ID tidak valid (harus berupa UUID atau angka yang sesuai)');

    case 'P0001':
      return new AppError(422, error.message || 'Aturan bisnis database dilanggar');

    default:
      console.error('DB error:', error);
      return new AppError(500, 'Terjadi kesalahan pada database');
  }
}

function unwrap({ data, error }) {
  if (error) throw fromDb(error);
  return data;
}

module.exports = { AppError, asyncHandler, unwrap, fromDb };
