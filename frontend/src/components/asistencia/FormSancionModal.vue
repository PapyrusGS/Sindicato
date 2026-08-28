<template>
  <transition name="fade">
    <div v-if="visible" class="modal-backdrop" @click.self="cerrar">
      <div class="modal-container">
        <!-- Header -->
        <div class="modal-header">
          <div>
            <h2 class="modal-title">{{ isEditMode ? '✏️ Editar / Corregir Sanción' : 'Imponer Nueva Sanción / Infracción' }}</h2>
            <p class="modal-subtitle">
              {{ isEditMode ? 'Los cambios realizados se registrarán en la tabla de auditoría con su usuario e IP' : 'Registro de sanciones económicas o castigos operativos para choferes' }}
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

        <AppLoader v-if="loadingAux" :visible="true" message="Cargando choferes e infracciones..." style="padding: 2rem 0;" />

        <!-- Form Body -->
        <form v-else @submit.prevent="guardarSancion" class="modal-body">
          <!-- 1. Chofer Sancionado -->
          <div class="form-group">
            <label class="form-label">Chofer Sancionado *</label>
            <select v-model="form.chofer_id" class="form-control" required>
              <option value="" disabled>Seleccione un chofer de su grupo...</option>
              <option v-for="c in auxiliares.choferes" :key="c.id" :value="c.id">
                {{ c.nombre_completo }} — (CI: {{ c.ci }}, Placa: {{ c.placa }})
              </option>
            </select>
          </div>

          <!-- 2. Parada / Lugar de la Infracción (Opcional) -->
          <div class="form-group">
            <label class="form-label">Lugar / Parada donde ocurrió la Infracción (Opcional)</label>
            <select v-model="form.lugar_id" class="form-control">
              <option :value="null">Ninguna / En Ruta</option>
              <option v-for="p in auxiliares.paradas" :key="p.id" :value="p.id">
                📍 {{ p.nombre }}
              </option>
            </select>
          </div>

          <!-- 3. Selector de Tipo de Sanción -->
          <div class="form-group">
            <label class="form-label">Tipo de Sanción a Aplicar *</label>
            <div class="tipo-sancion-grid">
              <div
                class="tipo-card"
                :class="{ 'tipo-card--selected': form.tipo_sancion === 'ECONOMICA' }"
                @click="form.tipo_sancion = 'ECONOMICA'"
              >
                <div class="tipo-card__icon">💰</div>
                <div class="tipo-card__content">
                  <h4>Sanción Económica (Bs.)</h4>
                  <p>Genera una multa monetaria pendiente de cobro para la tesorería del sindicato.</p>
                </div>
              </div>

              <div
                class="tipo-card"
                :class="{ 'tipo-card--selected': form.tipo_sancion === 'CASTIGO' }"
                @click="form.tipo_sancion = 'CASTIGO'"
              >
                <div class="tipo-card__icon">⏹️</div>
                <div class="tipo-card__content">
                  <h4>Castigo Operativo / Medida Disciplinaria</h4>
                  <p>Parquear vehículo por horas, suspensión temporal de salida o llamado de atención.</p>
                </div>
              </div>
            </div>
          </div>

          <!-- 4. Infracciones Comunes (Sugerencia Rápida) -->
          <div class="form-group">
            <label class="form-label">Sugerencia de Infracciones Comunes (Haga clic para autocompletar):</label>
            <div class="sugerencias-tags">
              <button
                v-for="(inf, idx) in auxiliares.infracciones_comunes"
                :key="idx"
                type="button"
                class="sugerencia-tag"
                @click="form.motivo = inf"
              >
                + {{ inf }}
              </button>
            </div>
          </div>

          <!-- 5. Motivo / Descripción de la Infracción -->
          <div class="form-group">
            <label class="form-label">Motivo / Descripción de la Infracción *</label>
            <textarea
              v-model="form.motivo"
              class="form-control"
              rows="3"
              placeholder="Describa brevemente qué ocurrió (ej. Se saltó la parada de vuelta a las 07:30 AM)..."
              required
            ></textarea>
          </div>

          <!-- 6. Campo Condicional según Tipo -->
          <div v-if="form.tipo_sancion === 'ECONOMICA'" class="form-group">
            <label class="form-label">Monto de la Multa (Bs.) *</label>
            <input
              v-model.number="form.monto"
              type="number"
              step="any"
              min="1"
              class="form-control"
              placeholder="ej. 50"
              required
            />
            <small class="text-muted" style="font-size: 0.75rem;">Se registrará con estado <strong>PENDIENTE DE PAGO</strong>.</small>
          </div>

          <div v-else class="form-group">
            <label class="form-label">Detalle del Castigo Operativo / Medida Disciplinaria *</label>
            <input
              v-model="form.sancion_detalle"
              type="text"
              class="form-control"
              placeholder="ej. Parquear el vehículo 1 hora en la parada Obelisco antes de reanudar salida"
              required
            />
            <small class="text-muted" style="font-size: 0.75rem;">Quedará registrado como antecedente operativo sin cobro de dinero.</small>
          </div>

          <!-- Footer Actions -->
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="cerrar">Cancelar</button>
            <button type="submit" class="btn btn-primary" :disabled="guardando">
              <span v-if="guardando">Guardando Cambios...</span>
              <span v-else>{{ isEditMode ? 'Guardar Modificación & Auditar' : 'Imponer y Guardar Sanción' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { sancionApi } from '@/api/sancionApi'
import AppLoader from '@/components/common/AppLoader.vue'

const props = defineProps({
  visible: { type: Boolean, default: false },
  sancionEdit: { type: Object, default: null },
})

const emit = defineEmits(['close', 'saved'])

const isEditMode = computed(() => !!props.sancionEdit)

const loadingAux = ref(true)
const guardando = ref(false)
const errorMsg = ref(null)

const auxiliares = ref({
  choferes: [],
  paradas: [],
  infracciones_comunes: [],
})

const form = reactive({
  chofer_id: '',
  lugar_id: null,
  tipo_sancion: 'CASTIGO',
  motivo: '',
  sancion_detalle: '',
  monto: 50,
})

watch(() => props.sancionEdit, (newVal) => {
  if (newVal) {
    form.chofer_id = newVal.chofer_id
    form.lugar_id = newVal.lugar_id
    form.tipo_sancion = newVal.tipo_sancion
    form.motivo = newVal.motivo
    form.sancion_detalle = newVal.sancion_detalle
    form.monto = Number(newVal.monto) || 50
  } else {
    resetForm()
  }
}, { immediate: true })

function resetForm() {
  form.chofer_id = ''
  form.lugar_id = null
  form.tipo_sancion = 'CASTIGO'
  form.motivo = ''
  form.sancion_detalle = ''
  form.monto = 50
}

async function cargarAuxiliares() {
  loadingAux.value = true
  try {
    const res = await sancionApi.obtenerAuxiliares()
    auxiliares.value = res.data.data
  } catch (err) {
    errorMsg.value = err.response?.data?.message || 'Error al cargar auxiliares para sanción'
  } finally {
    loadingAux.value = false
  }
}

function cerrar() {
  emit('close')
}

async function guardarSancion() {
  guardando.value = true
  errorMsg.value = null

  try {
    if (isEditMode.value) {
      await sancionApi.actualizar(props.sancionEdit.id, form)
    } else {
      await sancionApi.registrar(form)
    }
    emit('saved')
  } catch (err) {
    errorMsg.value = err.response?.data?.message || 'Error al guardar la sanción'
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

.tipo-sancion-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 0.5rem; }
.tipo-card {
  padding: 1rem; border: 1px solid var(--color-border); border-radius: var(--radius-xl);
  background: #ffffff; cursor: pointer; transition: all var(--transition-fast); display: flex; gap: 0.75rem;
}
.tipo-card:hover { border-color: var(--color-primary-400); transform: translateY(-2px); }
.tipo-card--selected { border-color: var(--color-primary-600); background: var(--color-primary-50); }
.tipo-card__icon { font-size: 1.8rem; flex-shrink: 0; }
.tipo-card h4 { font-size: 0.9rem; font-weight: 700; color: var(--color-text-primary); margin-bottom: 0.2rem; }
.tipo-card p { font-size: 0.75rem; color: var(--color-text-secondary); line-height: 1.3; }

.sugerencias-tags { display: flex; flex-wrap: wrap; gap: 0.35rem; margin-top: 0.5rem; }
.sugerencia-tag {
  font-size: 0.75rem; padding: 0.25rem 0.5rem; background: var(--color-bg-tertiary);
  border: 1px solid var(--color-border); border-radius: var(--radius-md);
  color: var(--color-text-secondary); font-weight: 600; cursor: pointer; transition: all var(--transition-fast);
}
.sugerencia-tag:hover { background: var(--color-primary-50); color: var(--color-primary-700); border-color: var(--color-primary-300); }

.modal-error { padding: 0.75rem 1.25rem; background: var(--color-error-bg); border-bottom: 1px solid #fecaca; color: var(--color-error); font-size: 0.85rem; display: flex; align-items: center; gap: 0.5rem; }
.modal-error svg { width: 18px; height: 18px; flex-shrink: 0; }

.modal-footer { padding: 1rem 0 0 0; border-top: 1px solid var(--color-border); display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1rem; }
</style>
