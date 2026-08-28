<template>
  <div class="dashboard-view">
    <div class="welcome-card">
      <h1 class="welcome-title">
        ¡Bienvenido, <span class="welcome-name">{{ userFullName }}</span>!
      </h1>
      <div class="roles-banner">
        <span class="roles-label">Roles asignados a su perfil:</span>
        <div class="role-chips">
          <span v-for="role in roles" :key="role" class="badge" :class="getRoleBadgeClass(role)">
            {{ role }}
          </span>
        </div>
      </div>
    </div>

    <!-- ─── ACCIONES POR ROL (Panel Dinámico) ────────────────────────── -->
    <div class="section-title">
      <h2>Módulos y funciones disponibles</h2>
      <p>Seleccione la opción requerida según sus atribuciones en el sindicato</p>
    </div>

    <div class="role-panels-grid">
      <!-- ─── Panel Inspector ────────────────────────────────────────── -->
      <div v-if="hasRole('Inspector')" class="role-card">
        <div class="role-card__header">
          <span class="badge badge-inspector">Inspector</span>
          <h3>Módulo de Inspección & Control de Asistencia</h3>
        </div>
        <div class="role-card__actions">
          <button class="btn btn-primary" @click="$router.push('/dashboard/asistencias')" id="btn-inspector-asistencia">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
            Tomar Asistencia
          </button>
          <button class="btn btn-secondary" @click="$router.push('/dashboard/multas')" id="btn-inspector-multa">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            Registrar Sanción
          </button>
        </div>
      </div>

      <!-- ─── Panel Tesorero ─────────────────────────────────────────── -->
      <div v-if="hasRole('Tesorero')" class="role-card">
        <div class="role-card__header">
          <span class="badge badge-tesorero">Tesorero</span>
          <h3>Módulo de Tesorería & Cobros</h3>
        </div>
        <p class="role-card__desc">Administra cobros de cuotas mensuales, aportes sindicales y registros de pagos.</p>
        <div class="role-card__actions">
          <button class="btn btn-primary" @click="$router.push('/dashboard/cobros')" id="btn-tesorero-pago">
            💳 Procesar Cobros e Historial
          </button>
        </div>
      </div>

      <!-- ─── Panel Jefe de Grupo ────────────────────────────────────── -->
      <div v-if="hasRole('Jefe de Grupo')" class="role-card">
        <div class="role-card__header">
          <span class="badge badge-chofer">Jefe de Grupo</span>
          <h3>Gestión del Grupo Asignado</h3>
        </div>
        <p class="role-card__desc">Supervisa choferes, turnos y vehículos pertenecientes a su grupo de trabajo.</p>
        <div class="role-card__actions" style="display:flex; gap:0.5rem; flex-wrap:wrap;">
          <button class="btn btn-primary" @click="$router.push('/dashboard/obligaciones')" id="btn-jefe-grupo">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><rect x="3" y="4" width="18" height="16" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            Obligaciones y Aportes del Grupo
          </button>
          <button class="btn btn-secondary" @click="$router.push('/dashboard/auditoria')" id="btn-jefe-auditoria">
            🔍 Bitácora & Auditoría Global
          </button>
        </div>
      </div>

      <!-- ─── Panel Chofer ───────────────────────────────────────────── -->
      <div v-if="hasRole('Chofer')" class="role-card">
        <div class="role-card__header">
          <span class="badge badge-chofer">Chofer</span>
          <h3>Perfil del Afiliado</h3>
        </div>
        <p class="role-card__desc">Consulte sus vehículos asignados, estado de cuenta y registro de asistencias.</p>
        <div class="role-card__actions">
          <button class="btn btn-primary" @click="$router.push('/dashboard/mi-perfil')" id="btn-chofer-vehiculos">
            👤 Mi Perfil & Vehículos
          </button>
        </div>
      </div>

      <!-- ─── Panel Administrador ────────────────────────────────────── -->
      <div v-if="hasRole('Administrador')" class="role-card">
        <div class="role-card__header">
          <span class="badge badge-admin">Administrador</span>
          <h3>Administración General del Sistema</h3>
        </div>
        <p class="role-card__desc">Gestión integral de usuarios, asignación de roles, vehículos y auditoría general.</p>
        <div class="role-card__actions">
          <button class="btn btn-primary" @click="$router.push('/dashboard/afiliados')" id="btn-admin-usuarios">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            Gestión de Afiliaciones
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useAuth } from '@/composables/useAuth'

const { userFullName, roles, hasRole } = useAuth()

function getRoleBadgeClass(role) {
  const map = {
    'Administrador': 'badge-admin',
    'Inspector': 'badge-inspector',
    'Tesorero': 'badge-tesorero',
    'Jefe de Grupo': 'badge-chofer',
    'Chofer': 'badge-chofer',
  }
  return map[role] || 'badge-chofer'
}
</script>

<style scoped>
.welcome-card {
  background: #ffffff;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-xl);
  padding: 1.5rem;
  box-shadow: var(--shadow-sm);
  margin-bottom: 2rem;
}
.welcome-title { font-size: 1.5rem; font-weight: 800; color: var(--color-text-primary); }
.welcome-name { color: var(--color-primary-700); }

.roles-banner { display: flex; align-items: center; gap: 0.75rem; margin-top: 0.5rem; }
.roles-label { font-size: 0.85rem; color: var(--color-text-secondary); }
.role-chips { display: flex; flex-wrap: wrap; gap: 0.4rem; }

.section-title { margin-bottom: 1.25rem; }
.section-title h2 { font-size: 1.25rem; font-weight: 700; color: var(--color-text-primary); }
.section-title p { font-size: 0.85rem; color: var(--color-text-secondary); }

.role-panels-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 1.25rem;
}

.role-card {
  background: #ffffff;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-xl);
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  box-shadow: var(--shadow-sm);
}
.role-card:hover {
  border-color: var(--color-primary-300);
}
.role-card__header h3 { font-size: 1.05rem; font-weight: 700; color: var(--color-text-primary); margin-top: 0.4rem; }
.role-card__desc { font-size: 0.85rem; color: var(--color-text-secondary); margin: 0.5rem 0 1.25rem 0; }
.role-card__actions { display: flex; gap: 0.5rem; }
</style>
