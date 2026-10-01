const bcrypt = require('bcryptjs');
const supabase = require('../config/supabase');
const { AppError, unwrap } = require('../utils/errors');

const USER_SELECT = [
  'id,nama,alamat,email,user_type,is_active,created_at,updated_at',
  'mahasiswa_profiles(nim,prodi,angkatan)',
  'dosen_profiles(nip)',
  'user_roles!user_roles_user_id_fkey(assigned_at,roles(id,code,name))',
].join(',');

const BASE_ROLE = { mahasiswa: 'mahasiswa', admin: 'admin' };

const one = (v) => (Array.isArray(v) ? v[0] : v);
const defined = (obj) => Object.fromEntries(Object.entries(obj).filter(([, v]) => v !== undefined));

function format(u) {
  const m = one(u.mahasiswa_profiles);
  const d = one(u.dosen_profiles);
  return {
    id: u.id,
    nama: u.nama,
    alamat: u.alamat,
    email: u.email,
    user_type: u.user_type,
    is_active: u.is_active,
    ...(m && { nim: m.nim, prodi: m.prodi, angkatan: m.angkatan }),
    ...(d && { nip: d.nip }),
    roles: (u.user_roles || []).map((r) => r.roles).sort((a, b) => a.id - b.id),
    created_at: u.created_at,
    updated_at: u.updated_at,
  };
}

async function getById(id) {
  const user = unwrap(await supabase.from('users').select(USER_SELECT).eq('id', id).maybeSingle());
  if (!user) throw new AppError(404, 'User tidak ditemukan');
  return format(user);
}

async function list({ page, limit, user_type, role, search, is_active }) {
  let roleUserIds = null;
  if (role) {
    const r = unwrap(await supabase.from('roles').select('id').eq('code', role).maybeSingle());
    if (r) {
      const rows = unwrap(await supabase.from('user_roles').select('user_id').eq('role_id', r.id));
      roleUserIds = rows.map((x) => x.user_id);
    }
    if (!roleUserIds || !roleUserIds.length) return { rows: [], total: 0 };
  }

  let q = supabase.from('users').select(USER_SELECT, { count: 'exact' });
  if (user_type) q = q.eq('user_type', user_type);
  if (is_active !== undefined) q = q.eq('is_active', is_active);
  if (roleUserIds) q = q.in('id', roleUserIds);

  const s = (search || '').replace(/[%,()*\\]/g, ' ').trim();
  if (s) {
    const [mhs, dsn] = await Promise.all([
      supabase.from('mahasiswa_profiles').select('user_id').ilike('nim', `%${s}%`),
      supabase.from('dosen_profiles').select('user_id').ilike('nip', `%${s}%`),
    ]);
    const extra = [...unwrap(mhs), ...unwrap(dsn)].map((x) => x.user_id);
    const conds = [`nama.ilike.%${s}%`, `email.ilike.%${s}%`];
    if (extra.length) conds.push(`id.in.(${extra.join(',')})`);
    q = q.or(conds.join(','));
  }

  const from = (page - 1) * limit;
  const { data, error, count } = await q.order('nama').range(from, from + limit - 1);
  if (error) unwrap({ error });
  return { rows: data.map(format), total: count };
}

async function resolveRoleIds(userType, roleIds = []) {
  const ids = [...new Set(roleIds)];
  const roles = unwrap(await supabase.from('roles').select('id,code,name,allowed_user_types'));
  const byId = new Map(roles.map((r) => [r.id, r]));

  for (const id of ids) {
    const r = byId.get(id);
    if (!r) throw new AppError(400, `Role dengan id ${id} tidak ditemukan`);
    if (!r.allowed_user_types.includes(userType)) {
      throw new AppError(422, `Role "${r.name}" tidak dapat diberikan ke akun ${userType}`);
    }
  }
  const base = roles.find((r) => r.code === BASE_ROLE[userType]);
  if (base && !ids.includes(base.id)) ids.push(base.id);
  return ids;
}

async function applyRoles(userId, roleIds, assignedBy) {
  unwrap(
    await supabase.rpc('set_user_roles', {
      p_user_id: userId,
      p_role_ids: roleIds,
      p_assigned_by: assignedBy || null,
    })
  );
}

async function create(data, actorId) {
  const { password, role_ids, nim, prodi, angkatan, nip, ...account } = data;
  const roleIds = await resolveRoleIds(account.user_type, role_ids);
  const password_hash = await bcrypt.hash(password, 10);

  const { id } = unwrap(
    await supabase.from('users').insert({ ...account, password_hash }).select('id').single()
  );
  try {
    if (account.user_type === 'mahasiswa') {
      unwrap(await supabase.from('mahasiswa_profiles').insert(defined({ user_id: id, nim, prodi, angkatan })));
    } else if (account.user_type === 'dosen') {
      unwrap(await supabase.from('dosen_profiles').insert({ user_id: id, nip }));
    }
    await applyRoles(id, roleIds, actorId);
  } catch (err) {
    await supabase.from('users').delete().eq('id', id); // rollback manual
    throw err;
  }
  return getById(id);
}

async function update(id, data, actorId) {
  const user = await getById(id);
  const { password, nim, prodi, angkatan, nip, ...account } = data;
  const mhsFields = defined({ nim, prodi, angkatan });

  if (user.user_type !== 'mahasiswa' && Object.keys(mhsFields).length) {
    throw new AppError(400, 'NIM/prodi/angkatan hanya untuk akun mahasiswa');
  }
  if (user.user_type !== 'dosen' && nip !== undefined) {
    throw new AppError(400, 'NIP hanya untuk akun dosen');
  }
  if (id === actorId && account.is_active === false) {
    throw new AppError(400, 'Tidak dapat menonaktifkan akun sendiri');
  }

  const patch = { ...account };
  if (password) patch.password_hash = await bcrypt.hash(password, 10);
  if (Object.keys(patch).length) unwrap(await supabase.from('users').update(patch).eq('id', id));
  if (Object.keys(mhsFields).length) {
    unwrap(await supabase.from('mahasiswa_profiles').update(mhsFields).eq('user_id', id));
  }
  if (nip !== undefined) unwrap(await supabase.from('dosen_profiles').update({ nip }).eq('user_id', id));
  return getById(id);
}

// Edit profil sendiri: nama/alamat/password saja (bukan email, role, status, NIM/NIP)
async function updateOwnProfile(id, data) {
  const patch = defined({ nama: data.nama, alamat: data.alamat });
  if (data.password) patch.password_hash = await bcrypt.hash(data.password, 10);
  if (Object.keys(patch).length) unwrap(await supabase.from('users').update(patch).eq('id', id));
  return getById(id);
}

async function remove(id, actorId) {
  if (id === actorId) throw new AppError(400, 'Tidak dapat menghapus akun sendiri');
  await getById(id);
  unwrap(await supabase.from('users').delete().eq('id', id)); // profil & role ikut terhapus (CASCADE)
}

// Ganti seluruh role (cocok untuk form checkbox)
async function setRoles(id, roleIds, actorId) {
  const user = await getById(id);
  const finalIds = await resolveRoleIds(user.user_type, roleIds);
  const adminRole = user.roles.find((r) => r.code === 'admin');
  if (id === actorId && adminRole && !finalIds.includes(adminRole.id)) {
    throw new AppError(400, 'Tidak dapat mencabut role admin dari akun sendiri');
  }
  await applyRoles(id, finalIds, actorId);
  return getById(id);
}

async function addRoles(id, roleIds, actorId) {
  const user = await getById(id);
  return setRoles(id, [...user.roles.map((r) => r.id), ...roleIds], actorId);
}

async function removeRole(id, roleId, actorId) {
  const user = await getById(id);
  const role = user.roles.find((r) => r.id === roleId);
  if (!role) throw new AppError(404, 'User tidak memiliki role tersebut');
  if (BASE_ROLE[user.user_type] === role.code) {
    throw new AppError(422, `Role "${role.name}" adalah role dasar akun ${user.user_type} dan tidak bisa dicabut`);
  }
  return setRoles(id, user.roles.filter((r) => r.id !== roleId).map((r) => r.id), actorId);
}

module.exports = {
  getById,
  list,
  create,
  update,
  updateOwnProfile,
  remove,
  setRoles,
  addRoles,
  removeRole,
};
