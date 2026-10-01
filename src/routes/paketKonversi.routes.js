const router = require('express').Router();
const { authenticate, requireRole } = require('../middleware/auth');
const validate = require('../middleware/validate');
const { asyncHandler } = require('../utils/errors');
const paketService = require('../services/paketKonversiService');
const schemas = require('../validators/paketKonversiSchemas');

router.use(authenticate, requireRole('admin', 'tim_mbkm'));

router.get(
  '/',
  validate(schemas.listPaketKonversi, 'query'),
  asyncHandler(async (req, res) => {
    res.json({ success: true, data: await paketService.list(req.query) });
  })
);

router.get(
  '/:id',
  validate(schemas.paketKonversiIdParam, 'params'),
  asyncHandler(async (req, res) => {
    res.json({ success: true, data: await paketService.getById(req.params.id) });
  })
);

router.post(
  '/',
  validate(schemas.createPaketKonversi, 'body'),
  asyncHandler(async (req, res) => {
    const row = await paketService.create(req.body);
    res.status(201).json({ success: true, message: 'Paket konversi berhasil ditambahkan', data: row });
  })
);

router.put(
  '/:id',
  validate(schemas.paketKonversiIdParam, 'params'),
  validate(schemas.updatePaketKonversi, 'body'),
  asyncHandler(async (req, res) => {
    const row = await paketService.update(req.params.id, req.body);
    res.json({ success: true, message: 'Paket konversi berhasil diperbarui', data: row });
  })
);

router.delete(
  '/:id',
  validate(schemas.paketKonversiIdParam, 'params'),
  asyncHandler(async (req, res) => {
    await paketService.remove(req.params.id);
    res.json({ success: true, message: 'Paket konversi berhasil dihapus' });
  })
);

module.exports = router;
