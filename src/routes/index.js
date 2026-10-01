const router = require('express').Router();

router.use('/auth', require('./auth.routes'));
router.use('/users', require('./user.routes'));
router.use('/roles', require('./role.routes'));
router.use('/dashboard', require('./dashboard.routes'));
router.use('/mitra', require('./mitra.routes'));
router.use('/prodi', require('./prodi.routes'));
router.use('/cpl', require('./cpl.routes'));
router.use('/mata-kuliah', require('./mataKuliah.routes'));
router.use('/paket-konversi', require('./paketKonversi.routes'));

module.exports = router;

