<template>
  <transition name="fade">
    <div v-if="visible" class="modal-backdrop" @click.self="cerrar">
      <div class="modal-container">
        <!-- Header -->
        <div class="modal-header">
          <div>
            <h2 class="modal-title">💳 Registrar Cobro en Caja</h2>
            <p class="modal-subtitle">Seleccione el chofer, las deudas a cancelar y el método de pago</p>
          </div>
          <button class="modal-close-btn" @click="cerrar">&times;</button>
        </div>

        <!-- Alert Error -->
        <transition name="fade">
          <div v-if="errorMsg" class="modal-error">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            <span>{{ errorMsg }}</span>
          </div>
        </transition>

        <AppLoader v-if="loadingChoferes" :visible="true" message="Cargando choferes elegibles..." style="padding: 3rem 0;" />

        <!-- Form Body -->
        <form v-else @submit.prevent="guardarCobro" class="modal-body">
          <!-- Notice for Tesorero -->
          <div v-if="metaChoferes.es_tesorero" class="alert-info">
            ℹ️ <strong>Rol Tesorero:</strong> Se muestran los choferes de su grupo. Por regla del sindicato, su propia cuenta no se incluye (debe ser cobrada por el Jefe de Grupo).
          </div>
          <div v-else-if="metaChoferes.es_jefe_o_admin" class="alert-info">
            👑 <strong>Rol Jefe / Admin:</strong> Puede registrar el cobro de choferes de todos los grupos.
          </div>

          <!-- 1. Selección de Chofer -->
          <div class="form-group">
            <label class="form-label">Chofer Deudor *</label>
            <select v-model="selectedChoferId" @change="onChoferChange" class="form-control" required>
              <option value="" disabled>Seleccione un chofer para ver su estado de cuenta...</option>
              <option v-for="c in choferesList" :key="c.id" :value="c.id">
                {{ c.nombre_completo }} — (CI: {{ c.ci }}, {{ c.grupo_nombre }})
              </option>
            </select>
          </div>

          <AppLoader v-if="loadingCuenta" :visible="true" message="Consultando estado de cuenta del chofer..." style="padding: 1.5rem 0;" />

          <!-- 2. Deudas Pendientes del Chofer -->
          <div v-else-if="estadoCuenta" class="deudas-section">
            <h3 class="deudas-title">Deudas Pendientes de {{ estadoCuenta.chofer?.nombre_completo }}</h3>

            <!-- 2.1 Obligaciones de Grupo (Mensual y Ayuda) -->
            <div class="deudas-category">
              <h4>📅 Cuotas Mensuales y Aportes de Ayuda:</h4>
              <div v-if="estadoCuenta.obligaciones_pendientes.length > 0" class="deudas-list">
                <div
                  v-for="ob in estadoCuenta.obligaciones_pendientes"
                  :key="'ob-' + ob.id"
                  class="deuda-item"
                  :class="{ 'deuda-item--selected': seleccionadosObligaciones.has(ob.id) }"
                >
                  <label class="deuda-checkbox">
                    <input
                      type="checkbox"
                      :checked="seleccionadosObligaciones.has(ob.id)"
                      @change="toggleObligacion(ob)"
                    />
                    <div class="deuda-info">
                      <strong>{{ ob.concepto }}</strong>
                      <small class="text-muted">Vence: {{ ob.fecha_vencimiento }}</small>
                    </div>
                  </label>
                  <div class="deuda-monto">
                    <strong>Bs. {{ ob.saldo_pendiente.toFixed(2) }}</strong>
                  </div>
                </div>
              </div>
              <p v-else class="text-muted" style="font-size: 0.8rem; padding: 0.5rem 0;">No tiene cuotas de grupo pendientes.</p>
            </div>

            <!-- 2.2 Multas e Infracciones Económicas -->
            <div class="deudas-category" style="margin-top: 1rem;">
              <h4>💰 Multas e Infracciones Económicas:</h4>
              <div v-if="estadoCuenta.multas_pendientes.length > 0" class="deudas-list">
                <div
                  v-for="m in estadoCuenta.multas_pendientes"
                  :key="'m-' + m.id"
                  class="deuda-item"
                  :class="{ 'deuda-item--selected': seleccionadosMultas.has(m.id) }"
                >
                  <label class="deuda-checkbox">
                    <input
                      type="checkbox"
                      :checked="seleccionadosMultas.has(m.id)"
                      @change="toggleMulta(m)"
                    />
                    <div class="deuda-info">
                      <strong>{{ m.motivo }}</strong>
                      <small class="text-muted">Parada: {{ m.lugar_nombre }} — {{ m.fecha_infraccion }}</small>
                    </div>
                  </label>
                  <div class="deuda-monto">
                    <strong class="text-danger">Bs. {{ m.monto.toFixed(2) }}</strong>
                  </div>
                </div>
              </div>
              <p v-else class="text-muted" style="font-size: 0.8rem; padding: 0.5rem 0;">No tiene multas económicas pendientes.</p>
            </div>

            <!-- 3. Método de Pago & Resumen -->
            <div class="pago-config-grid" style="margin-top: 1.25rem;">
              <div class="form-group">
                <label class="form-label">Método de Pago *</label>
                <select v-model="form.metodo_pago" class="form-control" required>
                  <option value="EFECTIVO">💵 Efectivo en Caja</option>
                  <option value="TRANSFERENCIA_QR">📱 Transferencia QR / Bancaria</option>
                </select>
              </div>

              <div class="form-group">
                <label class="form-label">Observación / Nota (Opcional)</label>
                <input
                  v-model="form.observacion"
                  type="text"
                  class="form-control"
                  placeholder="ej. Cobro completo en reunión de grupo"
                />
              </div>
            </div>

            <!-- Total Box -->
            <div class="total-box">
              <span>Total a Cobrar:</span>
              <span class="total-amount">Bs. {{ calcularTotal().toFixed(2) }}</span>
            </div>
          </div>

          <!-- Footer Actions -->
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="cerrar">Cancelar</button>
            <button
              type="submit"
              class="btn btn-primary"
              :disabled="guardando || !selectedChoferId || calcularTotal() <= 0"
            >
              <span v-if="guardando">Procesando Cobro...</span>
              <span v-else>Confirmar y Registrar Cobro</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { cobroApi } from '@/api/cobroApi'
import AppLoader from '@/components/common/AppLoader.vue'

defineProps({
  visible: { type: Boolean, default: false },
})

const emit = defineEmits(['close', 'saved'])

const loadingChoferes = ref(true)
const loadingCuenta = ref(false)
const guardando = ref(false)
const errorMsg = ref(null)

const choferesList = ref([])
const metaChoferes = ref({})
const selectedChoferId = ref('')
const estadoCuenta = ref(null)

const seleccionadosObligaciones = ref(new Set())
const seleccionadosMultas = ref(new Set())

const form = reactive({
  metodo_pago: 'EFECTIVO',
  observacion: '',
})

async function cargarChoferes() {
  loadingChoferes.value = true
  try {
    const res = await cobroApi.obtenerChoferesElegibles()
    choferesList.value = res.data.data.choferes || []
    metaChoferes.value = res.data.data
  } catch (err) {
    errorMsg.value = err.response?.data?.message || 'Error al cargar lista de choferes'
  } finally {
    loadingChoferes.value = false
  }
}

async function onChoferChange() {
  if (!selectedChoferId.value) return
  loadingCuenta.value = true
  errorMsg.value = null
  estadoCuenta.value = null
  seleccionadosObligaciones.value.clear()
  seleccionadosMultas.value.clear()

  try {
    const res = await cobroApi.obtenerEstadoCuenta(selectedChoferId.value)
    estadoCuenta.value = res.data.data

    // Marcar por defecto todas las deudas pendientes
    if (estadoCuenta.value.obligaciones_pendientes) {
      estadoCuenta.value.obligaciones_pendientes.forEach(ob => seleccionadosObligaciones.value.add(ob.id))
    }
    if (estadoCuenta.value.multas_pendientes) {
      estadoCuenta.value.multas_pendientes.forEach(m => seleccionadosMultas.value.add(m.id))
    }
  } catch (err) {
    errorMsg.value = err.response?.data?.message || 'Error al consultar estado de cuenta'
  } finally {
    loadingCuenta.value = false
  }
}

function toggleObligacion(ob) {
  if (seleccionadosObligaciones.value.has(ob.id)) {
    seleccionadosObligaciones.value.delete(ob.id)
  } else {
    seleccionadosObligaciones.value.add(ob.id)
  }
}

function toggleMulta(m) {
  if (seleccionadosMultas.value.has(m.id)) {
    seleccionadosMultas.value.delete(m.id)
  } else {
    seleccionadosMultas.value.add(m.id)
  }
}

function calcularTotal() {
  if (!estadoCuenta.value) return 0
  let total = 0

  if (estadoCuenta.value.obligaciones_pendientes) {
    estadoCuenta.value.obligaciones_pendientes.forEach(ob => {
      if (seleccionadosObligaciones.value.has(ob.id)) {
        total += ob.saldo_pendiente
      }
    })
  }

  if (estadoCuenta.value.multas_pendientes) {
    estadoCuenta.value.multas_pendientes.forEach(m => {
      if (seleccionadosMultas.value.has(m.id)) {
        total += m.monto
      }
    })
  }

  return total
}

function cerrar() {
  emit('close')
}

async function guardarCobro() {
  if (calcularTotal() <= 0) {
    errorMsg.value = 'Debe seleccionar al menos una deuda pendiente para cobrar'
    return
  }

  guardando.value = true
  errorMsg.value = null

  try {
    const deudasOblig = []
    if (estadoCuenta.value?.obligaciones_pendientes) {
      estadoCuenta.value.obligaciones_pendientes.forEach(ob => {
        if (seleccionadosObligaciones.value.has(ob.id)) {
          deudasOblig.push({ id: ob.id, monto: ob.saldo_pendiente })
        }
      })
    }

    const deudasMultas = []
    if (estadoCuenta.value?.multas_pendientes) {
      estadoCuenta.value.multas_pendientes.forEach(m => {
        if (seleccionadosMultas.value.has(m.id)) {
          deudasMultas.push({ id: m.id, monto: m.monto })
        }
      })
    }

    const payload = {
      chofer_id: selectedChoferId.value,
      metodo_pago: form.metodo_pago,
      observacion: form.observacion,
      deudas_obligaciones: deudasOblig,
      deudas_multas: deudasMultas,
    }

    await cobroApi.procesarCobro(payload)
    emit('saved')
  } catch (err) {
    errorMsg.value = err.response?.data?.message || 'Error al procesar el cobro'
  } finally {
    guardando.value = false
  }
}

onMounted(() => {
  cargarChoferes()
})
</script>

<style scoped>
.modal-backdrop {
  position: fixed; top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0, 0, 0, 0.4); backdrop-filter: blur(4px);
  display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 1rem;
}
.modal-container {
  background: #ffffff; border: 1px solid var(--color-border); border-radius: var(--radius-2xl);
  width: 100%; max-width: 720px; max-height: 90vh; display: flex; flex-direction: column;
  box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); overflow: hidden;
}
.modal-header {
  padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--color-border);
  display: flex; justify-content: space-between; align-items: center; background: var(--color-bg-tertiary);
}
.modal-title { font-size: 1.2rem; font-weight: 800; color: var(--color-text-primary); }
.modal-subtitle { font-size: 0.8rem; color: var(--color-text-secondary); }
.modal-close-btn { background: none; border: none; font-size: 1.5rem; color: var(--color-text-muted); cursor: pointer; }

.modal-body { padding: 1.5rem; overflow-y: auto; flex: 1; }

.alert-info {
  background: var(--color-primary-50); border: 1px solid var(--color-primary-200);
  border-radius: var(--radius-lg); padding: 0.75rem 1rem; font-size: 0.85rem;
  color: var(--color-primary-900); margin-bottom: 1rem;
}

.deudas-title { font-size: 1rem; font-weight: 700; color: var(--color-text-primary); margin-bottom: 0.75rem; }
.deudas-category h4 { font-size: 0.85rem; font-weight: 700; color: var(--color-text-secondary); margin-bottom: 0.5rem; }

.deudas-list { display: flex; flex-direction: column; gap: 0.5rem; }
.deuda-item {
  display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 1rem;
  border: 1px solid var(--color-border); border-radius: var(--radius-xl); background: #ffffff;
  transition: all var(--transition-fast);
}
.deuda-item--selected { border-color: var(--color-primary-600); background: var(--color-primary-50); }

.deuda-checkbox { display: flex; align-items: center; gap: 0.75rem; cursor: pointer; }
.deuda-info { display: flex; flex-direction: column; }

.pago-config-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }

.total-box {
  margin-top: 1rem; padding: 1rem 1.25rem; background: var(--color-success-bg);
  border: 1px solid #bbf7d0; border-radius: var(--radius-xl); display: flex;
  justify-content: space-between; align-items: center; font-size: 1.1rem; font-weight: 800; color: var(--color-success);
}
.total-amount { font-size: 1.4rem; }

.modal-error { padding: 0.75rem 1.25rem; background: var(--color-error-bg); border-bottom: 1px solid #fecaca; color: var(--color-error); font-size: 0.85rem; display: flex; align-items: center; gap: 0.5rem; }
.modal-error svg { width: 18px; height: 18px; flex-shrink: 0; }

.modal-footer { padding: 1rem 0 0 0; border-top: 1px solid var(--color-border); display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.25rem; }
</style>
