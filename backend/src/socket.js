const { Server } = require('socket.io');
const fs = require('fs');
const path = require('path');
const jwt = require('jsonwebtoken');
const config = require('./config');
const { getAssignedPermissions } = require('./services/permissionService');

let ioInstance = null;

const userRoom = (userId) => `user:${userId}`;

  const detectFrontendOrigins = () => {
  const origins = new Set();

  const addOrigin = (url) => {
    if (!url) return;
    const origin = new URL(url).origin;
    if (!seen.has(origin)) {
      seen.add(origin);
      origins.add(origin);
    }
  };

  const addOriginWithPort = (url, port) => {
    if (!url) return;
    const origin = new URL(url).origin;
    const urlObj = new URL(origin);
    const hostWithPort = `${urlObj.protocol}//${urlObj.hostname}:${port}`;
    if (!seen.has(hostWithPort)) {
      seen.add(hostWithPort);
      origins.add(hostWithPort);
    }
  };

  const seen = new Set();

  addOrigin(process.env.FRONTEND_URL);
  addOrigin(process.env.APP_URL);
  addOriginWithPort(process.env.FRONTEND_URL, 18080);
  addOriginWithPort(process.env.APP_URL, 18080);

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
    console.warn('[socket][cors] Could not read Laravel .env:', err.message);
  }

  addOrigin('http://localhost:18080');
  addOrigin('http://127.0.0.1:18080');

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
    console.warn('[socket][cors] Could not detect host IPs:', err.message);
  }

  return Array.from(origins);
};

const FRONTEND_ORIGINS = detectFrontendOrigins();
console.log('[socket][cors] Allowed origins:', FRONTEND_ORIGINS);

const initSocket = (httpServer) => {
  ioInstance = new Server(httpServer, {
    cors: {
      origin: (origin, callback) => {
        if (!origin || FRONTEND_ORIGINS.includes(origin)) {
          callback(null, true);
        } else {
          callback(new Error('Not allowed by CORS'));
        }
      },
      methods: ['GET', 'POST']
    }
  });

  ioInstance.use((socket, next) => {
    try {
      const user = jwt.verify(socket.handshake.auth?.token || '', config.jwtSecret);
      if (!user.id) return next(new Error('Unauthorized'));
      socket.data.userId = user.id;
      return next();
    } catch (err) {
      return next(new Error('Unauthorized'));
    }
  });

  ioInstance.on('connection', (socket) => {
    socket.join(userRoom(socket.data.userId));
  });

  return ioInstance;
};

const getSocket = () => {
  if (!ioInstance) {
    throw new Error('Socket.io instance has not been initialized');
  }
  return ioInstance;
};

const emitAuthorized = async (event, payload, permission, userId = null) => {
  if (!ioInstance) return;
  const permissionsByUser = new Map();
  for (const socket of ioInstance.sockets.sockets.values()) {
    const id = socket.data.userId;
    if (!id || (userId !== null && String(id) !== String(userId))) continue;
    try {
      if (!permissionsByUser.has(id)) {
        permissionsByUser.set(id, await getAssignedPermissions(id));
      }
      if (permissionsByUser.get(id).includes(permission)) socket.emit(event, payload);
    } catch (err) {
      console.warn('[socket] unable to verify permissions', { error: err.message });
    }
  }
};

const emitSocketEvent = (event, payload) =>
  emitAuthorized(event, payload, 'dashboard.view');

const emitToUser = (userId, event, payload) =>
  emitAuthorized(event, payload, 'dialer.create_call', userId);

module.exports = {
  initSocket,
  getSocket,
  emitSocketEvent,
  emitToUser
};
