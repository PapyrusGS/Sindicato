import { useAuthStore } from '@/stores/authStore'

/**
 * Navigation guard: redirige a login si la ruta requiere autenticación.
 */
export function authGuard(to, from, next) {
  const authStore = useAuthStore()

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next({ name: 'login', query: { redirect: to.fullPath } })
  } else {
    next()
  }
}

/**
 * Navigation guard: redirige al dashboard si el usuario ya está autenticado.
 */
export function guestGuard(to, from, next) {
  const authStore = useAuthStore()

  if (to.meta.guest && authStore.isAuthenticated) {
    next({ name: 'dashboard' })
  } else {
    next()
  }
}
