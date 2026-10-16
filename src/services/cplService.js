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
  if (!data.program_studi_id) {
    throw new AppError(400, 'Program studi wajib dipilih terlebih dahulu sebelum mengisi CPL');
  }
  await prodi.mustExist(data.program_studi_id);

  const existingCpl = unwrap(
    await supabase
      .from('cpl')
      .select('id')
      .eq('program_studi_id', data.program_studi_id)
      .eq('kode', data.kode)
      .maybeSingle()
  );
  if (existingCpl) {
    throw new AppError(409, `Kode CPL "${data.kode}" sudah terdaftar pada program studi ini`);
  }

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
  const existing = await getById(id);
  const prodiId = data.program_studi_id !== undefined ? data.program_studi_id : existing.program_studi_id;

  if (data.program_studi_id !== undefined) await prodi.mustExist(prodiId);

  if (
    data.kode &&
    (data.kode !== existing.kode || (data.program_studi_id && data.program_studi_id !== existing.program_studi_id))
  ) {
    const duplicate = unwrap(
      await supabase
        .from('cpl')
        .select('id')
        .eq('program_studi_id', prodiId)
        .eq('kode', data.kode)
        .neq('id', id)
        .maybeSingle()
    );
    if (duplicate) {
      throw new AppError(409, `Kode CPL "${data.kode}" sudah digunakan pada program studi ini`);
    }
  }

  const patch = Object.fromEntries(
    Object.entries({
      kode: data.kode,
      program_studi_id: data.program_studi_id,
      deskripsi: data.deskripsi,
    }).filter(([, v]) => v !== undefined)
  );

  if (Object.keys(patch).length) {
    unwrap(await supabase.from('cpl').update(patch).eq('id', id));
  }
  return getById(id);
}

async function remove(id) {
  const existing = await getById(id);
  const { data: usedByMk } = await supabase
    .from('mata_kuliah')
    .select('id,kode,nama')
    .eq('cpl_id', id)
    .limit(1);

  if (usedByMk && usedByMk.length > 0) {
    throw new AppError(
      409,
      `CPL "${existing.kode}" tidak dapat dihapus karena masih digunakan oleh mata kuliah "${usedByMk[0].nama}" (${usedByMk[0].kode})`
    );
  }

  unwrap(await supabase.from('cpl').delete().eq('id', id));
}

module.exports = { getById, list, create, update, remove };
