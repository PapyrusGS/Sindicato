<template>
  <transition name="fade">
    <div v-if="visible" class="modal-backdrop" @click.self="cerrar">
      <div class="modal-container">
        <!-- Header -->
        <div class="modal-header">
          <div>
            <h2 class="modal-title">{{ isEditMode ? '✏️ Editar Obligación de Grupo' : 'Definir Nueva Obligación de Grupo' }}</h2>
            <p class="modal-subtitle">
              {{ isEditMode ? 'La modificación actualizará el monto asignado a choferes con estado PENDIENTE' : 'Crea cuotas mensuales o aportes de ayuda extraordinaria para el grupo' }}
            </p>
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

        <AppLoader v-if="loadingAux" :visible="true" message="Cargando datos del grupo..." style="padding: 2rem 0;" />

        <!-- Form Body -->
        <form v-else @submit.prevent="guardarObligacion" class="modal-body">
          <!-- 1. Selector de Grupo (Si es Admin puede elegir, si es Jefe queda pre-seleccionado) -->
          <div class="form-group">
            <label class="form-label">Grupo de Trabajo Asignado *</label>
            <select v-model="form.grupo_id" class="form-control" :disabled="!auxiliares.is_admin" required>
              <option value="" disabled>Seleccione un grupo...</option>
              <option v-for="g in auxiliares.grupos" :key="g.id" :value="g.id">
                {{ g.nombre }} — ({{ g.total_choferes }} choferes activos)
              </option>
            </select>
          </div>

          <!-- 2. Selector de Categoría de Obligación -->
          <div class="form-group">
            <label class="form-label">Tipo de Obligación *</label>
            <div class="tipo-grid">
              <div
                class="tipo-card"
                :class="{ 'tipo-card--selected': form.tipo_categoria === 'MENSUAL' }"
                @click="setTipoCategoria('MENSUAL')"
              >
                <div class="tipo-card__icon">📅</div>
                <div class="tipo-card__content">
                  <h4>Cuota Mensual de Grupo</h4>
                  <p>Cuota regular del grupo con duración automática estricta de <strong>1 mes</strong>.</p>
                </div>
              </div>

              <div
                class="tipo-card"
                :class="{ 'tipo-card--selected': form.tipo_categoria === 'AYUDA' }"
                @click="setTipoCategoria('AYUDA')"
              >
                <div class="tipo-card__icon">🚑</div>
                <div class="tipo-card__content">
                  <h4>Aporte de Ayuda / Emergencia</h4>
                  <p>Aporte extraordinario para emergencia médica o personal de un compañero del sindicato.</p>
                </div>
              </div>
            </div>
          </div>

          <!-- 3. Concepto o Causa de la Obligación -->
          <div class="form-group">
            <label class="form-label">Concepto / Causa de la Obligación *</label>
            <input
              v-model="form.concepto"
              type="text"
              class="form-control"
              :placeholder="form.tipo_categoria === 'MENSUAL' ? 'ej. Cuota Mensual Grupo A - Agosto 2026' : 'ej. Aporte Solidario por Emergencia Salud chofer Pedro Condori'"
              required
            />
          </div>

          <!-- 4. Grid de Monto y Fechas -->
          <div class="form-grid">
            <div class="form-group">
              <label class="form-label">Monto por Chofer (Bs.) *</label>
              <input
                v-model.number="form.monto_individual"
                type="number"
                step="any"
                min="1"
                class="form-control"
                placeholder="ej. 100"
                required
              />
            </div>

            <div class="form-group">
              <label class="form-label">Fecha de Inicio *</label>
              <input
                v-model="form.fecha_inicio"
                type="date"
                class="form-control"
                @change="onFechaInicioChange"
                required
              />
            </div>

            <div class="form-group form-group--full">
              <label class="form-label">Fecha de Vencimiento / Fin *</label>
              <input
                v-model="form.fecha_fin"
                type="date"
                class="form-control"
                :disabled="form.tipo_categoria === 'MENSUAL'"
                required
              />
              <small v-if="form.tipo_categoria === 'MENSUAL'" class="text-muted" style="font-size: 0.75rem;">
                ℹ️ Las cuotas mensuales tienen vigencia estricta de <strong>1 mes</strong> a partir de la fecha de inicio.
              </small>
            </div>
          </div>

          <!-- Live Preview Box -->
          <div class="preview-box">
            <div class="preview-title">📊 Resumen de Asignación Masiva Automática:</div>
            <div class="preview-content">
              <span>Se creará el registro de deuda <strong>PENDIENTE</strong> a <strong>{{ grupoSeleccionadoObj?.total_choferes || 0 }} choferes</strong> del {{ grupoSeleccionadoObj?.nombre || 'grupo' }}.</span>
              <div class="preview-monto">
                Total a Recaudar: <strong>Bs. {{ (Number(form.monto_individual || 0) * (grupoSeleccionadoObj?.total_choferes || 0)).toFixed(2) }}</strong>
              </div>
            </div>
          </div>

          <!-- Footer Actions -->
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="cerrar">Cancelar</button>
            <button type="submit" class="btn btn-primary" :disabled="guardando">
              <span v-if="guardando">Asignando a Choferes...</span>
              <span v-else>{{ isEditMode ? 'Guardar Modificación & Auditar' : 'Crear y Cargar a Choferes del Grupo' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { obligacionApi } from '@/api/obligacionApi'
import AppLoader from '@/components/common/AppLoader.vue'

const props = defineProps({
  visible: { type: Boolean, default: false },
  obligacionEdit: { type: Object, default: null },
})

const emit = defineEmits(['close', 'saved'])

const isEditMode = computed(() => !!props.obligacionEdit)
const loadingAux = ref(true)
const guardando = ref(false)
const errorMsg = ref(null)

const auxiliares = ref({
  grupos: [],
  grupo_jefe_id: null,
  is_admin: false,
})

const todayStr = new Date().toISOString().slice(0, 10)

const form = reactive({
  grupo_id: '',
  tipo_categoria: 'MENSUAL',
  concepto: '',
  monto_individual: 100,
  fecha_inicio: todayStr,
  fecha_fin: getOneMonthLater(todayStr),
})

function getOneMonthLater(dateStr) {
  const d = new Date(dateStr)
  d.setMonth(d.getMonth() + 1)
  d.setDate(d.getDate() - 1)
  return d.toISOString().slice(0, 10)
}

function setTipoCategoria(tipo) {
  form.tipo_categoria = tipo
  if (tipo === 'MENSUAL') {
    form.fecha_fin = getOneMonthLater(form.fecha_inicio)
  }
}

function onFechaInicioChange() {
  if (form.tipo_categoria === 'MENSUAL') {
    form.fecha_fin = getOneMonthLater(form.fecha_inicio)
  }
}

const grupoSeleccionadoObj = computed(() => {
  return auxiliares.value.grupos.find(g => g.id === form.grupo_id)
})

watch(() => props.obligacionEdit, (newVal) => {
  if (newVal) {
    form.grupo_id = newVal.grupo_id
    form.tipo_categoria = newVal.tipo_categoria
    form.concepto = newVal.concepto
    form.monto_individual = Number(newVal.monto_individual) || 100
    form.fecha_inicio = newVal.fecha_inicio ? newVal.fecha_inicio.slice(0, 10) : todayStr
    form.fecha_fin = newVal.fecha_fin ? newVal.fecha_fin.slice(0, 10) : getOneMonthLater(todayStr)
  } else {
    resetForm()
  }
}, { immediate: true })

function resetForm() {
  form.grupo_id = auxiliares.value.grupo_jefe_id || (auxiliares.value.grupos[0]?.id ?? '')
  form.tipo_categoria = 'MENSUAL'
  form.concepto = ''
  form.monto_individual = 100
  form.fecha_inicio = todayStr
  form.fecha_fin = getOneMonthLater(todayStr)
}

async function cargarAuxiliares() {
  loadingAux.value = true
  try {
    const res = await obligacionApi.obtenerAuxiliares()
    auxiliares.value = res.data.data
    if (!props.obligacionEdit) {
      form.grupo_id = auxiliares.value.grupo_jefe_id || (auxiliares.value.grupos[0]?.id ?? '')
    }
  } catch (err) {
    errorMsg.value = err.response?.data?.message || 'Error al cargar datos auxiliares del grupo'
  } finally {
    loadingAux.value = false
  }
}

function cerrar() {
  emit('close')
}

async function guardarObligacion() {
  guardando.value = true
  errorMsg.value = null

  try {
    if (isEditMode.value) {
      await obligacionApi.actualizar(props.obligacionEdit.id, form)
    } else {
      await obligacionApi.crear(form)
    }
    emit('saved')
  } catch (err) {
    errorMsg.value = err.response?.data?.message || 'Error al guardar la obligación'
  } finally {
    guardando.value = false
  }
}

onMounted(() => {
  cargarAuxiliares()
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
  width: 100%; max-width: 680px; max-height: 90vh; display: flex; flex-direction: column;
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

.tipo-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 0.5rem; }
.tipo-card {
  padding: 1rem; border: 1px solid var(--color-border); border-radius: var(--radius-xl);
  background: #ffffff; cursor: pointer; transition: all var(--transition-fast); display: flex; gap: 0.75rem;
}
.tipo-card:hover { border-color: var(--color-primary-400); transform: translateY(-2px); }
.tipo-card--selected { border-color: var(--color-primary-600); background: var(--color-primary-50); }
.tipo-card__icon { font-size: 1.8rem; flex-shrink: 0; }
.tipo-card h4 { font-size: 0.9rem; font-weight: 700; color: var(--color-text-primary); margin-bottom: 0.2rem; }
.tipo-card p { font-size: 0.75rem; color: var(--color-text-secondary); line-height: 1.3; }

.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem; }
.form-group--full { grid-column: 1 / -1; }

.preview-box {
  margin-top: 1.25rem; padding: 1rem 1.25rem; background: var(--color-primary-50);
  border: 1px solid var(--color-primary-200); border-radius: var(--radius-xl); font-size: 0.85rem;
}
.preview-title { font-weight: 700; color: var(--color-primary-900); margin-bottom: 0.35rem; }
.preview-content { color: var(--color-text-secondary); }
.preview-monto { margin-top: 0.4rem; font-weight: 800; color: var(--color-primary-800); }

.modal-error { padding: 0.75rem 1.25rem; background: var(--color-error-bg); border-bottom: 1px solid #fecaca; color: var(--color-error); font-size: 0.85rem; display: flex; align-items: center; gap: 0.5rem; }
.modal-error svg { width: 18px; height: 18px; flex-shrink: 0; }

.modal-footer { padding: 1rem 0 0 0; border-top: 1px solid var(--color-border); display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.25rem; }
</style>
