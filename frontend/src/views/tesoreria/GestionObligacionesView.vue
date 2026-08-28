<template>
  <div class="obligaciones-page">
    <!-- Header Page Actions -->
    <div class="obligaciones-page__header">
      <div>
        <h1 class="obligaciones-page__title">Gestión de Obligaciones del Grupo</h1>
        <p class="obligaciones-page__subtitle">Administración de cuotas mensuales y aportes extraordinarios de ayuda / emergencia</p>
      </div>

      <button class="btn btn-primary" @click="abrirNuevoModal" id="btn-nueva-obligacion">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;margin-right:6px;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Definir Nueva Obligación
      </button>
    </div>

    <!-- Search & Filter Bar -->
    <div class="obligaciones-page__filters">
      <div class="search-box">
        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input
          v-model="searchQuery"
          @input="debouncedSearch"
          type="text"
          placeholder="Buscar por concepto o motivo..."
          class="form-control search-input"
        />
      </div>

      <div class="filter-tabs">
        <button
          class="filter-tab"
          :class="{ 'filter-tab--active': filtroCategoria === 'todas' }"
          @click="setFiltroCategoria('todas')"
        >
          Todas
        </button>
        <button
          class="filter-tab"
          :class="{ 'filter-tab--active': filtroCategoria === 'MENSUAL' }"
          @click="setFiltroCategoria('MENSUAL')"
        >
          📅 Cuotas Mensuales (1 mes)
        </button>
        <button
          class="filter-tab"
          :class="{ 'filter-tab--active': filtroCategoria === 'AYUDA' }"
          @click="setFiltroCategoria('AYUDA')"
        >
          🚑 Aportes de Ayuda / Emergencia
        </button>
      </div>
    </div>

    <!-- Data Table -->
    <div class="obligaciones-page__table-wrapper table-container">
      <AppLoader v-if="loading" :visible="true" message="Cargando obligaciones del grupo..." style="padding: 3rem 0;" />

      <table v-else-if="obligaciones.length > 0" class="data-table">
        <thead>
          <tr>
            <th>Concepto / Causa</th>
            <th>Tipo & Vigencia</th>
            <th>Grupo</th>
            <th>Monto Chofer</th>
            <th>Total Esperado</th>
            <th>Recaudación</th>
            <th style="text-align: right;">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="ob in obligaciones" :key="ob.id">
            <td>
              <div class="concepto-cell">
                <strong class="concepto-title">{{ ob.concepto }}</strong>
                <small class="text-muted">Jefe: {{ ob.jefe_persona?.nombre_completo || 'Sistema' }}</small>
              </div>
            </td>
            <td>
              <div class="tipo-cell">
                <span :class="ob.tipo_categoria === 'MENSUAL' ? 'badge badge-admin' : 'badge badge-success'">
                  {{ ob.tipo_categoria === 'MENSUAL' ? '📅 Cuota Mensual' : '🚑 Ayuda / Emergencia' }}
                </span>
                <small class="text-muted" style="margin-top:2px;">
                  📅 {{ formatDate(ob.fecha_inicio) }} al {{ formatDate(ob.fecha_fin) }}
                </small>
              </div>
            </td>
            <td>
              <span class="badge badge-chofer">{{ ob.grupo?.nombre || 'Grupo' }}</span>
            </td>
            <td>
              <strong>Bs. {{ Number(ob.monto_individual).toFixed(2) }}</strong>
            </td>
            <td>
              <span class="text-primary font-mono"><strong>Bs. {{ Number(ob.monto_total_esperado).toFixed(2) }}</strong></span>
            </td>
            <td>
              <div class="recaudacion-cell">
                <div class="recaudacion-bar">
                  <div class="recaudacion-fill" :style="{ width: (ob.metrics?.porcentaje_pagado || 0) + '%' }"></div>
                </div>
                <small class="recaudacion-sub">
                  Bs. {{ Number(ob.metrics?.monto_recaudado || 0).toFixed(2) }} ({{ ob.metrics?.porcentaje_pagado }}%) — {{ ob.metrics?.choferes_pagados }}/{{ ob.metrics?.total_choferes }} choferes
                </small>
              </div>
            </td>
            <td style="text-align: right;">
              <div class="actions-cell">
                <button
                  class="action-btn action-btn--view"
                  @click="abrirDetallesModal(ob.id)"
                  title="Ver desglose de deudas y pagos por chofer"
                  :id="`btn-detalles-${ob.id}`"
                >
                  👁️ Choferes
                </button>
                <button
                  class="action-btn action-btn--edit"
                  @click="abrirEditarModal(ob)"
                  title="Editar datos de la obligación"
                  :id="`btn-editar-ob-${ob.id}`"
                >
                  ✏️ Editar
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-else class="empty-state">
        <p>No se encontraron obligaciones registradas para su grupo con el filtro seleccionado.</p>
      </div>
    </div>

    <!-- Modal Form (Crear / Editar) -->
    <FormObligacionModal
      v-if="modalVisible"
      :visible="modalVisible"
      :obligacionEdit="obligacionSeleccionada"
      @close="cerrarModal"
      @saved="onObligacionGuardada"
    />

    <!-- Modal Detalles de Choferes -->
    <DetallesChoferesModal
      v-if="detallesModalVisible"
      :visible="detallesModalVisible"
      :obligacionId="detallesObligacionId"
      @close="detallesModalVisible = false"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { obligacionApi } from '@/api/obligacionApi'
import AppLoader from '@/components/common/AppLoader.vue'
import FormObligacionModal from '@/components/tesoreria/FormObligacionModal.vue'
import DetallesChoferesModal from '@/components/tesoreria/DetallesChoferesModal.vue'

const loading = ref(false)
const obligaciones = ref([])
const searchQuery = ref('')
const filtroCategoria = ref('todas')

const modalVisible = ref(false)
const obligacionSeleccionada = ref(null)

const detallesModalVisible = ref(false)
const detallesObligacionId = ref(null)

let searchTimeout = null

async function cargarObligaciones() {
  loading.value = true
  try {
    const params = {}
    if (searchQuery.value) params.q = searchQuery.value
    if (filtroCategoria.value === 'MENSUAL' || filtroCategoria.value === 'AYUDA') {
      params.tipo_categoria = filtroCategoria.value
    }

    const res = await obligacionApi.listar(params)
    obligaciones.value = res.data.data.obligaciones || []
  } catch (err) {
    console.error('Error al cargar obligaciones:', err)
  } finally {
    loading.value = false
  }
}

function debouncedSearch() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    cargarObligaciones()
  }, 300)
}

function setFiltroCategoria(cat) {
  filtroCategoria.value = cat
  cargarObligaciones()
}

function abrirNuevoModal() {
  obligacionSeleccionada.value = null
  modalVisible.value = true
}

function abrirEditarModal(ob) {
  obligacionSeleccionada.value = ob
  modalVisible.value = true
}

function abrirDetallesModal(id) {
  detallesObligacionId.value = id
  detallesModalVisible.value = true
}

function cerrarModal() {
  modalVisible.value = false
  obligacionSeleccionada.value = null
}

function onObligacionGuardada() {
  cerrarModal()
  cargarObligaciones()
}

function formatDate(dateStr) {
  if (!dateStr) return 'N/A'
  const d = new Date(dateStr)
  return d.toLocaleDateString('es-BO', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

onMounted(() => {
  cargarObligaciones()
})
</script>

<style scoped>
.obligaciones-page__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}
.obligaciones-page__title { font-size: 1.6rem; font-weight: 800; color: var(--color-text-primary); }
.obligaciones-page__subtitle { font-size: 0.85rem; color: var(--color-text-secondary); }

.obligaciones-page__filters {
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

.concepto-cell { display: flex; flex-direction: column; }
.concepto-title { color: var(--color-text-primary); font-size: 0.95rem; }

.tipo-cell { display: flex; flex-direction: column; }

.recaudacion-cell { display: flex; flex-direction: column; width: 160px; }
.recaudacion-bar { height: 8px; background: var(--color-bg-tertiary); border-radius: var(--radius-full); overflow: hidden; border: 1px solid var(--color-border); }
.recaudacion-fill { height: 100%; background: var(--color-success); transition: width 0.4s ease; }
.recaudacion-sub { font-size: 0.7rem; color: var(--color-text-secondary); margin-top: 0.25rem; }

.actions-cell { display: flex; justify-content: flex-end; gap: 0.4rem; }
.action-btn { font-size: 0.75rem; font-weight: 600; padding: 0.35rem 0.6rem; border-radius: var(--radius-md); border: 1px solid var(--color-border); background: #ffffff; cursor: pointer; }
.action-btn--view:hover { background: var(--color-bg-tertiary); border-color: var(--color-border); }
.action-btn--edit:hover { background: var(--color-primary-50); border-color: var(--color-primary-300); color: var(--color-primary-800); }

.empty-state { padding: 3rem; text-align: center; color: var(--color-text-muted); font-size: 0.9rem; }
</style>
