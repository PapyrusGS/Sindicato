<template>
  <div class="cobros-page">
    <!-- Header Page Actions -->
    <div class="cobros-page__header">
      <div>
        <h1 class="cobros-page__title">Procesamiento de Cobros e Historial de Caja</h1>
        <p class="cobros-page__subtitle">Registro de cobros a choferes y seguimiento de transacciones realizadas</p>
      </div>

      <button class="btn btn-primary" @click="modalVisible = true" id="btn-nuevo-cobro">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;margin-right:6px;"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
        Registrar Nuevo Cobro
      </button>
    </div>

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
            <th>Quién Cobró (Cobrador)</th>
            <th>A Quién se Cobró (Chofer)</th>
            <th>Conceptos / Deudas Canceladas</th>
            <th>Método Pago</th>
            <th>Monto Total (Bs.)</th>
            <th>Fecha y Hora</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in pagos" :key="p.id">
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
              <strong class="text-success font-mono" style="font-size:1rem;">
                Bs. {{ Number(p.monto_total).toFixed(2) }}
              </strong>
            </td>
            <td>
              <div class="date-cell">
                <span>📅 {{ formatDate(p.fecha_pago) }}</span>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-else class="empty-state">
        <p>No se encontraron cobros ni transacciones registradas con el filtro seleccionado.</p>
      </div>
    </div>

    <!-- Modal Form (Registrar Cobro) -->
    <FormCobroModal
      v-if="modalVisible"
      :visible="modalVisible"
      @close="modalVisible = false"
      @saved="onCobroGuardado"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { cobroApi } from '@/api/cobroApi'
import AppLoader from '@/components/common/AppLoader.vue'
import FormCobroModal from '@/components/tesoreria/FormCobroModal.vue'

const loading = ref(false)
const pagos = ref([])
const searchQuery = ref('')
const filtroMetodo = ref('todos')

const modalVisible = ref(false)
let searchTimeout = null

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

function onCobroGuardado() {
  modalVisible.value = false
  cargarHistorial()
}

function formatDate(dateStr) {
  if (!dateStr) return 'N/A'
  const d = new Date(dateStr)
  return d.toLocaleString('es-BO', { dateStyle: 'short', timeStyle: 'short' })
}

onMounted(() => {
  cargarHistorial()
})
</script>

<style scoped>
.cobros-page__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}
.cobros-page__title { font-size: 1.6rem; font-weight: 800; color: var(--color-text-primary); }
.cobros-page__subtitle { font-size: 0.85rem; color: var(--color-text-secondary); }

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
.filter-tab--active {
  background: var(--color-primary-600);
  color: white;
}

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

.empty-state { padding: 3rem; text-align: center; color: var(--color-text-muted); font-size: 0.9rem; }
</style>
