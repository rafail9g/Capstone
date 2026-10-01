const express = require('express');
const helmet = require('helmet');
const cors = require('cors');
const swaggerUi = require('swagger-ui-express');

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

// Swagger
app.use(
  '/api-docs',
  swaggerUi.serve,
  swaggerUi.setup(openapiSpec, {
    customSiteTitle: 'MBKM API Docs'
  })
);

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