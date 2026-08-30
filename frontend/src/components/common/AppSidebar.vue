<template>
  <aside class="app-sidebar" :class="{ 'app-sidebar--collapsed': collapsed }">
    <div class="app-sidebar__header">
      <div v-if="!collapsed" class="app-sidebar__brand">
        <div class="app-sidebar__logo">
          <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="24" cy="24" r="22" stroke="currentColor" stroke-width="2.5" />
            <circle cx="24" cy="24" r="6" stroke="currentColor" stroke-width="2" />
            <line x1="2" y1="24" x2="46" y2="24" stroke="currentColor" stroke-width="1.5" />
            <path d="M24 8C24 8 32 16 32 24C32 32 24 40 24 40" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
            <path d="M24 8C24 8 16 16 16 24C16 32 24 40 24 40" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
          </svg>
        </div>
        <span class="app-sidebar__title">Sindicato</span>
      </div>
      <button
        class="app-sidebar__toggle"
        :title="collapsed ? 'Expandir menú' : 'Contraer menú'"
        @click="$emit('toggle')"
        id="btn-collapse-sidebar"
      >
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
            :title="collapsed ? item.label : ''"
            :id="`nav-${item.name}`"
          >
            <span class="app-sidebar__link-icon" v-html="item.icon"></span>
            <div v-if="!collapsed" class="app-sidebar__link-content">
              <span class="app-sidebar__link-text">{{ item.label }}</span>
            </div>
          </router-link>
        </li>
      </ul>
    </nav>

    <div v-if="!collapsed" class="app-sidebar__footer">
      <div class="app-sidebar__version">
        <span>v1.0.0 — Sindicato de Choferes</span>
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

const allMenuItems = [
  {
    name: 'dashboard',
    label: 'Dashboard',
    to: '/dashboard',
    roles: [],
    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>',
  },
  {
    name: 'afiliados',
    label: 'Afiliaciones & Personas',
    to: '/dashboard/afiliados',
    roles: ['Administrador'],
    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
  },
  {
    name: 'asistencias',
    label: 'Control Asistencia',
    to: '/dashboard/asistencias',
    roles: ['Inspector', 'Administrador'],
    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>',
  },
  {
    name: 'multas',
    label: 'Sanciones e Infracciones',
    to: '/dashboard/multas',
    roles: ['Inspector', 'Administrador'],
    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
  },
  {
    name: 'obligaciones',
    label: 'Obligaciones del Grupo',
    to: '/dashboard/obligaciones',
    roles: ['Jefe de Grupo', 'Administrador'],
    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="16" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
  },
  {
    name: 'cobros',
    label: 'Cobros & Historial de Caja',
    to: '/dashboard/cobros',
    roles: ['Tesorero', 'Jefe de Grupo', 'Administrador'],
    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>',
  },
  {
    name: 'auditoria',
    label: 'Bitácora & Auditorías',
    to: '/dashboard/auditoria',
    roles: ['Jefe de Grupo', 'Inspector', 'Tesorero', 'Administrador'],
    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>',
  },
  {
    name: 'mi-perfil',
    label: 'Mi Perfil & Vehículos',
    to: '/dashboard/mi-perfil',
    roles: [],
    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
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
.app-sidebar {
  width: var(--sidebar-width);
  background-color: #ffffff;
  border-right: 1px solid var(--color-border);
  display: flex;
  flex-direction: column;
  transition: width 0.2s ease;
  flex-shrink: 0;
  overflow: hidden;
  white-space: nowrap;
}

.app-sidebar--collapsed {
  width: var(--sidebar-collapsed-width);
}

.app-sidebar__header {
  height: var(--header-height);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 1.25rem;
  border-bottom: 1px solid var(--color-border);
  gap: 0.5rem;
}

.app-sidebar--collapsed .app-sidebar__header {
  padding: 0 0.5rem;
  justify-content: center;
}

.app-sidebar__brand {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  overflow: hidden;
}
.app-sidebar__logo {
  width: 28px;
  height: 28px;
  min-width: 28px;
  color: var(--color-primary-600);
}
.app-sidebar__title {
  font-size: 1.15rem;
  font-weight: 800;
  color: var(--color-text-primary);
}

.app-sidebar__toggle {
  background: #ffffff;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-sm);
  padding: 0.35rem;
  color: var(--color-text-muted);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all var(--transition-fast);
}
.app-sidebar__toggle:hover {
  background: var(--color-bg-tertiary);
  color: var(--color-primary-600);
  border-color: var(--color-primary-400);
}
.app-sidebar__toggle svg { width: 18px; height: 18px; }

.app-sidebar__nav {
  padding: 1rem 0.75rem;
  flex: 1;
  overflow-y: auto;
  overflow-x: hidden;
}
.app-sidebar--collapsed .app-sidebar__nav {
  padding: 1rem 0.4rem;
}

.app-sidebar__menu {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.app-sidebar__link {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.7rem 0.9rem;
  border-radius: var(--radius-md);
  color: var(--color-text-secondary);
  font-size: 0.9rem;
  font-weight: 600;
  transition: all var(--transition-fast);
  text-decoration: none;
}
.app-sidebar--collapsed .app-sidebar__link {
  justify-content: center;
  padding: 0.75rem 0.35rem;
  gap: 0;
}

.app-sidebar__link:hover {
  background-color: var(--color-bg-tertiary);
  color: var(--color-text-primary);
}
.app-sidebar__link--active {
  background-color: var(--color-primary-50);
  color: var(--color-primary-700);
  border-left: 4px solid var(--color-primary-600);
}
.app-sidebar--collapsed .app-sidebar__link--active {
  border-left: none;
  background-color: var(--color-primary-100);
}

.app-sidebar__link-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 20px;
}
.app-sidebar__link-icon svg { width: 20px; height: 20px; }
.app-sidebar--collapsed .app-sidebar__link-icon svg { width: 22px; height: 22px; }

.app-sidebar__link-content {
  display: flex;
  align-items: center;
  width: 100%;
  overflow: hidden;
}

.app-sidebar__link-text {
  overflow: hidden;
  text-overflow: ellipsis;
}

.app-sidebar__footer {
  padding: 1rem 1.25rem;
  border-top: 1px solid var(--color-border);
  font-size: 0.75rem;
  color: var(--color-text-muted);
}
</style>
