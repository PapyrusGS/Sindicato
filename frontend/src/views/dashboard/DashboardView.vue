<template>
  <div class="dashboard-view">
    <!-- ─── ACCIONES Y MÓDULOS DEL SISTEMA ────────────────────────── -->
    <div class="section-title">
      <h2>Módulos y Funciones Disponibles</h2>
      <p>Seleccione el módulo que desea gestionar</p>
    </div>

    <div class="role-panels-grid">
      <!-- ─── Panel Inspector ────────────────────────────────────────── -->
      <div v-if="hasRole('Inspector') || hasRole('Administrador')" class="role-card">
        <div class="role-card__header">
          <h3>Control de Asistencia & Multas</h3>
        </div>
        <p class="role-card__desc">Toma de asistencia en paradas, verificación de turnos y registro de infracciones.</p>
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
      <div v-if="hasRole('Tesorero') || hasRole('Administrador')" class="role-card">
        <div class="role-card__header">
          <h3>Tesorería & Cobros</h3>
        </div>
        <p class="role-card__desc">Administra cobros de cuotas mensuales, aportes sindicales y estados de cuenta.</p>
        <div class="role-card__actions">
          <button class="btn btn-primary" @click="$router.push('/dashboard/cobros')" id="btn-tesorero-pago">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
            Procesar Cobros e Historial
          </button>
        </div>
      </div>

      <!-- ─── Panel Jefe de Grupo ────────────────────────────────────── -->
      <div v-if="hasRole('Jefe de Grupo') || hasRole('Administrador')" class="role-card">
        <div class="role-card__header">
          <h3>Gestión del Grupo Asignado</h3>
        </div>
        <p class="role-card__desc">Supervisa choferes, turnos y obligaciones pertenecientes al grupo de trabajo.</p>
        <div class="role-card__actions" style="display:flex; gap:0.5rem; flex-wrap:wrap;">
          <button class="btn btn-primary" @click="$router.push('/dashboard/obligaciones')" id="btn-jefe-grupo">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><rect x="3" y="4" width="18" height="16" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            Obligaciones del Grupo
          </button>
          <button class="btn btn-secondary" @click="$router.push('/dashboard/auditoria')" id="btn-jefe-auditoria">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            Bitácora de Auditorías
          </button>
        </div>
      </div>

      <!-- ─── Panel Chofer ───────────────────────────────────────────── -->
      <div v-if="hasRole('Chofer') || hasRole('Administrador')" class="role-card">
        <div class="role-card__header">
          <h3>Mi Perfil & Vehículos</h3>
        </div>
        <p class="role-card__desc">Consulte sus vehículos asignados, estado de cuenta y registro de asistencias.</p>
        <div class="role-card__actions">
          <button class="btn btn-primary" @click="$router.push('/dashboard/mi-perfil')" id="btn-chofer-vehiculos">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Ver Mi Perfil & Vehículos
          </button>
        </div>
      </div>

      <!-- ─── Panel Administrador ────────────────────────────────────── -->
      <div v-if="hasRole('Administrador')" class="role-card">
        <div class="role-card__header">
          <h3>Administración de Afiliaciones</h3>
        </div>
        <p class="role-card__desc">Gestión integral de choferes, propietarios, vehículos y registro de afiliados.</p>
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

const { hasRole } = useAuth()
</script>

<style scoped>
.dashboard-view {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.section-title { margin-bottom: 0.5rem; }
.section-title h2 { font-size: 1.35rem; font-weight: 800; color: var(--color-text-primary); }
.section-title p { font-size: 0.9rem; color: var(--color-text-secondary); margin-top: 0.25rem; }

.role-panels-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
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
  transition: all var(--transition-fast);
}
.role-card:hover {
  border-color: var(--color-primary-400);
  box-shadow: var(--shadow-md);
}
.role-card__header h3 { font-size: 1.1rem; font-weight: 700; color: var(--color-text-primary); }
.role-card__desc { font-size: 0.88rem; color: var(--color-text-secondary); margin: 0.6rem 0 1.25rem 0; line-height: 1.45; }
.role-card__actions { display: flex; gap: 0.5rem; }
</style>
