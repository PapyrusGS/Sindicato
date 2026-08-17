<template>
  <aside class="app-sidebar" :class="{ 'app-sidebar--collapsed': collapsed }">
    <div class="app-sidebar__header">
      <div class="app-sidebar__brand">
        <div class="app-sidebar__logo">
          <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="24" cy="24" r="22" stroke="currentColor" stroke-width="2.5" />
            <circle cx="24" cy="24" r="6" stroke="currentColor" stroke-width="2" />
            <line x1="2" y1="24" x2="46" y2="24" stroke="currentColor" stroke-width="1.5" />
            <path d="M24 8C24 8 32 16 32 24C32 32 24 40 24 40" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
            <path d="M24 8C24 8 16 16 16 24C16 32 24 40 24 40" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
          </svg>
        </div>
        <transition name="fade">
          <span v-if="!collapsed" class="app-sidebar__title">Sindicato</span>
        </transition>
      </div>
      <button class="app-sidebar__toggle" @click="$emit('toggle')" id="btn-collapse-sidebar">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
          <polyline :points="collapsed ? '9 18 15 12 9 6' : '15 18 9 12 15 6'" />
        </svg>
      </button>
    </div>

    <nav class="app-sidebar__nav">
      <ul class="app-sidebar__menu">
        <li v-for="item in visibleMenuItems" :key="item.name">
          <router-link
            :to="item.to"
            class="app-sidebar__link"
            :class="{ 'app-sidebar__link--active': isActive(item.to) }"
            :id="`nav-${item.name}`"
          >
            <span class="app-sidebar__link-icon" v-html="item.icon"></span>
            <transition name="fade">
              <div v-if="!collapsed" class="app-sidebar__link-content">
                <span class="app-sidebar__link-text">{{ item.label }}</span>
                <span v-if="item.roleBadge" class="app-sidebar__role-tag">{{ item.roleBadge }}</span>
              </div>
            </transition>
          </router-link>
        </li>
      </ul>
    </nav>

    <div class="app-sidebar__footer">
      <div class="app-sidebar__version">
        <transition name="fade">
          <span v-if="!collapsed">v1.0.0 — RBAC Active</span>
        </transition>
      </div>
    </div>
  </aside>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAuth } from '@/composables/useAuth'

defineProps({
  collapsed: {
    type: Boolean,
    default: false,
  },
})

defineEmits(['toggle'])

const route = useRoute()
const { hasAnyRole } = useAuth()

// ─── Definición de menú dinámico por rol ──────────────────────────────
const allMenuItems = [
  {
    name: 'dashboard',
    label: 'Dashboard',
    to: '/dashboard',
    roles: [], // Todos ven el dashboard
    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>',
  },
  // ─── Módulo Chofer ─────────────────────────────────────────────────
  {
    name: 'mis-vehiculos',
    label: 'Mis Vehículos',
    to: '/dashboard/mis-vehiculos',
    roles: ['Chofer'],
    roleBadge: 'Chofer',
    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="1" y="3" width="15" height="13" rx="2"/><circle cx="8.5" cy="13.5" r="2.5"/><path d="M16 8h4l3 5v4h-3"/></svg>',
  },
  // ─── Módulo Inspector ──────────────────────────────────────────────
  {
    name: 'asistencias',
    label: 'Control Asistencia',
    to: '/dashboard/asistencias',
    roles: ['Inspector'],
    roleBadge: 'Inspector',
    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>',
  },
  {
    name: 'multas',
    label: 'Registro de Multas',
    to: '/dashboard/multas',
    roles: ['Inspector'],
    roleBadge: 'Inspector',
    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
  },
  // ─── Módulo Tesorero ───────────────────────────────────────────────
  {
    name: 'caja-pagos',
    label: 'Caja & Pagos',
    to: '/dashboard/caja-pagos',
    roles: ['Tesorero'],
    roleBadge: 'Tesorero',
    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>',
  },
  // ─── Módulo Jefe de Grupo ──────────────────────────────────────────
  {
    name: 'mi-grupo',
    label: 'Gestión de Grupo',
    to: '/dashboard/mi-grupo',
    roles: ['Jefe de Grupo'],
    roleBadge: 'Jefe Grupo',
    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
  },
  // ─── Módulo Administrador ──────────────────────────────────────────
  {
    name: 'usuarios-roles',
    label: 'Usuarios & Roles',
    to: '/dashboard/usuarios-roles',
    roles: ['Administrador'],
    roleBadge: 'Admin',
    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
  },
]

const visibleMenuItems = computed(() => {
  return allMenuItems.filter((item) => {
    if (item.roles.length === 0) return true
    return hasAnyRole(item.roles)
  })
})

function isActive(to) {
  return route.path === to || route.path.startsWith(to + '/')
}
</script>

<style scoped>
.app-sidebar__link-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
}
.app-sidebar__role-tag {
  font-size: 0.6rem;
  padding: 0.1rem 0.35rem;
  background: var(--color-bg-glass);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-sm);
  color: var(--color-text-muted);
}
</style>
