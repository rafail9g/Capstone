const supabase = require('../config/supabase');
const { AppError, unwrap } = require('../utils/errors');
const { sanitizeSearch } = require('../utils/search');

const PAKET_SELECT =
  'id,nama,deskripsi,created_at,updated_at,' +
  'paket_konversi_item(mata_kuliah(id,kode,nama,sks,program_studi(id,kode,nama),cpl(id,kode,deskripsi),cpmk(id,urutan,deskripsi)))';

const formatPaket = (row) => {
  if (!row) return row;
  const items = row.paket_konversi_item || [];
  const mataKuliah = items
    .map((item) => item.mata_kuliah)
    .filter(Boolean)
    .map((mk) => {
      if (mk.cpmk) {
        mk.cpmk = [...mk.cpmk].sort((a, b) => a.urutan - b.urutan);
      }
      return mk;
    });

  const totalSks = mataKuliah.reduce((sum, mk) => sum + (mk.sks || 0), 0);

  delete row.paket_konversi_item;
  return {
    ...row,
    total_sks: totalSks,
    jumlah_mata_kuliah: mataKuliah.length,
    mata_kuliah: mataKuliah,
  };
};

async function assertMataKuliahExist(mataKuliahIds) {
  if (!mataKuliahIds || !mataKuliahIds.length) return;
  const { data, error } = await supabase
    .from('mata_kuliah')
    .select('id')
    .in('id', mataKuliahIds);

  if (error) unwrap({ error });
  const foundIds = new Set((data || []).map((m) => m.id));
  const missing = mataKuliahIds.filter((id) => !foundIds.has(id));
  if (missing.length > 0) {
    throw new AppError(400, `Mata kuliah dengan ID ${missing.join(', ')} tidak ditemukan`);
  }
}

async function getById(id) {
  const row = unwrap(
    await supabase.from('paket_konversi').select(PAKET_SELECT).eq('id', id).maybeSingle()
  );
  if (!row) throw new AppError(404, 'Paket konversi tidak ditemukan');
  return formatPaket(row);
}

async function list({ page = 1, limit = 10, search } = {}) {
  let q = supabase.from('paket_konversi').select(PAKET_SELECT, { count: 'exact' });

  const s = sanitizeSearch(search);
  if (s) {
    q = q.or('nama.ilike.%' + s + '%,deskripsi.ilike.%' + s + '%');
  }

  const from = (page - 1) * limit;
  const { data, error, count } = await q.order('created_at', { ascending: false }).range(from, from + limit - 1);
  if (error) unwrap({ error });

  return {
    rows: (data || []).map(formatPaket),
    total: count,
  };
}

async function create(data) {
  if (data.mata_kuliah_ids && data.mata_kuliah_ids.length > 0) {
    await assertMataKuliahExist(data.mata_kuliah_ids);
  }

  const inserted = unwrap(
    await supabase
      .from('paket_konversi')
      .insert({
        nama: data.nama,
        deskripsi: data.deskripsi || null,
      })
      .select('id')
      .single()
  );

  if (data.mata_kuliah_ids && data.mata_kuliah_ids.length > 0) {
    const { error } = await supabase.rpc('set_paket_konversi_items', {
      p_paket_id: inserted.id,
      p_mata_kuliah_ids: data.mata_kuliah_ids,
    });
    if (error) {
      await supabase.from('paket_konversi').delete().eq('id', inserted.id); // rollback
      unwrap({ error });
    }
  }

  return getById(inserted.id);
}

async function update(id, data) {
  await getById(id);

  if (data.mata_kuliah_ids !== undefined) {
    await assertMataKuliahExist(data.mata_kuliah_ids);
  }

  const patch = Object.fromEntries(
    Object.entries({
      nama: data.nama,
      deskripsi: data.deskripsi,
    }).filter(([, v]) => v !== undefined)
  );

  if (Object.keys(patch).length > 0) {
    unwrap(await supabase.from('paket_konversi').update(patch).eq('id', id));
  }

  if (data.mata_kuliah_ids !== undefined) {
    unwrap(
      await supabase.rpc('set_paket_konversi_items', {
        p_paket_id: id,
        p_mata_kuliah_ids: data.mata_kuliah_ids,
      })
    );
  }

  return getById(id);
}

async function remove(id) {
  await getById(id);
  unwrap(await supabase.from('paket_konversi').delete().eq('id', id));
}

module.exports = {
  getById,
  list,
  create,
  update,
  remove,
};
