// Bersihkan karakter yang punya arti khusus di filter PostgREST (.or / ilike)
const sanitizeSearch = (search) => (search || '').replace(/[%,()*\\]/g, ' ').trim();

module.exports = { sanitizeSearch };
