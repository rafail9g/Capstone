const { AppError } = require('../utils/errors');

module.exports = (schema, source = 'body') => (req, res, next) => {
  const result = schema.safeParse(req[source]);
  if (!result.success) {
    const details = result.error.issues.map((i) => ({
      field: i.path.join('.'),
      message: i.message,
    }));
    return next(new AppError(400, 'Validasi gagal', details));
  }
  req[source] = result.data;
  next();
};
