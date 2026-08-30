<template>
  <div class="mobile-view">
    <div class="view-header">
      <h2>💵 Cobros & Historial de Caja</h2>
      <p class="text-muted">Procesamiento táctil de cobros e historial financiero</p>
    </div>

    <!-- Pestañas internas -->
    <div class="mobile-subtabs">
      <button :class="{ active: tab === 'cobrar' }" @click="tab = 'cobrar'">💵 Procesar Cobro</button>
      <button :class="{ active: tab === 'historial' }" @click="tab = 'historial'">📜 Historial de Caja</button>
    </div>

    <div v-if="loading" class="loader-box">Cargando choferes elegibles...</div>

    <div v-else class="view-body">
      <!-- ─── TAB 1: PROCESAR COBRO EN CAJA ─────────────────────────── -->
      <div v-if="tab === 'cobrar'">
        <div class="m-card">
          <div class="form-group">
            <label class="form-label">👨‍✈️ Seleccionar Chofer a Cobrar</label>
            <select v-model="choferIdSeleccionado" class="form-input" @change="cargarEstadoCuenta">
              <option value="" disabled>Seleccione un chofer de la lista...</option>
              <option v-for="c in choferesElegibles" :key="c.id" :value="c.id">
                {{ c.nombre_completo }} (CI: {{ c.ci }}) — Placa: {{ c.placa }}
              </option>
            </select>
          </div>
        </div>

        <!-- Estado de Cuenta del Chofer Seleccionado -->
        <div v-if="estadoCuenta" class="m-card">
          <div class="auto-header">
            <strong>Chofer: {{ estadoCuenta.chofer.nombre_completo }}</strong>
            <small class="font-mono text-muted">CI: {{ estadoCuenta.chofer.ci }}</small>
          </div>

          <div class="total-banner" style="margin: 0.75rem 0;">
            <span>Saldo Total Pendiente:</span>
            <strong class="text-danger">Bs. {{ Number(estadoCuenta.total_deuda_pendiente).toFixed(2) }}</strong>
          </div>

          <!-- Cuotas de Grupo -->
          <h4 class="section-title">📅 Cuotas Mensuales y Aportes Pendientes:</h4>
          <div v-if="estadoCuenta.obligaciones_pendientes.length > 0">
            <div v-for="ob in estadoCuenta.obligaciones_pendientes" :key="'ob-' + ob.id" class="debt-item">
              <label class="checkbox-label">
                <input type="checkbox" :value="ob" v-model="obligacionesSeleccionadas" />
                <div>
                  <strong>{{ ob.concepto }}</strong>
                  <small class="text-muted">Saldo: Bs. {{ Number(ob.saldo_pendiente).toFixed(2) }}</small>
                </div>
              </label>
            </div>
          </div>
          <p v-else class="text-muted" style="font-size:0.8rem;">No tiene cuotas de grupo pendientes.</p>

          <!-- Multas Económicas -->
          <h4 class="section-title">💰 Multas Económicas Pendientes:</h4>
          <div v-if="estadoCuenta.multas_pendientes.length > 0">
            <div v-for="m in estadoCuenta.multas_pendientes" :key="'m-' + m.id" class="debt-item">
              <label class="checkbox-label">
                <input type="checkbox" :value="m" v-model="multasSeleccionadas" />
                <div>
                  <strong>{{ m.motivo }}</strong>
                  <small class="text-muted">Monto: Bs. {{ Number(m.monto).toFixed(2) }}</small>
                </div>
              </label>
            </div>
          </div>
          <p v-else class="text-muted" style="font-size:0.8rem;">No tiene multas económicas pendientes.</p>

          <!-- Resumen de Cobro -->
          <div class="cobro-summary">
            <div class="info-row">
              <span>Método de Pago:</span>
              <select v-model="metodoPago" class="form-input-sm">
                <option value="EFECTIVO">💵 Efectivo</option>
                <option value="TRANSFERENCIA_QR">📱 Transferencia QR</option>
              </select>
            </div>

            <div class="info-row" style="margin-top: 0.5rem;">
              <span>Total a Cobrar Seleccionado:</span>
              <strong class="text-success" style="font-size:1.2rem;">Bs. {{ totalCalculado.toFixed(2) }}</strong>
            </div>

            <button class="btn btn-primary btn-block btn-lg" style="margin-top: 1rem;" :disabled="totalCalculado <= 0 || processing" @click="procesarPago">
              <span v-if="processing">Procesando...</span>
              <span v-else>Confirmar y Cobrar (Bs. {{ totalCalculado.toFixed(2) }})</span>
            </button>
          </div>
        </div>
      </div>

      <!-- ─── TAB 2: HISTORIAL DE CAJA ──────────────────────────────── -->
      <div v-if="tab === 'historial'">
        <div v-if="historialPagos.length > 0">
          <div v-for="p in historialPagos" :key="p.id" class="m-card">
            <div class="auto-header">
              <strong>📅 {{ p.fecha_pago }}</strong>
              <strong class="text-success">Bs. {{ Number(p.monto_total).toFixed(2) }}</strong>
            </div>
            <div class="info-row" style="margin-top:0.3rem;">
              <span>Chofer: {{ p.chofer?.persona?.nombre_completo || p.chofer_nombre || 'N/A' }}</span>
              <span class="tag-badge">{{ p.metodo_pago }}</span>
            </div>
            <small class="text-muted">Cobrado por: {{ p.cobrador_persona?.nombre_completo || p.cobrador_nombre || 'Tesorero' }}</small>
          </div>
        </div>
        <div v-else class="empty-msg">No se registraron cobros en caja.</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import apiMobile from '@/services/axiosMobile'

const tab = ref('cobrar')
const loading = ref(true)
const processing = ref(false)

const choferesElegibles = ref([])
const choferIdSeleccionado = ref('')
const estadoCuenta = ref(null)
const historialPagos = ref([])

const obligacionesSeleccionadas = ref([])
const multasSeleccionadas = ref([])
const metodoPago = ref('EFECTIVO')

const totalCalculado = computed(() => {
  const sumOb = obligacionesSeleccionadas.value.reduce((acc, i) => acc + (float(i.saldo_pendiente) || 0), 0)
  const sumM = multasSeleccionadas.value.reduce((acc, i) => acc + (float(i.monto) || 0), 0)
  return sumOb + sumM
})

function float(val) {
  return parseFloat(val) || 0
}

async function cargarChoferes() {
  loading.value = true
  try {
    const res = await apiMobile.get('/cobros/choferes')
    choferesElegibles.value = res.data.data.choferes || (Array.isArray(res.data.data) ? res.data.data : [])
  } catch (err) {
    console.error('Error al cargar choferes elegibles:', err)
  } finally {
    loading.value = false
  }
}

async function cargarEstadoCuenta() {
  if (!choferIdSeleccionado.value) return
  obligacionesSeleccionadas.value = []
  multasSeleccionadas.value = []

  try {
    const res = await apiMobile.get(`/cobros/estado-cuenta/${choferIdSeleccionado.value}`)
    estadoCuenta.value = res.data.data
  } catch (err) {
    alert('Error al obtener estado de cuenta del chofer')
  }
}

async function cargarHistorial() {
  try {
    const res = await apiMobile.get('/cobros/historial')
    historialPagos.value = res.data.data.pagos || (Array.isArray(res.data.data) ? res.data.data : [])
  } catch (err) {
    console.error('Error al cargar historial de caja:', err)
  }
}

async function procesarPago() {
  if (totalCalculado.value <= 0) return
  processing.value = true

  const payload = {
    chofer_id: choferIdSeleccionado.value,
    metodo_pago: metodoPago.value,
    deudas_obligaciones: obligacionesSeleccionadas.value.map(o => ({
      id: o.id,
      monto: o.saldo_pendiente,
    })),
    deudas_multas: multasSeleccionadas.value.map(m => ({
      id: m.id,
      monto: m.monto,
    })),
    observacion: 'Cobro procesado desde App Móvil',
  }

  try {
    const res = await apiMobile.post('/cobros/procesar', payload)
    alert(res.data.message || 'Pago procesado con éxito en caja')
    cargarEstadoCuenta()
    cargarHistorial()
  } catch (err) {
    const msg = err.response?.data?.message || 'Error al procesar el pago'
    const details = err.response?.data?.errors ? '\n• ' + Object.values(err.response.data.errors).flat().join('\n• ') : ''
    alert(msg + details)
  } finally {
    processing.value = false
  }
}

onMounted(() => {
  cargarChoferes()
  cargarHistorial()
})
</script>

<style scoped>
.mobile-view { padding: 1rem; padding-bottom: 5rem; }
.view-header { margin-bottom: 1rem; }
.view-header h2 { font-size: 1.3rem; font-weight: 800; }

.mobile-subtabs { display: flex; gap: 0.4rem; background: #e2e8f0; padding: 0.2rem; border-radius: 0.6rem; margin-bottom: 1rem; }
.mobile-subtabs button { flex: 1; padding: 0.5rem; font-size: 0.75rem; font-weight: 700; border: none; background: none; border-radius: 0.5rem; cursor: pointer; }
.mobile-subtabs button.active { background: #ffffff; color: #2563eb; box-shadow: 0 1px 2px rgba(0,0,0,0.1); }

.m-card { background: #ffffff; border: 1px solid #cbd5e1; border-radius: 0.8rem; padding: 1rem; margin-bottom: 1rem; }
.form-group { margin-bottom: 0.75rem; }
.form-label { display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.25rem; }
.form-input { width: 100%; padding: 0.6rem 0.8rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; font-size: 0.9rem; }
.form-input-sm { padding: 0.4rem 0.6rem; border: 1px solid #cbd5e1; border-radius: 0.4rem; font-size: 0.85rem; }

.section-title { font-size: 0.85rem; font-weight: 800; color: #1e293b; margin: 0.8rem 0 0.4rem 0; }
.debt-item { padding: 0.5rem 0; border-bottom: 1px solid #f1f5f9; }
.checkbox-label { display: flex; align-items: center; gap: 0.6rem; font-size: 0.85rem; cursor: pointer; }

.total-banner { padding: 0.75rem; background: #fef2f2; border: 1px solid #fecaca; border-radius: 0.6rem; display: flex; justify-content: space-between; font-size: 0.9rem; }
.cobro-summary { margin-top: 1rem; padding-top: 1rem; border-top: 1px dashed #cbd5e1; }
.info-row { display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem; }

.btn { padding: 0.75rem 1rem; font-size: 0.85rem; font-weight: 700; border-radius: 0.6rem; border: none; cursor: pointer; }
.btn-primary { background: #2563eb; color: white; }
.btn-block { width: 100%; }
.tag-badge { font-size: 0.7rem; background: #e2e8f0; padding: 0.2rem 0.4rem; border-radius: 0.3rem; }
.empty-msg { text-align: center; color: #94a3b8; font-size: 0.85rem; padding: 1.5rem; background: #f8fafc; border-radius: 0.8rem; }
</style>
