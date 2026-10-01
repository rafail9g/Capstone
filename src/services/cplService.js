const supabase = require('../config/supabase');
const { AppError, unwrap } = require('../utils/errors');
const { sanitizeSearch } = require('../utils/search');
const prodi = require('./prodiService');

const CPL_SELECT =
  'id,kode,deskripsi,program_studi_id,created_at,updated_at,program_studi(id,kode,nama)';

async function getById(id) {
  const row = unwrap(await supabase.from('cpl').select(CPL_SELECT).eq('id', id).maybeSingle());
  if (!row) throw new AppError(404, 'CPL tidak ditemukan');
  return row;
}

// Dipakai form mata kuliah: dropdown CPL diisi lewat ?program_studi_id=...
async function list({ page = 1, limit = 10, search, program_studi_id } = {}) {
  let q = supabase.from('cpl').select(CPL_SELECT, { count: 'exact' });

  if (program_studi_id !== undefined) q = q.eq('program_studi_id', program_studi_id);

  const s = sanitizeSearch(search);
  if (s) q = q.or('kode.ilike.%' + s + '%,deskripsi.ilike.%' + s + '%');

  const from = (page - 1) * limit;
  const { data, error, count } = await q.order('kode').range(from, from + limit - 1);
  if (error) unwrap({ error });
  return { rows: data, total: count };
}

async function create(data) {
  await prodi.mustExist(data.program_studi_id);
  const inserted = unwrap(
    await supabase
      .from('cpl')
      .insert({
        kode: data.kode,
        program_studi_id: data.program_studi_id,
        deskripsi: data.deskripsi,
      })
      .select('id')
      .single()
  );
  return getById(inserted.id);
}

async function update(id, data) {
  await getById(id);
  if (data.program_studi_id !== undefined) await prodi.mustExist(data.program_studi_id);

  const patch = Object.fromEntries(
    Object.entries({
      kode: data.kode,
      program_studi_id: data.program_studi_id,
      deskripsi: data.deskripsi,
    }).filter(([, v]) => v !== undefined)
  );

  unwrap(await supabase.from('cpl').update(patch).eq('id', id));
  return getById(id);
}

async function remove(id) {
  await getById(id);
  // Kalau masih dipakai mata kuliah, DB menolak (FK restrict) -> 409
  unwrap(await supabase.from('cpl').delete().eq('id', id));
}

module.exports = { getById, list, create, update, remove };
