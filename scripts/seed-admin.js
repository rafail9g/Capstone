// Membuat akun admin pertama: npm run seed:admin
const env = require('../src/config/env'); // eslint-disable-line no-unused-vars
const users = require('../src/services/userService');

(async () => {
  const { ADMIN_NAMA, ADMIN_EMAIL, ADMIN_PASSWORD } = process.env;
  if (!ADMIN_EMAIL || !ADMIN_PASSWORD) {
    console.error('Isi ADMIN_EMAIL dan ADMIN_PASSWORD di .env');
    process.exit(1);
  }
  try {
    const u = await users.create(
      { nama: ADMIN_NAMA || 'Administrator', email: ADMIN_EMAIL.toLowerCase(), password: ADMIN_PASSWORD, user_type: 'admin' },
      null
    );
    console.log(`Admin dibuat: ${u.email}`);
  } catch (err) {
    console.error('Gagal:', err.message);
    process.exit(1);
  }
})();
