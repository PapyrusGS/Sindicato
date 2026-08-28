<template>
  <div class="afiliados-page">
    <!-- Header Page Actions -->
    <div class="afiliados-page__header">
      <div>
        <h1 class="afiliados-page__title">Gestión de Afiliaciones</h1>
        <p class="afiliados-page__subtitle">Administración de Personas, Usuarios, Choferes, Vehículos y Auditoría</p>
      </div>

      <button
        v-if="isAdmin"
        class="btn btn-primary"
        @click="abrirNuevoModal"
        id="btn-nueva-afiliacion"
      >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;margin-right:6px;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Nueva Afiliación / Registro
      </button>
    </div>

    <!-- Search & Filter Bar -->
    <div class="afiliados-page__filters">
      <div class="search-box">
        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input
          v-model="searchQuery"
          @input="debouncedSearch"
          type="text"
          placeholder="Buscar por CI, nombre o apellido..."
          class="form-control search-input"
        />
      </div>

      <div class="filter-tabs">
        <button
          class="filter-tab"
          :class="{ 'filter-tab--active': estadoFiltro === 'todos' }"
          @click="setFiltroEstado('todos')"
        >
          Todos
        </button>
        <button
          class="filter-tab"
          :class="{ 'filter-tab--active': estadoFiltro === 'activos' }"
          @click="setFiltroEstado('activos')"
        >
          Activos (1)
        </button>
        <button
          class="filter-tab"
          :class="{ 'filter-tab--active': estadoFiltro === 'inactivos' }"
          @click="setFiltroEstado('inactivos')"
        >
          Inactivos (0)
        </button>
      </div>
    </div>

    <!-- Data Table -->
    <div class="afiliados-page__table-wrapper table-container">
      <AppLoader v-if="loading" :visible="true" message="Cargando afiliados..." style="padding: 3rem 0;" />

      <table v-else-if="afiliados.length > 0" class="data-table">
        <thead>
          <tr>
            <th>CI</th>
            <th>Nombre Completo</th>
            <th>Usuario</th>
            <th>Roles Asignados</th>
            <th>Vehículos Registrados</th>
            <th>Última Auditoría</th>
            <th>Estado</th>
            <th v-if="isAdmin" style="text-align: right;">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="a in afiliados" :key="a.id" :class="{ 'tr--inactive': !a.estado }">
            <td class="font-mono"><strong>{{ a.ci }}</strong></td>
            <td>
              <div class="user-cell">
                <span class="user-name">{{ a.nombre_completo }}</span>
                <small v-if="a.celular" class="user-sub">Cel: {{ a.celular }}</small>
              </div>
            </td>
            <td>
              <span class="username-tag">@{{ a.usuario?.username || 'Sin usuario' }}</span>
            </td>
            <td>
              <div class="role-tags">
                <span v-for="r in a.usuario?.roles" :key="r" class="badge" :class="getRoleTagClass(r)">
                  {{ r }}
                </span>
              </div>
            </td>
            <td>
              <div v-if="a.vehiculos.length > 0" class="vehiculos-list">
                <div v-for="v in a.vehiculos" :key="v.id" class="vehiculo-chip">
                  <strong>{{ v.placa }}</strong> ({{ v.marca }} {{ v.modelo }})
                  <small>— {{ v.tipo }}</small>
                </div>
              </div>
              <span v-else class="text-muted" style="font-size:0.8rem;">Sin vehículo asignado</span>
            </td>
            <td>
              <div class="audit-cell" v-if="a.auditoria">
                <span class="audit-user">👤 {{ a.auditoria.usuarioA || 'Sistema' }}</span>
                <small class="audit-date">📅 {{ a.auditoria.fechaA }}</small>
              </div>
            </td>
            <td>
              <span :class="a.estado ? 'status-pill status-pill--active' : 'status-pill status-pill--inactive'">
                {{ a.estado ? 'Activo (1)' : 'Inactivo (0)' }}
              </span>
            </td>
            <td v-if="isAdmin" style="text-align: right;">
              <div class="actions-cell">
                <button
                  class="action-btn action-btn--edit"
                  @click="abrirEditarModal(a)"
                  title="Editar datos del afiliado"
                  :id="`btn-editar-${a.id}`"
                >
                  ✏️ Editar
                </button>
                <button
                  class="action-btn"
                  :class="a.estado ? 'action-btn--delete' : 'action-btn--activate'"
                  @click="confirmarCambioEstado(a)"
                  :title="a.estado ? 'Desactivar afiliado (Eliminación Lógica)' : 'Reactivar afiliado'"
                  :id="`btn-estado-${a.id}`"
                >
                  {{ a.estado ? '🗑️ Desactivar' : '🔄 Activar' }}
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-else class="empty-state">
        <p>No se encontraron afiliados registrados con el criterio de búsqueda.</p>
      </div>
    </div>

    <!-- Modal Form (Crear y Editar) -->
    <FormAfiliacionModal
      v-if="modalVisible"
      :visible="modalVisible"
      :afiliadoEdit="afiliadoSeleccionado"
      @close="cerrarModal"
      @saved="onAfiliadoGuardado"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { afiliacionApi } from '@/api/afiliacionApi'
import { useAuth } from '@/composables/useAuth'
import AppLoader from '@/components/common/AppLoader.vue'
import FormAfiliacionModal from '@/components/afiliacion/FormAfiliacionModal.vue'

const { hasRole } = useAuth()
const isAdmin = ref(hasRole('Administrador'))

const loading = ref(false)
const afiliados = ref([])
const searchQuery = ref('')
const estadoFiltro = ref('todos')

const modalVisible = ref(false)
const afiliadoSeleccionado = ref(null)

let searchTimeout = null

async function cargarAfiliados() {
  loading.value = true
  try {
    const params = {}
    if (searchQuery.value) params.q = searchQuery.value
    if (estadoFiltro.value !== 'todos') {
      params.estado = estadoFiltro.value === 'activos' ? 1 : 0
    }

    const res = await afiliacionApi.listar(params)
    afiliados.value = res.data.data.afiliados || []
  } catch (err) {
    console.error('Error al cargar afiliados:', err)
  } finally {
    loading.value = false
  }
}

function debouncedSearch() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    cargarAfiliados()
  }, 300)
}

function setFiltroEstado(filtro) {
  estadoFiltro.value = filtro
  cargarAfiliados()
}

function abrirNuevoModal() {
  afiliadoSeleccionado.value = null
  modalVisible.value = true
}

function abrirEditarModal(afiliado) {
  afiliadoSeleccionado.value = afiliado
  modalVisible.value = true
}

function cerrarModal() {
  modalVisible.value = false
  afiliadoSeleccionado.value = null
}

function onAfiliadoGuardado() {
  cerrarModal()
  cargarAfiliados()
}

async function confirmarCambioEstado(afiliado) {
  const accion = afiliado.estado ? 'desactivar (eliminación lógica)' : 'reactivar'
  if (!confirm(`¿Está seguro que desea ${accion} al afiliado ${afiliado.nombre_completo}?`)) {
    return
  }

  try {
    await afiliacionApi.cambiarEstado(afiliado.id, !afiliado.estado)
    cargarAfiliados()
  } catch (err) {
    alert(err.response?.data?.message || 'Error al modificar el estado del afiliado')
  }
}

function getRoleTagClass(role) {
  const map = {
    'Administrador': 'badge-admin',
    'Inspector': 'badge-inspector',
    'Tesorero': 'badge-tesorero',
    'Jefe de Grupo': 'badge-chofer',
    'Chofer': 'badge-chofer',
  }
  return map[role] || 'badge-chofer'
}

onMounted(() => {
  cargarAfiliados()
})
</script>

<style scoped>
.afiliados-page__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}
.afiliados-page__title { font-size: 1.6rem; font-weight: 800; color: var(--color-text-primary); }
.afiliados-page__subtitle { font-size: 0.85rem; color: var(--color-text-secondary); }

.afiliados-page__filters {
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
  padding: 0.45rem 1rem;
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
.user-name { font-weight: 600; color: var(--color-text-primary); }
.user-sub { font-size: 0.75rem; color: var(--color-text-muted); }

.username-tag { font-size: 0.8rem; font-weight: 600; color: var(--color-primary-700); }

.role-tags { display: flex; flex-wrap: wrap; gap: 0.25rem; }

.vehiculos-list { display: flex; flex-direction: column; gap: 0.2rem; }
.vehiculo-chip { font-size: 0.8rem; background: var(--color-bg-tertiary); padding: 0.25rem 0.5rem; border-radius: var(--radius-sm); border: 1px solid var(--color-border); }

.audit-cell { display: flex; flex-direction: column; font-size: 0.75rem; color: var(--color-text-secondary); }

.actions-cell { display: flex; justify-content: flex-end; gap: 0.4rem; }
.action-btn { font-size: 0.75rem; font-weight: 600; padding: 0.35rem 0.6rem; border-radius: var(--radius-md); border: 1px solid var(--color-border); background: #ffffff; cursor: pointer; }
.action-btn--edit:hover { background: var(--color-primary-50); border-color: var(--color-primary-300); color: var(--color-primary-800); }
.action-btn--delete:hover { background: var(--color-error-bg); border-color: #fecaca; color: var(--color-error); }
.action-btn--activate:hover { background: var(--color-success-bg); border-color: #bbf7d0; color: var(--color-success); }

.empty-state { padding: 3rem; text-align: center; color: var(--color-text-muted); font-size: 0.9rem; }
.tr--inactive { opacity: 0.65; background-color: #f8fafc; }
</style>
