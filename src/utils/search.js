const sanitizeSearch = (search) => (search || '').replace(/[%,()*\\]/g, ' ').trim();

module.exports = { sanitizeSearch };
