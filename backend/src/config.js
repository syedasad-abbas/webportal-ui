require('dotenv').config();
const fs = require('fs');

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

// A host-networked FreeSWITCH is reachable from the backend container at the
// container's default Docker gateway. This avoids pinning the host's DHCP IP.
const detectDockerGateway = () => {
  try {
    const routes = fs.readFileSync('/proc/net/route', 'utf8').trim().split('\n').slice(1);
    const defaultRoute = routes.find((line) => line.trim().split(/\s+/)[1] === '00000000');
    if (!defaultRoute) return null;
    const gatewayHex = defaultRoute.trim().split(/\s+/)[2];
    if (!/^[0-9A-Fa-f]{8}$/.test(gatewayHex)) return null;
    return gatewayHex.match(/../g).reverse().map((octet) => parseInt(octet, 16)).join('.');
  } catch (err) {
    return null;
  }
};

const detectFreeSwitchHost = () => {
  const configured = optionalEnv(process.env.FREESWITCH_HOST, null);
  if (configured) return configured;

  const hostIpFile = optionalEnv(
    process.env.FREESWITCH_HOST_IP_FILE,
    '/mnt/gateways/.host-ip'
  );
  try {
    const detected = optionalEnv(fs.readFileSync(hostIpFile, 'utf8'), null);
    if (detected && /^(?:\d{1,3}\.){3}\d{1,3}$/.test(detected)) {
      return detected;
    }
  } catch (err) {
    // The shared file is created by the host-networked FreeSWITCH container.
  }

  return detectDockerGateway() || 'host.docker.internal';
};

const detectPublishedFreeSwitchIp = () => {
  const hostIpFile = optionalEnv(
    process.env.FREESWITCH_HOST_IP_FILE,
    '/mnt/gateways/.host-ip'
  );
  try {
    const detected = optionalEnv(fs.readFileSync(hostIpFile, 'utf8'), null);
    if (detected && /^(?:\d{1,3}\.){3}\d{1,3}$/.test(detected)) {
      return detected;
    }
  } catch (err) {
    // FreeSWITCH may not have published the host address yet.
  }
  return null;
};

// Detect the host's primary IP address (works both in Docker and on bare metal)
const detectHostIp = () => {
  try {
    const { networkInterfaces } = require('os');
    const interfaces = networkInterfaces();
    for (const name of Object.keys(interfaces)) {
      for (const iface of interfaces[name]) {
        if (iface.family === 'IPv4' && !iface.internal) {
          return iface.address;
        }
      }
    }
  } catch (err) {
    // ignore
  }
  return null;
};

const baseConfig = {
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
    host: detectFreeSwitchHost(),
    port: process.env.FREESWITCH_PORT || 8021,
    password: process.env.FREESWITCH_PASSWORD || 'ClueCon',
    connectTimeoutMs: toInt(process.env.FREESWITCH_CONNECT_TIMEOUT_MS, 5000),
    recordingsPath: process.env.FREESWITCH_RECORDINGS_PATH || '/var/recordings',
    externalSipIp: optionalEnv(
      process.env.FREESWITCH_EXTERNAL_SIP_IP,
      optionalEnv(process.env.PUBLIC_HOST, optionalEnv(process.env.PUBLIC_IP, null))
    ),
    advertisedSipIp: optionalEnv(
      process.env.FREESWITCH_EXTERNAL_SIP_IP,
      detectPublishedFreeSwitchIp()
    ),
    directoryDomain: optionalEnv(
      process.env.FREESWITCH_DIRECTORY_DOMAIN,
      optionalEnv(process.env.SIP_DOMAIN, optionalEnv(process.env.HOST_IP, null))
    ),
    profile: optionalEnv(process.env.FREESWITCH_SIP_PROFILE, 'external') || 'external',
    gatewayConfigPath: optionalEnv(process.env.FREESWITCH_GATEWAY_PATH, null),
    directoryConfigPath: optionalEnv(process.env.FREESWITCH_DIRECTORY_PATH, null)
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
    callTimelineMinutes: toInt(process.env.METRICS_CALL_TIMELINE_MINUTES, 30) || 30,
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

// Auto-detect external SIP IP and directory domain from FreeSWITCH at startup
// when not explicitly configured via environment variables.
let configInitialized = false;

const initConfig = async () => {
  if (configInitialized) return baseConfig;
  configInitialized = true;

  const { getGlobalVar } = require('./lib/freeswitch');

  // Use the same address as Sofia for From/identity headers. Keep this
  // independent from RTP discovery, which may resolve to a public NAT address.
  if (!baseConfig.freeswitch.advertisedSipIp) {
    try {
      const advertisedSipIp = await getGlobalVar('external_sip_ip');
      if (advertisedSipIp && advertisedSipIp !== 'stun:stun.freeswitch.org') {
        baseConfig.freeswitch.advertisedSipIp = advertisedSipIp;
      }
    } catch (err) {
      // Fall back to the configured ESL host when it is an IP address.
    }
    if (
      !baseConfig.freeswitch.advertisedSipIp &&
      /^(?:\d{1,3}\.){3}\d{1,3}$/.test(baseConfig.freeswitch.host || '')
    ) {
      baseConfig.freeswitch.advertisedSipIp = baseConfig.freeswitch.host;
    }
  }

  // Auto-detect external SIP IP from FreeSWITCH's STUN-discovered address
  if (!baseConfig.freeswitch.externalSipIp) {
    try {
      const natAddr = await getGlobalVar('nat_public_addr');
      if (natAddr) {
        baseConfig.freeswitch.externalSipIp = natAddr;
        console.log(`[config] Auto-detected external SIP IP from FreeSWITCH: ${natAddr}`);
      }
    } catch (err) {
      // FreeSWITCH might not be ready yet, will use fallback
    }

    // Fallback: try external_sip_ip variable
    if (!baseConfig.freeswitch.externalSipIp) {
      try {
        const externalSip = await getGlobalVar('external_sip_ip');
        if (externalSip && externalSip !== 'stun:stun.freeswitch.org') {
          baseConfig.freeswitch.externalSipIp = externalSip;
          console.log(`[config] Using FreeSWITCH external_sip_ip: ${externalSip}`);
        }
      } catch (err) {
        // ignore
      }
    }

    // Final fallback: use the host IP published by host-networked FreeSWITCH.
    // Never advertise the backend container's Docker bridge gateway as SIP.
    if (!baseConfig.freeswitch.externalSipIp) {
      const freeswitchHost = baseConfig.freeswitch.host;
      if (freeswitchHost && freeswitchHost !== 'host.docker.internal') {
        baseConfig.freeswitch.externalSipIp = freeswitchHost;
        console.log(`[config] Using FreeSWITCH host IP as external SIP IP: ${freeswitchHost}`);
      }
    }
  }

  // Auto-detect directory domain from FreeSWITCH's domain variable
  if (!baseConfig.freeswitch.directoryDomain) {
    try {
      const domain = await getGlobalVar('domain');
      if (domain) {
        baseConfig.freeswitch.directoryDomain = domain;
        console.log(`[config] Auto-detected directory domain from FreeSWITCH: ${domain}`);
      }
    } catch (err) {
      // FreeSWITCH might not be ready yet, will use fallback
    }

    // Fallback: use external SIP IP as domain
    if (!baseConfig.freeswitch.directoryDomain && baseConfig.freeswitch.externalSipIp) {
      baseConfig.freeswitch.directoryDomain = baseConfig.freeswitch.externalSipIp;
      console.log(`[config] Using external SIP IP as directory domain: ${baseConfig.freeswitch.externalSipIp}`);
    }
  }

  return baseConfig;
};

module.exports = baseConfig;
module.exports.initConfig = initConfig;
module.exports.detectHostIp = detectHostIp;
