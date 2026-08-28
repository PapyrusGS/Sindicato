<template>
  <div id="mobile-app">
    <!-- Si no ha iniciado sesión -->
    <MobileLoginView v-if="!user" @login-success="onLoginSuccess" />

    <!-- Si ya está autenticado -->
    <div v-else class="app-layout">
      <!-- Header Móvil Superior -->
      <header class="app-header">
        <div class="header-brand">
          <span class="brand-logo">🚍</span>
          <div>
            <h1 class="brand-title">Sindicato Móvil</h1>
            <small class="brand-user font-mono">@{{ user.username }}</small>
          </div>
        </div>

        <div class="header-status">
          <span class="network-badge" :class="isOnline ? 'online' : 'offline'">
            {{ isOnline ? '🟢 Online' : '🟠 Offline' }}
          </span>
        </div>
      </header>

      <!-- Contenido Principal Dinámico -->
      <main class="app-content">
        <MobileChoferView v-if="activeTab === 'chofer'" />
        <MobileInspectorView v-else-if="activeTab === 'inspector'" />
        <MobileTesoreroView v-else-if="activeTab === 'tesorero'" />
        <MobileJefeAdminView v-else-if="activeTab === 'admin'" />
        <MobileConfigView v-else-if="activeTab === 'config'" @logout="onLogout" />
      </main>

      <!-- Bottom Navigation Bar Táctil Universal -->
      <nav class="bottom-nav">
        <button
          class="nav-item"
          :class="{ active: activeTab === 'chofer' }"
          @click="activeTab = 'chofer'"
        >
          <span class="nav-icon">👤</span>
          <span class="nav-label">Chofer</span>
        </button>

        <button
          v-if="hasAnyRole(['Inspector', 'Administrador'])"
          class="nav-item"
          :class="{ active: activeTab === 'inspector' }"
          @click="activeTab = 'inspector'"
        >
          <span class="nav-icon">📋</span>
          <span class="nav-label">Inspector</span>
        </button>

        <button
          v-if="hasAnyRole(['Tesorero', 'Jefe de Grupo', 'Administrador'])"
          class="nav-item"
          :class="{ active: activeTab === 'tesorero' }"
          @click="activeTab = 'tesorero'"
        >
          <span class="nav-icon">💵</span>
          <span class="nav-label">Tesorero</span>
        </button>

        <button
          v-if="hasAnyRole(['Jefe de Grupo', 'Administrador'])"
          class="nav-item"
          :class="{ active: activeTab === 'admin' }"
          @click="activeTab = 'admin'"
        >
          <span class="nav-icon">👑</span>
          <span class="nav-label">Jefe/Admin</span>
        </button>

        <button
          class="nav-item"
          :class="{ active: activeTab === 'config' }"
          @click="activeTab = 'config'"
        >
          <span class="nav-icon">⚙️</span>
          <span class="nav-label">Ajustes</span>
        </button>
      </nav>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { mobileStorage } from '@/services/storage'
import { offlineSyncService } from '@/services/offlineSync'
import MobileLoginView from '@/views/MobileLoginView.vue'
import MobileChoferView from '@/views/MobileChoferView.vue'
import MobileInspectorView from '@/views/MobileInspectorView.vue'
import MobileTesoreroView from '@/views/MobileTesoreroView.vue'
import MobileJefeAdminView from '@/views/MobileJefeAdminView.vue'
import MobileConfigView from '@/views/MobileConfigView.vue'

const user = ref(mobileStorage.getUser())
const activeTab = ref('chofer')
const isOnline = ref(true)

function onLoginSuccess(u) {
  user.value = u
  determinarTabInicial()
}

function onLogout() {
  user.value = null
  activeTab.value = 'chofer'
}

function hasAnyRole(rolesTarget) {
  if (!user.value || !user.value.roles) return false
  return user.value.roles.some(r => rolesTarget.includes(r))
}

function determinarTabInicial() {
  if (hasAnyRole(['Inspector'])) {
    activeTab.value = 'inspector'
  } else if (hasAnyRole(['Tesorero'])) {
    activeTab.value = 'tesorero'
  } else if (hasAnyRole(['Jefe de Grupo', 'Administrador'])) {
    activeTab.value = 'admin'
  } else {
    activeTab.value = 'chofer'
  }
}

async function chequearRed() {
  isOnline.value = await offlineSyncService.probarConexion()
}

onMounted(() => {
  if (user.value) {
    determinarTabInicial()
  }
  chequearRed()
  window.addEventListener('online', chequearRed)
  window.addEventListener('offline', () => { isOnline.value = false })
})
</script>

<style>
/* Reset global y diseño táctil sobrio móvil */
* { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; -webkit-tap-highlight-color: transparent; }

body { background: #f8fafc; color: #0f172a; overflow-x: hidden; }

#mobile-app { min-height: 100vh; display: flex; flex-direction: column; }

.app-layout { flex: 1; display: flex; flex-direction: column; }

.app-header {
  background: #2563eb; color: #ffffff; padding: 0.75rem 1rem;
  display: flex; justify-content: space-between; align-items: center;
  position: sticky; top: 0; z-index: 100; box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.header-brand { display: flex; align-items: center; gap: 0.5rem; }
.brand-logo { font-size: 1.5rem; }
.brand-title { font-size: 1rem; font-weight: 800; }
.brand-user { font-size: 0.75rem; opacity: 0.9; }

.network-badge { font-size: 0.7rem; font-weight: 700; padding: 0.2rem 0.5rem; border-radius: 1rem; }
.network-badge.online { background: #166534; color: #ffffff; }
.network-badge.offline { background: #ea580c; color: #ffffff; }

.app-content { flex: 1; overflow-y: auto; }

.bottom-nav {
  position: fixed; bottom: 0; left: 0; right: 0;
  background: #ffffff; border-top: 1px solid #e2e8f0;
  display: flex; justify-content: space-around;
  padding: 0.4rem 0.2rem; z-index: 100; box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
}
.nav-item {
  flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center;
  background: none; border: none; padding: 0.3rem 0; cursor: pointer; color: #64748b; transition: all 0.2s;
}
.nav-icon { font-size: 1.2rem; margin-bottom: 0.1rem; }
.nav-label { font-size: 0.68rem; font-weight: 600; }
.nav-item.active { color: #2563eb; font-weight: 700; }

.font-mono { font-family: monospace; }
.text-muted { color: #64748b; }
.text-primary { color: #2563eb; }
.text-danger { color: #dc2626; }
.text-success { color: #16a34a; }
</style>
