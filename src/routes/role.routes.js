const router = require('express').Router();
const roles = require('../services/roleService');
const validate = require('../middleware/validate');
const { authenticate, requireRole } = require('../middleware/auth');
const { asyncHandler } = require('../utils/errors');
const s = require('../validators/schemas');

router.use(authenticate);

router.get(
  '/',
  validate(s.listRoles, 'query'),
  asyncHandler(async (req, res) => {
    res.json({ success: true, data: await roles.list(req.query) });
  })
);

router.get(
  '/:id',
  validate(s.roleIdParam, 'params'),
  asyncHandler(async (req, res) => {
    res.json({ success: true, data: await roles.getById(req.params.id) });
  })
);

router.post(
  '/',
  requireRole('admin'),
  validate(s.createRole),
  asyncHandler(async (req, res) => {
    res.status(201).json({ success: true, data: await roles.create(req.body) });
  })
);

router.put(
  '/:id',
  requireRole('admin'),
  validate(s.roleIdParam, 'params'),
  validate(s.updateRole),
  asyncHandler(async (req, res) => {
    res.json({ success: true, data: await roles.update(req.params.id, req.body) });
  })
);

router.delete(
  '/:id',
  requireRole('admin'),
  validate(s.roleIdParam, 'params'),
  asyncHandler(async (req, res) => {
    await roles.remove(req.params.id);
    res.json({ success: true, message: 'Role berhasil dihapus' });
  })
);

module.exports = router;
