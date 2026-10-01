const router = require('express').Router();
const { authenticate, requireRole } = require('../middleware/auth');
const validate = require('../middleware/validate');
const { asyncHandler } = require('../utils/errors');
const cpl = require('../services/cplService');
const schemas = require('../validators/akademikSchemas');

router.use(authenticate, requireRole('admin'));

router.get(
  '/',
  validate(schemas.listCpl, 'query'),
  asyncHandler(async (req, res) => {
    res.json({ success: true, data: await cpl.list(req.query) });
  })
);

router.get(
  '/:id',
  validate(schemas.cplIdParam, 'params'),
  asyncHandler(async (req, res) => {
    res.json({ success: true, data: await cpl.getById(req.params.id) });
  })
);

router.post(
  '/',
  validate(schemas.createCpl, 'body'),
  asyncHandler(async (req, res) => {
    const row = await cpl.create(req.body);
    res.status(201).json({ success: true, message: 'CPL berhasil ditambahkan', data: row });
  })
);

router.put(
  '/:id',
  validate(schemas.cplIdParam, 'params'),
  validate(schemas.updateCpl, 'body'),
  asyncHandler(async (req, res) => {
    const row = await cpl.update(req.params.id, req.body);
    res.json({ success: true, message: 'CPL berhasil diperbarui', data: row });
  })
);

router.delete(
  '/:id',
  validate(schemas.cplIdParam, 'params'),
  asyncHandler(async (req, res) => {
    await cpl.remove(req.params.id);
    res.json({ success: true, message: 'CPL berhasil dihapus' });
  })
);

module.exports = router;
