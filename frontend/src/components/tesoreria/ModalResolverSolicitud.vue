<template>
  <transition name="fade">
    <div v-if="visible" class="modal-backdrop" @click.self="cerrar">
      <div class="modal-container">
        <!-- Header -->
        <div class="modal-header">
          <div class="header-info">
            <span class="header-icon">🛡️</span>
            <div>
              <h2 class="modal-title">Autorización de Cambio de Pago</h2>
              <p class="modal-subtitle">Solicitud #{{ solicitud?.id }} — Cobro #{{ solicitud?.pago_id }}</p>
            </div>
          </div>
          <button class="modal-close-btn" @click="cerrar">&times;</button>
        </div>

        <!-- Error Alert -->
        <div v-if="errorMsg" class="modal-alert-error">
          <span>⚠️ {{ errorMsg }}</span>
        </div>

        <!-- Body -->
        <div class="modal-body" v-if="solicitud">
          <div class="notice-security">
            <span class="notice-icon">🔒</span>
            <p>
              <strong>Control de Seguridad Sindical:</strong> El cobro excedió el tiempo de gracia de 1:30 minutos.
              Para modificar o anular el estado de este cobro, se requiere la autorización explícita del chofer afectado.
            </p>
          </div>

          <!-- Info Cards Grid -->
          <div class="info-grid">
            <div class="info-card">
              <span class="info-label">Chofer Registrado:</span>
              <strong class="info-value">👤 {{ solicitud.chofer?.persona?.nombre_completo || 'Chofer' }}</strong>
              <small class="text-muted">CI: {{ solicitud.chofer?.persona?.ci }}</small>
            </div>

            <div class="info-card">
              <span class="info-label">Monto del Cobro:</span>
              <strong class="info-value text-success font-mono" style="font-size:1.15rem;">
                Bs. {{ Number(solicitud.pago?.monto_total || 0).toFixed(2) }}
              </strong>
              <small class="text-muted">Método: {{ solicitud.pago?.metodo_pago }}</small>
            </div>

            <div class="info-card info-card--full">
              <span class="info-label">Solicitado por (Tesorero / Caja):</span>
              <strong class="info-value">👮 {{ solicitud.solicitante_persona?.nombre_completo || 'Tesorero' }}</strong>
              <small class="text-muted">Fecha solicitud: {{ formatFecha(solicitud.created_at) }}</small>
            </div>

            <div class="info-card info-card--full reason-box">
              <span class="info-label text-warning font-bold">⚠️ Motivo del Tesorero para anular / corregir:</span>
              <p class="reason-text">{{ solicitud.motivo }}</p>
            </div>
          </div>

          <!-- Observación de respuesta del chofer -->
          <div class="form-group" style="margin-top: 1.25rem;">
            <label class="form-label">
              Comentario u Observación de Respuesta (Opcional):
            </label>
            <textarea
              v-model="observacionRespuesta"
              class="form-control"
              rows="2"
              placeholder="Ej: Acepto la anulación, efectivamente el cobro no me correspondía a mí."
              :disabled="procesando"
            ></textarea>
          </div>
        </div>

        <!-- Footer Actions -->
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" @click="cerrar" :disabled="procesando">
            Cerrar
          </button>

          <button
            type="button"
            class="btn btn-danger"
            @click="enviarRespuesta('DENEGAR')"
            :disabled="procesando"
            id="btn-denegar-solicitud"
          >
            <span v-if="procesando && accionEjecutada === 'DENEGAR'">Procesando...</span>
            <span v-else>❌ Denegar Solicitud</span>
          </button>

          <button
            type="button"
            class="btn btn-success"
            @click="enviarRespuesta('ACEPTAR')"
            :disabled="procesando"
            id="btn-aceptar-solicitud"
          >
            <span v-if="procesando && accionEjecutada === 'ACEPTAR'">Procesando...</span>
            <span v-else>✅ Aceptar y Anular Cobro</span>
          </button>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { ref } from 'vue'
import { cobroApi } from '@/api/cobroApi'

const props = defineProps({
  visible: { type: Boolean, default: false },
  solicitud: { type: Object, default: null },
})

const emit = defineEmits(['close', 'resolved'])

const procesando = ref(false)
const accionEjecutada = ref('')
const observacionRespuesta = ref('')
const errorMsg = ref(null)

function formatFecha(dateStr) {
  if (!dateStr) return 'N/A'
  return new Date(dateStr).toLocaleString('es-BO', { dateStyle: 'short', timeStyle: 'short' })
}

function cerrar() {
  if (!procesando.value) {
    emit('close')
  }
}

async function enviarRespuesta(accion) {
  if (!props.solicitud?.id) return

  procesando.value = true
  accionEjecutada.value = accion
  errorMsg.value = null

  try {
    await cobroApi.responderSolicitud(props.solicitud.id, {
      accion: accion,
      observacion: observacionRespuesta.value.trim() || null,
    })

    emit('resolved', { solicitudId: props.solicitud.id, accion })
    emit('close')
  } catch (err) {
    errorMsg.value = err.response?.data?.message || 'Error al procesar la respuesta de la solicitud'
  } finally {
    procesando.value = false
    accionEjecutada.value = ''
  }
}
</script>

<style scoped>
.modal-backdrop {
  position: fixed; top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);
  display: flex; align-items: center; justify-content: center; z-index: 1100; padding: 1rem;
}

.modal-container {
  background: #ffffff; border: 1px solid var(--color-border); border-radius: var(--radius-2xl);
  width: 100%; max-width: 600px; display: flex; flex-direction: column;
  box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); overflow: hidden;
}

.modal-header {
  padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--color-border);
  display: flex; justify-content: space-between; align-items: center; background: var(--color-bg-tertiary);
}

.header-info { display: flex; align-items: center; gap: 0.75rem; }
.header-icon { font-size: 1.75rem; }
.modal-title { font-size: 1.15rem; font-weight: 800; color: var(--color-text-primary); }
.modal-subtitle { font-size: 0.75rem; color: var(--color-text-muted); }
.modal-close-btn { background: none; border: none; font-size: 1.5rem; color: var(--color-text-muted); cursor: pointer; }

.modal-body { padding: 1.25rem 1.5rem; overflow-y: auto; max-height: 70vh; }

.notice-security {
  display: flex; align-items: flex-start; gap: 0.75rem;
  background: #eff6ff; border: 1px solid #bfdbfe; border-radius: var(--radius-lg);
  padding: 0.85rem 1rem; font-size: 0.825rem; color: #1e3a8a; margin-bottom: 1.25rem;
}
.notice-icon { font-size: 1.2rem; }

.info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.85rem; }
.info-card {
  background: var(--color-bg-tertiary); border: 1px solid var(--color-border);
  border-radius: var(--radius-lg); padding: 0.75rem 0.9rem; display: flex; flex-direction: column;
}
.info-card--full { grid-column: span 2; }
.info-label { font-size: 0.75rem; color: var(--color-text-muted); margin-bottom: 0.2rem; }
.info-value { font-size: 0.9rem; color: var(--color-text-primary); }

.reason-box {
  background: #fffbeb; border: 1px solid #fde68a; padding: 0.85rem 1rem;
}
.reason-text {
  margin-top: 0.35rem; font-size: 0.875rem; color: #92400e; font-weight: 500; line-height: 1.4;
}

.modal-alert-error {
  padding: 0.75rem 1.25rem; background: var(--color-error-bg); border-bottom: 1px solid #fecaca;
  color: var(--color-error); font-size: 0.85rem;
}

.modal-footer {
  padding: 1rem 1.5rem; border-top: 1px solid var(--color-border);
  display: flex; justify-content: flex-end; gap: 0.75rem; background: var(--color-bg-secondary);
}

.btn-success {
  background: var(--color-success); color: white; border: none; padding: 0.5rem 1.1rem;
  border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; cursor: pointer;
  transition: opacity var(--transition-fast);
}
.btn-success:hover { opacity: 0.9; }

.btn-danger {
  background: var(--color-error); color: white; border: none; padding: 0.5rem 1.1rem;
  border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; cursor: pointer;
  transition: opacity var(--transition-fast);
}
.btn-danger:hover { opacity: 0.9; }
</style>
