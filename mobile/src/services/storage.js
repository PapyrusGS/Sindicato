// Manejador de Almacenamiento Local Móvil (LocalStorage / Offline Storage)

const STORAGE_KEYS = {
  API_URL: 'sindicato_mobile_api_url',
  TOKEN: 'sindicato_mobile_token',
  USER: 'sindicato_mobile_user',
  OFFLINE_ASISTENCIAS: 'sindicato_mobile_offline_asistencias',
  CACHE_AUXILIARES: 'sindicato_mobile_cache_auxiliares',
  CACHE_HOY: 'sindicato_mobile_cache_hoy',
}

export const mobileStorage = {
  // Configuración de IP del Servidor Backend
  getApiUrl() {
    return localStorage.getItem(STORAGE_KEYS.API_URL) || 'http://localhost:8000/api'
  },
  setApiUrl(url) {
    let cleanUrl = url.trim()
    if (!cleanUrl.endsWith('/api')) {
      cleanUrl = cleanUrl.replace(/\/+$/, '') + '/api'
    }
    localStorage.setItem(STORAGE_KEYS.API_URL, cleanUrl)
  },

  // Token Sanctum
  getToken() {
    return localStorage.getItem(STORAGE_KEYS.TOKEN) || ''
  },
  setToken(token) {
    if (token) localStorage.setItem(STORAGE_KEYS.TOKEN, token)
    else localStorage.removeItem(STORAGE_KEYS.TOKEN)
  },

  // Usuario y Roles
  getUser() {
    const data = localStorage.getItem(STORAGE_KEYS.USER)
    return data ? JSON.parse(data) : null
  },
  setUser(user) {
    if (user) localStorage.setItem(STORAGE_KEYS.USER, JSON.stringify(user))
    else localStorage.removeItem(STORAGE_KEYS.USER)
  },

  // ─── ALMACENAMIENTO DE ASISTENCIAS OFFLINE (MODO SIN INTERNET) ───────
  getOfflineAsistencias() {
    const data = localStorage.getItem(STORAGE_KEYS.OFFLINE_ASISTENCIAS)
    return data ? JSON.parse(data) : []
  },

  saveOfflineAsistencia(record) {
    const queue = this.getOfflineAsistencias()
    queue.push({
      id_local: 'OFFLINE_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5),
      ...record,
      fecha_guardado_local: new Date().toLocaleString(),
    })
    localStorage.setItem(STORAGE_KEYS.OFFLINE_ASISTENCIAS, JSON.stringify(queue))
    return queue
  },

  clearOfflineAsistencias() {
    localStorage.removeItem(STORAGE_KEYS.OFFLINE_ASISTENCIAS)
  },

  // Caché auxiliar para funcionamiento sin conexión
  setCache(key, data) {
    localStorage.setItem('sindicato_cache_' + key, JSON.stringify(data))
  },
  getCache(key) {
    const data = localStorage.getItem('sindicato_cache_' + key)
    return data ? JSON.parse(data) : null
  },

  clearAll() {
    localStorage.removeItem(STORAGE_KEYS.TOKEN)
    localStorage.removeItem(STORAGE_KEYS.USER)
  }
}
