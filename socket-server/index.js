import 'dotenv/config';
import { createServer } from 'node:http';
import { Server } from 'socket.io';
import axios from 'axios';

const PORT = Number(process.env.SOCKET_IO_PORT || 6001);
const API_URL = process.env.API_URL || 'http://localhost:8000/api';
const CORS = (process.env.SOCKET_IO_CORS || 'http://localhost:5173').split(',');

const httpServer = createServer((req, res) => {
  if (req.url === '/health') {
    res.writeHead(200, { 'content-type': 'application/json' });
    res.end(JSON.stringify({ status: 'ok', service: 'aurora-chat-socket' }));
    return;
  }
  res.writeHead(404);
  res.end();
});

const io = new Server(httpServer, {
  cors: { origin: CORS, credentials: true },
  transports: ['websocket', 'polling'],
});

const users = new Map();
const conversationRooms = new Map();

io.use(async (socket, next) => {
  const token = socket.handshake.auth?.token;
  if (!token) return next(new Error('Authentication required'));
  try {
    const response = await axios.get(`${API_URL}/auth/me`, {
      headers: { Authorization: `Bearer ${token}` },
      timeout: 5000,
    });
    socket.user = response.data.data;
    socket.apiToken = token;
    next();
  } catch {
    next(new Error('Invalid authentication token'));
  }
});

io.on('connection', (socket) => {
  const userId = socket.user.id;
  users.set(userId, socket.id);
  socket.join(`user:${userId}`);
  socket.emit('connected', { user: socket.user });

  socket.on('conversation:join', ({ conversationId }) => {
    if (!conversationId) return;
    socket.join(`conversation:${conversationId}`);
    if (!conversationRooms.has(conversationId)) conversationRooms.set(conversationId, new Set());
    conversationRooms.get(conversationId).add(userId);
    socket.to(`conversation:${conversationId}`).emit('presence:online', { userId });
  });

  socket.on('conversation:leave', ({ conversationId }) => {
    if (!conversationId) return;
    socket.leave(`conversation:${conversationId}`);
    conversationRooms.get(conversationId)?.delete(userId);
    socket.to(`conversation:${conversationId}`).emit('presence:offline', { userId });
  });

  socket.on('message:send', async (payload, callback) => {
    try {
      if (!payload?.conversationId || !payload?.messageId || !payload?.ciphertext) {
        throw new Error('Invalid encrypted message payload');
      }

      const response = await axios.post(`${API_URL}/messages`, {
        conversation_id: payload.conversationId,
        message_id: payload.messageId,
        ciphertext: payload.ciphertext,
        sender_ephemeral_pubkey: payload.senderEphemeralPubkey,
        iv: payload.iv,
        mac: payload.mac,
      }, { headers: { Authorization: `Bearer ${socket.apiToken}` } });

      const message = response.data.data;
      io.to(`conversation:${payload.conversationId}`).emit('message:received', message);
      callback?.({ ok: true, message });
    } catch (error) {
      callback?.({ ok: false, error: error.response?.data?.message || error.message });
    }
  });

  socket.on('typing:start', ({ conversationId }) => {
    socket.to(`conversation:${conversationId}`).emit('typing:start', { userId });
  });

  socket.on('typing:stop', ({ conversationId }) => {
    socket.to(`conversation:${conversationId}`).emit('typing:stop', { userId });
  });

  socket.on('key:announce', ({ conversationId, publicKey }) => {
    socket.to(`conversation:${conversationId}`).emit('key:announced', { userId, publicKey });
  });

  socket.on('disconnect', () => {
    users.delete(userId);
    for (const [conversationId, members] of conversationRooms.entries()) {
      if (members.delete(userId)) {
        socket.to(`conversation:${conversationId}`).emit('presence:offline', { userId });
      }
    }
  });
});

httpServer.listen(PORT, () => console.log(`Aurora Chat Socket server listening on ${PORT}`));