<template>
  <div class="sanciones-page">
    <!-- Header Page Actions -->
    <div class="sanciones-page__header">
      <div>
        <h1 class="sanciones-page__title">Registro de Sanciones e Infracciones</h1>
        <p class="sanciones-page__subtitle">Historial de sanciones económicas y castigos operativos impuestos por inspectores</p>
      </div>

      <button class="btn btn-primary" @click="abrirNuevoModal" id="btn-nueva-sancion">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;margin-right:6px;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Imponer Nueva Sanción
      </button>
    </div>

    <!-- Search & Filter Bar -->
    <div class="sanciones-page__filters">
      <div class="search-box">
        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input
          v-model="searchQuery"
          @input="debouncedSearch"
          type="text"
          placeholder="Buscar por motivo, chofer o CI..."
          class="form-control search-input"
        />
      </div>

      <div class="filter-tabs">
        <button
          class="filter-tab"
          :class="{ 'filter-tab--active': filtroTipo === 'todas' }"
          @click="setFiltroTipo('todas')"
        >
          Todas
        </button>
        <button
          class="filter-tab"
          :class="{ 'filter-tab--active': filtroTipo === 'CASTIGO' }"
          @click="setFiltroTipo('CASTIGO')"
        >
          Castigos Operativos
        </button>
        <button
          class="filter-tab"
          :class="{ 'filter-tab--active': filtroTipo === 'ECONOMICA' }"
          @click="setFiltroTipo('ECONOMICA')"
        >
          Sanciones Económicas
        </button>
        <button
          class="filter-tab"
          :class="{ 'filter-tab--active': filtroTipo === 'PENDIENTE' }"
          @click="setFiltroTipo('PENDIENTE')"
        >
          Pendientes de Pago
        </button>
      </div>
    </div>

    <!-- Data Table -->
    <div class="sanciones-page__table-wrapper table-container">
      <AppLoader v-if="loading" :visible="true" message="Cargando sanciones registradas..." style="padding: 3rem 0;" />

      <table v-else-if="sanciones.length > 0" class="data-table">
        <thead>
          <tr>
            <th>Chofer Sancionado</th>
            <th>Tipo de Sanción</th>
            <th>Motivo de Infracción / Castigo</th>
            <th>Monto (Bs.)</th>
            <th>Estado Pago</th>
            <th>Lugar / Inspector</th>
            <th>Fecha y Hora</th>
            <th style="text-align: right;">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="s in sanciones" :key="s.id">
            <td>
              <div class="user-cell">
                <span class="user-name"><strong>{{ s.chofer?.persona?.nombre_completo || 'N/A' }}</strong></span>
                <small class="user-sub">CI: {{ s.chofer?.persona?.ci }}</small>
              </div>
            </td>
            <td>
              <span :class="s.tipo_sancion === 'ECONOMICA' ? 'badge badge-tesorero' : 'badge badge-success'">
                {{ s.tipo_sancion === 'ECONOMICA' ? '💰 Económica' : '⏹️ Castigo Operativo' }}
              </span>
            </td>
            <td>
              <div class="sancion-info">
                <span class="sancion-motivo"><strong>{{ s.motivo }}</strong></span>
                <small class="sancion-detalle">{{ s.sancion_detalle }}</small>
              </div>
            </td>
            <td>
              <strong v-if="s.tipo_sancion === 'ECONOMICA'" class="text-danger">
                Bs. {{ Number(s.monto).toFixed(2) }}
              </strong>
              <span v-else class="text-muted" style="font-size:0.8rem;">—</span>
            </td>
            <td>
              <span :class="getPagoBadgeClass(s.estado_pago)">
                {{ getPagoLabel(s.estado_pago) }}
              </span>
            </td>
            <td>
              <div class="inspector-cell">
                <span>📍 {{ s.lugar?.nombre || 'En Ruta' }}</span>
                <small class="text-muted">👮 {{ s.inspector?.persona?.nombre_completo || 'Inspector' }}</small>
              </div>
            </td>
            <td>
              <div class="date-cell">
                <span>{{ formatDate(s.fecha_infraccion) }}</span>
              </div>
            </td>
            <td style="text-align: right;">
              <button
                class="action-btn action-btn--edit"
                @click="abrirEditarModal(s)"
                title="Editar / Corregir error en sanción (Se guardará registro de auditoría)"
                :id="`btn-editar-sancion-${s.id}`"
              >
                ✏️ Corregir
              </button>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-else class="empty-state">
        <p>No se encontraron sanciones registradas en la base de datos con el criterio seleccionado.</p>
      </div>
    </div>

    <!-- Modal Form (Imponer o Editar Sanción) -->
    <FormSancionModal
      v-if="modalVisible"
      :visible="modalVisible"
      :sancionEdit="sancionSeleccionada"
      @close="cerrarModal"
      @saved="onSancionGuardada"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { sancionApi } from '@/api/sancionApi'
import AppLoader from '@/components/common/AppLoader.vue'
import FormSancionModal from '@/components/asistencia/FormSancionModal.vue'

const loading = ref(false)
const sanciones = ref([])
const searchQuery = ref('')
const filtroTipo = ref('todas')

const modalVisible = ref(false)
const sancionSeleccionada = ref(null)

let searchTimeout = null

async function cargarSanciones() {
  loading.value = true
  try {
    const params = {}
    if (searchQuery.value) params.q = searchQuery.value
    if (filtroTipo.value === 'CASTIGO' || filtroTipo.value === 'ECONOMICA') {
      params.tipo_sancion = filtroTipo.value
    } else if (filtroTipo.value === 'PENDIENTE') {
      params.estado_pago = 'PENDIENTE'
    }

    const res = await sancionApi.listar(params)
    sanciones.value = res.data.data.sanciones || []
  } catch (err) {
    console.error('Error al cargar sanciones:', err)
  } finally {
    loading.value = false
  }
}

function debouncedSearch() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    cargarSanciones()
  }, 300)
}

function setFiltroTipo(filtro) {
  filtroTipo.value = filtro
  cargarSanciones()
}

function abrirNuevoModal() {
  sancionSeleccionada.value = null
  modalVisible.value = true
}

function abrirEditarModal(sancion) {
  sancionSeleccionada.value = sancion
  modalVisible.value = true
}

function cerrarModal() {
  modalVisible.value = false
  sancionSeleccionada.value = null
}

function onSancionGuardada() {
  cerrarModal()
  cargarSanciones()
}

function getPagoBadgeClass(estado) {
  if (estado === 'PENDIENTE') return 'badge badge-tesorero'
  if (estado === 'PAGADO') return 'badge badge-success'
  return 'badge badge-chofer'
}

function getPagoLabel(estado) {
  if (estado === 'PENDIENTE') return '⏳ Pendiente Cobro'
  if (estado === 'PAGADO') return '✓ Pagado'
  return '— N/A (Castigo)'
}

function formatDate(dateStr) {
  if (!dateStr) return 'N/A'
  const d = new Date(dateStr)
  return d.toLocaleString('es-BO', { dateStyle: 'short', timeStyle: 'short' })
}

onMounted(() => {
  cargarSanciones()
})
</script>

<style scoped>
.sanciones-page__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}
.sanciones-page__title { font-size: 1.6rem; font-weight: 800; color: var(--color-text-primary); }
.sanciones-page__subtitle { font-size: 0.85rem; color: var(--color-text-secondary); }

.sanciones-page__filters {
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
.filter-tab--active {
  background: var(--color-primary-600);
  color: white;
}

.user-cell { display: flex; flex-direction: column; }
.user-name { color: var(--color-text-primary); }
.user-sub { font-size: 0.75rem; color: var(--color-text-muted); }

.sancion-info { display: flex; flex-direction: column; }
.sancion-motivo { font-weight: 700; color: var(--color-text-primary); }
.sancion-detalle { font-size: 0.75rem; color: var(--color-text-secondary); }

.inspector-cell { display: flex; flex-direction: column; font-size: 0.8rem; }
.date-cell { font-size: 0.8rem; color: var(--color-text-secondary); }

.action-btn { font-size: 0.75rem; font-weight: 600; padding: 0.35rem 0.6rem; border-radius: var(--radius-md); border: 1px solid var(--color-border); background: #ffffff; cursor: pointer; }
.action-btn--edit:hover { background: var(--color-primary-50); border-color: var(--color-primary-300); color: var(--color-primary-800); }

.empty-state { padding: 3rem; text-align: center; color: var(--color-text-muted); font-size: 0.9rem; }
</style>
