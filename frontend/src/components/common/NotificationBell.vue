<template>
  <div class="notif-wrapper" ref="wrapperRef">
    <!-- Bell Button -->
    <button
      class="notif-btn"
      :class="{ 'notif-btn--active': isOpen }"
      @click="toggleDropdown"
      title="Notificaciones y Solicitudes"
      id="btn-notificaciones"
    >
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="bell-icon">
        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
        <path d="M13.73 21a2 2 0 0 1-3.46 0" />
      </svg>

      <!-- Badge Counter -->
      <span v-if="noLeidasCount > 0" class="notif-badge">
        {{ noLeidasCount > 9 ? '9+' : noLeidasCount }}
      </span>
    </button>

    <!-- Dropdown Menu -->
    <transition name="dropdown">
      <div v-if="isOpen" class="notif-dropdown">
        <!-- Header -->
        <div class="notif-header">
          <div class="notif-header__title">
            <span class="font-bold">Notificaciones</span>
            <span v-if="noLeidasCount > 0" class="badge badge-danger">
              {{ noLeidasCount }} nueva{{ noLeidasCount > 1 ? 's' : '' }}
            </span>
          </div>
          <button
            v-if="noLeidasCount > 0"
            class="btn-mark-all"
            @click="marcarTodasLeidas"
          >
            ✓ Marcar leídas
          </button>
        </div>

        <!-- Notification List -->
        <div class="notif-list">
          <div v-if="loading && notificaciones.length === 0" class="notif-loading">
            Cargando notificaciones...
          </div>

          <template v-else-if="notificaciones.length > 0">
            <div
              v-for="item in notificaciones"
              :key="item.id"
              class="notif-item"
              :class="{
                'notif-item--unread': !item.leido,
                'notif-item--solicitud': item.tipo === 'SOLICITUD_CAMBIO_PAGO',
              }"
              @click="onItemClick(item)"
            >
              <div class="notif-item__icon">
                <span v-if="item.tipo === 'SOLICITUD_CAMBIO_PAGO'">⚠️</span>
                <span v-else-if="item.tipo === 'ALERTA'">🚨</span>
                <span v-else-if="item.tipo === 'INFO'">📢</span>
                <span v-else>🔔</span>
              </div>

              <div class="notif-item__content">
                <div class="notif-item__header">
                  <span class="notif-item__title">{{ item.titulo }}</span>
                  <small class="notif-item__time">{{ formatTimeAgo(item.created_at) }}</small>
                </div>

                <p class="notif-item__message">{{ item.mensaje }}</p>

                <!-- Action Button for Solicitud Cambio Pago -->
                <div v-if="item.tipo === 'SOLICITUD_CAMBIO_PAGO' && item.data?.solicitud_id" class="notif-action-box">
                  <button
                    class="btn-action-solicitud"
                    @click.stop="abrirModalResolucion(item)"
                    id="btn-revisar-solicitud"
                  >
                    🛡️ Revisar y Responder Solicitud
                  </button>
                </div>
              </div>
            </div>
          </template>

          <div v-else class="notif-empty">
            <span class="empty-icon">🔕</span>
            <p>No tienes notificaciones pendientes.</p>
          </div>
        </div>
      </div>
    </transition>

    <!-- Modal Resolver Solicitud (Aceptar / Denegar) -->
    <ModalResolverSolicitud
      v-if="modalResolverVisible"
      :visible="modalResolverVisible"
      :solicitud="solicitudSeleccionada"
      @close="modalResolverVisible = false"
      @resolved="onSolicitudResuelta"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { notificacionApi } from '@/api/notificacionApi'
import { cobroApi } from '@/api/cobroApi'
import ModalResolverSolicitud from '@/components/tesoreria/ModalResolverSolicitud.vue'

const isOpen = ref(false)
const wrapperRef = ref(null)
const loading = ref(false)
const notificaciones = ref([])
const noLeidasCount = ref(0)

const modalResolverVisible = ref(false)
const solicitudSeleccionada = ref(null)

let pollingTimer = null

async function cargarNotificaciones() {
  try {
    const res = await notificacionApi.obtenerNotificaciones()
    notificaciones.value = res.data.data.notificaciones || []
    noLeidasCount.value = res.data.data.no_leidas_count || 0
  } catch (err) {
    // Silencioso en polling de fondo
  }
}

function toggleDropdown() {
  isOpen.value = !isOpen.value
  if (isOpen.value) {
    cargarNotificaciones()
  }
}

async function onItemClick(item) {
  if (!item.leido) {
    try {
      await notificacionApi.marcarLeida(item.id)
      item.leido = true
      noLeidasCount.value = Math.max(0, noLeidasCount.value - 1)
    } catch (err) {
      console.error(err)
    }
  }

  if (item.tipo === 'SOLICITUD_CAMBIO_PAGO') {
    abrirModalResolucion(item)
  }
}

async function abrirModalResolucion(item) {
  const solicitudId = item.data?.solicitud_id
  if (!solicitudId) return

  try {
    // Cargar la solicitud fresca desde pendientes
    const res = await cobroApi.obtenerSolicitudesPendientes()
    const pendientes = res.data.data || []
    const match = pendientes.find(s => s.id === solicitudId)

    if (match) {
      solicitudSeleccionada.value = match
      modalResolverVisible.value = true
      isOpen.value = false
    } else {
      alert('Esta solicitud ya fue resuelta o no requiere acción adicional.')
      cargarNotificaciones()
    }
  } catch (err) {
    console.error('Error al abrir solicitud:', err)
  }
}

function onSolicitudResuelta() {
  cargarNotificaciones()
}

async function marcarTodasLeidas() {
  try {
    await notificacionApi.marcarTodasLeidas()
    notificaciones.value.forEach(n => (n.leido = true))
    noLeidasCount.value = 0
  } catch (err) {
    console.error(err)
  }
}

function formatTimeAgo(dateStr) {
  if (!dateStr) return ''
  const diffSec = Math.floor((new Date() - new Date(dateStr)) / 1000)
  if (diffSec < 60) return 'Hace un momento'
  if (diffSec < 3600) return `Hace ${Math.floor(diffSec / 60)} min`
  if (diffSec < 86400) return `Hace ${Math.floor(diffSec / 3600)} h`
  return new Date(dateStr).toLocaleDateString('es-BO', { month: 'short', day: 'numeric' })
}

function handleClickOutside(event) {
  if (wrapperRef.value && !wrapperRef.value.contains(event.target)) {
    isOpen.value = false
  }
}

onMounted(() => {
  cargarNotificaciones()
  document.addEventListener('click', handleClickOutside)
  // Polling cada 20 segundos
  pollingTimer = setInterval(cargarNotificaciones, 20000)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
  if (pollingTimer) clearInterval(pollingTimer)
})
</script>

<style scoped>
.notif-wrapper { position: relative; }

.notif-btn {
  background: none; border: 1px solid var(--color-border); border-radius: var(--radius-lg);
  padding: 0.45rem; color: var(--color-text-secondary); cursor: pointer; position: relative;
  display: flex; align-items: center; justify-content: center;
  transition: all var(--transition-fast);
}
.notif-btn:hover, .notif-btn--active {
  background: var(--color-bg-tertiary); color: var(--color-primary-600);
  border-color: var(--color-primary-300);
}
.bell-icon { width: 20px; height: 20px; }

.notif-badge {
  position: absolute; top: -5px; right: -5px;
  background: #ef4444; color: white; font-size: 0.65rem; font-weight: 800;
  border-radius: 9999px; min-width: 18px; height: 18px; padding: 0 4px;
  display: flex; align-items: center; justify-content: center;
  border: 2px solid #ffffff; animation: pulse 2s infinite;
}

@keyframes pulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.1); }
}

.notif-dropdown {
  position: absolute; top: calc(100% + 10px); right: 0;
  width: 360px; max-width: 90vw; background: #ffffff;
  border: 1px solid var(--color-border); border-radius: var(--radius-2xl);
  box-shadow: 0 20px 25px -5px rgba(0,0,0,0.15), 0 10px 10px -5px rgba(0,0,0,0.04);
  display: flex; flex-direction: column; overflow: hidden; z-index: 100;
}

.notif-header {
  padding: 0.85rem 1.15rem; border-bottom: 1px solid var(--color-border);
  display: flex; justify-content: space-between; align-items: center; background: var(--color-bg-tertiary);
}
.notif-header__title { display: flex; align-items: center; gap: 0.5rem; font-size: 0.9rem; }
.btn-mark-all {
  background: none; border: none; font-size: 0.75rem; color: var(--color-primary-600);
  font-weight: 600; cursor: pointer;
}
.btn-mark-all:hover { text-decoration: underline; }

.notif-list { max-height: 380px; overflow-y: auto; display: flex; flex-direction: column; }

.notif-item {
  display: flex; gap: 0.75rem; padding: 0.85rem 1.15rem; border-bottom: 1px solid var(--color-border);
  cursor: pointer; transition: background var(--transition-fast); text-align: left;
}
.notif-item:hover { background: var(--color-bg-tertiary); }
.notif-item--unread { background: #f0fdf4; }
.notif-item--solicitud.notif-item--unread { background: #fffbeb; }

.notif-item__icon { font-size: 1.25rem; flex-shrink: 0; margin-top: 0.1rem; }
.notif-item__content { flex: 1; display: flex; flex-direction: column; gap: 0.2rem; }
.notif-item__header { display: flex; justify-content: space-between; align-items: baseline; }
.notif-item__title { font-size: 0.825rem; font-weight: 700; color: var(--color-text-primary); }
.notif-item__time { font-size: 0.7rem; color: var(--color-text-muted); }
.notif-item__message { font-size: 0.775rem; color: var(--color-text-secondary); line-height: 1.35; margin: 0; }

.notif-action-box { margin-top: 0.4rem; }
.btn-action-solicitud {
  background: #f59e0b; color: white; border: none; font-size: 0.725rem; font-weight: 700;
  padding: 0.35rem 0.65rem; border-radius: var(--radius-md); cursor: pointer;
  display: inline-flex; align-items: center; gap: 0.35rem; transition: background var(--transition-fast);
}
.btn-action-solicitud:hover { background: #d97706; }

.notif-empty { padding: 2.5rem 1rem; text-align: center; color: var(--color-text-muted); font-size: 0.85rem; }
.empty-icon { font-size: 2rem; display: block; margin-bottom: 0.5rem; opacity: 0.6; }
.notif-loading { padding: 2rem; text-align: center; color: var(--color-text-muted); font-size: 0.85rem; }
</style>
