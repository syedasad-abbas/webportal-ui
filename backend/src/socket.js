const { Server } = require('socket.io');
const fs = require('fs');
const path = require('path');

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

  ioInstance.on('connection', (socket) => {
    console.log('[socket] client connected', socket.id);

    // Allow a client to identify itself so we can target events at one user.
    // Accepts userId from handshake auth or query, or a later 'identify' event.
    const joinUserRoom = (userId) => {
      const id = parseInt(userId, 10);
      if (!Number.isInteger(id) || id <= 0) {
        return;
      }
      socket.join(userRoom(id));
      console.log('[socket] client joined room', { socket: socket.id, userId: id });
    };

    joinUserRoom(socket.handshake.auth?.userId ?? socket.handshake.query?.userId);
    socket.on('identify', joinUserRoom);

    socket.on('disconnect', () => {
      console.log('[socket] client disconnected', socket.id);
    });
  });

  return ioInstance;
};

const getSocket = () => {
  if (!ioInstance) {
    throw new Error('Socket.io instance has not been initialized');
  }
  return ioInstance;
};

const emitSocketEvent = (event, payload) => {
  if (!ioInstance) {
    return;
  }
  ioInstance.emit(event, payload);
};

const emitToUser = (userId, event, payload) => {
  if (!ioInstance) {
    return;
  }
  ioInstance.to(userRoom(userId)).emit(event, payload);
};

module.exports = {
  initSocket,
  getSocket,
  emitSocketEvent,
  emitToUser
};
