<template>
  <header class="app-header">
    <div class="app-header__left">
      <button class="app-header__menu-btn" @click="$emit('toggle-sidebar')" id="btn-toggle-sidebar">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
          <line x1="3" y1="6" x2="21" y2="6" />
          <line x1="3" y1="12" x2="21" y2="12" />
          <line x1="3" y1="18" x2="21" y2="18" />
        </svg>
      </button>
      <h2 class="app-header__page-title">{{ pageTitle }}</h2>
    </div>

    <div class="app-header__right">
      <div class="app-header__user" @click="showDropdown = !showDropdown" id="btn-user-menu">
        <div class="app-header__avatar">
          {{ userInitials }}
        </div>
        <div class="app-header__user-info">
          <span class="app-header__user-name">{{ userFullName }}</span>
          <span class="app-header__user-roles">{{ userRolesFormatted }}</span>
        </div>
        <svg class="app-header__chevron" :class="{ 'app-header__chevron--open': showDropdown }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <polyline points="6 9 12 15 18 9" />
        </svg>
      </div>

      <transition name="dropdown">
        <div v-if="showDropdown" class="app-header__dropdown">
          <div class="app-header__dropdown-header">
            <strong class="text-primary">{{ userFullName }}</strong>
            <span class="text-secondary">@{{ user?.username }}</span>
            <div class="app-header__role-badges">
              <span v-for="r in roles" :key="r" class="app-header__role-badge">{{ r }}</span>
            </div>
          </div>
          <hr class="dropdown-divider" />
          <button class="app-header__dropdown-item" @click="handleLogout" id="btn-logout">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
              <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
              <polyline points="16 17 21 12 16 7" />
              <line x1="21" y1="12" x2="9" y2="12" />
            </svg>
            Cerrar Sesión
          </button>
        </div>
      </transition>
    </div>
  </header>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAuth } from '@/composables/useAuth'

defineEmits(['toggle-sidebar'])

const route = useRoute()
const { user, userFullName, userRolesFormatted, roles, logout } = useAuth()
const showDropdown = ref(false)

const pageTitle = computed(() => route.meta.title || 'Dashboard')

const userInitials = computed(() => {
  const name = userFullName.value || 'U'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
})

async function handleLogout() {
  showDropdown.value = false
  await logout()
}
</script>

<style scoped>
.app-header {
  height: var(--header-height);
  background-color: #ffffff;
  border-bottom: 1px solid var(--color-border);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 1.5rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
  position: relative;
  z-index: 50;
}
.app-header__left {
  display: flex;
  align-items: center;
  gap: 1rem;
}
.app-header__menu-btn {
  background: none;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  padding: 0.4rem;
  color: var(--color-text-secondary);
  cursor: pointer;
  display: flex;
  align-items: center;
}
.app-header__menu-btn:hover {
  background: var(--color-bg-tertiary);
  color: var(--color-text-primary);
}
.app-header__menu-btn svg { width: 20px; height: 20px; }

.app-header__page-title {
  font-size: 1.15rem;
  font-weight: 700;
  color: var(--color-text-primary);
}

.app-header__right { position: relative; }

.app-header__user {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.35rem 0.6rem;
  border-radius: var(--radius-lg);
  cursor: pointer;
  transition: background var(--transition-fast);
  border: 1px solid transparent;
}
.app-header__user:hover {
  background: var(--color-bg-tertiary);
  border-color: var(--color-border);
}

.app-header__avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: var(--color-primary-100);
  color: var(--color-primary-800);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.85rem;
  border: 1px solid var(--color-primary-200);
}

.app-header__user-info { display: flex; flex-direction: column; line-height: 1.2; }
.app-header__user-name { font-size: 0.875rem; font-weight: 600; color: var(--color-text-primary); }
.app-header__user-roles { font-size: 0.75rem; color: var(--color-primary-700); font-weight: 500; }
.app-header__chevron { width: 16px; height: 16px; color: var(--color-text-muted); transition: transform var(--transition-fast); }
.app-header__chevron--open { transform: rotate(180deg); }

.app-header__dropdown {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  width: 240px;
  background: #ffffff;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-xl);
  box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
  padding: 0.85rem;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.app-header__dropdown-header { display: flex; flex-direction: column; font-size: 0.85rem; }
.app-header__role-badges { display: flex; flex-wrap: wrap; gap: 0.25rem; margin-top: 0.4rem; }
.app-header__role-badge { font-size: 0.65rem; padding: 0.15rem 0.4rem; background: var(--color-primary-50); color: var(--color-primary-800); border-radius: var(--radius-sm); border: 1px solid var(--color-primary-200); font-weight: 600; }
.dropdown-divider { border: none; border-top: 1px solid var(--color-border); margin: 0.25rem 0; }

.app-header__dropdown-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem;
  border-radius: var(--radius-md);
  border: none;
  background: none;
  color: var(--color-error);
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  width: 100%;
}
.app-header__dropdown-item:hover { background: var(--color-error-bg); }
.app-header__dropdown-item svg { width: 16px; height: 16px; }
</style>
