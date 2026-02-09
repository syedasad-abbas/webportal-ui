require('dotenv').config();

const parseJSON = (value, fallback) => {
  try {
    return JSON.parse(value);
  } catch (err) {
    return fallback;
  }
};

const optionalEnv = (value, fallback = null) => {
  if (typeof value !== 'string') {
    return fallback;
  }
  const trimmed = value.trim();
  return trimmed.length > 0 ? trimmed : fallback;
};

const toInt = (value, fallback = null) => {
  if (value === undefined || value === null || value === '') {
    return fallback;
  }
  const parsed = parseInt(value, 10);
  return Number.isNaN(parsed) ? fallback : parsed;
};

const sanitizeTransport = (value, fallback = 'udp') => {
  const normalized = (value || '').toString().trim().toLowerCase();
  return ['udp', 'tcp', 'tls'].includes(normalized) ? normalized : fallback;
};

const parseCsvList = (value, fallback = []) => {
  if (!value) {
    return fallback;
  }
  const items = value
    .split(',')
    .map((token) => token.trim())
    .filter(Boolean);
  return items.length ? items : fallback;
};

module.exports = {
  port: process.env.PORT || 4000,
  db: {
    host: process.env.DB_HOST || 'db',
    port: process.env.DB_PORT || 5432,
    user: process.env.DB_USER || 'webphone',
    password: process.env.DB_PASSWORD || 'supersecret',
    database: process.env.DB_NAME || 'webphone'
  },
  jwtSecret: process.env.JWT_SECRET || 'change_me',
  freeswitch: {
    host: process.env.FREESWITCH_HOST || 'freeswitch',
    port: process.env.FREESWITCH_PORT || 8021,
    password: process.env.FREESWITCH_PASSWORD || 'ClueCon',
    recordingsPath: process.env.FREESWITCH_RECORDINGS_PATH || '/var/recordings',
    externalSipIp: optionalEnv(
      process.env.FREESWITCH_EXTERNAL_SIP_IP,
      optionalEnv(process.env.PUBLIC_HOST, optionalEnv(process.env.PUBLIC_IP, null))
    ),
    profile: optionalEnv(process.env.FREESWITCH_SIP_PROFILE, 'external') || 'external',
    gatewayConfigPath: optionalEnv(process.env.FREESWITCH_GATEWAY_PATH, null)
  },
  defaults: {
    adminEmail: process.env.DEFAULT_ADMIN_EMAIL || 'admin@webphone.local',
    adminPassword: process.env.DEFAULT_ADMIN_PASSWORD || 'AdminPass123!',
    adminRole: process.env.DEFAULT_ADMIN_ROLE || 'superadmin',
    groupName: process.env.DEFAULT_GROUP_NAME || 'Standard User',
    groupPermissions: parseJSON(process.env.DEFAULT_GROUP_PERMISSIONS || '["dial"]', ['dial']),
    carrierName: process.env.DEFAULT_CARRIER_NAME || 'Default Carrier',
    carrierCallerId: process.env.DEFAULT_CARRIER_CALLER_ID || '1000',
    carrierDomain: optionalEnv(process.env.DEFAULT_CARRIER_DOMAIN, '127.0.0.1'),
    carrierPort: toInt(process.env.DEFAULT_CARRIER_PORT, 5062),
    carrierTransport: sanitizeTransport(process.env.DEFAULT_CARRIER_TRANSPORT, 'udp')
  },
  passwordReset: {
    otpTtlMinutes: toInt(process.env.PASSWORD_RESET_OTP_TTL_MINUTES, 10),
    maxAttempts: toInt(process.env.PASSWORD_RESET_OTP_MAX_ATTEMPTS, 5),
    internalToken: optionalEnv(process.env.PASSWORD_RESET_INTERNAL_TOKEN, null)
  },
  internalTokens: {
    backendSync: optionalEnv(process.env.BACKEND_INTERNAL_TOKEN, null)
  },
  metrics: {
    presenceMinutes: toInt(process.env.PRESENCE_WINDOW_MINUTES, 5) || 5,
    activityWindowHours: toInt(process.env.ACTIVITY_WINDOW_HOURS, 24) || 24,
    broadcastIntervalSeconds: toInt(process.env.METRICS_BROADCAST_SECONDS, 15) || 15,
    dialingWindowMinutes: toInt(process.env.METRICS_DIALING_WINDOW_MINUTES, 5) || 5,
    activityTimezone: process.env.METRICS_ACTIVITY_TIMEZONE || 'Asia/Karachi',
    activityAnchorHour: toInt(process.env.METRICS_ACTIVITY_ANCHOR_HOUR, 21) || 21
  },
  frontend: {
    allowedRoles: parseCsvList(process.env.FRONTEND_ALLOWED_ROLES, [])
  },
  permissions: {
    callDial: optionalEnv(process.env.CALL_DIAL_PERMISSION, 'dial')
  }
};
