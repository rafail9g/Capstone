const router = require('express').Router();
const { authenticate, requireRole } = require('../middleware/auth');
const validate = require('../middleware/validate');
const { asyncHandler } = require('../utils/errors');
const mk = require('../services/mataKuliahService');
const schemas = require('../validators/akademikSchemas');

router.use(authenticate, requireRole('admin'));

router.get(
  '/',
  validate(schemas.listMataKuliah, 'query'),
  asyncHandler(async (req, res) => {
    res.json({ success: true, data: await mk.list(req.query) });
  })
);

router.get(
  '/:id',
  validate(schemas.mataKuliahIdParam, 'params'),
  asyncHandler(async (req, res) => {
    res.json({ success: true, data: await mk.getById(req.params.id) });
  })
);

router.post(
  '/',
  validate(schemas.createMataKuliah, 'body'),
  asyncHandler(async (req, res) => {
    const row = await mk.create(req.body);
    res.status(201).json({ success: true, message: 'Mata kuliah berhasil ditambahkan', data: row });
  })
);

router.put(
  '/:id',
  validate(schemas.mataKuliahIdParam, 'params'),
  validate(schemas.updateMataKuliah, 'body'),
  asyncHandler(async (req, res) => {
    const row = await mk.update(req.params.id, req.body);
    res.json({ success: true, message: 'Mata kuliah berhasil diperbarui', data: row });
  })
);

router.delete(
  '/:id',
  validate(schemas.mataKuliahIdParam, 'params'),
  asyncHandler(async (req, res) => {
    await mk.remove(req.params.id);
    res.json({ success: true, message: 'Mata kuliah berhasil dihapus' });
  })
);

module.exports = router;
