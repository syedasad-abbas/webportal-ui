const { Server } = require('socket.io');

let ioInstance = null;

const userRoom = (userId) => `user:${userId}`;

const initSocket = (httpServer) => {
  ioInstance = new Server(httpServer, {
    cors: {
      origin: '*',
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
