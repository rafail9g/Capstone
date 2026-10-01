const notFound = (req, res) =>
  res.status(404).json({ success: false, message: 'Endpoint tidak ditemukan' });

const errorHandler = (err, req, res, next) => {
  if (err.type === 'entity.parse.failed') {
    return res.status(400).json({ success: false, message: 'JSON tidak valid' });
  }
  if (err.status) {
    return res
      .status(err.status)
      .json({ success: false, message: err.message, ...(err.details && { errors: err.details }) });
  }
  console.error(err);
  res.status(500).json({ success: false, message: 'Terjadi kesalahan pada server' });
};

module.exports = { notFound, errorHandler };
