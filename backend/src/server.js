const http = require('http');
const express = require('express');
const cors = require('cors');
const helmet = require('helmet');
const morgan = require('morgan');
const fs = require('fs');
const path = require('path');
const config = require('./config');
const { ensureDefaults } = require('./services/bootstrapService');
const { syncAllSipUsers } = require('./lib/sipDirectoryConfig');
const { initSocket } = require('./socket');
const { scheduleMetricsBroadcast, startMetricsBroadcasting, fetchDashboardMetrics } = require('./services/metricsService');
const adminRoutes = require('./routes/admin');
const authRoutes = require('./routes/auth');
const callRoutes = require('./routes/calls');
const campaignDialerRoutes = require('./routes/campaignDialer');
const freeswitchRoutes = require('./routes/freeswitch');
const { csrfProtection, validateCsrfToken, CSRF_HEADER_NAME } = require('./middleware/csrf');

  const detectFrontendOrigins = () => {
  const origins = new Set();

  const addOrigin = (url) => {
    if (!url) return;
    let origin = url.replace(/\/$/, '');
    if (!seen.has(origin)) {
      seen.add(origin);
      origins.add(origin);
    }
  };

  const addOriginWithPort = (url, port) => {
    if (!url) return;
    let origin = url.replace(/\/$/, '');
    const urlObj = new URL(origin);
    const hostWithPort = `${urlObj.protocol}//${urlObj.hostname}:${port}`;
    if (!seen.has(hostWithPort)) {
      seen.add(hostWithPort);
      origins.add(hostWithPort);
    }
  };

  const seen = new Set();

  // 1. Explicit env vars
  addOrigin(process.env.FRONTEND_URL);
  addOrigin(process.env.APP_URL);

  // Also add :18080 variants if the env var is set without a port
  addOriginWithPort(process.env.FRONTEND_URL, 18080);
  addOriginWithPort(process.env.APP_URL, 18080);

  // 2. Read from Laravel .env (mounted at /var/www/html)
  try {
    const laravelEnv = '/var/www/html/.env';
    if (fs.existsSync(laravelEnv)) {
      const content = fs.readFileSync(laravelEnv, 'utf8');
      const match = content.match(/^APP_URL=(.+)$/m);
      if (match) {
        const appUrl = match[1].trim();
        addOrigin(appUrl);
        addOriginWithPort(appUrl, 18080);
      }
    }
  } catch (err) {
    console.warn('[cors] Could not read Laravel .env:', err.message);
  }

  // 3. Allow localhost access
  addOrigin('http://localhost:18080');
  addOrigin('http://127.0.0.1:18080');

  // 4. Auto-detect host LAN IPs and construct likely frontend origins
  try {
    const interfaces = require('os').networkInterfaces();
    for (const iface of Object.values(interfaces)) {
      for (const addr of iface) {
        if (addr.family === 'IPv4' && !addr.internal) {
          addOrigin(`http://${addr.address}:18080`);
        }
      }
    }
  } catch (err) {
    console.warn('[cors] Could not detect host IPs:', err.message);
  }

  return Array.from(origins);
};

const FRONTEND_ORIGINS = detectFrontendOrigins();
console.log('[cors] Allowed origins:', FRONTEND_ORIGINS);

const corsMiddleware = cors({
  origin: (origin, callback) => {
    if (!origin || FRONTEND_ORIGINS.includes(origin)) {
      callback(null, true);
    } else {
      callback(new Error('Not allowed by CORS'));
    }
  },
  credentials: true,
});

const app = express();
const httpServer = http.createServer(app);

app.use(helmet());
app.use(corsMiddleware);
app.use(csrfProtection);
app.use(express.json());
app.use(express.urlencoded({ extended: false }));
app.use(morgan('dev'));

app.get('/health', (_req, res) => res.json({ status: 'ok' }));
app.use('/admin', validateCsrfToken, adminRoutes);
app.use('/auth', validateCsrfToken, authRoutes);
app.use('/calls', validateCsrfToken, callRoutes);
app.use('/dialer/campaign', validateCsrfToken, campaignDialerRoutes);
app.use('/freeswitch', freeswitchRoutes);

const DB_ERROR_CODES = new Set([
  '23505', '23503', '23514', '23502',
  '22P02', '42703', '42P01',
  '08000', '08003', '08006', '08001', '08004', '08007',
  '57P03', '53300',
  '40001', '40P01', '40002', '40P02',
  '28000', '28P01',
  '42601', '42501'
]);

const sanitizeDbError = (err) => {
  if (!err) return { status: 500, message: 'Internal server error' };
  const code = err.code || (err.parent && err.parent.code) || (err.original && err.original.code);
  if (code && DB_ERROR_CODES.has(code)) {
    console.error('[error] Database error', { code, message: err.message });
    if (code === '23505') return { status: 409, message: 'Duplicate entry.' };
    if (code === '23503') return { status: 400, message: 'Referenced record not found.' };
    if (code === '23514') return { status: 400, message: 'Invalid value for one of the fields.' };
    if (code === '23502') return { status: 400, message: 'A required field is missing.' };
    if (code === '22P02') return { status: 400, message: 'Invalid value format.' };
    if (code === '42703' || code === '42P01') return { status: 500, message: 'Internal server error' };
    if (code === '40001' || code === '40P01') return { status: 503, message: 'Database temporarily unavailable, please retry.' };
    return { status: 500, message: 'Internal server error' };
  }
  if (err.severity === 'FATAL' || (err.parent && err.parent.severity === 'FATAL')) {
    console.error('[error] Database fatal error', err.message);
    return { status: 500, message: 'Internal server error' };
  }
  return null;
};

app.use((err, req, res, next) => {
  if (res.headersSent) return next(err);
  const dbErr = sanitizeDbError(err);
  if (dbErr) {
    return res.status(dbErr.status).json({ message: dbErr.message });
  }
  next(err);
});

module.exports = { sanitizeDbError };

const start = async () => {
  await config.initConfig();
  await ensureDefaults();
  await syncAllSipUsers();
  const io = initSocket(httpServer);
  io.on('connection', (socket) => {
    fetchDashboardMetrics()
      .then((snapshot) => socket.emit('dashboard.metrics', snapshot))
      .catch((err) => console.warn('[metrics] initial emit failed', err.message));
  });
  httpServer.listen(config.port, () => {
    console.log(`Backend listening on port ${config.port}`);
    startMetricsBroadcasting();
  });
};

start().catch((err) => {
  console.error('Failed to start backend', err);
  process.exit(1);
});
