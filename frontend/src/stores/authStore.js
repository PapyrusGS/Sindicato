import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi } from '@/api/authApi'

export const useAuthStore = defineStore('auth', () => {
  // ─── State ──────────────────────────────────────────────────────────
  const user = ref(JSON.parse(localStorage.getItem('user') || 'null'))
  const token = ref(localStorage.getItem('token') || null)
  const loading = ref(false)
  const error = ref(null)

  // ─── Getters ────────────────────────────────────────────────────────
  const isAuthenticated = computed(() => !!token.value)

  /**
   * Lista de nombres de roles asignados al usuario.
   */
  const roles = computed(() => {
    if (!user.value?.roles) return []
    return Array.isArray(user.value.roles) ? user.value.roles : []
  })

  /**
   * Nombre completo de la persona o el username.
   */
  const userFullName = computed(() => {
    return user.value?.persona?.nombre_completo || user.value?.username || 'Usuario'
  })

  /**
   * Roles formateados como string para mostrar en la interfaz (ej. "Chofer, Inspector").
   */
  const userRolesFormatted = computed(() => {
    if (roles.value.length === 0) return 'Sin Rol'
    return roles.value.join(', ')
  })

  /**
   * Comprobar si el usuario tiene un rol específico (Administrador tiene permiso total).
   */
  function hasRole(roleName) {
    if (roles.value.includes('Administrador')) return true
    return roles.value.includes(roleName)
  }

  /**
   * Comprobar si el usuario tiene al menos uno de los roles especificados.
   */
  function hasAnyRole(roleList = []) {
    if (roles.value.includes('Administrador')) return true
    return roleList.some((role) => roles.value.includes(role))
  }

  const isAdmin = computed(() => hasRole('Administrador'))

  // ─── Actions ────────────────────────────────────────────────────────

  /**
   * Iniciar sesión con username y password.
   */
  async function login(credentials) {
    loading.value = true
    error.value = null

    try {
      const response = await authApi.login(credentials)
      const data = response.data.data

      user.value = data.usuario
      token.value = data.token

      localStorage.setItem('user', JSON.stringify(data.usuario))
      localStorage.setItem('token', data.token)

      return data
    } catch (err) {
      error.value = err.response?.data?.message || 'Error al iniciar sesión'
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Registrar un nuevo usuario.
   */
  async function register(data) {
    loading.value = true
    error.value = null

    try {
      const response = await authApi.register(data)
      const result = response.data.data

      user.value = result.usuario
      token.value = result.token

      localStorage.setItem('user', JSON.stringify(result.usuario))
      localStorage.setItem('token', result.token)

      return result
    } catch (err) {
      error.value = err.response?.data?.message || 'Error al registrarse'
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Cerrar sesión.
   */
  async function logout() {
    try {
      await authApi.logout()
    } catch {
      // Ignorar errores de logout
    } finally {
      clearAuth()
    }
  }

  /**
   * Obtener datos del usuario autenticado actualizando el estado.
   */
  async function fetchUser() {
    try {
      const response = await authApi.me()
      const userData = response.data.data
      user.value = userData
      localStorage.setItem('user', JSON.stringify(userData))
    } catch {
      clearAuth()
    }
  }

  /**
   * Limpiar datos de autenticación.
   */
  function clearAuth() {
    user.value = null
    token.value = null
    error.value = null
    localStorage.removeItem('user')
    localStorage.removeItem('token')
  }

  return {
    // State
    user,
    token,
    loading,
    error,
    // Getters
    isAuthenticated,
    roles,
    userFullName,
    userRolesFormatted,
    isAdmin,
    // Role Methods
    hasRole,
    hasAnyRole,
    // Actions
    login,
    register,
    logout,
    fetchUser,
    clearAuth,
  }
})
