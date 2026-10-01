const supabase = require('../config/supabase');
const { AppError, unwrap } = require('../utils/errors');

async function list() {
  return unwrap(await supabase.from('program_studi').select('id,kode,nama').order('id'));
}

async function mustExist(id) {
  const row = unwrap(await supabase.from('program_studi').select('id').eq('id', id).maybeSingle());
  if (!row) throw new AppError(400, 'Program studi tidak ditemukan');
}

module.exports = { list, mustExist };
