const express = require('express');
const helmet = require('helmet');
const cors = require('cors');

const env = require('./config/env');
const routes = require('./routes');

const openapiSpec = require('../openapi.json');

const { notFound, errorHandler } = require('./middleware/error');

const app = express();

app.get('/', (req, res) => {
  res.json({
    success: true,
    message: 'MBKM API is running',
    docs: '/api-docs',
    health: '/health'
  });
});

// Swagger (via CDN, tanpa swagger-ui-express)
// Harus di atas helmet() supaya CSP helmet tidak memblokir script CDN dan inline script
app.get('/api-docs/openapi.json', (req, res) => {
  res.json(openapiSpec);
});

app.get(['/api-docs', '/api-docs/'], (req, res) => {
  res.type('html').send(`<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <title>MBKM API Docs</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.17.14/swagger-ui.css" />
</head>
<body>
  <div id="swagger-ui"></div>
  <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.17.14/swagger-ui-bundle.js"></script>
  <script>
    window.onload = function () {
      window.ui = SwaggerUIBundle({
        url: '/api-docs/openapi.json',
        dom_id: '#swagger-ui'
      });
    };
  </script>
</body>
</html>`);
});

app.use(helmet());

app.use(cors({
  origin: env.corsOrigin === '*'
    ? true
    : env.corsOrigin.split(',')
}));

app.use(express.json({ limit: '100kb' }));

app.get('/health', (req, res) => {
  res.json({
    success: true,
    status: 'ok'
  });
});

app.use('/api', routes);

app.use(notFound);
app.use(errorHandler);

module.exports = app;