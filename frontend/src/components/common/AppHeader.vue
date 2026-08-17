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
            <strong>{{ userFullName }}</strong>
            <span>@{{ user?.username }}</span>
            <div class="app-header__role-badges">
              <span v-for="r in roles" :key="r" class="app-header__role-badge">{{ r }}</span>
            </div>
          </div>
          <hr />
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
.app-header__user-info {
  display: flex;
  flex-direction: column;
  line-height: 1.2;
}
.app-header__user-roles {
  font-size: 0.7rem;
  color: var(--color-primary-400);
}
.app-header__role-badges {
  display: flex;
  flex-wrap: wrap;
  gap: 0.25rem;
  margin-top: 0.35rem;
}
.app-header__role-badge {
  font-size: 0.65rem;
  padding: 0.15rem 0.4rem;
  background: rgba(99, 102, 241, 0.15);
  color: var(--color-primary-300);
  border-radius: var(--radius-sm);
  border: 1px solid rgba(99, 102, 241, 0.3);
}
</style>
