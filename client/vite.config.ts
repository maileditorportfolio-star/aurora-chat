import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
export default defineConfig({ 
  plugins: [react()], 
  server: { port: 5173 },
  define: {
    'import.meta.env.VITE_API_URL': JSON.stringify('https://chat.auroracyber.online/api'),
    'import.meta.env.VITE_SOCKET_URL': JSON.stringify('https://chat.auroracyber.online/socketio'),
  }
});