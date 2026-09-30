<template>
  <div class="cobros-page">
    <!-- Header Page Actions -->
    <div class="cobros-page__header">
      <div>
        <h1 class="cobros-page__title">Procesamiento de Cobros e Historial de Caja</h1>
        <p class="cobros-page__subtitle">Registro de cobros a choferes, control de ventana de gracia (1:30 min) y solicitudes de cambio</p>
      </div>

      <button class="btn btn-primary" @click="modalVisible = true" id="btn-nuevo-cobro">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;margin-right:6px;"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
        Registrar Nuevo Cobro
      </button>
    </div>

    <!-- Alert / Toast Banner -->
    <transition name="fade">
      <div v-if="toastMsg" class="toast-banner" :class="toastMsg.tipo === 'success' ? 'toast-banner--success' : 'toast-banner--error'">
        <span>{{ toastMsg.texto }}</span>
        <button class="toast-close" @click="toastMsg = null">&times;</button>
      </div>
    </transition>

    <!-- Grace Period Undo Banner (Aparece tras registrar un cobro si está dentro de 90s) -->
    <transition name="fade">
      <div v-if="ultimoCobroGuardado && segundosRestantes(ultimoCobroGuardado) > 0" class="grace-banner">
        <div class="grace-banner__content">
          <span class="grace-banner__icon">⏱️</span>
          <div>
            <strong>¡Cobro #{{ ultimoCobroGuardado.id }} registrado exitosamente!</strong>
            <p>
              ¿Te equivocaste de chofer? Tienes
              <span class="grace-timer-highlight font-mono">{{ formatSegundos(segundosRestantes(ultimoCobroGuardado)) }}</span>
              para <strong>anular directamente el registro</strong> y restablecer las deudas sin requerir autorizaciones.
            </p>
          </div>
        </div>

        <div class="grace-banner__actions">
          <button
            class="btn btn-undo-grace"
            @click="anularCobroDirectamente(ultimoCobroGuardado)"
            :disabled="anulandoId === ultimoCobroGuardado.id"
            id="btn-undo-grace"
          >
            <span v-if="anulandoId === ultimoCobroGuardado.id">Anulando...</span>
            <span v-else>↺ Deshacer / Anular Cobro Ahora</span>
          </button>
          <button class="btn-dismiss-grace" @click="ultimoCobroGuardado = null" title="Ocultar aviso">&times;</button>
        </div>
      </div>
    </transition>

    <!-- Search & Filter Bar -->
    <div class="cobros-page__filters">
      <div class="search-box">
        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input
          v-model="searchQuery"
          @input="debouncedSearch"
          type="text"
          placeholder="Buscar por chofer, CI o cobrador..."
          class="form-control search-input"
        />
      </div>

      <div class="filter-tabs">
        <button
          class="filter-tab"
          :class="{ 'filter-tab--active': filtroMetodo === 'todos' }"
          @click="setFiltroMetodo('todos')"
        >
          Todos los Métodos
        </button>
        <button
          class="filter-tab"
          :class="{ 'filter-tab--active': filtroMetodo === 'EFECTIVO' }"
          @click="setFiltroMetodo('EFECTIVO')"
        >
          💵 Efectivo
        </button>
        <button
          class="filter-tab"
          :class="{ 'filter-tab--active': filtroMetodo === 'TRANSFERENCIA_QR' }"
          @click="setFiltroMetodo('TRANSFERENCIA_QR')"
        >
          📱 Transferencia QR
        </button>
      </div>
    </div>

    <!-- Data Table of Payments History -->
    <div class="cobros-page__table-wrapper table-container">
      <AppLoader v-if="loading" :visible="true" message="Cargando historial de cobros..." style="padding: 3rem 0;" />

      <table v-else-if="pagos.length > 0" class="data-table">
        <thead>
          <tr>
            <th>Quién Cobró</th>
            <th>A Quién se Cobró</th>
            <th>Conceptos Cancelados</th>
            <th>Método</th>
            <th>Monto Total</th>
            <th>Fecha / Hora</th>
            <th style="text-align: center;">Estado & Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in pagos" :key="p.id" :class="{ 'row-anulado': !p.estado }">
            <td>
              <div class="user-cell">
                <span class="user-name"><strong>👮 {{ p.cobrador_persona?.nombre_completo || 'Sistema' }}</strong></span>
                <small class="user-sub">CI: {{ p.cobrador_persona?.ci }}</small>
              </div>
            </td>
            <td>
              <div class="user-cell">
                <span class="user-name"><strong>👤 {{ p.chofer?.persona?.nombre_completo || 'N/A' }}</strong></span>
                <small class="user-sub">CI: {{ p.chofer?.persona?.ci }}</small>
              </div>
            </td>
            <td>
              <div class="conceptos-list">
                <!-- Obligaciones canceladas -->
                <div v-for="po in p.pago_obligaciones" :key="'po-' + po.id" class="concepto-tag">
                  📅 {{ po.obligacion_chofer?.obligacion?.concepto || 'Cuota de grupo' }}
                  <small>(Bs. {{ Number(po.monto_abonado).toFixed(2) }})</small>
                </div>
                <!-- Multas canceladas -->
                <div v-for="pm in p.pago_multas" :key="'pm-' + pm.id" class="concepto-tag concepto-tag--multa">
                  💰 {{ pm.multa?.motivo || 'Multa económica' }}
                  <small>(Bs. {{ Number(pm.monto_abonado).toFixed(2) }})</small>
                </div>
              </div>
            </td>
            <td>
              <span :class="p.metodo_pago === 'EFECTIVO' ? 'badge badge-success' : 'badge badge-admin'">
                {{ p.metodo_pago === 'EFECTIVO' ? '💵 Efectivo' : '📱 Transferencia QR' }}
              </span>
            </td>
            <td>
              <strong :class="p.estado ? 'text-success' : 'text-muted'" class="font-mono" style="font-size:1rem;">
                Bs. {{ Number(p.monto_total).toFixed(2) }}
              </strong>
            </td>
            <td>
              <div class="date-cell">
                <span>📅 {{ formatDate(p.fecha_pago || p.created_at) }}</span>
              </div>
            </td>
            <td style="text-align: center;">
              <!-- 1. Cobro ya anulado -->
              <div v-if="!p.estado" class="status-cell">
                <span class="badge badge-anulado">⛔ Anulado</span>
                <small v-if="p.observacion" class="status-obs" :title="p.observacion">
                  {{ p.observacion }}
                </small>
              </div>

              <!-- 2. Cobro con solicitud de cambio pendiente -->
              <div v-else-if="tieneSolicitudPendiente(p)" class="status-cell">
                <span class="badge badge-warning" title="El chofer debe aceptar o denegar la solicitud">
                  ⏳ Solicitud Enviada
                </span>
                <small class="text-muted" style="font-size:0.7rem;">Esperando chofer</small>
              </div>

              <!-- 3. Cobro dentro de la ventana de gracia (<= 90s) -->
              <div v-else-if="puedeAnularDirecto(p)" class="status-cell">
                <span class="badge badge-gracia font-mono">
                  ⏱️ {{ formatSegundos(segundosRestantes(p)) }}
                </span>
                <button
                  class="btn-action-undo"
                  @click="anularCobroDirectamente(p)"
                  :disabled="anulandoId === p.id"
                  title="Anular directamente sin autorización (ventana de gracia)"
                >
                  <span v-if="anulandoId === p.id">...</span>
                  <span v-else>↺ Anular Directo</span>
                </button>
              </div>

              <!-- 4. Cobro fuera del tiempo de gracia (> 90s) -> Solicitar Cambio con Notificación -->
              <div v-else class="status-cell">
                <span class="badge badge-success" style="margin-bottom:0.25rem;">✓ Vigente</span>
                <button
                  class="btn-action-request"
                  @click="abrirModalSolicitarCambio(p)"
                  title="Solicitar anulación o cambio notificando al chofer y jefe de grupo"
                >
                  📢 Solicitar Cambio
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-else class="empty-state">
        <p>No se encontraron cobros ni transacciones registradas con el filtro seleccionado.</p>
      </div>
    </div>

    <!-- Modal Registrar Cobro -->
    <FormCobroModal
      v-if="modalVisible"
      :visible="modalVisible"
      @close="modalVisible = false"
      @saved="onCobroGuardado"
    />

    <!-- Modal Solicitar Cambio (> 90s) -->
    <ModalSolicitarCambio
      v-if="modalSolicitudVisible"
      :visible="modalSolicitudVisible"
      :pago="pagoParaSolicitud"
      @close="modalSolicitudVisible = false"
      @saved="onSolicitudEnviada"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { cobroApi } from '@/api/cobroApi'
import AppLoader from '@/components/common/AppLoader.vue'
import FormCobroModal from '@/components/tesoreria/FormCobroModal.vue'
import ModalSolicitarCambio from '@/components/tesoreria/ModalSolicitarCambio.vue'

const loading = ref(false)
const pagos = ref([])
const searchQuery = ref('')
const filtroMetodo = ref('todos')

const modalVisible = ref(false)
const modalSolicitudVisible = ref(false)
const pagoParaSolicitud = ref(null)

const ultimoCobroGuardado = ref(null)
const anulandoId = ref(null)
const toastMsg = ref(null)

const nowTick = ref(Date.now())
let tickerInterval = null
let searchTimeout = null

// ─── Helpers de Ventana de Gracia (90s) ──────────────────────────────

function segundosRestantes(pago) {
  if (!pago || !pago.estado || !pago.created_at) return 0
  const createdMs = new Date(pago.created_at).getTime()
  const diffSec = Math.floor((nowTick.value - createdMs) / 1000)
  return Math.max(0, 90 - diffSec)
}

function puedeAnularDirecto(pago) {
  return pago.estado && segundosRestantes(pago) > 0
}

function formatSegundos(seg) {
  const m = Math.floor(seg / 60)
  const s = seg % 60
  return `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`
}

function tieneSolicitudPendiente(pago) {
  if (!pago.solicitudes_cambio || pago.solicitudes_cambio.length === 0) return false
  return pago.solicitudes_cambio.some(s => s.estado === 'PENDIENTE')
}

// ─── Operaciones de Cobro e Historial ────────────────────────────────

async function cargarHistorial() {
  loading.value = true
  try {
    const params = {}
    if (searchQuery.value) params.q = searchQuery.value
    if (filtroMetodo.value !== 'todos') {
      params.metodo_pago = filtroMetodo.value
    }

    const res = await cobroApi.obtenerHistorial(params)
    pagos.value = res.data.data.pagos || []
  } catch (err) {
    console.error('Error al cargar historial de cobros:', err)
  } finally {
    loading.value = false
  }
}

function debouncedSearch() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    cargarHistorial()
  }, 300)
}

function setFiltroMetodo(metodo) {
  filtroMetodo.value = metodo
  cargarHistorial()
}

function onCobroGuardado(nuevoPago) {
  modalVisible.value = false
  if (nuevoPago) {
    ultimoCobroGuardado.value = nuevoPago
  }
  toastMsg.value = { tipo: 'success', texto: 'Cobro registrado correctamente en caja.' }
  cargarHistorial()
}

async function anularCobroDirectamente(pago) {
  if (!confirm(`¿Confirmas la anulación directa del cobro #${pago.id} de Bs. ${Number(pago.monto_total).toFixed(2)}? Las deudas del chofer volverán a estar pendientes.`)) {
    return
  }

  anulandoId.value = pago.id
  try {
    const res = await cobroApi.anularInmediato(pago.id)
    toastMsg.value = { tipo: 'success', texto: res.data.message || 'Cobro anulado exitosamente.' }
    if (ultimoCobroGuardado.value?.id === pago.id) {
      ultimoCobroGuardado.value = null
    }
    cargarHistorial()
  } catch (err) {
    toastMsg.value = { tipo: 'error', texto: err.response?.data?.message || 'Error al anular cobro.' }
  } finally {
    anulandoId.value = null
  }
}

function abrirModalSolicitarCambio(pago) {
  pagoParaSolicitud.value = pago
  modalSolicitudVisible.value = true
}

function onSolicitudEnviada() {
  toastMsg.value = {
    tipo: 'success',
    texto: 'Solicitud de cambio enviada. El Chofer y el Jefe de Grupo han sido notificados.',
  }
  cargarHistorial()
}

function formatDate(dateStr) {
  if (!dateStr) return 'N/A'
  const d = new Date(dateStr)
  return d.toLocaleString('es-BO', { dateStyle: 'short', timeStyle: 'short' })
}

onMounted(() => {
  cargarHistorial()
  tickerInterval = setInterval(() => {
    nowTick.value = Date.now()
  }, 1000)
})

onUnmounted(() => {
  if (tickerInterval) clearInterval(tickerInterval)
})
</script>

<style scoped>
.cobros-page__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.25rem;
}
.cobros-page__title { font-size: 1.6rem; font-weight: 800; color: var(--color-text-primary); }
.cobros-page__subtitle { font-size: 0.85rem; color: var(--color-text-secondary); }

/* Grace Period Banner */
.grace-banner {
  background: #fef3c7;
  border: 1px solid #f59e0b;
  border-radius: var(--radius-xl);
  padding: 1rem 1.25rem;
  margin-bottom: 1.25rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  box-shadow: 0 4px 6px -1px rgba(245, 158, 11, 0.1);
  animation: fadeIn 0.3s ease;
}
.grace-banner__content { display: flex; align-items: flex-start; gap: 0.75rem; color: #78350f; font-size: 0.85rem; }
.grace-banner__icon { font-size: 1.5rem; line-height: 1; }
.grace-timer-highlight {
  background: #b45309; color: #ffffff; padding: 0.15rem 0.45rem;
  border-radius: var(--radius-sm); font-weight: 800; font-size: 0.85rem;
}

.grace-banner__actions { display: flex; align-items: center; gap: 0.75rem; }
.btn-undo-grace {
  background: #dc2626; color: white; border: none; padding: 0.5rem 1rem;
  border-radius: var(--radius-md); font-size: 0.85rem; font-weight: 700;
  cursor: pointer; transition: background var(--transition-fast); white-space: nowrap;
}
.btn-undo-grace:hover { background: #b91c1c; }
.btn-dismiss-grace {
  background: none; border: none; font-size: 1.4rem; color: #92400e; cursor: pointer; padding: 0.2rem;
}

/* Toast Banner */
.toast-banner {
  padding: 0.75rem 1.25rem; border-radius: var(--radius-lg); margin-bottom: 1.25rem;
  display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem; font-weight: 600;
}
.toast-banner--success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
.toast-banner--error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
.toast-close { background: none; border: none; font-size: 1.2rem; cursor: pointer; color: inherit; }

/* Filters */
.cobros-page__filters {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.25rem;
}
.search-box { position: relative; width: 340px; }
.search-icon { position: absolute; left: 0.85rem; top: 50%; transform: translateY(-50%); width: 18px; height: 18px; color: var(--color-text-muted); }
.search-input { padding-left: 2.5rem; }

.filter-tabs {
  display: flex;
  background: #ffffff;
  border: 1px solid var(--color-border);
  padding: 0.2rem;
  border-radius: var(--radius-lg);
}
.filter-tab {
  padding: 0.45rem 0.9rem;
  font-size: 0.85rem;
  font-weight: 600;
  border: none;
  background: none;
  color: var(--color-text-secondary);
  border-radius: var(--radius-md);
  cursor: pointer;
}
.filter-tab--active { background: var(--color-primary-600); color: white; }

/* Table */
.user-cell { display: flex; flex-direction: column; }
.user-name { color: var(--color-text-primary); font-size: 0.85rem; }
.user-sub { font-size: 0.75rem; color: var(--color-text-muted); }

.conceptos-list { display: flex; flex-direction: column; gap: 0.25rem; }
.concepto-tag {
  font-size: 0.75rem; background: var(--color-bg-tertiary); padding: 0.2rem 0.5rem;
  border-radius: var(--radius-sm); border: 1px solid var(--color-border); color: var(--color-text-primary);
}
.concepto-tag--multa { background: var(--color-error-bg); border-color: #fecaca; color: var(--color-error); }

.date-cell { font-size: 0.8rem; color: var(--color-text-secondary); }

.row-anulado { opacity: 0.65; background-color: #f8fafc; }
.row-anulado td { text-decoration: line-through; text-decoration-color: #ef4444; }
.row-anulado td:last-child { text-decoration: none; }

.status-cell { display: flex; flex-direction: column; align-items: center; gap: 0.35rem; }

.badge-anulado { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; font-size: 0.75rem; }
.status-obs { font-size: 0.65rem; color: #94a3b8; max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

.badge-gracia {
  background: #fef3c7; color: #b45309; border: 1px solid #fde68a; font-size: 0.75rem; font-weight: 800;
}

.btn-action-undo {
  background: #ef4444; color: white; border: none; font-size: 0.75rem; font-weight: 700;
  padding: 0.25rem 0.6rem; border-radius: var(--radius-sm); cursor: pointer;
  transition: opacity var(--transition-fast);
}
.btn-action-undo:hover { opacity: 0.9; }

.btn-action-request {
  background: #f59e0b; color: white; border: none; font-size: 0.725rem; font-weight: 700;
  padding: 0.25rem 0.55rem; border-radius: var(--radius-sm); cursor: pointer;
  transition: background var(--transition-fast);
}
.btn-action-request:hover { background: #d97706; }

.empty-state { padding: 3rem; text-align: center; color: var(--color-text-muted); font-size: 0.9rem; }
</style>
