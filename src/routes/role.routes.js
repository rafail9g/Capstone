const router = require('express').Router();
const roles = require('../services/roleService');
const validate = require('../middleware/validate');
const { authenticate } = require('../middleware/auth');
const { asyncHandler } = require('../utils/errors');
const s = require('../validators/schemas');

router.use(authenticate);

// List master data role (digunakan untuk dropdown / checkbox pilihan role dosen)
router.get(
  '/',
  validate(s.listRoles, 'query'),
  asyncHandler(async (req, res) => {
    res.json({ success: true, data: await roles.list(req.query) });
  })
);

// Detail master data role
router.get(
  '/:id',
  validate(s.roleIdParam, 'params'),
  asyncHandler(async (req, res) => {
    res.json({ success: true, data: await roles.getById(req.params.id) });
  })
);

module.exports = router;
