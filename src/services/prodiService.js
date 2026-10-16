const supabase = require('../config/supabase');
const { AppError, unwrap } = require('../utils/errors');

async function list() {
  return unwrap(await supabase.from('program_studi').select('id,kode,nama').order('id'));
}

async function mustExist(id) {
  if (!id) throw new AppError(400, 'Program studi wajib dipilih terlebih dahulu');
  const row = unwrap(await supabase.from('program_studi').select('id,kode,nama').eq('id', id).maybeSingle());
  if (!row) throw new AppError(404, 'Program studi yang dipilih tidak ditemukan');
  return row;
}

module.exports = { list, mustExist };
