const jwt = require('jsonwebtoken');
const env = require('../config/env');
const supabase = require('../config/supabase');
const { AppError, unwrap } = require('../utils/errors');

async function authenticate(req, res, next) {
  try {
    const header = req.headers.authorization || '';
    const token = header.startsWith('Bearer ') ? header.slice(7) : null;
    if (!token) throw new AppError(401, 'Token tidak ditemukan');

    let payload;
    try {
      payload = jwt.verify(token, env.jwtSecret);
    } catch {
      throw new AppError(401, 'Token tidak valid atau sudah kedaluwarsa');
    }

    const user = unwrap(
      await supabase
        .from('users')
        .select('id,nama,email,user_type,is_active,user_roles!user_roles_user_id_fkey(roles(code))')
        .eq('id', payload.sub)
        .maybeSingle()
    );
    if (!user || !user.is_active) throw new AppError(401, 'Akun tidak ditemukan atau nonaktif');

    req.user = {
      id: user.id,
      nama: user.nama,
      email: user.email,
      user_type: user.user_type,
      roles: user.user_roles.map((r) => r.roles.code),
    };
    next();
  } catch (err) {
    next(err);
  }
}

const requireRole = (...codes) => (req, res, next) =>
  codes.some((c) => req.user.roles.includes(c))
    ? next()
    : next(new AppError(403, 'Anda tidak memiliki akses'));

module.exports = { authenticate, requireRole };
