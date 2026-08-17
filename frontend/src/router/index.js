import { createRouter, createWebHistory } from 'vue-router'
import { authGuard, guestGuard } from './guards'

const routes = [
  // ─── Rutas de Autenticación (Layout: Auth) ────────────────────────
  {
    path: '/',
    component: () => import('@/layouts/AuthLayout.vue'),
    beforeEnter: guestGuard,
    children: [
      {
        path: '',
        redirect: '/login',
      },
      {
        path: 'login',
        name: 'login',
        component: () => import('@/views/auth/LoginView.vue'),
        meta: { guest: true, title: 'Iniciar Sesión' },
      },
      {
        path: 'register',
        name: 'register',
        component: () => import('@/views/auth/RegisterView.vue'),
        meta: { guest: true, title: 'Registro' },
      },
    ],
  },

  // ─── Rutas del Dashboard (Layout: Dashboard, protegidas) ──────────
  {
    path: '/dashboard',
    component: () => import('@/layouts/DashboardLayout.vue'),
    beforeEnter: authGuard,
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'dashboard',
        component: () => import('@/views/dashboard/DashboardView.vue'),
        meta: { requiresAuth: true, title: 'Dashboard' },
      },
      {
        path: 'afiliados',
        name: 'afiliados',
        component: () => import('@/views/afiliacion/GestionAfiliadosView.vue'),
        meta: { requiresAuth: true, title: 'Gestión de Afiliaciones' },
      },
      {
        path: 'usuarios-roles',
        name: 'usuarios-roles',
        component: () => import('@/views/afiliacion/GestionAfiliadosView.vue'),
        meta: { requiresAuth: true, title: 'Usuarios & Roles' },
      },
    ],
  },

  // ─── 404 ──────────────────────────────────────────────────────────
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    redirect: '/login',
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

// Actualizar el título de la página según la ruta
router.afterEach((to) => {
  const appName = import.meta.env.VITE_APP_NAME || 'Sindicato de Choferes'
  document.title = to.meta.title ? `${to.meta.title} | ${appName}` : appName
})

export default router
