const router = require('express').Router();
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');
const rateLimit = require('express-rate-limit');
const env = require('../config/env');
const supabase = require('../config/supabase');
const users = require('../services/userService');
const validate = require('../middleware/validate');
const { authenticate } = require('../middleware/auth');
const { AppError, asyncHandler, unwrap } = require('../utils/errors');
const schemas = require('../validators/schemas');

const loginLimiter = rateLimit({
  windowMs: 15 * 60 * 1000,
  max: 20,
  standardHeaders: true,
  legacyHeaders: false,
  message: { success: false, message: 'Terlalu banyak percobaan login, coba lagi nanti' },
});

router.post(
  '/login',
  loginLimiter,
  validate(schemas.login),
  asyncHandler(async (req, res) => {
    const { email, password } = req.body;
    const row = unwrap(
      await supabase.from('users').select('id,password_hash,is_active').eq('email', email).maybeSingle()
    );
    const ok = row && (await bcrypt.compare(password, row.password_hash));
    if (!ok) throw new AppError(401, 'Email atau password salah');
    if (!row.is_active) throw new AppError(403, 'Akun nonaktif');

    const token = jwt.sign({ sub: row.id }, env.jwtSecret, { expiresIn: env.jwtExpiresIn });
    res.json({ success: true, data: { token, user: await users.getById(row.id) } });
  })
);

const registerLimiter = rateLimit({
  windowMs: 15 * 60 * 1000,
  max: 10,
  standardHeaders: true,
  legacyHeaders: false,
  message: { success: false, message: 'Terlalu banyak percobaan daftar, coba lagi nanti' },
});

router.post(
  '/register',
  registerLimiter,
  validate(schemas.registerMahasiswa),
  asyncHandler(async (req, res) => {
    const user = await users.create({ ...req.body, user_type: 'mahasiswa' }, null);
    const token = jwt.sign({ sub: user.id }, env.jwtSecret, { expiresIn: env.jwtExpiresIn });
    res.status(201).json({ success: true, message: 'Pendaftaran berhasil', data: { token, user } });
  })
);

router.get(
  '/me',
  authenticate,
  asyncHandler(async (req, res) => {
    res.json({ success: true, data: await users.getById(req.user.id) });
  })
);

router.put(
  '/me',
  authenticate,
  validate(schemas.updateSelf),
  asyncHandler(async (req, res) => {
    const user = await users.updateOwnProfile(req.user.id, req.body);
    res.json({ success: true, message: 'Profil berhasil diperbarui', data: user });
  })
);

module.exports = router;
