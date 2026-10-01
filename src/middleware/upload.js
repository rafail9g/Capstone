const multer = require('multer');
const { AppError } = require('../utils/errors');

const ALLOWED_MIME = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
const MAX_SIZE = 5 * 1024 * 1024; // 5 MB

// Simpan di memory (buffer) – kita langsung pipe ke Supabase Storage
const storage = multer.memoryStorage();

const upload = multer({
  storage,
  limits: { fileSize: MAX_SIZE },
  fileFilter(_req, file, cb) {
    if (!ALLOWED_MIME.includes(file.mimetype)) {
      return cb(new AppError(400, 'Format foto tidak didukung. Gunakan JPG, PNG, WebP, atau GIF'));
    }
    cb(null, true);
  },
});

/**
 * Middleware single-file upload untuk field "foto".
 * Lempar AppError yang ramah kalau ada masalah Multer.
 */
function uploadFotoMiddleware(req, res, next) {
  upload.single('foto')(req, res, (err) => {
    if (!err) return next();
    if (err instanceof multer.MulterError) {
      if (err.code === 'LIMIT_FILE_SIZE')
        return next(new AppError(400, 'Ukuran foto maksimal 5 MB'));
      return next(new AppError(400, `Upload error: ${err.message}`));
    }
    next(err);
  });
}

module.exports = { uploadFotoMiddleware };
