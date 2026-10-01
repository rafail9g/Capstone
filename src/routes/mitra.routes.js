const router = require('express').Router();
const { authenticate, requireRole } = require('../middleware/auth');
const validate = require('../middleware/validate');
const { uploadFotoMiddleware } = require('../middleware/upload');
const { asyncHandler } = require('../utils/errors');
const mitra = require('../services/mitraService');
const schemas = require('../validators/mitraSchemas');

router.use(authenticate, requireRole('admin'));

router.get(
  '/',
  validate(schemas.listMitra, 'query'),
  asyncHandler(async (req, res) => {
    const result = await mitra.list(req.query);
    res.json({ success: true, data: result });
  })
);

router.get(
  '/:id',
  validate(schemas.mitraIdParam, 'params'),
  asyncHandler(async (req, res) => {
    const row = await mitra.getById(req.params.id);
    res.json({ success: true, data: row });
  })
);

router.post(
  '/',
  uploadFotoMiddleware,
  validate(schemas.createMitra, 'body'),
  asyncHandler(async (req, res) => {
    let fotoUrl = null;
    if (req.file) {
      fotoUrl = await mitra.uploadFoto(
        req.file.buffer,
        req.file.originalname,
        req.file.mimetype
      );
    }
    const row = await mitra.create(req.body, fotoUrl);
    res.status(201).json({ success: true, message: 'Mitra berhasil ditambahkan', data: row });
  })
);

router.put(
  '/:id',
  validate(schemas.mitraIdParam, 'params'),
  uploadFotoMiddleware,
  validate(schemas.updateMitra, 'body'),
  asyncHandler(async (req, res) => {
    let fotoUrl;
    if (req.file) {
      const existing = await mitra.getById(req.params.id);
      fotoUrl = await mitra.replaceFoto(
        existing.foto_url,
        req.file.buffer,
        req.file.originalname,
        req.file.mimetype
      );
    }
    const row = await mitra.update(req.params.id, req.body, fotoUrl);
    res.json({ success: true, message: 'Mitra berhasil diperbarui', data: row });
  })
);

router.delete(
  '/:id',
  validate(schemas.mitraIdParam, 'params'),
  asyncHandler(async (req, res) => {
    await mitra.remove(req.params.id);
    res.json({ success: true, message: 'Mitra berhasil dihapus' });
  })
);

module.exports = router;
