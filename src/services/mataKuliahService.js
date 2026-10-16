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

async function assertCpl(cplId, programStudiId) {
  if (!programStudiId) {
    throw new AppError(400, 'Program studi wajib dipilih terlebih dahulu sebelum memilih CPL');
  }
  if (!cplId) {
    throw new AppError(400, 'CPL wajib dipilih terlebih dahulu dari program studi yang dipilih');
  }
  const cpl = unwrap(
    await supabase.from('cpl').select('id,kode,program_studi_id').eq('id', cplId).maybeSingle()
  );
  if (!cpl) throw new AppError(404, 'CPL yang dipilih tidak ditemukan');
  if (cpl.program_studi_id !== programStudiId) {
    throw new AppError(
      422,
      'CPL yang dipilih tidak sesuai dengan program studi mata kuliah. Silakan pilih CPL dari program studi yang sama.'
    );
  }
}

async function create(data) {
  if (!data.program_studi_id) {
    throw new AppError(400, 'Program studi wajib dipilih terlebih dahulu sebelum mengisi data mata kuliah');
  }
  await prodi.mustExist(data.program_studi_id);

  if (!data.cpl_id) {
    throw new AppError(400, 'CPL wajib dipilih terlebih dahulu dari program studi yang dipilih');
  }
  await assertCpl(data.cpl_id, data.program_studi_id);

  if (!Array.isArray(data.cpmk) || data.cpmk.length === 0) {
    throw new AppError(400, 'Setiap CPL yang dipilih wajib diisi minimal 1 CPMK terlebih dahulu');
  }

  const cleanCpmk = data.cpmk.map((s) => (typeof s === 'string' ? s.trim() : '')).filter(Boolean);
  if (cleanCpmk.length === 0) {
    throw new AppError(400, 'Deskripsi CPMK tidak boleh kosong');
  }
  const uniqueCpmk = new Set(cleanCpmk.map((s) => s.toLowerCase()));
  if (uniqueCpmk.size !== cleanCpmk.length) {
    throw new AppError(400, 'Terdapat deskripsi CPMK yang duplikat, setiap CPMK harus unik');
  }

  const existingMk = unwrap(
    await supabase
      .from('mata_kuliah')
      .select('id')
      .eq('program_studi_id', data.program_studi_id)
      .eq('kode', data.kode)
      .maybeSingle()
  );
  if (existingMk) {
    throw new AppError(409, `Kode mata kuliah "${data.kode}" sudah terdaftar pada program studi ini`);
  }

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

  const { error } = await supabase.rpc('set_cpmk', {
    p_mata_kuliah_id: inserted.id,
    p_deskripsi: cleanCpmk,
  });
  if (error) {
    await supabase.from('mata_kuliah').delete().eq('id', inserted.id); // batalkan
    unwrap({ error });
  }

  return getById(inserted.id);
}

async function update(id, data) {
  const existing = await getById(id);

  const prodiId = data.program_studi_id !== undefined ? data.program_studi_id : existing.program_studi_id;
  const cplId = data.cpl_id !== undefined ? data.cpl_id : existing.cpl_id;

  if (data.program_studi_id !== undefined) await prodi.mustExist(prodiId);
  if (data.program_studi_id !== undefined || data.cpl_id !== undefined) {
    await assertCpl(cplId, prodiId);
  }

  if (
    data.kode &&
    (data.kode !== existing.kode || (data.program_studi_id && data.program_studi_id !== existing.program_studi_id))
  ) {
    const duplicate = unwrap(
      await supabase
        .from('mata_kuliah')
        .select('id')
        .eq('program_studi_id', prodiId)
        .eq('kode', data.kode)
        .neq('id', id)
        .maybeSingle()
    );
    if (duplicate) {
      throw new AppError(409, `Kode mata kuliah "${data.kode}" sudah digunakan pada program studi ini`);
    }
  }

  let cleanCpmk;
  if (data.cpmk !== undefined) {
    if (!Array.isArray(data.cpmk) || data.cpmk.length === 0) {
      throw new AppError(400, 'Setiap CPL yang dipilih wajib diisi minimal 1 CPMK terlebih dahulu');
    }
    cleanCpmk = data.cpmk.map((s) => (typeof s === 'string' ? s.trim() : '')).filter(Boolean);
    if (cleanCpmk.length === 0) {
      throw new AppError(400, 'Deskripsi CPMK tidak boleh kosong');
    }
    const uniqueCpmk = new Set(cleanCpmk.map((s) => s.toLowerCase()));
    if (uniqueCpmk.size !== cleanCpmk.length) {
      throw new AppError(400, 'Terdapat deskripsi CPMK yang duplikat, setiap CPMK harus unik');
    }
  }

  const patch = Object.fromEntries(
    Object.entries({
      program_studi_id: data.program_studi_id,
      kode: data.kode,
      nama: data.nama,
      cpl_id: data.cpl_id,
      sks: data.sks,
    }).filter(([, v]) => v !== undefined)
  );

  if (Object.keys(patch).length) {
    unwrap(await supabase.from('mata_kuliah').update(patch).eq('id', id));
  }

  if (cleanCpmk !== undefined) {
    unwrap(await supabase.rpc('set_cpmk', { p_mata_kuliah_id: id, p_deskripsi: cleanCpmk }));
  }

  return getById(id);
}

async function remove(id) {
  const existing = await getById(id);
  const { data: usedInPaket } = await supabase
    .from('paket_konversi_item')
    .select('paket_konversi(id,nama)')
    .eq('mata_kuliah_id', id)
    .limit(1);

  if (usedInPaket && usedInPaket.length > 0 && usedInPaket[0].paket_konversi) {
    throw new AppError(
      409,
      `Mata kuliah "${existing.nama}" tidak dapat dihapus karena masih digunakan pada paket konversi "${usedInPaket[0].paket_konversi.nama}"`
    );
  }

  unwrap(await supabase.from('mata_kuliah').delete().eq('id', id));
}

module.exports = { getById, list, create, update, remove };
