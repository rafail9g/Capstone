const env = require('./config/env');
const app = require('./app');

app.listen(env.port, () => console.log(`MBKM API berjalan di http://localhost:${env.port}`));
