class AppError extends Error {
  constructor(status, message, details) {
    super(message);
    this.status = status;
    this.details = details;
  }
}

const asyncHandler = (fn) => (req, res, next) =>
  Promise.resolve(fn(req, res, next)).catch(next);

const FIELD_LABEL = { email: 'Email', nim: 'NIM', nip: 'NIP', code: 'Kode role', kode: 'Kode' };

// Ubah error PostgREST/Postgres menjadi AppError yang ramah
function fromDb(error) {
  if (error instanceof AppError) return error;
  switch (error.code) {
    case '23505': {
      const key = /Key \((.+?)\)=/.exec(error.details || '')?.[1] || '';
      const field = Object.keys(FIELD_LABEL).find((f) => key.includes(f));
      return new AppError(409, `${field ? FIELD_LABEL[field] : 'Data'} sudah terdaftar`);
    }
    case '23503':
      return new AppError(409, 'Data masih dipakai atau terkait dengan data lain');
    case '23514':
    case '22P02':
      return new AppError(400, 'Data tidak valid');
    case 'P0001': // raise exception dari trigger
      return new AppError(422, error.message);
    default:
      console.error('DB error:', error);
      return new AppError(500, 'Terjadi kesalahan pada database');
  }
}

// Ambil data dari hasil query supabase-js, lempar error jika gagal
function unwrap({ data, error }) {
  if (error) throw fromDb(error);
  return data;
}

module.exports = { AppError, asyncHandler, unwrap, fromDb };
