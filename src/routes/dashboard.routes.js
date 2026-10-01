const router = require('express').Router();
const { authenticate } = require('../middleware/auth');

const ROLE_INFO = {
  admin: { label: 'Admin', menu: [{ key: 'kelola_dosen', label: 'Kelola Akun & Role Dosen', path: '/api/users' }] },
  mahasiswa: { label: 'Mahasiswa', menu: [{ key: 'profil', label: 'Profil Saya', path: '/api/auth/me' }] },
  tim_mbkm: { label: 'Tim MBKM', menu: [{ key: 'tim_mbkm', label: 'Menu Tim MBKM', path: '#' }] },
  gpm: { label: 'GPM', menu: [{ key: 'gpm', label: 'Menu GPM', path: '#' }] },
  penguji_semhas: { label: 'Penguji Semhas', menu: [{ key: 'penguji_semhas', label: 'Menu Penguji Semhas', path: '#' }] },
  dpl: { label: 'DPL', menu: [{ key: 'dpl', label: 'Menu DPL (Mahasiswa Bimbingan)', path: '#' }] },
  wakil_dekan_1: { label: 'Wakil Dekan 1', menu: [{ key: 'wd1', label: 'Menu Wakil Dekan 1', path: '#' }] },
};

router.get('/', authenticate, (req, res) => {
  const { user_type, roles } = req.user;
  const allRoleCodes = user_type === 'mahasiswa' ? ['mahasiswa'] : roles;
  const sections = allRoleCodes
    .filter((code) => ROLE_INFO[code])
    .map((code) => ({ role: code, label: ROLE_INFO[code].label, menu: ROLE_INFO[code].menu }));

  const menu = [...new Map(sections.flatMap((s) => s.menu).map((m) => [m.key, m])).values()];

  res.json({
    success: true,
    data: {
      user: req.user,
      sections, 
    },
  });
});

module.exports = router;
