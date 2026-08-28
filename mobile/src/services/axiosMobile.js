import axios from 'axios'
import { mobileStorage } from './storage'

const apiMobile = axios.create({
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  timeout: 10000, // 10 segundos de timeout para detectar desconexión de red
})

// Interceptor de Request: Adjunta URL dinámica de API y Token Sanctum
apiMobile.interceptors.request.use((config) => {
  config.baseURL = mobileStorage.getApiUrl()
  const token = mobileStorage.getToken()
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config;
}, (error) => {
  return Promise.reject(error)
})

// Interceptor de Response: Manejo limpio de errores de red y sesión
apiMobile.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      mobileStorage.clearAll()
    }
    return Promise.reject(error)
  }
)

export default apiMobile
