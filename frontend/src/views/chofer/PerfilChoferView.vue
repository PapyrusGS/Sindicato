<template>
  <div class="perfil-page">
    <!-- Page Header -->
    <div class="perfil-page__header">
      <div>
        <h1 class="perfil-page__title">Mi Perfil de Afiliado & Historial Personal</h1>
        <p class="perfil-page__subtitle">Consulta de datos personales, flota de vehículos, asistencias registradas, deudas y pagos</p>
      </div>

      <div class="header-badges">
        <span v-for="r in perfilData?.persona?.roles" :key="r" class="badge badge-admin">
          {{ r }}
        </span>
      </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="main-tabs">
      <button
        class="tab-btn"
        :class="{ 'tab-btn--active': activeTab === 'perfil' }"
        @click="activeTab = 'perfil'"
      >
        👤 Mi Perfil & Vehículos
      </button>

      <button
        class="tab-btn"
        :class="{ 'tab-btn--active': activeTab === 'asistencias' }"
        @click="activeTab = 'asistencias'"
      >
        📋 Mis Asistencias ({{ historialData?.asistencias?.length || 0 }})
      </button>

      <button
        class="tab-btn"
        :class="{ 'tab-btn--active': activeTab === 'deudas' }"
        @click="activeTab = 'deudas'"
      >
        💳 Mis Deudas y Pagos
        <span v-if="(historialData?.deudas?.total_deuda_pendiente || 0) > 0" class="badge badge-tesorero ml-1" style="font-size:0.7rem;">
          Bs. {{ Number(historialData.deudas.total_deuda_pendiente).toFixed(2) }}
        </span>
      </button>
    </div>

    <AppLoader v-if="loading" :visible="true" message="Cargando información personal..." style="padding: 4rem 0;" />

    <div v-else class="perfil-content">
      <!-- ─── PESTAÑA 1: PERFIL & VEHÍCULOS ──────────────────────────── -->
      <div v-if="activeTab === 'perfil' && perfilData" class="tab-pane">
        <!-- TARJETA 1: DATOS PERSONALES -->
        <div class="card card-personal">
          <div class="card-header">
            <div class="card-header__icon">👤</div>
            <div>
              <h2 class="card-title">Datos Personales y Cuenta de Afiliado</h2>
              <p class="card-subtitle">Información registrada en la base de datos del sindicato</p>
            </div>
          </div>

          <div class="personal-grid">
            <div class="info-item">
              <span class="info-label">Nombre Completo:</span>
              <strong class="info-value">{{ perfilData.persona.nombre_completo }}</strong>
            </div>

            <div class="info-item">
              <span class="info-label">Cédula de Identidad (CI):</span>
              <strong class="info-value font-mono">{{ perfilData.persona.ci }}</strong>
            </div>

            <div class="info-item">
              <span class="info-label">Celular / Teléfono:</span>
              <span class="info-value">{{ perfilData.persona.celular || 'Sin registrar' }}</span>
            </div>

            <div class="info-item">
              <span class="info-label">Nombre de Usuario:</span>
              <span class="info-value text-primary font-mono">@{{ perfilData.persona.username }}</span>
            </div>

            <div class="info-item info-item--full">
              <span class="info-label">Dirección Domiciliaria:</span>
              <span class="info-value">{{ perfilData.persona.direccion || 'Sin registrar' }}</span>
            </div>

            <div class="info-item info-item--full group-box">
              <div class="group-box__icon">👥</div>
              <div>
                <span class="info-label">Grupo de Trabajo Asignado:</span>
                <h3 class="group-title">{{ perfilData.grupo.nombre }}</h3>
                <p class="group-desc">{{ perfilData.grupo.descripcion || 'Grupo operativo del sindicato' }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- TARJETA 2: VEHÍCULO EN CONDUCCIÓN ACTIVA (COMO CHOFER) -->
        <div class="card card-conduccion" style="margin-top: 1.5rem;">
          <div class="card-header">
            <div class="card-header__icon">👨‍✈️</div>
            <div>
              <h2 class="card-title">Vehículo que Conduce Actualmente</h2>
              <p class="card-subtitle">Asignación operativa activa para turnos e inspecciones</p>
            </div>
          </div>

          <div v-if="perfilData.conduce_actualmente.length > 0" class="auto-cards-grid">
            <div
              v-for="c in perfilData.conduce_actualmente"
              :key="c.chofer_auto_id"
              class="auto-card auto-card--active"
            >
              <div class="auto-card__header">
                <span class="placa-badge font-mono">{{ c.placa }}</span>
                <span v-if="c.es_mi_propio_auto" class="badge badge-success">
                  🚗 Es su propio vehículo
                </span>
                <span v-else class="badge badge-admin">
                  🚘 Propiedad de tercero
                </span>
              </div>

              <div class="auto-card__body">
                <h3 class="auto-title">{{ c.marca }} {{ c.modelo }}</h3>
                <span class="auto-gestion text-muted">Año / Gestión: {{ c.gestion }}</span>

                <div class="owner-box" style="margin-top: 0.75rem;">
                  <span class="owner-label">Propietario del Vehículo:</span>
                  <strong class="owner-name">{{ c.propietario.nombre_completo }}</strong>
                  <small class="text-muted font-mono">CI: {{ c.propietario.ci }} — Cel: {{ c.propietario.celular || 'N/A' }}</small>
                </div>
              </div>
            </div>
          </div>

          <div v-else class="empty-box">
            <p>⚠️ Actualmente no tiene ningún vehículo asignado para conducir en su turno.</p>
          </div>
        </div>

        <!-- TARJETA 3: FLOTA DE VEHÍCULOS PROPIOS (SI ES PROPIETARIO) -->
        <div v-if="perfilData.resumen.es_propietario" class="card card-flota" style="margin-top: 1.5rem;">
          <div class="card-header">
            <div class="card-header__icon">🚘</div>
            <div>
              <h2 class="card-title">Flota de Vehículos de su Propiedad</h2>
              <p class="card-subtitle">
                Posee {{ perfilData.resumen.total_autos_propiedad }} vehículo(s) registrado(s) a su nombre. A continuación se detalla quién conduce cada auto:
              </p>
            </div>
          </div>

          <div v-if="perfilData.vehiculos_propiedad.length > 0" class="flota-grid">
            <div
              v-for="v in perfilData.vehiculos_propiedad"
              :key="v.auto_id"
              class="flota-card"
            >
              <div class="flota-card__header">
                <span class="placa-badge font-mono">{{ v.placa }}</span>
                <span :class="getStatusBadgeClass(v.estado_conduccion)">
                  {{ getStatusBadgeLabel(v) }}
                </span>
              </div>

              <div class="flota-card__body">
                <h3 class="auto-title">{{ v.marca }} {{ v.modelo }}</h3>
                <span class="auto-gestion text-muted">Gestión: {{ v.gestion }}</span>

                <div class="choferes-section">
                  <span class="choferes-title">Conductor(es) Asignado(s):</span>

                  <div v-if="v.choferes_asignados.length > 0" class="choferes-list">
                    <div
                      v-for="ch in v.choferes_asignados"
                      :key="ch.chofer_id"
                      class="chofer-item"
                      :class="{ 'chofer-item--self': ch.es_usted_mismo }"
                    >
                      <div class="chofer-item__icon">
                        {{ ch.es_usted_mismo ? '🚗' : '👨‍✈️' }}
                      </div>
                      <div class="chofer-item__info">
                        <strong class="chofer-name">
                          {{ ch.es_usted_mismo ? 'Usted mismo' : ch.nombre_completo }}
                        </strong>
                        <small class="text-muted">
                          CI: {{ ch.ci }} {{ ch.celular ? '— Cel: ' + ch.celular : '' }}
                        </small>
                      </div>
                    </div>
                  </div>

                  <div v-else class="no-chofer-msg">
                    <span>⚠️ Ningún chofer asignado actualmente a este vehículo.</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ─── PESTAÑA 2: MIS ASISTENCIAS ─────────────────────────────── -->
      <div v-else-if="activeTab === 'asistencias' && historialData" class="tab-pane">
        <div class="card">
          <div class="card-header">
            <div class="card-header__icon">📋</div>
            <div>
              <h2 class="card-title">Historial Personal de Control de Asistencias</h2>
              <p class="card-subtitle">Registros de retardo, asistencias y faltas procesadas por los inspectores de parada</p>
            </div>
          </div>

          <div class="table-container">
            <table v-if="historialData.asistencias.length > 0" class="data-table">
              <thead>
                <tr>
                  <th>Fecha y Hora</th>
                  <th>Lugar / Parada</th>
                  <th>Estado de Asistencia</th>
                  <th>Observaciones / Detalle</th>
                  <th>Registrado Por</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="a in historialData.asistencias" :key="a.id">
                  <td class="font-mono"><strong>📅 {{ a.fecha_asistencia }}</strong></td>
                  <td>
                    <span>📍 {{ a.lugar_nombre }}</span>
                  </td>
                  <td>
                    <span :class="getAsistenciaBadgeClass(a.estado)">
                      {{ a.estado }}
                    </span>
                  </td>
                  <td>
                    <span class="text-muted" style="font-size:0.85rem;">{{ a.observacion || 'Sin observaciones' }}</span>
                  </td>
                  <td>
                    <small class="text-muted">👤 {{ a.registrado_por }}</small>
                  </td>
                </tr>
              </tbody>
            </table>

            <div v-else class="empty-box">
              <p>No tiene registros de asistencias registradas hasta la fecha.</p>
            </div>
          </div>
        </div>
      </div>

      <!-- ─── PESTAÑA 3: MIS DEUDAS Y PAGOS ──────────────────────────── -->
      <div v-else-if="activeTab === 'deudas' && historialData" class="tab-pane">
        <!-- SECCIÓN 1: DEUDAS PENDIENTES -->
        <div class="card card-deudas" style="margin-bottom: 1.5rem;">
          <div class="card-header">
            <div class="card-header__icon">💳</div>
            <div>
              <h2 class="card-title">Estado de Cuenta & Deudas Pendientes</h2>
              <p class="card-subtitle">Cuotas de grupo y sanciones económicas pendientes de cobro</p>
            </div>
          </div>

          <!-- Total Callout -->
          <div class="total-deuda-banner">
            <span>Total Pendiente a Cancelar:</span>
            <span class="total-deuda-monto">Bs. {{ Number(historialData.deudas.total_deuda_pendiente).toFixed(2) }}</span>
          </div>

          <!-- Grid de Deudas (Cuotas & Multas) -->
          <div class="deudas-grid" style="margin-top: 1rem;">
            <!-- Cuotas de Grupo -->
            <div class="deudas-subpanel">
              <h3 class="subpanel-title">📅 Cuotas Mensuales y Aportes de Ayuda:</h3>
              <div v-if="historialData.deudas.obligaciones_pendientes.length > 0" class="deudas-list">
                <div v-for="ob in historialData.deudas.obligaciones_pendientes" :key="'ob-' + ob.id" class="deuda-card">
                  <div class="deuda-card__info">
                    <strong>{{ ob.concepto }}</strong>
                    <small class="text-muted">Grupo: {{ ob.grupo_nombre }} — Vence: {{ ob.fecha_vencimiento }}</small>
                  </div>
                  <div class="deuda-card__monto">
                    <span class="badge badge-tesorero">{{ ob.estado_pago }}</span>
                    <strong class="text-danger">Saldo: Bs. {{ Number(ob.saldo_pendiente).toFixed(2) }}</strong>
                  </div>
                </div>
              </div>
              <p v-else class="text-muted" style="font-size:0.85rem; padding: 0.5rem 0;">✓ No tiene cuotas de grupo pendientes.</p>
            </div>

            <!-- Multas Económicas -->
            <div class="deudas-subpanel">
              <h3 class="subpanel-title">💰 Sanciones e Infracciones Económicas:</h3>
              <div v-if="historialData.deudas.multas_pendientes.length > 0" class="deudas-list">
                <div v-for="m in historialData.deudas.multas_pendientes" :key="'m-' + m.id" class="deuda-card">
                  <div class="deuda-card__info">
                    <strong>{{ m.motivo }}</strong>
                    <small class="text-muted">Lugar: {{ m.lugar_nombre }} — {{ m.fecha_infraccion }}</small>
                  </div>
                  <div class="deuda-card__monto">
                    <span class="badge badge-tesorero">PENDIENTE</span>
                    <strong class="text-danger">Bs. {{ Number(m.monto).toFixed(2) }}</strong>
                  </div>
                </div>
              </div>
              <p v-else class="text-muted" style="font-size:0.85rem; padding: 0.5rem 0;">✓ No tiene multas económicas pendientes.</p>
            </div>
          </div>
        </div>

        <!-- SECCIÓN 2: HISTORIAL DE PAGOS REALIZADOS -->
        <div class="card">
          <div class="card-header">
            <div class="card-header__icon">💵</div>
            <div>
              <h2 class="card-title">Historial de Pagos Realizados</h2>
              <p class="card-subtitle">Comprobantes y registros de cobros cancelados en caja</p>
            </div>
          </div>

          <div class="table-container">
            <table v-if="historialData.pagos_realizados.length > 0" class="data-table">
              <thead>
                <tr>
                  <th>Fecha y Hora</th>
                  <th>Quién le Cobró (Cobrador)</th>
                  <th>Conceptos / Deudas Canceladas</th>
                  <th>Método Pago</th>
                  <th>Monto Total Abonado</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="p in historialData.pagos_realizados" :key="p.id">
                  <td class="font-mono"><strong>📅 {{ p.fecha_pago }}</strong></td>
                  <td>
                    <div class="user-cell">
                      <span>👤 {{ p.cobrador_nombre }}</span>
                      <small class="text-muted font-mono">CI: {{ p.cobrador_ci }}</small>
                    </div>
                  </td>
                  <td>
                    <div class="conceptos-list">
                      <div v-for="(c, idx) in p.conceptos_cancelados" :key="idx" class="concepto-tag">
                        {{ c }}
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
                </tr>
              </tbody>
            </table>

            <div v-else class="empty-box">
              <p>No se encontraron pagos registrados a su nombre hasta la fecha.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { choferApi } from '@/api/choferApi'
import AppLoader from '@/components/common/AppLoader.vue'

const activeTab = ref('perfil')
const loading = ref(true)
const perfilData = ref(null)
const historialData = ref(null)

async function cargarDatos() {
  loading.value = true
  try {
    const [resPerfil, resHistorial] = await Promise.all([
      choferApi.obtenerMiPerfil(),
      choferApi.obtenerMiHistorial(),
    ])
    perfilData.value = resPerfil.data.data
    historialData.value = resHistorial.data.data
  } catch (err) {
    console.error('Error al cargar datos del chofer:', err)
  } finally {
    loading.value = false
  }
}

function getStatusBadgeClass(estado) {
  switch (estado) {
    case 'CONDUCIDO_POR_USTED': return 'badge badge-success'
    case 'CONDUCIDO_POR_OTRO': return 'badge badge-admin'
    default: return 'badge badge-tesorero'
  }
}

function getStatusBadgeLabel(v) {
  if (v.estado_conduccion === 'CONDUCIDO_POR_USTED') {
    return '🚗 Conducido por usted'
  } else if (v.estado_conduccion === 'CONDUCIDO_POR_OTRO') {
    const count = v.choferes_asignados.length
    return `👨‍✈️ Conducido por ${count} chofer(es)`
  } else {
    return '⚠️ Sin chofer asignado'
  }
}

function getAsistenciaBadgeClass(estado) {
  switch (estado) {
    case 'PRESENTE': return 'badge badge-success'
    case 'RETARDO': return 'badge badge-tesorero'
    case 'FALTA': return 'badge badge-danger'
    case 'LICENCIA': return 'badge badge-admin'
    default: return 'badge'
  }
}

onMounted(() => {
  cargarDatos()
})
</script>

<style scoped>
.perfil-page__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.25rem;
}
.perfil-page__title { font-size: 1.6rem; font-weight: 800; color: var(--color-text-primary); }
.perfil-page__subtitle { font-size: 0.85rem; color: var(--color-text-secondary); }

.header-badges { display: flex; gap: 0.4rem; }

.main-tabs {
  display: flex;
  background: #ffffff;
  border: 1px solid var(--color-border);
  padding: 0.25rem;
  border-radius: var(--radius-xl);
  margin-bottom: 1.5rem;
  gap: 0.25rem;
}
.tab-btn {
  padding: 0.6rem 1.2rem;
  font-size: 0.85rem;
  font-weight: 700;
  border: none;
  background: none;
  color: var(--color-text-secondary);
  border-radius: var(--radius-lg);
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.4rem;
  transition: all var(--transition-fast);
}
.tab-btn--active {
  background: var(--color-primary-600);
  color: white;
}

.card {
  background: #ffffff; border: 1px solid var(--color-border);
  border-radius: var(--radius-2xl); padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.card-header { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.25rem; border-bottom: 1px solid var(--color-border); padding-bottom: 1rem; }
.card-header__icon { font-size: 1.8rem; }
.card-title { font-size: 1.15rem; font-weight: 800; color: var(--color-text-primary); }
.card-subtitle { font-size: 0.8rem; color: var(--color-text-secondary); }

.personal-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; }
.info-item { display: flex; flex-direction: column; }
.info-item--full { grid-column: 1 / -1; }
.info-label { font-size: 0.75rem; color: var(--color-text-secondary); font-weight: 600; }
.info-value { font-size: 0.95rem; margin-top: 0.2rem; }

.group-box {
  margin-top: 0.5rem; padding: 1rem; background: var(--color-primary-50);
  border: 1px solid var(--color-primary-200); border-radius: var(--radius-xl);
  display: flex; gap: 0.85rem; align-items: center;
}
.group-box__icon { font-size: 2rem; }
.group-title { font-size: 1.1rem; font-weight: 800; color: var(--color-primary-900); }
.group-desc { font-size: 0.8rem; color: var(--color-text-secondary); margin-top: 0.15rem; }

.auto-cards-grid, .flota-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; }

.auto-card, .flota-card {
  border: 1px solid var(--color-border); border-radius: var(--radius-xl);
  padding: 1.25rem; background: #ffffff; display: flex; flex-direction: column;
}
.auto-card__header, .flota-card__header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; }

.placa-badge {
  font-size: 1.1rem; font-weight: 800; background: var(--color-bg-tertiary);
  padding: 0.25rem 0.6rem; border-radius: var(--radius-md); border: 1px solid var(--color-border); color: var(--color-primary-900);
}

.auto-title { font-size: 1.05rem; font-weight: 800; color: var(--color-text-primary); }
.auto-gestion { font-size: 0.8rem; }

.owner-box {
  padding: 0.75rem; background: var(--color-bg-tertiary); border: 1px solid var(--color-border);
  border-radius: var(--radius-lg); display: flex; flex-direction: column;
}
.owner-label { font-size: 0.7rem; color: var(--color-text-secondary); font-weight: 600; }
.owner-name { font-size: 0.9rem; color: var(--color-text-primary); }

.choferes-section { margin-top: 1rem; border-top: 1px dashed var(--color-border); padding-top: 0.75rem; }
.choferes-title { font-size: 0.75rem; font-weight: 700; color: var(--color-text-secondary); display: block; margin-bottom: 0.4rem; }

.choferes-list { display: flex; flex-direction: column; gap: 0.5rem; }
.chofer-item {
  display: flex; align-items: center; gap: 0.6rem; padding: 0.5rem 0.75rem;
  border-radius: var(--radius-lg); background: var(--color-bg-tertiary); border: 1px solid var(--color-border);
}
.chofer-item--self { background: var(--color-success-bg); border-color: #bbf7d0; }
.chofer-item__icon { font-size: 1.2rem; }
.chofer-item__info { display: flex; flex-direction: column; }
.chofer-name { font-size: 0.85rem; color: var(--color-text-primary); }

.no-chofer-msg { font-size: 0.8rem; color: var(--color-text-muted); font-style: italic; }

.empty-box { padding: 1.5rem; text-align: center; color: var(--color-text-muted); font-size: 0.9rem; background: var(--color-bg-tertiary); border-radius: var(--radius-xl); border: 1px dashed var(--color-border); }

/* Deudas & Pagos Styling */
.total-deuda-banner {
  padding: 1rem 1.25rem; background: var(--color-error-bg); border: 1px solid #fecaca;
  border-radius: var(--radius-xl); display: flex; justify-content: space-between; align-items: center;
  font-size: 1.1rem; font-weight: 800; color: var(--color-error); margin-bottom: 1.25rem;
}
.total-deuda-monto { font-size: 1.5rem; }

.deudas-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.deudas-subpanel { background: var(--color-bg-tertiary); border: 1px solid var(--color-border); border-radius: var(--radius-xl); padding: 1rem; }
.subpanel-title { font-size: 0.85rem; font-weight: 700; color: var(--color-text-secondary); margin-bottom: 0.75rem; }

.deudas-list { display: flex; flex-direction: column; gap: 0.5rem; }
.deuda-card {
  padding: 0.75rem; background: #ffffff; border: 1px solid var(--color-border);
  border-radius: var(--radius-lg); display: flex; justify-content: space-between; align-items: center;
}
.deuda-card__info { display: flex; flex-direction: column; font-size: 0.85rem; }
.deuda-card__monto { display: flex; flex-direction: column; align-items: flex-end; font-size: 0.85rem; }

.conceptos-list { display: flex; flex-direction: column; gap: 0.2rem; }
.concepto-tag { font-size: 0.75rem; background: var(--color-bg-tertiary); padding: 0.2rem 0.5rem; border-radius: var(--radius-sm); border: 1px solid var(--color-border); }
</style>
