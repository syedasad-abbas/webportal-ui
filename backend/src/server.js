const http = require('http');
const express = require('express');
const cors = require('cors');
const morgan = require('morgan');
const config = require('./config');
const { ensureDefaults } = require('./services/bootstrapService');
const { initSocket } = require('./socket');
const { scheduleMetricsBroadcast, startMetricsBroadcasting, fetchDashboardMetrics } = require('./services/metricsService');
const adminRoutes = require('./routes/admin');
const authRoutes = require('./routes/auth');
const callRoutes = require('./routes/calls');
const campaignDialerRoutes = require('./routes/campaignDialer');

const app = express();
const httpServer = http.createServer(app);

app.use(cors());
app.use(express.json());
app.use(express.urlencoded({ extended: false }));
app.use(morgan('dev'));

app.get('/health', (_req, res) => res.json({ status: 'ok' }));
app.use('/admin', adminRoutes);
app.use('/auth', authRoutes);
app.use('/calls', callRoutes);
app.use('/dialer/campaign', campaignDialerRoutes);

const start = async () => {
  await ensureDefaults();
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
