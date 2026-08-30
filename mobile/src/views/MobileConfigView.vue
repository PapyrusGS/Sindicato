<template>
  <div class="mobile-view">
    <div class="view-header">
      <h2>⚙️ Configuración & Estado Móvil</h2>
      <p class="text-muted">Parámetros de conexión al servidor y estado de sincronización offline</p>
    </div>

    <div class="view-body">
      <!-- ─── SECCIÓN 1: ESTADO DE RED & SINCRONIZACIÓN ───────────────── -->
      <div class="m-card">
        <h3 class="section-title">📡 Estado de Red y Servidor Backend</h3>

        <div class="status-box" :class="online ? 'status-online' : 'status-offline'">
          <span>{{ online ? '🟢 Servidor Conectado y En Línea' : '🔴 Sin Conexión con el Servidor API' }}</span>
          <button class="btn btn-sm" @click="probarConexion">Probar Re-conexión</button>
        </div>

        <div class="form-group" style="margin-top: 1rem;">
          <label class="form-label">Dirección del Servidor API (Backend)</label>
          <input
            v-model="apiUrl"
            type="text"
            class="form-input font-mono"
            placeholder="http://localhost:8000/api"
            @change="guardarApiUrl"
          />
          <small class="text-muted">Servidor Local: http://localhost:8000/api o IP local de su red (ej. http://192.168.1.50:8000/api)</small>
        </div>
      </div>

      <!-- ─── SECCIÓN 2: GESTOR DE ASISTENCIAS OFFLINE ───────────────── -->
      <div class="m-card">
        <h3 class="section-title">💾 Memoria Local & Asistencias Sin Enviar</h3>

        <div class="offline-status">
          <div>
            <strong>{{ pendientesCount }} Registro(s) Pendiente(s)</strong>
            <p class="text-muted" style="font-size:0.8rem;">Asistencias guardadas en el celular en modo offline</p>
          </div>
          <button class="btn btn-warning" :disabled="pendientesCount === 0 || syncing" @click="sincronizar">
            <span v-if="syncing">Sincronizando...</span>
            <span v-else>🔄 Sincronizar Ahora</span>
          </button>
        </div>

        <div v-if="syncFeedback" class="alert alert-info" style="margin-top:0.75rem;">
          {{ syncFeedback }}
        </div>
      </div>

      <!-- ─── SECCIÓN 3: SESIÓN DE USUARIO & CERRAR SESIÓN ───────────── -->
      <div class="m-card">
        <h3 class="section-title">👤 Datos de Sesión Activa</h3>
        <div v-if="user" class="user-box">
          <div class="info-row"><span>Nombre:</span> <strong>{{ user.persona?.nombre_completo || user.username }}</strong></div>
          <div class="info-row"><span>Usuario:</span> <strong class="font-mono">@{{ user.username }}</strong></div>
          <div class="info-row">
            <span>Roles:</span>
            <span>
              <small v-for="r in user.roles" :key="r" class="role-badge">{{ r }}</small>
            </span>
          </div>
        </div>

        <button class="btn btn-danger btn-block btn-lg" style="margin-top: 1.5rem;" @click="logout">
          🔒 Cerrar Sesión Móvil
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { mobileStorage } from '@/services/storage'
import { offlineSyncService } from '@/services/offlineSync'

const emit = defineEmits(['logout'])

const apiUrl = ref(mobileStorage.getApiUrl())
const user = ref(mobileStorage.getUser())
const online = ref(true)
const pendientesCount = ref(offlineSyncService.getPendientesCount())
const syncing = ref(false)
const syncFeedback = ref('')

function guardarApiUrl() {
  mobileStorage.setApiUrl(apiUrl.value)
  apiUrl.value = mobileStorage.getApiUrl()
  probarConexion()
}

async function probarConexion() {
  online.value = await offlineSyncService.probarConexion()
}

async function sincronizar() {
  syncing.value = true
  syncFeedback.value = ''
  try {
    const res = await offlineSyncService.sincronizarPendientes()
    pendientesCount.value = offlineSyncService.getPendientesCount()
    syncFeedback.value = res.message
  } catch (err) {
    syncFeedback.value = 'Error al sincronizar datos.'
  } finally {
    syncing.value = false
  }
}

function logout() {
  mobileStorage.clearAll()
  emit('logout')
}

onMounted(() => {
  probarConexion()
  pendientesCount.value = offlineSyncService.getPendientesCount()
})
</script>

<style scoped>
.mobile-view { padding: 1rem; padding-bottom: 5rem; }
.view-header { margin-bottom: 1rem; }
.view-header h2 { font-size: 1.3rem; font-weight: 800; }

.m-card { background: #ffffff; border: 1px solid #cbd5e1; border-radius: 0.8rem; padding: 1rem; margin-bottom: 1rem; }
.section-title { font-size: 0.95rem; font-weight: 800; color: #1e293b; margin-bottom: 0.75rem; }

.status-box { padding: 0.75rem 1rem; border-radius: 0.6rem; display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem; font-weight: 700; }
.status-online { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }
.status-offline { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }

.offline-status { display: flex; justify-content: space-between; align-items: center; }

.form-group { margin-bottom: 0.75rem; }
.form-label { display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.25rem; }
.form-input { width: 100%; padding: 0.6rem 0.8rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; font-size: 0.9rem; }

.info-row { display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem; margin-bottom: 0.4rem; }
.role-badge { font-size: 0.7rem; background: #e2e8f0; padding: 0.2rem 0.4rem; border-radius: 0.3rem; margin-left: 0.2rem; }

.btn { padding: 0.6rem 1rem; font-size: 0.85rem; font-weight: 700; border-radius: 0.6rem; border: none; cursor: pointer; }
.btn-sm { padding: 0.3rem 0.6rem; font-size: 0.75rem; background: #ffffff; border: 1px solid #cbd5e1; }
.btn-warning { background: #ea580c; color: white; }
.btn-danger { background: #dc2626; color: white; }
.btn-block { width: 100%; }
.alert-info { background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; padding: 0.6rem; border-radius: 0.5rem; font-size: 0.8rem; }
</style>
