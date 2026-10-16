const { AppError } = require('../utils/errors');

module.exports = (schema, source = 'body') => (req, res, next) => {
  const result = schema.safeParse(req[source]);
  if (!result.success) {
    const details = result.error.issues.map((i) => ({
      field: i.path.join('.'),
      message: i.message,
    }));
    const firstMessage = result.error.issues[0]?.message;
    const message = firstMessage ? `Validasi gagal: ${firstMessage}` : 'Validasi gagal';
    return next(new AppError(400, message, details));
  }
  req[source] = result.data;
  next();
};
