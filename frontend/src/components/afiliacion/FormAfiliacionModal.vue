<template>
  <transition name="fade">
    <div v-if="isVisible" class="modal-backdrop" @click.self="close">
      <div class="modal-container">
        <!-- Header -->
        <div class="modal-header">
          <div>
            <h2 class="modal-title">{{ isEditMode ? 'Editar Afiliado' : 'Registro de Afiliación y Vehículo' }}</h2>
            <p class="modal-subtitle">{{ isEditMode ? 'Modificar datos personales, cuenta, roles o vehículo' : 'Proceso guiado de alta de usuario, chofer o propietario' }}</p>
          </div>
          <button class="modal-close-btn" @click="close">&times;</button>
        </div>

        <!-- Indicator Steps -->
        <div class="modal-steps">
          <div
            v-for="(s, idx) in steps"
            :key="idx"
            class="modal-step-item"
            :class="{
              'modal-step-item--active': currentStep === idx + 1,
              'modal-step-item--completed': currentStep > idx + 1,
            }"
          >
            <div class="modal-step-num">{{ idx + 1 }}</div>
            <span class="modal-step-label">{{ s }}</span>
          </div>
        </div>

        <!-- Error Alert -->
        <transition name="fade">
          <div v-if="error" class="modal-error">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            <span>{{ error }}</span>
          </div>
        </transition>

        <!-- Form Body -->
        <div class="modal-body">
          <!-- ─── PASO 1: Datos Personales & Cuenta ─────────────────── -->
          <div v-if="currentStep === 1" class="step-content">
            <h3 class="step-title">1. Información Personal y Credenciales</h3>
            
            <div class="form-grid">
              <div class="form-group">
                <label class="form-label">Primer Nombre *</label>
                <input
                  v-model="form.primer_nombre"
                  type="text"
                  class="form-input"
                  placeholder="ej. Juan"
                  @input="cleanNombreField('primer_nombre')"
                  required
                />
              </div>
              <div class="form-group">
                <label class="form-label">Segundo Nombre</label>
                <input
                  v-model="form.segundo_nombre"
                  type="text"
                  class="form-input"
                  placeholder="ej. Carlos"
                  @input="cleanNombreField('segundo_nombre')"
                />
              </div>
              <div class="form-group">
                <label class="form-label">Primer Apellido *</label>
                <input
                  v-model="form.primer_apellido"
                  type="text"
                  class="form-input"
                  placeholder="ej. Pérez"
                  @input="cleanNombreField('primer_apellido')"
                  required
                />
              </div>
              <div class="form-group">
                <label class="form-label">Segundo Apellido</label>
                <input
                  v-model="form.segundo_apellido"
                  type="text"
                  class="form-input"
                  placeholder="ej. Mamani"
                  @input="cleanNombreField('segundo_apellido')"
                />
              </div>
              <div class="form-group">
                <label class="form-label">Cédula de Identidad (CI) *</label>
                <input
                  v-model="form.ci"
                  type="text"
                  class="form-input"
                  placeholder="ej. 4521678"
                  @input="cleanCiField"
                  required
                />
              </div>
              <div class="form-group">
                <label class="form-label">Celular (8 dígitos, inicia en 6 o 7)</label>
                <input
                  v-model="form.celular"
                  type="text"
                  class="form-input"
                  placeholder="ej. 71234567"
                  maxlength="8"
                  @input="cleanCelularField"
                />
                <small v-if="celularError" class="input-hint error-hint">{{ celularError }}</small>
              </div>
              <div class="form-group form-group--full">
                <label class="form-label">Dirección / Domicilio</label>
                <input v-model="form.direccion" type="text" class="form-input" placeholder="ej. Av. 6 de Agosto #123" />
              </div>
            </div>

            <hr class="form-divider" />

            <h3 class="step-title">Credenciales de Acceso</h3>
            <div class="form-grid">
              <div class="form-group">
                <label class="form-label">Nombre de Usuario (Username) *</label>
                <input v-model="form.username" type="text" class="form-input" placeholder="ej. juan.perez" required />
              </div>
              <div class="form-group">
                <label class="form-label">Contraseña {{ isEditMode ? '(Dejar en blanco para no cambiar)' : '*' }}</label>
                <input v-model="form.password" type="password" class="form-input" placeholder="••••••••" :required="!isEditMode" />
              </div>
            </div>

            <div class="form-group form-group--full" style="margin-top: 1rem;">
              <label class="form-label">Roles Adicionales del Sistema</label>
              <div class="checkbox-group">
                <label v-for="r in rolesDisponibles" :key="r.id" class="checkbox-label">
                  <input type="checkbox" :value="r.nombre" v-model="form.roles" />
                  <span>{{ r.nombre }}</span>
                </label>
              </div>
            </div>
          </div>

          <!-- ─── PASO 2: Modalidad & Vehículo ───────────────────────── -->
          <div v-if="currentStep === 2" class="step-content">
            <h3 class="step-title">2. Seleccionar Modalidad de Afiliación</h3>

            <div v-if="!isEditMode" class="modalidad-cards">
              <div
                class="modalidad-card"
                :class="{ 'modalidad-card--selected': form.modalidad === 'propietario_auto' }"
                @click="form.modalidad = 'propietario_auto'"
              >
                <div class="modalidad-card__badge">Propietario</div>
                <h4>Registrar Vehículo Propio</h4>
                <p>El afiliado registra un nuevo auto. Puede conducirlo él mismo o asignárselo a otro chofer.</p>
              </div>

              <div
                class="modalidad-card"
                :class="{ 'modalidad-card--selected': form.modalidad === 'chofer_existente' }"
                @click="form.modalidad = 'chofer_existente'"
              >
                <div class="modalidad-card__badge">Chofer</div>
                <h4>Registrar como Chofer</h4>
                <p>El afiliado se registra como chofer y se le asigna a un vehículo existente en la flota.</p>
              </div>
            </div>

            <!-- Grupo asignado -->
            <div class="form-group form-group--full" style="margin-top: 1.5rem;">
              <label class="form-label">Grupo de Trabajo Asignado *</label>
              <select v-model="form.grupo_id" class="form-input" required>
                <option value="" disabled>Seleccione un grupo...</option>
                <option v-for="g in grupos" :key="g.id" :value="g.id">{{ g.nombre }} — {{ g.descripcion }}</option>
              </select>
            </div>

            <!-- CASO 1: Propietario con Vehículo -->
            <div v-if="form.modalidad === 'propietario_auto' || isEditMode" class="subpanel">
              <h4 class="subpanel-title">Datos del Vehículo {{ isEditMode ? 'Asociado' : '' }}</h4>
              <div class="form-grid">
                <div class="form-group">
                  <label class="form-label">Placa del Vehículo *</label>
                  <input
                    v-model="form.auto_placa"
                    type="text"
                    class="form-input font-mono"
                    placeholder="ej. 1234-ABC"
                    @input="cleanPlacaField"
                  />
                </div>
                <div class="form-group">
                  <label class="form-label">Marca</label>
                  <input v-model="form.auto_marca" type="text" class="form-input" placeholder="ej. Toyota" @input="cleanNombreField('auto_marca')" />
                </div>
                <div class="form-group">
                  <label class="form-label">Modelo</label>
                  <input v-model="form.auto_modelo" type="text" class="form-input" placeholder="ej. Hiace" @input="cleanNombreField('auto_modelo')" />
                </div>
                <div class="form-group">
                  <label class="form-label">Gestión / Año</label>
                  <input v-model.number="form.auto_gestion" type="number" class="form-input" placeholder="ej. 2022" />
                </div>
              </div>

              <div v-if="!isEditMode" class="chofer-toggle-panel">
                <label class="toggle-label">
                  <input type="checkbox" v-model="form.es_el_chofer" />
                  <span class="toggle-switch"></span>
                  <span class="toggle-text">¿Este afiliado será el CHOFER principal de su vehículo?</span>
                </label>
              </div>

              <div v-if="!isEditMode && !form.es_el_chofer" class="form-group form-group--full" style="margin-top: 1rem;">
                <label class="form-label">Asignar vehículo a OTRO chofer existente *</label>
                <select v-model="form.chofer_id_asignado" class="form-input" required>
                  <option value="" disabled>Seleccione un chofer de la lista...</option>
                  <option v-for="c in choferesDisponibles" :key="c.id" :value="c.id">
                    {{ c.nombre_completo }} (CI: {{ c.ci }})
                  </option>
                </select>
              </div>
            </div>

            <!-- CASO 2: Chofer Existente -->
            <div v-if="!isEditMode && form.modalidad === 'chofer_existente'" class="subpanel">
              <h4 class="subpanel-title">Asignación de Vehículo Existente (Opcional)</h4>
              <div class="form-group form-group--full">
                <label class="form-label">Seleccionar Vehículo de la Flota</label>
                <select v-model="form.auto_id_existente" class="form-input">
                  <option :value="null">Sin vehículo asignado por ahora</option>
                  <option v-for="a in autosDisponibles" :key="a.id" :value="a.id">
                    {{ a.placa }} — {{ a.marca }} {{ a.modelo }} (Propietario: {{ a.propietario }})
                  </option>
                </select>
              </div>
            </div>
          </div>

          <!-- ─── PASO 3: Resumen y Confirmación ─────────────────────── -->
          <div v-if="currentStep === 3" class="step-content">
            <h3 class="step-title">3. Confirmar Datos de Afiliación</h3>

            <div class="summary-box">
              <div class="summary-row">
                <span class="summary-label">Nombre Completo:</span>
                <strong class="summary-value">{{ form.primer_nombre }} {{ form.segundo_nombre }} {{ form.primer_apellido }} {{ form.segundo_apellido }}</strong>
              </div>
              <div class="summary-row">
                <span class="summary-label">CI / Teléfono:</span>
                <span class="summary-value">{{ form.ci }} / {{ form.celular || 'Sin celular' }}</span>
              </div>
              <div class="summary-row">
                <span class="summary-label">Usuario / Username:</span>
                <span class="summary-value">@{{ form.username }}</span>
              </div>
              <div class="summary-row">
                <span class="summary-label">Roles Asignados:</span>
                <span class="summary-value">
                  <span v-for="r in getRolesCalculados()" :key="r" class="summary-badge">{{ r }}</span>
                </span>
              </div>
              <div class="summary-row">
                <span class="summary-label">Grupo:</span>
                <span class="summary-value">{{ getNombreGrupo(form.grupo_id) }}</span>
              </div>

              <hr />

              <div v-if="form.auto_placa">
                <div class="summary-row">
                  <span class="summary-label">Vehículo:</span>
                  <strong class="summary-value">{{ form.auto_placa }} — {{ form.auto_marca }} {{ form.auto_modelo }} ({{ form.auto_gestion }})</strong>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer Actions -->
        <div class="modal-footer">
          <button v-if="currentStep > 1" class="btn btn-secondary" @click="currentStep--" :disabled="submitting">
            Atrás
          </button>

          <button v-if="currentStep < 3" class="btn btn-primary" @click="nextStep">
            Siguiente
          </button>

          <button v-if="currentStep === 3" class="btn btn-success" @click="submit" :disabled="submitting">
            <span v-if="submitting">Guardando...</span>
            <span v-else>{{ isEditMode ? 'Guardar Cambios' : 'Confirmar y Registrar Afiliación' }}</span>
          </button>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { ref, reactive, watch, computed } from 'vue'
import { afiliacionApi } from '@/api/afiliacionApi'

const props = defineProps({
  visible: { type: Boolean, default: false },
  show: { type: Boolean, default: false },
  afiliadoEdit: { type: Object, default: null },
  affiliateData: { type: Object, default: null },
})

const emit = defineEmits(['close', 'saved'])

const isVisible = computed(() => props.visible || props.show)
const editData = computed(() => props.afiliadoEdit || props.affiliateData)
const isEditMode = computed(() => !!editData.value)

const currentStep = ref(1)
const steps = ['Datos Personales', 'Modalidad & Vehículo', 'Confirmación']
const submitting = ref(false)
const error = ref(null)
const celularError = ref('')

const rolesDisponibles = ref([])
const grupos = ref([])
const choferesDisponibles = ref([])
const autosDisponibles = ref([])

const form = reactive({
  id: null,
  primer_nombre: '',
  segundo_nombre: '',
  primer_apellido: '',
  segundo_apellido: '',
  ci: '',
  celular: '',
  direccion: '',
  username: '',
  password: '',
  roles: [],
  modalidad: 'propietario_auto',
  grupo_id: '',
  auto_id: null,
  auto_placa: '',
  auto_marca: '',
  auto_modelo: '',
  auto_gestion: new Date().getFullYear(),
  es_el_chofer: true,
  chofer_id_asignado: '',
  auto_id_existente: null,
})

// Helper para formatear cadenas a Title Case (Primeras letras mayúsculas, sin números)
function toTitleCase(str) {
  if (!str) return ''
  // Eliminar dígitos números
  const clean = str.replace(/[0-9]/g, '')
  return clean.replace(/\w\S*/g, (txt) => txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase())
}

function cleanNombreField(field) {
  if (form[field]) {
    form[field] = toTitleCase(form[field])
  }
}

function cleanCelularField() {
  if (form.celular) {
    // Mantener sólo dígitos
    form.celular = form.celular.replace(/\D/g, '').slice(0, 8)
    if (form.celular.length > 0 && !['6', '7'].includes(form.celular[0])) {
      celularError.value = 'El celular debe comenzar con 6 o 7.'
    } else if (form.celular.length > 0 && form.celular.length < 8) {
      celularError.value = 'El celular debe tener 8 dígitos.'
    } else {
      celularError.value = ''
    }
  } else {
    celularError.value = ''
  }
}

function cleanCiField() {
  if (form.ci) {
    form.ci = form.ci.toUpperCase().replace(/\s+/g, '')
  }
}

function cleanPlacaField() {
  if (form.auto_placa) {
    form.auto_placa = form.auto_placa.toUpperCase().replace(/[^A-Z0-9\-]/g, '')
  }
}

watch(isVisible, (val) => {
  if (val) {
    cargarAuxiliares().then(() => {
      if (editData.value) {
        populateForm(editData.value)
      } else {
        resetForm()
      }
    })
  }
}, { immediate: true })

async function cargarAuxiliares() {
  try {
    const res = await afiliacionApi.obtenerAuxiliares()
    const data = res.data.data
    rolesDisponibles.value = data.roles.filter(r => r.nombre !== 'Administrador')
    grupos.value = data.grupos
    choferesDisponibles.value = data.choferes
    autosDisponibles.value = data.autos
  } catch (err) {
    error.value = 'Error al cargar opciones del formulario'
  }
}

function resetForm() {
  currentStep.value = 1
  error.value = null
  celularError.value = ''
  Object.assign(form, {
    id: null,
    primer_nombre: '',
    segundo_nombre: '',
    primer_apellido: '',
    segundo_apellido: '',
    ci: '',
    celular: '',
    direccion: '',
    username: '',
    password: '',
    roles: [],
    modalidad: 'propietario_auto',
    grupo_id: grupos.value[0]?.id || '',
    auto_id: null,
    auto_placa: '',
    auto_marca: '',
    auto_modelo: '',
    auto_gestion: new Date().getFullYear(),
    es_el_chofer: true,
    chofer_id_asignado: '',
    auto_id_existente: null,
  })
}

function populateForm(data) {
  currentStep.value = 1
  error.value = null
  celularError.value = ''
  form.id = data.id
  form.primer_nombre = data.primer_nombre || ''
  form.segundo_nombre = data.segundo_nombre || ''
  form.primer_apellido = data.primer_apellido || ''
  form.segundo_apellido = data.segundo_apellido || ''
  form.ci = data.ci || ''
  form.celular = data.celular || ''
  form.direccion = data.direccion || ''
  form.username = data.usuario?.username || ''
  form.password = ''
  form.roles = [...(data.usuario?.roles || [])]
  form.grupo_id = grupos.value[0]?.id || ''

  if (data.vehiculos && data.vehiculos.length > 0) {
    const v = data.vehiculos[0]
    form.auto_id = v.id
    form.auto_placa = v.placa
    form.auto_marca = v.marca
    form.auto_modelo = v.modelo
    form.auto_gestion = v.gestion
  }
}

function nextStep() {
  error.value = null
  if (currentStep.value === 1) {
    if (!form.primer_nombre || !form.primer_apellido || !form.ci || !form.username) {
      error.value = 'Por favor complete los campos obligatorios (*)'
      return
    }
    if (form.celular && (form.celular.length !== 8 || !['6', '7'].includes(form.celular[0]))) {
      error.value = 'El celular debe tener 8 dígitos y comenzar con 6 o 7 (ej. 71234567)'
      return
    }
    if (!isEditMode.value && !form.password) {
      error.value = 'La contraseña es obligatoria para nuevos usuarios'
      return
    }
  } else if (currentStep.value === 2) {
    if (!form.grupo_id) {
      error.value = 'Debe seleccionar un grupo de trabajo'
      return
    }
    if (!isEditMode.value && form.modalidad === 'propietario_auto') {
      if (!form.auto_placa || !form.auto_marca || !form.auto_modelo || !form.auto_gestion) {
        error.value = 'Por favor complete todos los datos del vehículo'
        return
      }
    }
  }
  currentStep.value++
}

function getRolesCalculados() {
  const set = new Set(form.roles)
  if (form.modalidad === 'propietario_auto') {
    set.add('Propietario')
    if (form.es_el_chofer) set.add('Chofer')
  } else {
    set.add('Chofer')
  }
  return Array.from(set)
}

function getNombreGrupo(id) {
  const g = grupos.value.find(item => item.id === id)
  return g ? g.nombre : 'Sin grupo'
}

async function submit() {
  submitting.value = true
  error.value = null

  try {
    if (isEditMode.value) {
      await afiliacionApi.actualizar(form.id, form)
    } else {
      await afiliacionApi.registrar(form)
    }
    emit('saved')
    close()
  } catch (err) {
    error.value = err.response?.data?.message || 'Error al procesar el registro de afiliación'
  } finally {
    submitting.value = false
  }
}

function close() {
  emit('close')
}
</script>

<style scoped>
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}
.modal-container {
  background: #ffffff;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-xl);
  width: 100%;
  max-width: 720px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
  overflow: hidden;
}
.modal-header {
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--color-border);
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: var(--color-bg-tertiary);
}
.modal-title { font-size: 1.15rem; font-weight: 700; color: var(--color-text-primary); }
.modal-subtitle { font-size: 0.8rem; color: var(--color-text-secondary); }
.modal-close-btn { background: none; border: none; font-size: 1.5rem; color: var(--color-text-muted); cursor: pointer; }

.modal-steps {
  display: flex;
  justify-content: space-between;
  padding: 0.85rem 1.5rem;
  background: #f8fafc;
  border-bottom: 1px solid var(--color-border);
}
.modal-step-item { display: flex; align-items: center; gap: 0.5rem; opacity: 0.55; }
.modal-step-item--active { opacity: 1; font-weight: 700; color: var(--color-primary-700); }
.modal-step-item--completed { opacity: 0.9; color: var(--color-success); }
.modal-step-num {
  width: 22px; height: 22px; border-radius: 50%; background: #e2e8f0; color: var(--color-text-secondary);
  display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700;
}
.modal-step-item--active .modal-step-num { background: var(--color-primary-600); color: white; }
.modal-step-item--completed .modal-step-num { background: var(--color-success); color: white; }

.modal-body { padding: 1.5rem; overflow-y: auto; flex: 1; }
.step-title { font-size: 0.95rem; font-weight: 700; color: var(--color-primary-800); margin-bottom: 1rem; }

.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.form-group--full { grid-column: 1 / -1; }
.form-label { display: block; font-size: 0.8rem; font-weight: 600; color: var(--color-text-primary); margin-bottom: 0.35rem; }
.form-input {
  width: 100%; padding: 0.6rem 0.8rem; background: #ffffff; border: 1px solid var(--color-border);
  border-radius: var(--radius-md); color: var(--color-text-primary); font-size: 0.9rem;
}
.form-input:focus { border-color: var(--color-primary-600); outline: none; }
.form-divider { margin: 1.5rem 0; border: none; border-top: 1px solid var(--color-border); }

.input-hint { display: block; font-size: 0.75rem; margin-top: 0.25rem; }
.error-hint { color: #dc2626; font-weight: 600; }

.checkbox-group { display: flex; flex-wrap: wrap; gap: 1rem; margin-top: 0.5rem; }
.checkbox-label { display: flex; align-items: center; gap: 0.4rem; font-size: 0.85rem; cursor: pointer; color: var(--color-text-primary); font-weight: 500; }

.modalidad-cards { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.modalidad-card {
  padding: 1rem; border: 1px solid var(--color-border); border-radius: var(--radius-lg);
  background: #ffffff; cursor: pointer; transition: all var(--transition-fast);
}
.modalidad-card:hover { border-color: var(--color-primary-400); }
.modalidad-card--selected { border-color: var(--color-primary-600); background: var(--color-primary-50); }
.modalidad-card__badge { display: inline-block; font-size: 0.7rem; font-weight: 700; padding: 0.15rem 0.4rem; background: #e2e8f0; border-radius: var(--radius-sm); margin-bottom: 0.5rem; color: #475569; }
.modalidad-card h4 { font-size: 0.9rem; font-weight: 700; color: var(--color-text-primary); margin-bottom: 0.35rem; }
.modalidad-card p { font-size: 0.75rem; color: var(--color-text-secondary); line-height: 1.3; }

.subpanel { margin-top: 1.5rem; padding: 1.25rem; background: var(--color-bg-tertiary); border: 1px solid var(--color-border); border-radius: var(--radius-lg); }
.subpanel-title { font-size: 0.85rem; font-weight: 700; color: var(--color-text-primary); margin-bottom: 1rem; }

.chofer-toggle-panel { margin-top: 1rem; padding: 0.75rem 1rem; background: #ffffff; border: 1px solid var(--color-border); border-radius: var(--radius-md); }
.toggle-label { display: flex; align-items: center; gap: 0.75rem; cursor: pointer; font-size: 0.85rem; color: var(--color-text-primary); font-weight: 600; }

.summary-box { background: var(--color-bg-tertiary); padding: 1.25rem; border-radius: var(--radius-lg); border: 1px solid var(--color-border); }
.summary-row { display: flex; justify-content: space-between; margin-bottom: 0.6rem; font-size: 0.85rem; }
.summary-label { color: var(--color-text-secondary); }
.summary-value { color: var(--color-text-primary); text-align: right; font-weight: 600; }
.summary-badge { font-size: 0.7rem; padding: 0.15rem 0.4rem; background: var(--color-primary-600); color: white; border-radius: var(--radius-sm); margin-left: 0.25rem; }

.modal-error { padding: 0.75rem 1.25rem; background: var(--color-error-bg); border-bottom: 1px solid #fecaca; color: var(--color-error); font-size: 0.85rem; display: flex; align-items: center; gap: 0.5rem; }
.modal-error svg { width: 18px; height: 18px; flex-shrink: 0; }

.modal-footer { padding: 1rem 1.5rem; border-top: 1px solid var(--color-border); display: flex; justify-content: flex-end; gap: 0.75rem; background: var(--color-bg-tertiary); }
.btn { padding: 0.55rem 1.2rem; font-size: 0.85rem; font-weight: 600; border-radius: var(--radius-md); border: none; cursor: pointer; transition: all var(--transition-fast); }
.btn-primary { background: var(--color-primary-600); color: white; }
.btn-primary:hover { background: var(--color-primary-700); }
.btn-success { background: var(--color-success-bg); color: var(--color-success); border: 1px solid #bbf7d0; font-weight: 700; }
.btn-success:hover { background: #bbf7d0; }
.btn-secondary { background: #ffffff; color: var(--color-text-primary); border: 1px solid var(--color-border); }
.btn-secondary:hover { background: var(--color-bg-tertiary); }
</style>
