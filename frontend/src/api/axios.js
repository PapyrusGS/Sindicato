import axios from 'axios'
import { useAuthStore } from '@/stores/authStore'

const getBaseURL = () => {
  if (import.meta.env.VITE_API_URL && !import.meta.env.VITE_API_URL.includes('localhost')) {
    return import.meta.env.VITE_API_URL
  }
  const hostname = window.location.hostname || 'localhost'
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
