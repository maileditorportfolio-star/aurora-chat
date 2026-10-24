#!/opt/alt/alt-nodejs22/root/usr/bin/node
import { createServer } from 'node:http';
import { Server } from 'socket.io';
import axios from 'axios';
import 'dotenv/config';

const PORT = Number(process.env.PORT || process.env.SOCKET_IO_PORT || 6003);
const API_URL = process.env.API_URL || 'http://localhost:8000/api';
const CORS = (process.env.SOCKET_IO_CORS || 'https://chat.auroracyber.online').split(',');
// When running through Passenger with base_uri, Passenger strips the base_uri before reaching the app
// When running directly on a port, the full path including base_uri comes through
// We detect this by checking if the request path starts with the base_uri
const PASSENGER_BASE_URI = process.env.PASSENGER_BASE_URI || '';
// Socket.io path: if running through Passenger (base_uri stripped), use /socket.io
// If running directly, use the base_uri + /socket.io
const IS_PASSENGER = process.env.PASSENGER_BASE_URI && process.env.PORT === undefined;
const SOCKET_IO_PATH = IS_PASSENGER ? '/socket.io' : (PASSENGER_BASE_URI ? `${PASSENGER_BASE_URI}/socket.io` : '/socket.io');

const httpServer = createServer((req, res) => {
  const path = req.url || '';
  // Health check - handle both with and without base_uri prefix
  if (path === '/health' || path === `${PASSENGER_BASE_URI}/health`) {
    res.writeHead(200, { 'content-type': 'application/json' });
    res.end(JSON.stringify({ status: 'ok', service: 'aurora-chat-socket' }));
    return;
  }
  // Let socket.io handle its own routes - don't return 404 for socket.io paths
  // socket.io server will handle the upgrade/handshake
  res.writeHead(404);
  res.end();
});

const io = new Server(httpServer, {
  cors: { origin: CORS, credentials: true },
  transports: ['websocket', 'polling'],
  path: SOCKET_IO_PATH,
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

httpServer.listen(PORT, () => console.log(`Aurora Chat Socket server listening on ${PORT} (path: ${SOCKET_IO_PATH})`));