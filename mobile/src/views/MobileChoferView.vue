<template>
  <div class="mobile-view">
    <div class="view-header">
      <h2>👤 Mi Perfil & Vehículos</h2>
      <p class="text-muted">Consulta de información personal, vehículos y estado de cuenta</p>
    </div>

    <!-- Pestañas internas -->
    <div class="mobile-subtabs">
      <button :class="{ active: tab === 'vehiculos' }" @click="tab = 'vehiculos'">🚘 Perfil & Vehículos</button>
      <button :class="{ active: tab === 'asistencias' }" @click="tab = 'asistencias'">📋 Mis Asistencias</button>
      <button :class="{ active: tab === 'deudas' }" @click="tab = 'deudas'">💳 Deudas & Pagos</button>
    </div>

    <!-- Loader -->
    <div v-if="loading" class="loader-box">
      <span>Cargando datos del perfil...</span>
    </div>

    <div v-else-if="perfil" class="view-body">
      <!-- ─── TAB 1: VEHÍCULOS & PERFIL ──────────────────────────────── -->
      <div v-if="tab === 'vehiculos'">
        <div class="m-card">
          <div class="m-card-header">
            <h3>{{ perfil.persona.nombre_completo }}</h3>
            <small class="font-mono text-muted">CI: {{ perfil.persona.ci }}</small>
          </div>
          <div class="m-card-body">
            <div class="info-row"><span>Usuario:</span> <strong>@{{ perfil.persona.username }}</strong></div>
            <div class="info-row"><span>Grupo:</span> <strong>{{ perfil.grupo.nombre }}</strong></div>
            <div class="info-row"><span>Celular:</span> <span>{{ perfil.persona.celular || 'N/A' }}</span></div>
          </div>
        </div>

        <h3 class="section-title">🚘 Vehículo que Conduce Actualmente</h3>
        <div v-if="perfil.conduce_actualmente.length > 0" class="m-card">
          <div v-for="c in perfil.conduce_actualmente" :key="c.chofer_auto_id" class="m-auto-item">
            <div class="auto-header">
              <span class="placa-badge font-mono">{{ c.placa }}</span>
              <span class="tag-badge">{{ c.es_mi_propio_auto ? '🚗 Propio' : '🚘 Propiedad de tercero' }}</span>
            </div>
            <strong>{{ c.marca }} {{ c.modelo }} ({{ c.gestion }})</strong>
            <small class="text-muted">Propietario: {{ c.propietario.nombre_completo }} (CI: {{ c.propietario.ci }})</small>
          </div>
        </div>
        <div v-else class="empty-msg">No tiene vehículo asignado actualmente para su turno.</div>

        <div v-if="perfil.resumen.es_propietario">
          <h3 class="section-title">🚘 Flota de Vehículos Propios ({{ perfil.resumen.total_autos_propiedad }})</h3>
          <div v-for="v in perfil.vehiculos_propiedad" :key="v.auto_id" class="m-card">
            <div class="auto-header">
              <span class="placa-badge font-mono">{{ v.placa }}</span>
              <span class="tag-badge-green">{{ v.estado_conduccion }}</span>
            </div>
            <strong>{{ v.marca }} {{ v.modelo }}</strong>
            <div class="choferes-list">
              <small v-for="ch in v.choferes_asignados" :key="ch.chofer_id" class="chofer-sub">
                {{ ch.es_usted_mismo ? '🚗 Conducido por usted' : '👨‍✈️ Conducido por: ' + ch.nombre_completo + ' (CI: ' + ch.ci + ')' }}
              </small>
            </div>
          </div>
        </div>
      </div>

      <!-- ─── TAB 2: MIS ASISTENCIAS ─────────────────────────────────── -->
      <div v-if="tab === 'asistencias'">
        <div class="m-card">
          <h3 class="section-title">📋 Registro Personal de Asistencias</h3>
          <div v-if="historial?.asistencias?.length > 0">
            <div v-for="a in historial.asistencias" :key="a.id" class="history-item">
              <div>
                <strong>📅 {{ a.fecha_asistencia }}</strong>
                <small class="text-muted">📍 {{ a.lugar_nombre }} — {{ a.registrado_por }}</small>
              </div>
              <span :class="a.estado === 'PRESENTE' ? 'status-present' : 'status-absent'">{{ a.estado }}</span>
            </div>
          </div>
          <div v-else class="empty-msg">No se registraron asistencias.</div>
        </div>
      </div>

      <!-- ─── TAB 3: DEUDAS & PAGOS ──────────────────────────────────── -->
      <div v-if="tab === 'deudas'">
        <div class="total-banner">
          <span>Deuda Total Pendiente:</span>
          <strong class="text-danger">Bs. {{ Number(historial?.deudas?.total_deuda_pendiente || 0).toFixed(2) }}</strong>
        </div>

        <h3 class="section-title">💳 Transacciones y Pagos Realizados</h3>
        <div v-if="historial?.pagos_realizados?.length > 0">
          <div v-for="p in historial.pagos_realizados" :key="p.id" class="m-card">
            <div class="auto-header">
              <strong>📅 {{ p.fecha_pago }}</strong>
              <strong class="text-success">Bs. {{ Number(p.monto_total).toFixed(2) }}</strong>
            </div>
            <small class="text-muted">Cobrado por: {{ p.cobrador_nombre }} ({{ p.metodo_pago }})</small>
            <div class="concept-list">
              <small v-for="(c, idx) in p.conceptos_cancelados" :key="idx" class="concept-tag">{{ c }}</small>
            </div>
          </div>
        </div>
        <div v-else class="empty-msg">No registra pagos cancelados.</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import apiMobile from '@/services/axiosMobile'

const tab = ref('vehiculos')
const loading = ref(true)
const perfil = ref(null)
const historial = ref(null)

async function cargar() {
  loading.value = true
  try {
    const [resP, resH] = await Promise.all([
      apiMobile.get('/chofer/mi-perfil'),
      apiMobile.get('/chofer/mi-historial')
    ])
    perfil.value = resP.data.data
    historial.value = resH.data.data
  } catch (err) {
    console.error('Error al cargar perfil móvil:', err)
  } finally {
    loading.value = false
  }
}

onMounted(cargar)
</script>

<style scoped>
.mobile-view { padding: 1rem; padding-bottom: 5rem; }
.view-header { margin-bottom: 1rem; }
.view-header h2 { font-size: 1.3rem; font-weight: 800; }

.mobile-subtabs { display: flex; gap: 0.4rem; background: #e2e8f0; padding: 0.2rem; border-radius: 0.6rem; margin-bottom: 1rem; }
.mobile-subtabs button { flex: 1; padding: 0.5rem; font-size: 0.75rem; font-weight: 700; border: none; background: none; border-radius: 0.5rem; cursor: pointer; }
.mobile-subtabs button.active { background: #ffffff; color: #2563eb; box-shadow: 0 1px 2px rgba(0,0,0,0.1); }

.m-card { background: #ffffff; border: 1px solid #cbd5e1; border-radius: 0.8rem; padding: 1rem; margin-bottom: 1rem; }
.info-row { display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 0.4rem; }

.section-title { font-size: 0.95rem; font-weight: 800; color: #1e293b; margin: 1rem 0 0.5rem 0; }
.auto-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; }
.placa-badge { background: #f1f5f9; padding: 0.2rem 0.5rem; border-radius: 0.4rem; border: 1px solid #cbd5e1; font-weight: 800; color: #1e293b; }
.tag-badge { font-size: 0.7rem; background: #e2e8f0; padding: 0.2rem 0.4rem; border-radius: 0.3rem; }
.tag-badge-green { font-size: 0.7rem; background: #dcfce7; color: #166534; padding: 0.2rem 0.4rem; border-radius: 0.3rem; font-weight: 700; }

.choferes-list { margin-top: 0.5rem; display: flex; flex-direction: column; gap: 0.2rem; }
.chofer-sub { font-size: 0.75rem; color: #475569; }

.history-item { display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0; border-bottom: 1px solid #f1f5f9; }
.status-present { color: #16a34a; font-weight: 700; font-size: 0.8rem; }
.status-absent { color: #dc2626; font-weight: 700; font-size: 0.8rem; }

.total-banner { padding: 1rem; background: #fef2f2; border: 1px solid #fecaca; border-radius: 0.8rem; display: flex; justify-content: space-between; font-size: 1rem; margin-bottom: 1rem; }
.concept-tag { font-size: 0.7rem; background: #f1f5f9; padding: 0.2rem 0.4rem; border-radius: 0.3rem; margin-top: 0.2rem; display: inline-block; }
.empty-msg { text-align: center; color: #94a3b8; font-size: 0.85rem; padding: 1.5rem; background: #f8fafc; border-radius: 0.8rem; }
</style>
