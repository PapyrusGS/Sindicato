<template>
  <div class="mobile-view">
    <div class="view-header">
      <h2>👑 Panel Jefe de Grupo & Admin</h2>
      <p class="text-muted">Gestión de cuotas de grupo, afiliados y bitácora de auditoría</p>
    </div>

    <!-- Pestañas internas -->
    <div class="mobile-subtabs">
      <button :class="{ active: tab === 'cuotas' }" @click="tab = 'cuotas'">💳 Crear Cuotas</button>
      <button :class="{ active: tab === 'auditorias' }" @click="tab = 'auditorias'">🔍 Bitácora Auditoría</button>
    </div>

    <div class="view-body">
      <!-- ─── TAB 1: CREAR CUOTAS DE GRUPO ──────────────────────────── -->
      <div v-if="tab === 'cuotas'">
        <form @submit.prevent="crearObligacion" class="m-card">
          <h3 class="section-title">➕ Imponer Cuota Mensual o Ayuda de Grupo</h3>

          <div class="form-group">
            <label class="form-label">Grupo de Trabajo Target</label>
            <select v-model="formObligacion.grupo_id" class="form-input" required>
              <option v-for="g in grupos" :key="g.id" :value="g.id">{{ g.nombre }} — {{ g.descripcion }}</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Tipo de Obligación</label>
            <select v-model="formObligacion.tipo_categoria" class="form-input" required>
              <option value="MENSUAL">📅 Cuota Mensual Obligatoria</option>
              <option value="AYUDA">🚨 Aporte de Ayuda / Emergencia</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Concepto / Causa *</label>
            <input v-model="formObligacion.concepto" type="text" class="form-input" placeholder="ej. Cuota Mantenimiento Sede" required />
          </div>

          <div class="form-group">
            <label class="form-label">Monto Individual por Chofer (Bs.) *</label>
            <input v-model.number="formObligacion.monto_individual" type="number" step="any" class="form-input" placeholder="ej. 50.00" required />
          </div>

          <div class="form-group">
            <label class="form-label">Fecha de Vencimiento *</label>
            <input v-model="formObligacion.fecha_fin" type="date" class="form-input" required />
          </div>

          <button type="submit" class="btn btn-primary btn-block btn-lg" :disabled="saving">
            <span v-if="saving">Generando cuotas...</span>
            <span v-else>Confirmar e Imponer Masivamente</span>
          </button>
        </form>

        <h3 class="section-title">Obligaciones Activas del Grupo</h3>
        <div v-if="obligaciones.length > 0">
          <div v-for="o in obligaciones" :key="o.id" class="m-card">
            <div class="auto-header">
              <strong>{{ o.concepto }}</strong>
              <span class="tag-badge">{{ o.tipo_categoria }}</span>
            </div>
            <div class="info-row">
              <small class="text-muted">Grupo: {{ o.grupo_nombre }}</small>
              <strong class="text-primary">Bs. {{ Number(o.monto_individual).toFixed(2) }} / chofer</strong>
            </div>
          </div>
        </div>
      </div>

      <!-- ─── TAB 2: AUDITORÍAS E HISTORIAL GLOBAL ───────────────────── -->
      <div v-if="tab === 'auditorias'">
        <div v-if="auditorias.length > 0">
          <div v-for="a in auditorias" :key="a.id" class="m-card">
            <div class="auto-header">
              <span class="tag-badge-blue">{{ a.accion }}</span>
              <small class="font-mono text-muted">{{ a.fecha_a }}</small>
            </div>
            <strong>Tabla: {{ a.tabla_nombre }} (ID: {{ a.registro_id }})</strong>
            <div v-if="a.campo" class="info-row" style="margin-top:0.3rem;">
              <span>Campo: {{ a.campo }}</span>
              <small class="text-muted">{{ a.valor_anterior }} ➔ {{ a.valor_nuevo }}</small>
            </div>
            <small class="text-muted">Usuario auditado: {{ a.persona_nombre || a.usuario_audit }}</small>
          </div>
        </div>
        <div v-else class="empty-msg">No se encontraron registros de auditoría.</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import apiMobile from '@/services/axiosMobile'

const tab = ref('cuotas')
const saving = ref(false)

const grupos = ref([])
const obligaciones = ref([])
const auditorias = ref([])

const formObligacion = ref({
  grupo_id: 1,
  tipo_categoria: 'MENSUAL',
  concepto: '',
  monto_individual: 30,
  fecha_inicio: new Date().toISOString().split('T')[0],
  fecha_fin: new Date(Date.now() + 30 * 86400000).toISOString().split('T')[0],
})

async function cargar() {
  try {
    const [resAux, resOb, resAud] = await Promise.all([
      apiMobile.get('/obligaciones/auxiliares'),
      apiMobile.get('/obligaciones'),
      apiMobile.get('/auditorias'),
    ])
    grupos.value = resAux.data.data.grupos
    if (grupos.value.length > 0) formObligacion.value.grupo_id = grupos.value[0].id
    obligaciones.value = resOb.data.data
    auditorias.value = resAud.data.data.data || resAud.data.data
  } catch (err) {
    console.error('Error al cargar panel de administración móvil:', err)
  }
}

async function crearObligacion() {
  saving.value = true
  try {
    const res = await apiMobile.post('/obligaciones/crear', formObligacion.value)
    alert(res.data.message || 'Cuota creada e impuesta masivamente con éxito')
    formObligacion.value.concepto = ''
    cargar()
  } catch (err) {
    alert(err.response?.data?.message || 'Error al crear la obligación')
  } finally {
    saving.value = false
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
.form-group { margin-bottom: 0.75rem; }
.form-label { display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.25rem; }
.form-input { width: 100%; padding: 0.6rem 0.8rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; font-size: 0.9rem; }

.section-title { font-size: 0.95rem; font-weight: 800; color: #1e293b; margin: 1rem 0 0.5rem 0; }
.auto-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; }
.info-row { display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem; }

.tag-badge { font-size: 0.7rem; background: #e2e8f0; padding: 0.2rem 0.4rem; border-radius: 0.3rem; }
.tag-badge-blue { font-size: 0.7rem; background: #dbeafe; color: #1e40af; padding: 0.2rem 0.4rem; border-radius: 0.3rem; font-weight: 700; }

.btn { padding: 0.75rem 1rem; font-size: 0.85rem; font-weight: 700; border-radius: 0.6rem; border: none; cursor: pointer; }
.btn-primary { background: #2563eb; color: white; }
.btn-block { width: 100%; }
.empty-msg { text-align: center; color: #94a3b8; font-size: 0.85rem; padding: 1.5rem; background: #f8fafc; border-radius: 0.8rem; }
</style>
