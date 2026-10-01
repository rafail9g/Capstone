const router = require('express').Router();
const users = require('../services/userService');
const validate = require('../middleware/validate');
const { authenticate, requireRole } = require('../middleware/auth');
const { asyncHandler, AppError } = require('../utils/errors');
const s = require('../validators/schemas');

router.use(authenticate, requireRole('admin'));

const requireDosenTarget = asyncHandler(async (req, res, next) => {
  const target = await users.getById(req.params.id);
  if (target.user_type !== 'dosen') {
    throw new AppError(403, 'Admin hanya dapat mengelola akun dosen');
  }
  req.targetUser = target;
  next();
});

router.get(
  '/',
  validate(s.listUsers, 'query'),
  asyncHandler(async (req, res) => {
    const { rows, total } = await users.list({ ...req.query, user_type: 'dosen' });
    const { page, limit } = req.query;
    res.json({
      success: true,
      data: rows,
      meta: { page, limit, total, total_pages: Math.ceil(total / limit) },
    });
  })
);

router.post(
  '/',
  validate(s.createDosenByAdmin),
  asyncHandler(async (req, res) => {
    const data = { ...req.body, user_type: 'dosen' };
    res.status(201).json({ success: true, data: await users.create(data, req.user.id) });
  })
);

router.get(
  '/:id',
  validate(s.idParam, 'params'),
  requireDosenTarget,
  asyncHandler(async (req, res) => {
    res.json({ success: true, data: req.targetUser });
  })
);

router.put(
  '/:id',
  validate(s.idParam, 'params'),
  requireDosenTarget,
  validate(s.updateUser),
  asyncHandler(async (req, res) => {
    res.json({ success: true, data: await users.update(req.params.id, req.body, req.user.id) });
  })
);

router.delete(
  '/:id',
  validate(s.idParam, 'params'),
  requireDosenTarget,
  asyncHandler(async (req, res) => {
    await users.remove(req.params.id, req.user.id);
    res.json({ success: true, message: 'User berhasil dihapus' });
  })
);

router.get(
  '/:id/roles',
  validate(s.idParam, 'params'),
  requireDosenTarget,
  asyncHandler(async (req, res) => {
    res.json({ success: true, data: req.targetUser.roles });
  })
);

router.put(
  '/:id/roles',
  validate(s.idParam, 'params'),
  requireDosenTarget,
  validate(s.setRoles),
  asyncHandler(async (req, res) => {
    const user = await users.setRoles(req.params.id, req.body.role_ids, req.user.id);
    res.json({ success: true, message: 'Role berhasil diperbarui', data: user });
  })
);

router.post(
  '/:id/roles',
  validate(s.idParam, 'params'),
  requireDosenTarget,
  validate(s.addRoles),
  asyncHandler(async (req, res) => {
    const user = await users.addRoles(req.params.id, req.body.role_ids, req.user.id);
    res.json({ success: true, message: 'Role berhasil ditambahkan', data: user });
  })
);

router.delete(
  '/:id/roles/:roleId',
  validate(s.userRoleParams, 'params'),
  requireDosenTarget,
  asyncHandler(async (req, res) => {
    const user = await users.removeRole(req.params.id, req.params.roleId, req.user.id);
    res.json({ success: true, message: 'Role berhasil dicabut', data: user });
  })
);

module.exports = router;
