const { Server } = require('socket.io');

let ioInstance = null;

const initSocket = (httpServer) => {
  ioInstance = new Server(httpServer, {
    cors: {
      origin: '*',
      methods: ['GET', 'POST']
    }
  });

  ioInstance.on('connection', (socket) => {
    console.log('[socket] client connected', socket.id);
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

module.exports = {
  initSocket,
  getSocket,
  emitSocketEvent
};
