import axios from 'axios';
/// <reference types="vite/client" />
export const api = axios.create({
  baseURL: (import.meta.env as { VITE_API_URL?: string }).VITE_API_URL || 'http://localhost:8000/api',
  headers: { 'Content-Type': 'application/json' },
});
api.interceptors.request.use((config)=>{ const token=localStorage.getItem('aurora-token'); if(token) config.headers.Authorization=`Bearer ${token}`; return config; });
export const authApi={login:(data:unknown)=>api.post('/auth/login',data).then(r=>r.data.data),register:(data:unknown)=>api.post('/auth/register',data).then(r=>r.data.data),me:()=>api.get('/auth/me').then(r=>r.data.data),key:(public_key:string)=>api.post('/auth/public-key',{public_key})};