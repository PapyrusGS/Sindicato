import { storeToRefs } from 'pinia'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'

export function useAuth() {
  const authStore = useAuthStore()
  const router = useRouter()

  const {
    user,
    token,
    loading,
    error,
    isAuthenticated,
    roles,
    userFullName,
    userRolesFormatted,
    isAdmin,
  } = storeToRefs(authStore)

  async function login(credentials) {
    await authStore.login(credentials)
    router.push('/dashboard')
  }

  async function register(data) {
    await authStore.register(data)
    router.push('/dashboard')
  }

  async function logout() {
    await authStore.logout()
    router.push('/login')
  }

  return {
    user,
    token,
    loading,
    error,
    isAuthenticated,
    roles,
    userFullName,
    userRolesFormatted,
    isAdmin,
    hasRole: authStore.hasRole,
    hasAnyRole: authStore.hasAnyRole,
    login,
    register,
    logout,
    fetchUser: authStore.fetchUser,
  }
}
