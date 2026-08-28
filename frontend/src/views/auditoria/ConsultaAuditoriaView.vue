<template>
  <div class="auditoria-page">
    <!-- Header Page -->
    <div class="auditoria-page__header">
      <div>
        <h1 class="auditoria-page__title">Centro de Control y Bitácora de Auditoría</h1>
        <p class="auditoria-page__subtitle">Trazabilidad completa de acciones, fechas, responsables y valores modificados en el sistema</p>
      </div>

      <div class="header-badge">
        <span class="badge badge-admin">🔒 Control de Auditoría Activo</span>
      </div>
    </div>

    <!-- Filters Bar -->
    <div class="auditoria-page__filters card" style="padding: 1.25rem; margin-bottom: 1.25rem;">
      <div class="filters-grid">
        <!-- 1. Buscador -->
        <div class="form-group" style="margin-bottom:0;">
          <label class="form-label" style="font-size:0.75rem;">Buscar por Ejecutor o Afectado</label>
          <div class="search-box">
            <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input
              v-model="searchQuery"
              @input="debouncedSearch"
              type="text"
              placeholder="Nombre, CI o campo modificado..."
              class="form-control search-input"
            />
          </div>
        </div>

        <!-- 2. Selector de Módulo -->
        <div class="form-group" style="margin-bottom:0;">
          <label class="form-label" style="font-size:0.75rem;">Módulo / Sección</label>
          <select v-model="filtroModulo" @change="cargarAuditorias" class="form-control">
            <option value="">Todos los Módulos Disponibles</option>
            <option v-for="m in auxiliares.modulos" :key="m.key" :value="m.key">
              {{ m.label }}
            </option>
          </select>
        </div>

        <!-- 3. Selector de Acción -->
        <div class="form-group" style="margin-bottom:0;">
          <label class="form-label" style="font-size:0.75rem;">Tipo de Acción</label>
          <select v-model="filtroAccion" @change="cargarAuditorias" class="form-control">
            <option value="">Todas las Acciones</option>
            <option value="CREACION">✨ CREACION</option>
            <option value="MODIFICACION">✏️ MODIFICACION</option>
            <option value="DESACTIVACION">🚫 DESACTIVACION</option>
            <option value="REACTIVACION">🔄 REACTIVACION</option>
            <option value="ELIMINACION">🗑️ ELIMINACION</option>
          </select>
        </div>

        <!-- 4. Rango de Fechas -->
        <div class="form-group" style="margin-bottom:0;">
          <label class="form-label" style="font-size:0.75rem;">Desde Fecha</label>
          <input v-model="fechaDesde" @change="cargarAuditorias" type="date" class="form-control" />
        </div>

        <div class="form-group" style="margin-bottom:0;">
          <label class="form-label" style="font-size:0.75rem;">Hasta Fecha</label>
          <input v-model="fechaHasta" @change="cargarAuditorias" type="date" class="form-control" />
        </div>
      </div>
    </div>

    <!-- Data Table -->
    <div class="auditoria-page__table-wrapper table-container">
      <AppLoader v-if="loading" :visible="true" message="Cargando historial de auditorías..." style="padding: 3rem 0;" />

      <table v-else-if="auditorias.length > 0" class="data-table">
        <thead>
          <tr>
            <th>Fecha y Hora</th>
            <th>Módulo</th>
            <th>Acción</th>
            <th>Quién lo Hizo (Ejecutor)</th>
            <th>A Quién se Hizo (Afectado)</th>
            <th>Campo / Resumen Cambio</th>
            <th style="text-align: right;">Detalle</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="aud in auditorias" :key="aud.id_auditoria">
            <td>
              <div class="date-cell">
                <strong>📅 {{ formatDate(aud.fecha_a) }}</strong>
                <small class="text-muted font-mono">IP: {{ aud.direccion_ip || '127.0.0.1' }}</small>
              </div>
            </td>
            <td>
              <span class="badge badge-chofer" style="font-size:0.75rem;">
                {{ aud.enriquecido?.modulo_etiqueta }}
              </span>
            </td>
            <td>
              <span :class="getBadgeClass(aud.accion)">
                {{ aud.accion }}
              </span>
            </td>
            <td>
              <div class="user-cell">
                <strong class="user-name">👤 {{ aud.enriquecido?.ejecutor_nombre }}</strong>
                <small class="user-sub font-mono">CI: {{ aud.enriquecido?.ejecutor_ci || 'N/A' }}</small>
              </div>
            </td>
            <td>
              <div class="user-cell">
                <strong class="user-name">🚘 {{ aud.enriquecido?.afectado_nombre }}</strong>
                <small class="user-sub">{{ aud.enriquecido?.afectado_detalle }}</small>
              </div>
            </td>
            <td>
              <div class="change-cell">
                <code v-if="aud.campo" class="campo-code">{{ aud.campo }}</code>
                <span v-else class="text-muted" style="font-size:0.8rem;">Registro {{ aud.accion.toLowerCase() }}</span>
                <small v-if="aud.valor_nuevo" class="change-sub">
                  ➜ {{ truncateText(aud.valor_nuevo, 35) }}
                </small>
              </div>
            </td>
            <td style="text-align: right;">
              <button
                class="btn btn-secondary btn-sm"
                @click="abrirDetalleModal(aud)"
                title="Ver detalle completo de la inspección"
                :id="`btn-audit-${aud.id_auditoria}`"
              >
                🔍 Detalle
              </button>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-else class="empty-state">
        <p>No se encontraron registros de auditoría que coincidan con los filtros seleccionados.</p>
      </div>
    </div>

    <!-- Modal Detalle Auditoría -->
    <DetalleAuditoriaModal
      v-if="detalleModalVisible"
      :visible="detalleModalVisible"
      :auditoriaItem="auditoriaSeleccionada"
      @close="detalleModalVisible = false"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { auditoriaApi } from '@/api/auditoriaApi'
import AppLoader from '@/components/common/AppLoader.vue'
import DetalleAuditoriaModal from '@/components/auditoria/DetalleAuditoriaModal.vue'

const loading = ref(false)
const auditorias = ref([])
const auxiliares = ref({ modulos: [], acciones: [] })

const searchQuery = ref('')
const filtroModulo = ref('')
const filtroAccion = ref('')
const fechaDesde = ref('')
const fechaHasta = ref('')

const detalleModalVisible = ref(false)
const auditoriaSeleccionada = ref(null)

let searchTimeout = null

async function cargarAuxiliares() {
  try {
    const res = await auditoriaApi.obtenerAuxiliares()
    auxiliares.value = res.data.data
  } catch (err) {
    console.error('Error al cargar auxiliares de auditoría:', err)
  }
}

async function cargarAuditorias() {
  loading.value = true
  try {
    const params = {}
    if (searchQuery.value) params.q = searchQuery.value
    if (filtroModulo.value) params.modulo = filtroModulo.value
    if (filtroAccion.value) params.accion = filtroAccion.value
    if (fechaDesde.value) params.fecha_desde = fechaDesde.value
    if (fechaHasta.value) params.fecha_hasta = fechaHasta.value

    const res = await auditoriaApi.listar(params)
    auditorias.value = res.data.data.auditorias || []
  } catch (err) {
    console.error('Error al cargar historial de auditorías:', err)
  } finally {
    loading.value = false
  }
}

function debouncedSearch() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    cargarAuditorias()
  }, 300)
}

function abrirDetalleModal(item) {
  auditoriaSeleccionada.value = item
  detalleModalVisible.value = true
}

function getBadgeClass(accion) {
  switch (accion) {
    case 'CREACION': return 'badge badge-success'
    case 'MODIFICACION': return 'badge badge-admin'
    case 'DESACTIVACION': case 'ELIMINACION': return 'badge badge-tesorero'
    case 'REACTIVACION': return 'badge badge-chofer'
    default: return 'badge'
  }
}

function formatDate(dateStr) {
  if (!dateStr) return 'N/A'
  const d = new Date(dateStr)
  return d.toLocaleString('es-BO', { dateStyle: 'short', timeStyle: 'short' })
}

function truncateText(txt, maxLen) {
  if (!txt) return ''
  return txt.length > maxLen ? txt.substring(0, maxLen) + '...' : txt
}

onMounted(() => {
  cargarAuxiliares()
  cargarAuditorias()
})
</script>

<style scoped>
.auditoria-page__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}
.auditoria-page__title { font-size: 1.6rem; font-weight: 800; color: var(--color-text-primary); }
.auditoria-page__subtitle { font-size: 0.85rem; color: var(--color-text-secondary); }

.filters-grid {
  display: grid;
  grid-template-columns: 2fr 1.5fr 1.2fr 1fr 1fr;
  gap: 0.75rem;
  align-items: flex-end;
}

.search-box { position: relative; width: 100%; }
.search-icon { position: absolute; left: 0.85rem; top: 50%; transform: translateY(-50%); width: 18px; height: 18px; color: var(--color-text-muted); }
.search-input { padding-left: 2.5rem; }

.date-cell { display: flex; flex-direction: column; font-size: 0.8rem; }

.user-cell { display: flex; flex-direction: column; }
.user-name { color: var(--color-text-primary); font-size: 0.85rem; }
.user-sub { font-size: 0.75rem; color: var(--color-text-muted); }

.change-cell { display: flex; flex-direction: column; }
.campo-code { font-size: 0.75rem; font-weight: 700; color: var(--color-primary-800); background: var(--color-primary-50); padding: 0.15rem 0.4rem; border-radius: var(--radius-sm); align-self: flex-start; }
.change-sub { font-size: 0.75rem; color: var(--color-text-secondary); margin-top: 0.15rem; }

.btn-sm { font-size: 0.75rem; padding: 0.3rem 0.6rem; border-radius: var(--radius-md); }

.empty-state { padding: 3rem; text-align: center; color: var(--color-text-muted); font-size: 0.9rem; }
</style>
