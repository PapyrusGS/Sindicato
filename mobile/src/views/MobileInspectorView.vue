<template>
  <div class="mobile-view">
    <div class="view-header">
      <h2>🔍 Control de Asistencia & Sanciones</h2>
      <p class="text-muted">Marcación rápida en parada con persistencia local y sincronización offline</p>
    </div>

    <!-- Banner de Sincronización Offline -->
    <div v-if="pendientesCount > 0" class="sync-banner">
      <div class="sync-info">
        <span class="sync-icon">🟠</span>
        <div>
          <strong>{{ pendientesCount }} Asistencia(s) Guardada(s) Localmente</strong>
          <small>Almacenadas en la memoria del celular sin conexión</small>
        </div>
      </div>
      <button class="btn btn-sync" @click="sincronizarOffline" :disabled="syncing">
        <span v-if="syncing">Enviando...</span>
        <span v-else>🔄 Sincronizar Ahora</span>
      </button>
    </div>

    <!-- Pestañas internas -->
    <div class="mobile-subtabs">
      <button :class="{ active: tab === 'asistencia' }" @click="tab = 'asistencia'">📋 Control de Parada</button>
      <button :class="{ active: tab === 'sancion' }" @click="tab = 'sancion'">⚖️ Imponer Sanción</button>
    </div>

    <div v-if="loading" class="loader-box">Cargando lista de choferes...</div>

    <div v-else class="view-body">
      <!-- ─── TAB 1: CONTROL DE ASISTENCIA (ONLINE / OFFLINE) ───────── -->
      <div v-if="tab === 'asistencia'">
        <div class="m-card">
          <div class="form-group">
            <label class="form-label">📍 Seleccionar Parada / Lugar</label>
            <select v-model="lugarSeleccionado" class="form-input">
              <option v-for="l in lugares" :key="l.id" :value="l.id">{{ l.nombre }}</option>
            </select>
          </div>
        </div>

        <!-- Alerta feedback -->
        <div v-if="feedbackMsg" :class="feedbackError ? 'alert alert-error' : 'alert alert-success'">
          {{ feedbackMsg }}
        </div>

        <!-- Planilla de Marcación -->
        <div v-for="c in choferes" :key="c.chofer_id" class="chofer-card">
          <div class="chofer-info">
            <strong>{{ c.nombre_completo }}</strong>
            <small class="font-mono text-muted">Placa: {{ c.placa }} — Grupo: {{ c.grupo_nombre }}</small>
          </div>

          <div class="status-buttons">
            <button
              class="btn-status btn-presente"
              :class="{ selected: c.asistencia === true }"
              @click="c.asistencia = true"
            >
              ✓ PRESENTE
            </button>

            <button
              class="btn-status btn-falta"
              :class="{ selected: c.asistencia === false }"
              @click="c.asistencia = false"
            >
              ✗ FALTA
            </button>
          </div>
        </div>

        <!-- Acciones de Guardado: Servidor u Offline Local -->
        <div class="save-actions">
          <button class="btn btn-success btn-lg flex-1" @click="guardarEnServidor" :disabled="saving">
            🌐 Guardar En Línea
          </button>

          <button class="btn btn-warning btn-lg flex-1" @click="guardarLocalmente">
            💾 Guardar en Celular (Offline)
          </button>
        </div>
      </div>

      <!-- ─── TAB 2: IMPONER SANCIÓN / INFRACCIÓN ────────────────────── -->
      <div v-if="tab === 'sancion'">
        <form @submit.prevent="registrarSancion" class="m-card">
          <h3 class="section-title">⚖️ Registrar Infracción a Chofer</h3>

          <div class="form-group">
            <label class="form-label">Chofer Infraccionado</label>
            <select v-model="formSancion.chofer_id" class="form-input" required>
              <option value="" disabled>Seleccionar chofer...</option>
              <option v-for="c in choferes" :key="c.chofer_id" :value="c.chofer_id">
                {{ c.nombre_completo }} (Placa: {{ c.placa }})
              </option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Tipo de Sanción</label>
            <select v-model="formSancion.tipo_sancion" class="form-input" required>
              <option value="ECONOMICA">💰 Económica (Multa en Dinero)</option>
              <option value="CASTIGO">🚗 Castigo (Inmovilización de Vehículo)</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Motivo de la Infracción *</label>
            <input v-model="formSancion.motivo" type="text" class="form-input" placeholder="ej. Salto de parada" required />
          </div>

          <div v-if="formSancion.tipo_sancion === 'ECONOMICA'" class="form-group">
            <label class="form-label">Monto de la Multa (Bs.) *</label>
            <input v-model.number="formSancion.monto" type="number" step="any" class="form-input" placeholder="ej. 20.00" required />
          </div>

          <div v-else class="form-group">
            <label class="form-label">Detalle del Castigo *</label>
            <input v-model="formSancion.sancion_detalle" type="text" class="form-input" placeholder="ej. Parqueo 1 hora" required />
          </div>

          <button type="submit" class="btn btn-primary btn-block btn-lg" :disabled="saving">
            Registrar Infracción
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import apiMobile from '@/services/axiosMobile'
import { offlineSyncService } from '@/services/offlineSync'

const tab = ref('asistencia')
const loading = ref(true)
const saving = ref(false)
const syncing = ref(false)

const lugares = ref([])
const choferes = ref([])
const lugarSeleccionado = ref(1)

const pendientesCount = ref(offlineSyncService.getPendientesCount())
const feedbackMsg = ref('')
const feedbackError = ref(false)

const formSancion = ref({
  chofer_id: '',
  lugar_id: 1,
  tipo_sancion: 'ECONOMICA',
  motivo: '',
  sancion_detalle: '',
  monto: 20,
})

async function cargarPlanilla() {
  loading.value = true
  try {
    const res = await apiMobile.get('/asistencias/hoy')
    const data = res.data.data
    
    if (data.lugar) {
      lugares.value = [data.lugar]
      lugarSeleccionado.value = data.lugar.id
    } else {
      lugares.value = [{ id: 1, nombre: 'Parada General' }]
      lugarSeleccionado.value = 1
    }

    const grupoNombre = data.grupo?.nombre || 'Grupo Asignado'

    if (Array.isArray(data.choferes)) {
      choferes.value = data.choferes.map(c => ({
        chofer_id: c.chofer_id,
        nombre_completo: c.nombre_completo,
        placa: c.placa,
        grupo_nombre: c.grupo_nombre || grupoNombre,
        asistencia: c.asistencia !== undefined ? c.asistencia : true,
      }))
    } else {
      choferes.value = []
    }
  } catch (err) {
    console.error('Error al cargar asistencia:', err)
  } finally {
    loading.value = false
  }
}

async function guardarEnServidor() {
  saving.value = true
  feedbackMsg.value = ''
  feedbackError.value = false

  try {
    const payload = {
      lugar_id: lugarSeleccionado.value,
      fecha: new Date().toISOString().split('T')[0],
      asistencias: choferes.value.map(c => ({
        chofer_id: c.chofer_id,
        asistencia: c.asistencia,
      })),
    }

    const res = await apiMobile.post('/asistencias/guardar', payload)
    feedbackMsg.value = res.data.message || 'Asistencias guardadas exitosamente en el servidor'
  } catch (err) {
    feedbackError.value = true
    feedbackMsg.value = 'Sin respuesta del servidor. Se recomienda presionar "Guardar en Celular (Offline)".'
  } finally {
    saving.value = false
  }
}

function guardarLocalmente() {
  feedbackMsg.value = ''
  feedbackError.value = false

  const payload = {
    lugar_id: lugarSeleccionado.value,
    fecha: new Date().toISOString().split('T')[0],
    asistencias: choferes.value.map(c => ({
      chofer_id: c.chofer_id,
      asistencia: c.asistencia,
    })),
  }

  const res = offlineSyncService.guardarAsistenciaLocal(payload)
  pendientesCount.value = res.pendientes
  feedbackMsg.value = res.message
}

async function sincronizarOffline() {
  syncing.value = true
  try {
    const res = await offlineSyncService.sincronizarPendientes()
    pendientesCount.value = offlineSyncService.getPendientesCount()
    feedbackMsg.value = res.message
    feedbackError.value = !res.success
  } catch (err) {
    feedbackError.value = true
    feedbackMsg.value = 'Error al sincronizar con el servidor.'
  } finally {
    syncing.value = false
  }
}

async function registrarSancion() {
  saving.value = true
  formSancion.value.lugar_id = lugarSeleccionado.value
  formSancion.value.fecha_infraccion = new Date().toISOString()

  try {
    const res = await apiMobile.post('/sanciones/registrar', formSancion.value)
    alert(res.data.message || 'Sanción registrada correctamente')
    formSancion.value.motivo = ''
    formSancion.value.sancion_detalle = ''
  } catch (err) {
    const msg = err.response?.data?.message || 'Error al registrar la sanción'
    const details = err.response?.data?.errors ? '\n• ' + Object.values(err.response.data.errors).flat().join('\n• ') : ''
    alert(msg + details)
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  cargarPlanilla()
  pendientesCount.value = offlineSyncService.getPendientesCount()
})
</script>

<style scoped>
.mobile-view { padding: 1rem; padding-bottom: 5rem; }
.view-header { margin-bottom: 1rem; }
.view-header h2 { font-size: 1.3rem; font-weight: 800; }

.sync-banner { background: #fff7ed; border: 1px solid #ffedd5; padding: 0.75rem 1rem; border-radius: 0.8rem; display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
.sync-info { display: flex; align-items: center; gap: 0.6rem; font-size: 0.8rem; }
.btn-sync { background: #ea580c; color: white; border: none; padding: 0.4rem 0.8rem; font-size: 0.75rem; font-weight: 700; border-radius: 0.5rem; cursor: pointer; }

.mobile-subtabs { display: flex; gap: 0.4rem; background: #e2e8f0; padding: 0.2rem; border-radius: 0.6rem; margin-bottom: 1rem; }
.mobile-subtabs button { flex: 1; padding: 0.5rem; font-size: 0.75rem; font-weight: 700; border: none; background: none; border-radius: 0.5rem; cursor: pointer; }
.mobile-subtabs button.active { background: #ffffff; color: #2563eb; box-shadow: 0 1px 2px rgba(0,0,0,0.1); }

.m-card { background: #ffffff; border: 1px solid #cbd5e1; border-radius: 0.8rem; padding: 1rem; margin-bottom: 1rem; }
.form-group { margin-bottom: 0.75rem; }
.form-label { display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.25rem; }
.form-input { width: 100%; padding: 0.6rem 0.8rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; font-size: 0.9rem; }

.chofer-card { background: #ffffff; border: 1px solid #cbd5e1; border-radius: 0.8rem; padding: 0.85rem; margin-bottom: 0.75rem; display: flex; justify-content: space-between; align-items: center; }
.chofer-info { display: flex; flex-direction: column; }

.status-buttons { display: flex; gap: 0.4rem; }
.btn-status { padding: 0.45rem 0.75rem; font-size: 0.75rem; font-weight: 700; border-radius: 0.5rem; border: 1px solid #cbd5e1; background: #f8fafc; cursor: pointer; }
.btn-presente.selected { background: #22c55e; color: white; border-color: #16a34a; }
.btn-falta.selected { background: #ef4444; color: white; border-color: #dc2626; }

.save-actions { display: flex; gap: 0.5rem; margin-top: 1.25rem; }
.flex-1 { flex: 1; }
.btn { padding: 0.75rem 1rem; font-size: 0.85rem; font-weight: 700; border-radius: 0.6rem; border: none; cursor: pointer; }
.btn-success { background: #16a34a; color: white; }
.btn-warning { background: #f59e0b; color: white; }
.btn-primary { background: #2563eb; color: white; }
.btn-block { width: 100%; }

.alert { padding: 0.75rem; border-radius: 0.6rem; font-size: 0.85rem; margin-bottom: 1rem; }
.alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }
.alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }
</style>
