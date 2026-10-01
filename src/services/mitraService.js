const supabase = require('../config/supabase');
const { AppError, unwrap } = require('../utils/errors');
const { sanitizeSearch } = require('../utils/search');

const MITRA_SELECT = 'id,nama,alamat,email,deskripsi,foto_url,is_active,created_at,updated_at';

const defined = (obj) =>
  Object.fromEntries(Object.entries(obj).filter(([, v]) => v !== undefined));

async function getById(id) {
  const row = unwrap(
    await supabase.from('mitra').select(MITRA_SELECT).eq('id', id).maybeSingle()
  );
  if (!row) throw new AppError(404, 'Mitra tidak ditemukan');
  return row;
}

async function list({ page = 1, limit = 10, search, is_active } = {}) {
  let q = supabase.from('mitra').select(MITRA_SELECT, { count: 'exact' });

  if (is_active !== undefined) q = q.eq('is_active', is_active);

  const s = sanitizeSearch(search);
  if (s) {
    q = q.or('nama.ilike.%' + s + '%,email.ilike.%' + s + '%,alamat.ilike.%' + s + '%');
  }

  const from = (page - 1) * limit;
  const { data, error, count } = await q.order('nama').range(from, from + limit - 1);
  if (error) unwrap({ error });
  return { rows: data, total: count };
}

async function create(data, fotoUrl = null) {
  const payload = defined({
    nama: data.nama,
    alamat: data.alamat || null,
    email: data.email || null,
    deskripsi: data.deskripsi || null,
    foto_url: fotoUrl,
  });

  const row = unwrap(
    await supabase.from('mitra').insert(payload).select(MITRA_SELECT).single()
  );
  return row;
}

async function update(id, data, fotoUrl) {
  await getById(id);

  const patch = defined({
    nama: data.nama,
    alamat: data.alamat !== undefined ? (data.alamat || null) : undefined,
    email: data.email !== undefined ? (data.email || null) : undefined,
    deskripsi: data.deskripsi !== undefined ? (data.deskripsi || null) : undefined,
    is_active: data.is_active,
    ...(fotoUrl !== undefined && { foto_url: fotoUrl }),
  });

  if (!Object.keys(patch).length)
    throw new AppError(400, 'Minimal satu field harus diisi');

  const row = unwrap(
    await supabase.from('mitra').update(patch).eq('id', id).select(MITRA_SELECT).single()
  );
  return row;
}

async function remove(id) {
  const m = await getById(id);

  if (m.foto_url) {
    const path = m.foto_url.split('/mitra-foto/').pop();
    if (path) await supabase.storage.from('mitra-foto').remove([path]);
  }

  unwrap(await supabase.from('mitra').delete().eq('id', id));
}

async function uploadFoto(buffer, originalname, mimetype) {
  const ext = originalname.split('.').pop().toLowerCase();
  const filename = Date.now() + '-' + Math.random().toString(36).slice(2) + '.' + ext;

  const { error } = await supabase.storage
    .from('mitra-foto')
    .upload(filename, buffer, { contentType: mimetype, upsert: false });

  if (error) throw new AppError(500, 'Gagal upload foto: ' + error.message);

  const { data } = supabase.storage.from('mitra-foto').getPublicUrl(filename);
  return data.publicUrl;
}

async function replaceFoto(oldUrl, buffer, originalname, mimetype) {
  if (oldUrl) {
    const oldPath = oldUrl.split('/mitra-foto/').pop();
    if (oldPath) await supabase.storage.from('mitra-foto').remove([oldPath]);
  }
  return uploadFoto(buffer, originalname, mimetype);
}

module.exports = { getById, list, create, update, remove, uploadFoto, replaceFoto };
