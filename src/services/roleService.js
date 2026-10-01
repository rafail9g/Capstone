const supabase = require('../config/supabase');
const { AppError, unwrap } = require('../utils/errors');

async function list({ user_type } = {}) {
  let q = supabase.from('roles').select('*').order('id');
  if (user_type) q = q.contains('allowed_user_types', [user_type]);
  return unwrap(await q);
}

async function getById(id) {
  const role = unwrap(await supabase.from('roles').select('*').eq('id', id).maybeSingle());
  if (!role) throw new AppError(404, 'Role tidak ditemukan');
  return role;
}

async function create(data) {
  return unwrap(await supabase.from('roles').insert(data).select().single());
}

async function update(id, data) {
  const role = await getById(id);
  if (role.is_system && (data.code !== undefined || data.allowed_user_types !== undefined)) {
    throw new AppError(422, 'Kode dan tipe akun pada role bawaan tidak dapat diubah');
  }
  return unwrap(await supabase.from('roles').update(data).eq('id', id).select().single());
}

async function remove(id) {
  const role = await getById(id);
  if (role.is_system) throw new AppError(422, 'Role bawaan tidak dapat dihapus');
  // FK user_roles.role_id = RESTRICT -> ditolak (409) jika masih dipakai user
  unwrap(await supabase.from('roles').delete().eq('id', id));
}

module.exports = { list, getById, create, update, remove };
