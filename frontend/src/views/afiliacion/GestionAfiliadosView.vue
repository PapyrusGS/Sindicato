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
        class="btn btn-primary btn-lg"
        @click="abrirNuevoModal"
        id="btn-nueva-afiliacion"
      >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:20px;height:20px;margin-right:6px;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
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
          class="search-input"
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
    <div class="afiliados-page__table-wrapper">
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
                <span v-for="r in a.usuario?.roles" :key="r" class="role-tag" :class="getRoleTagClass(r)">
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
              <span v-else class="text-muted font-sm">Sin vehículos</span>
            </td>
            <td>
              <div class="audit-cell" title="Usuario y Fecha de la última acción registrada">
                <span class="audit-user">👤 {{ a.auditoria?.usuarioA || 'system' }}</span>
                <small class="audit-date">📅 {{ a.auditoria?.fechaA || 'N/A' }}</small>
              </div>
            </td>
            <td>
              <span class="status-badge" :class="a.estado ? 'status-badge--active' : 'status-badge--inactive'">
                {{ a.estado ? 'Activo (1)' : 'Inactivo (0)' }}
              </span>
            </td>
            <td v-if="isAdmin" style="text-align: right;">
              <div class="actions-cell">
                <button
                  class="action-btn action-btn--edit"
                  @click="abrirEditarModal(a)"
                  title="Editar datos del afiliado"
                >
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                </button>

                <button
                  class="action-btn"
                  :class="a.estado ? 'action-btn--delete' : 'action-btn--restore'"
                  @click="toggleEstado(a)"
                  :title="a.estado ? 'Eliminación Lógica (Desactivar)' : 'Reactivar Afiliado'"
                >
                  <svg v-if="a.estado" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                  <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Empty State -->
      <div v-else class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        <p>No se encontraron afiliados registrados</p>
        <span v-if="isAdmin">Haga clic en "Nueva Afiliación / Registro" para ingresar un nuevo afiliado.</span>
      </div>
    </div>

    <!-- Modal de Formulario Wizard (Creación & Edición) -->
    <FormAfiliacionModal
      :show="showModal"
      :affiliate-data="affiliateToEdit"
      @close="showModal = false"
      @saved="handleSaved"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { afiliacionApi } from '@/api/afiliacionApi'
import { useAuth } from '@/composables/useAuth'
import AppLoader from '@/components/common/AppLoader.vue'
import FormAfiliacionModal from '@/components/afiliacion/FormAfiliacionModal.vue'

const { isAdmin } = useAuth()

const afiliados = ref([])
const loading = ref(false)
const searchQuery = ref('')
const estadoFiltro = ref('todos')
const showModal = ref(false)
const affiliateToEdit = ref(null)

let searchTimeout = null

function debouncedSearch() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    cargarAfiliados()
  }, 300)
}

function setFiltroEstado(tipo) {
  estadoFiltro.value = tipo
  cargarAfiliados()
}

async function cargarAfiliados() {
  loading.value = true
  try {
    const params = { search: searchQuery.value }
    if (estadoFiltro.value === 'activos') params.estado = true
    if (estadoFiltro.value === 'inactivos') params.estado = false

    const res = await afiliacionApi.listar(params)
    afiliados.value = res.data.data.afiliados
  } catch (err) {
    console.error('Error cargando afiliados:', err)
  } finally {
    loading.value = false
  }
}

function abrirNuevoModal() {
  affiliateToEdit.value = null
  showModal.value = true
}

function abrirEditarModal(afiliado) {
  affiliateToEdit.value = afiliado
  showModal.value = true
}

async function toggleEstado(afiliado) {
  const nuevoEstado = !afiliado.estado
  const accion = nuevoEstado ? 'reactivar' : 'desactivar (eliminación lógica)'
  if (!confirm(`¿Está seguro de ${accion} al afiliado ${afiliado.nombre_completo}?`)) return

  try {
    await afiliacionApi.cambiarEstado(afiliado.id, nuevoEstado)
    cargarAfiliados()
  } catch (err) {
    alert(err.response?.data?.message || 'Error al cambiar estado')
  }
}

function handleSaved() {
  cargarAfiliados()
}

function getRoleTagClass(role) {
  const map = {
    'Administrador': 'role-tag--admin',
    'Jefe de Grupo': 'role-tag--jefe',
    'Inspector': 'role-tag--inspector',
    'Tesorero': 'role-tag--tesorero',
    'Chofer': 'role-tag--chofer',
    'Propietario': 'role-tag--propietario',
  }
  return map[role] || ''
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
.afiliados-page__title {
  font-size: 1.6rem;
  font-weight: 700;
  color: var(--color-text-primary);
}
.afiliados-page__subtitle {
  font-size: 0.85rem;
  color: var(--color-text-secondary);
}

.afiliados-page__filters {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.25rem;
  gap: 1rem;
}
.search-box {
  position: relative;
  width: 100%;
  max-width: 380px;
}
.search-icon {
  position: absolute;
  left: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  width: 18px;
  height: 18px;
  color: var(--color-text-muted);
}
.search-input {
  width: 100%;
  padding: 0.6rem 0.8rem 0.6rem 2.4rem;
  background: var(--color-bg-card);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  color: white;
  font-size: 0.9rem;
}

.filter-tabs {
  display: flex;
  gap: 0.35rem;
  background: var(--color-bg-card);
  padding: 0.25rem;
  border-radius: var(--radius-lg);
  border: 1px solid var(--color-border);
}
.filter-tab {
  padding: 0.35rem 0.75rem;
  font-size: 0.8rem;
  background: none;
  border: none;
  color: var(--color-text-muted);
  border-radius: var(--radius-md);
  cursor: pointer;
  transition: all var(--transition-fast);
}
.filter-tab--active {
  background: var(--color-primary-600);
  color: white;
  font-weight: 600;
}

.afiliados-page__table-wrapper {
  background: var(--color-bg-card);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-xl);
  overflow: hidden;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
}
.data-table th {
  padding: 0.85rem 1rem;
  background: rgba(0, 0, 0, 0.2);
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--color-text-secondary);
  border-bottom: 1px solid var(--color-border);
}
.data-table td {
  padding: 0.85rem 1rem;
  border-bottom: 1px solid var(--color-border);
  font-size: 0.85rem;
  color: var(--color-text-primary);
}
.tr--inactive { opacity: 0.55; background: rgba(0,0,0,0.15); }

.font-mono { font-family: monospace; font-size: 0.9rem; }
.user-cell { display: flex; flex-direction: column; }
.user-name { font-weight: 600; color: white; }
.user-sub { font-size: 0.75rem; color: var(--color-text-muted); }

.username-tag {
  font-size: 0.8rem;
  color: var(--color-primary-300);
}

.role-tags { display: flex; flex-wrap: wrap; gap: 0.25rem; }
.role-tag {
  font-size: 0.65rem;
  font-weight: 600;
  padding: 0.15rem 0.45rem;
  border-radius: var(--radius-sm);
  background: var(--color-bg-glass);
  border: 1px solid var(--color-border);
}
.role-tag--admin { background: rgba(99,102,241,0.2); color: var(--color-primary-300); }
.role-tag--jefe { background: rgba(245,158,11,0.2); color: #fcd34d; }
.role-tag--inspector { background: rgba(239,68,68,0.2); color: #fca5a5; }
.role-tag--tesorero { background: rgba(34,197,94,0.2); color: #86efac; }
.role-tag--chofer { background: rgba(59,130,246,0.2); color: #93c5fd; }
.role-tag--propietario { background: rgba(168,85,247,0.2); color: #d8b4fe; }

.vehiculos-list { display: flex; flex-direction: column; gap: 0.25rem; }
.vehiculo-chip {
  font-size: 0.75rem;
  padding: 0.2rem 0.5rem;
  background: var(--color-bg-glass);
  border-radius: var(--radius-sm);
  border: 1px solid var(--color-border);
}

.audit-cell { display: flex; flex-direction: column; line-height: 1.2; }
.audit-user { font-size: 0.75rem; color: var(--color-primary-300); }
.audit-date { font-size: 0.7rem; color: var(--color-text-muted); }

.status-badge {
  font-size: 0.7rem;
  font-weight: 600;
  padding: 0.2rem 0.5rem;
  border-radius: var(--radius-full);
}
.status-badge--active { background: rgba(34,197,94,0.2); color: #86efac; }
.status-badge--inactive { background: rgba(239,68,68,0.2); color: #fca5a5; }

.actions-cell { display: flex; justify-content: flex-end; gap: 0.4rem; }
.action-btn {
  width: 32px; height: 32px; border-radius: var(--radius-md); border: 1px solid var(--color-border);
  background: var(--color-bg-glass); color: var(--color-text-secondary); display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all var(--transition-fast);
}
.action-btn svg { width: 16px; height: 16px; }
.action-btn--edit:hover { background: rgba(99,102,241,0.2); color: var(--color-primary-300); border-color: var(--color-primary-500); }
.action-btn--delete:hover { background: rgba(239,68,68,0.2); color: #fca5a5; border-color: #ef4444; }
.action-btn--restore:hover { background: rgba(34,197,94,0.2); color: #86efac; border-color: #22c55e; }

.empty-state {
  padding: 3rem 1.5rem;
  text-align: center;
  color: var(--color-text-muted);
}
.empty-state svg { width: 48px; height: 48px; margin-bottom: 0.75rem; opacity: 0.4; }
.empty-state p { font-size: 1rem; font-weight: 600; color: var(--color-text-primary); }

.btn { display: inline-flex; align-items: center; padding: 0.6rem 1.2rem; font-size: 0.85rem; font-weight: 600; border-radius: var(--radius-lg); border: none; cursor: pointer; }
.btn-primary { background: var(--color-primary-600); color: white; }
.btn-primary:hover { background: var(--color-primary-500); }
</style>
