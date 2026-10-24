import { io, Socket } from 'socket.io-client';

let socket: Socket | null = null;

export function getSocket(token: string): Socket {
  if (socket?.connected) return socket;
  
  const SOCKET_URL = (import.meta.env as { VITE_SOCKET_URL?: string }).VITE_SOCKET_URL || 'https://chat.auroracyber.online/socketio';
  
  socket = io(SOCKET_URL, {
    auth: { token },
    transports: ['websocket', 'polling'],
    reconnection: true,
    reconnectionAttempts: 5,
    reconnectionDelay: 1000,
    path: '/socket.io',
  });
  
  socket.on('connect', () => {
    console.log('Socket connected:', socket?.id);
  });
  
  socket.on('disconnect', (reason) => {
    console.log('Socket disconnected:', reason);
  });
  
  socket.on('connect_error', (error) => {
    console.error('Socket connection error:', error.message);
  });
  
  return socket;
}

export function disconnectSocket() {
  if (socket) {
    socket.disconnect();
    socket = null;
  }
}

export function joinConversation(conversationId: string) {
  socket?.emit('conversation:join', { conversationId });
}

export function leaveConversation(conversationId: string) {
  socket?.emit('conversation:leave', { conversationId });
}

export function sendEncryptedMessage(payload: {
  conversationId: string;
  messageId: string;
  ciphertext: string;
  senderEphemeralPubkey: string;
  iv: string;
  mac: string;
}): Promise<{ ok: boolean; message?: any; error?: string }> {
  return new Promise((resolve) => {
    socket?.emit('message:send', payload, (response: any) => {
      resolve(response || { ok: false, error: 'No response' });
    });
  });
}

export function onMessageReceived(callback: (message: any) => void) {
  socket?.on('message:received', callback);
  return () => socket?.off('message:received', callback);
}

export function onTypingStart(callback: (data: { userId: string }) => void) {
  socket?.on('typing:start', callback);
  return () => socket?.off('typing:start', callback);
}

export function onTypingStop(callback: (data: { userId: string }) => void) {
  socket?.on('typing:stop', callback);
  return () => socket?.off('typing:stop', callback);
}

export function onPresenceOnline(callback: (data: { userId: string }) => void) {
  socket?.on('presence:online', callback);
  return () => socket?.off('presence:online', callback);
}

export function onPresenceOffline(callback: (data: { userId: string }) => void) {
  socket?.on('presence:offline', callback);
  return () => socket?.off('presence:offline', callback);
}

export function onKeyAnnounced(callback: (data: { userId: string; publicKey: any }) => void) {
  socket?.on('key:announced', callback);
  return () => socket?.off('key:announced', callback);
}

export function sendTypingStart(conversationId: string) {
  socket?.emit('typing:start', { conversationId });
}

export function sendTypingStop(conversationId: string) {
  socket?.emit('typing:stop', { conversationId });
}

export function announceKey(conversationId: string, publicKey: any) {
  socket?.emit('key:announce', { conversationId, publicKey });
}

export function onConnected(callback: (data: { user: any }) => void) {
  socket?.on('connected', callback);
  return () => socket?.off('connected', callback);
}