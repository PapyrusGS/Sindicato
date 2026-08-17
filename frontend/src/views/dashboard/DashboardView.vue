<template>
  <div class="dashboard-view">
    <div class="dashboard-view__welcome">
      <h1 class="dashboard-view__greeting">
        ¡Bienvenido, <span class="dashboard-view__name">{{ userFullName }}</span>!
      </h1>
      <div class="dashboard-view__roles-banner">
        <span class="dashboard-view__roles-label">Roles asignados:</span>
        <div class="dashboard-view__role-chips">
          <span v-for="role in roles" :key="role" class="dashboard-view__role-chip" :class="getRoleClass(role)">
            {{ role }}
          </span>
        </div>
      </div>
    </div>

    <!-- ─── ACCIONES POR ROL (Panel Dinámico) ────────────────────────── -->
    <div class="dashboard-view__section-title">
      <h2>Opciones disponibles para tu perfil</h2>
      <p>Accede rápidamente a las funciones según tus roles habilitados</p>
    </div>

    <div class="dashboard-view__role-panels">
      <!-- ─── Panel Inspector ────────────────────────────────────────── -->
      <div v-if="hasRole('Inspector')" class="dashboard-view__role-card dashboard-view__role-card--inspector">
        <div class="dashboard-view__role-card-header">
          <div class="dashboard-view__role-badge dashboard-view__role-badge--inspector">Inspector</div>
          <h3>Módulo de Inspección & Control</h3>
        </div>
        <p class="dashboard-view__role-card-desc">Controla la asistencia de los choferes y registra infracciones.</p>
        <div class="dashboard-view__action-buttons">
          <button class="dashboard-view__btn dashboard-view__btn--primary" id="btn-inspector-asistencia">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
            Tomar Asistencia
          </button>
          <button class="dashboard-view__btn dashboard-view__btn--danger" id="btn-inspector-multa">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            Registrar Multa
          </button>
        </div>
      </div>

      <!-- ─── Panel Tesorero ─────────────────────────────────────────── -->
      <div v-if="hasRole('Tesorero')" class="dashboard-view__role-card dashboard-view__role-card--tesorero">
        <div class="dashboard-view__role-card-header">
          <div class="dashboard-view__role-badge dashboard-view__role-badge--tesorero">Tesorero</div>
          <h3>Módulo de Tesorería & Cobros</h3>
        </div>
        <p class="dashboard-view__role-card-desc">Administra cobros de cuotas mensuales, aportes y registro de pagos.</p>
        <div class="dashboard-view__action-buttons">
          <button class="dashboard-view__btn dashboard-view__btn--success" id="btn-tesorero-pago">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            Registrar Cobro / Pago
          </button>
          <button class="dashboard-view__btn dashboard-view__btn--secondary" id="btn-tesorero-obligaciones">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            Ver Obligaciones
          </button>
        </div>
      </div>

      <!-- ─── Panel Jefe de Grupo ────────────────────────────────────── -->
      <div v-if="hasRole('Jefe de Grupo')" class="dashboard-view__role-card dashboard-view__role-card--jefe">
        <div class="dashboard-view__role-card-header">
          <div class="dashboard-view__role-badge dashboard-view__role-badge--jefe">Jefe de Grupo</div>
          <h3>Gestión del Grupo Asignado</h3>
        </div>
        <p class="dashboard-view__role-card-desc">Supervisa choferes, turnos y vehículos pertenecientes a tu grupo.</p>
        <div class="dashboard-view__action-buttons">
          <button class="dashboard-view__btn dashboard-view__btn--warning" id="btn-jefe-grupo">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            Mi Grupo de Choferes
          </button>
        </div>
      </div>

      <!-- ─── Panel Chofer ───────────────────────────────────────────── -->
      <div v-if="hasRole('Chofer')" class="dashboard-view__role-card dashboard-view__role-card--chofer">
        <div class="dashboard-view__role-card-header">
          <div class="dashboard-view__role-badge dashboard-view__role-badge--chofer">Chofer</div>
          <h3>Perfil del Afiliado</h3>
        </div>
        <p class="dashboard-view__role-card-desc">Consulta tus vehículos asignados, estado de cuenta y asistencias.</p>
        <div class="dashboard-view__action-buttons">
          <button class="dashboard-view__btn dashboard-view__btn--secondary" id="btn-chofer-vehiculos">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13" rx="2"/><circle cx="8.5" cy="13.5" r="2.5"/><path d="M16 8h4l3 5v4h-3"/></svg>
            Mis Vehículos
          </button>
        </div>
      </div>

      <!-- ─── Panel Administrador ────────────────────────────────────── -->
      <div v-if="hasRole('Administrador')" class="dashboard-view__role-card dashboard-view__role-card--admin">
        <div class="dashboard-view__role-card-header">
          <div class="dashboard-view__role-badge dashboard-view__role-badge--admin">Administrador</div>
          <h3>Administración Global del Sistema</h3>
        </div>
        <p class="dashboard-view__role-card-desc">Control total sobre usuarios, asignación de roles y configuraciones generales.</p>
        <div class="dashboard-view__action-buttons">
          <button class="dashboard-view__btn dashboard-view__btn--primary" @click="$router.push('/dashboard/afiliados')" id="btn-admin-usuarios">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            Gestión Usuarios y Roles
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useAuth } from '@/composables/useAuth'

const { userFullName, roles, hasRole } = useAuth()

function getRoleClass(role) {
  const map = {
    'Administrador': 'dashboard-view__role-chip--admin',
    'Jefe de Grupo': 'dashboard-view__role-chip--jefe',
    'Inspector': 'dashboard-view__role-chip--inspector',
    'Tesorero': 'dashboard-view__role-chip--tesorero',
    'Chofer': 'dashboard-view__role-chip--chofer',
  }
  return map[role] || ''
}
</script>

<style scoped>
.dashboard-view__roles-banner {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-top: 0.5rem;
}
.dashboard-view__roles-label {
  font-size: 0.85rem;
  color: var(--color-text-secondary);
}
.dashboard-view__role-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
}
.dashboard-view__role-chip {
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.25rem 0.6rem;
  border-radius: var(--radius-full);
  border: 1px solid var(--color-border);
  background: var(--color-bg-glass);
}
.dashboard-view__role-chip--admin {
  background: rgba(99, 102, 241, 0.2);
  color: var(--color-primary-300);
  border-color: var(--color-primary-500);
}
.dashboard-view__role-chip--jefe {
  background: rgba(245, 158, 11, 0.2);
  color: #fcd34d;
  border-color: #f59e0b;
}
.dashboard-view__role-chip--inspector {
  background: rgba(239, 68, 68, 0.2);
  color: #fca5a5;
  border-color: #ef4444;
}
.dashboard-view__role-chip--tesorero {
  background: rgba(34, 197, 94, 0.2);
  color: #86efac;
  border-color: #22c55e;
}
.dashboard-view__role-chip--chofer {
  background: rgba(59, 130, 246, 0.2);
  color: #93c5fd;
  border-color: #3b82f6;
}

.dashboard-view__section-title {
  margin: 2rem 0 1rem;
}
.dashboard-view__section-title h2 {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--color-text-primary);
}
.dashboard-view__section-title p {
  font-size: 0.85rem;
  color: var(--color-text-secondary);
}

.dashboard-view__role-panels {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 1.25rem;
}

.dashboard-view__role-card {
  background: var(--color-bg-card);
  backdrop-filter: blur(16px);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-xl);
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  transition: all var(--transition-fast);
}
.dashboard-view__role-card:hover {
  border-color: var(--color-border-hover);
  transform: translateY(-2px);
  box-shadow: var(--shadow-lg);
}

.dashboard-view__role-card-header {
  margin-bottom: 0.5rem;
}
.dashboard-view__role-card-header h3 {
  font-size: 1.05rem;
  font-weight: 600;
  color: var(--color-text-primary);
  margin-top: 0.35rem;
}
.dashboard-view__role-card-desc {
  font-size: 0.825rem;
  color: var(--color-text-secondary);
  margin-bottom: 1.25rem;
}

.dashboard-view__role-badge {
  display: inline-block;
  font-size: 0.65rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 0.15rem 0.5rem;
  border-radius: var(--radius-sm);
}
.dashboard-view__role-badge--inspector { background: rgba(239, 68, 68, 0.2); color: #fca5a5; }
.dashboard-view__role-badge--tesorero { background: rgba(34, 197, 94, 0.2); color: #86efac; }
.dashboard-view__role-badge--jefe { background: rgba(245, 158, 11, 0.2); color: #fcd34d; }
.dashboard-view__role-badge--chofer { background: rgba(59, 130, 246, 0.2); color: #93c5fd; }
.dashboard-view__role-badge--admin { background: rgba(99, 102, 241, 0.2); color: var(--color-primary-300); }

.dashboard-view__action-buttons {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.dashboard-view__btn {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.85rem;
  font-weight: 500;
  padding: 0.5rem 0.85rem;
  border-radius: var(--radius-md);
  cursor: pointer;
  transition: all var(--transition-fast);
}
.dashboard-view__btn svg {
  width: 16px;
  height: 16px;
}
.dashboard-view__btn--primary {
  background: var(--color-primary-600);
  color: white;
}
.dashboard-view__btn--primary:hover { background: var(--color-primary-500); }

.dashboard-view__btn--danger {
  background: rgba(239, 68, 68, 0.8);
  color: white;
}
.dashboard-view__btn--danger:hover { background: rgba(239, 68, 68, 1); }

.dashboard-view__btn--success {
  background: rgba(34, 197, 94, 0.8);
  color: white;
}
.dashboard-view__btn--success:hover { background: rgba(34, 197, 94, 1); }

.dashboard-view__btn--warning {
  background: rgba(245, 158, 11, 0.8);
  color: white;
}
.dashboard-view__btn--warning:hover { background: rgba(245, 158, 11, 1); }

.dashboard-view__btn--secondary {
  background: var(--color-bg-input);
  color: var(--color-text-primary);
  border: 1px solid var(--color-border);
}
.dashboard-view__btn--secondary:hover {
  border-color: var(--color-border-hover);
  background: rgba(255, 255, 255, 0.1);
}
</style>
