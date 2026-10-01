const router = require('express').Router();
const { authenticate, requireRole } = require('../middleware/auth');
const { asyncHandler } = require('../utils/errors');
const prodi = require('../services/prodiService');

router.use(authenticate, requireRole('admin'));

// Isi dropdown Program Studi
router.get(
  '/',
  asyncHandler(async (req, res) => {
    res.json({ success: true, data: await prodi.list() });
  })
);

module.exports = router;
