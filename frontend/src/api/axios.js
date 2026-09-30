import axios from 'axios'
import { useAuthStore } from '@/stores/authStore'

const getBaseURL = () => {
  if (import.meta.env.VITE_API_URL) {
    if (typeof window !== 'undefined' && window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1' && import.meta.env.VITE_API_URL.includes('localhost')) {
      return `${window.location.origin}/api`
    }
    return import.meta.env.VITE_API_URL
  }
  if (typeof window !== 'undefined' && window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
    return `${window.location.origin}/api`
  }
  const hostname = (typeof window !== 'undefined' && window.location.hostname) ? window.location.hostname : 'localhost'
  return `http://${hostname}:8000/api`
}

const api = axios.create({
  baseURL: getBaseURL(),
  timeout: 15000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
})

// ─── Request Interceptor ────────────────────────────────────────────
api.interceptors.request.use(
  (config) => {
    const authStore = useAuthStore()
    const token = authStore.token

    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }

    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// ─── Response Interceptor ───────────────────────────────────────────
api.interceptors.response.use(
  (response) => {
    return response
  },
  (error) => {
    const authStore = useAuthStore()

    // Si el token expiró o es inválido, cerrar sesión
    if (error.response && error.response.status === 401) {
      authStore.clearAuth()
      window.location.href = '/login'
    }

    return Promise.reject(error)
  }
)

export default api
