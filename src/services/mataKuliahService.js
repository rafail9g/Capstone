const supabase = require('../config/supabase');
const { AppError, unwrap } = require('../utils/errors');
const { sanitizeSearch } = require('../utils/search');
const prodi = require('./prodiService');

const MK_SELECT =
  'id,kode,nama,sks,program_studi_id,cpl_id,created_at,updated_at,' +
  'program_studi(id,kode,nama),cpl(id,kode,deskripsi),cpmk(id,urutan,deskripsi)';

const sortCpmk = (row) => {
  row.cpmk = (row.cpmk || []).sort((a, b) => a.urutan - b.urutan);
  return row;
};

async function getById(id) {
  const row = unwrap(await supabase.from('mata_kuliah').select(MK_SELECT).eq('id', id).maybeSingle());
  if (!row) throw new AppError(404, 'Mata kuliah tidak ditemukan');
  return sortCpmk(row);
}

async function list({ page = 1, limit = 10, search, program_studi_id } = {}) {
  let q = supabase.from('mata_kuliah').select(MK_SELECT, { count: 'exact' });

  if (program_studi_id !== undefined) q = q.eq('program_studi_id', program_studi_id);

  const s = sanitizeSearch(search);
  if (s) q = q.or('kode.ilike.%' + s + '%,nama.ilike.%' + s + '%');

  const from = (page - 1) * limit;
  const { data, error, count } = await q.order('kode').range(from, from + limit - 1);
  if (error) unwrap({ error });
  return { rows: data.map(sortCpmk), total: count };
}

// CPL harus ada dan milik program studi yang sama
async function assertCpl(cplId, programStudiId) {
  const cpl = unwrap(
    await supabase.from('cpl').select('id,program_studi_id').eq('id', cplId).maybeSingle()
  );
  if (!cpl) throw new AppError(400, 'CPL tidak ditemukan');
  if (cpl.program_studi_id !== programStudiId)
    throw new AppError(400, 'CPL tidak sesuai dengan program studi yang dipilih');
}

async function create(data) {
  await prodi.mustExist(data.program_studi_id);
  await assertCpl(data.cpl_id, data.program_studi_id);

  const inserted = unwrap(
    await supabase
      .from('mata_kuliah')
      .insert({
        program_studi_id: data.program_studi_id,
        kode: data.kode,
        nama: data.nama,
        cpl_id: data.cpl_id,
        sks: data.sks,
      })
      .select('id')
      .single()
  );

  if (data.cpmk.length) {
    const { error } = await supabase.rpc('set_cpmk', {
      p_mata_kuliah_id: inserted.id,
      p_deskripsi: data.cpmk,
    });
    if (error) {
      await supabase.from('mata_kuliah').delete().eq('id', inserted.id); // batalkan
      unwrap({ error });
    }
  }
  return getById(inserted.id);
}

async function update(id, data) {
  const existing = await getById(id);

  // Kalau prodi diganti, CPL harus ikut dipilih ulang (sesuai perilaku dropdown di form)
  const prodiId = data.program_studi_id ?? existing.program_studi_id;
  const cplId = data.cpl_id ?? existing.cpl_id;
  if (data.program_studi_id !== undefined) await prodi.mustExist(prodiId);
  if (data.program_studi_id !== undefined || data.cpl_id !== undefined)
    await assertCpl(cplId, prodiId);

  const patch = Object.fromEntries(
    Object.entries({
      program_studi_id: data.program_studi_id,
      kode: data.kode,
      nama: data.nama,
      cpl_id: data.cpl_id,
      sks: data.sks,
    }).filter(([, v]) => v !== undefined)
  );

  if (Object.keys(patch).length)
    unwrap(await supabase.from('mata_kuliah').update(patch).eq('id', id));

  // cpmk dikirim = ganti semua; tidak dikirim = biarkan
  if (data.cpmk !== undefined)
    unwrap(await supabase.rpc('set_cpmk', { p_mata_kuliah_id: id, p_deskripsi: data.cpmk }));

  return getById(id);
}

async function remove(id) {
  await getById(id);
  unwrap(await supabase.from('mata_kuliah').delete().eq('id', id)); // CPMK ikut terhapus
}

module.exports = { getById, list, create, update, remove };
