<template>
  <transition name="fade">
    <div v-if="visible" class="modal-backdrop" @click.self="cerrar">
      <div class="modal-container">
        <!-- Header -->
        <div class="modal-header">
          <div>
            <h2 class="modal-title">📢 Solicitar Cambio / Anulación de Cobro</h2>
            <p class="modal-subtitle">Cobro #{{ pago?.id }} — Requiere autorización del chofer</p>
          </div>
          <button class="modal-close-btn" @click="cerrar">&times;</button>
        </div>

        <!-- Alert Error -->
        <div v-if="errorMsg" class="modal-alert-error">
          <span>⚠️ {{ errorMsg }}</span>
        </div>

        <form @submit.prevent="enviarSolicitud" class="modal-body" v-if="pago">
          <!-- Warning Exceeded Grace Period -->
          <div class="alert-warning-time">
            <span class="warning-icon">⏳</span>
            <div>
              <strong>Tiempo de Gracia Expirado (> 1:30 minutos):</strong>
              <p>
                Han transcurrido más de 90 segundos desde el registro de este cobro. Por protocolo de seguridad, no se puede anular unilateralmente.
                Al enviar esta solicitud, el sistema enviará una notificación obligatoria al <strong>Chofer ({{ pago.chofer?.persona?.nombre_completo }})</strong> y al <strong>Jefe de Grupo</strong> para que el chofer autorice o deniegue la anulación.
              </p>
            </div>
          </div>

          <!-- Resumen del Cobro -->
          <div class="summary-box">
            <div class="summary-row">
              <span class="summary-label">Chofer Registrado:</span>
              <strong class="summary-value">👤 {{ pago.chofer?.persona?.nombre_completo }} (CI: {{ pago.chofer?.persona?.ci }})</strong>
            </div>
            <div class="summary-row">
              <span class="summary-label">Monto Total:</span>
              <strong class="summary-value text-success font-mono">Bs. {{ Number(pago.monto_total).toFixed(2) }} ({{ pago.metodo_pago }})</strong>
            </div>
            <div class="summary-row">
              <span class="summary-label">Fecha y Hora de Pago:</span>
              <span class="summary-value font-mono">{{ formatFecha(pago.fecha_pago || pago.created_at) }}</span>
            </div>
          </div>

          <!-- Campo de Motivo Justificado -->
          <div class="form-group" style="margin-top: 1.25rem;">
            <label class="form-label">
              Motivo o Justificación del Cambio / Error *
            </label>
            <textarea
              v-model="motivo"
              class="form-control"
              rows="3"
              required
              minlength="6"
              placeholder="Ejemplo: Por equivocación marqué al chofer Juan Pérez en lugar de Carlos López durante el cobro de la cuota mensual de agosto."
              :disabled="enviando"
            ></textarea>
            <small class="text-muted">Explique claramente el error para que el chofer lo pueda validar.</small>
          </div>

          <!-- Footer Actions -->
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="cerrar" :disabled="enviando">
              Cancelar
            </button>
            <button
              type="submit"
              class="btn btn-primary"
              :disabled="enviando || motivo.trim().length < 6"
              id="btn-confirmar-solicitud"
            >
              <span v-if="enviando">Enviando Notificaciones...</span>
              <span v-else>📨 Enviar Solicitud de Cambio</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { ref } from 'vue'
import { cobroApi } from '@/api/cobroApi'

const props = defineProps({
  visible: { type: Boolean, default: false },
  pago: { type: Object, default: null },
})

const emit = defineEmits(['close', 'saved'])

const motivo = ref('')
const enviando = ref(false)
const errorMsg = ref(null)

function formatFecha(dateStr) {
  if (!dateStr) return 'N/A'
  return new Date(dateStr).toLocaleString('es-BO', { dateStyle: 'short', timeStyle: 'short' })
}

function cerrar() {
  if (!enviando.value) {
    emit('close')
  }
}

async function enviarSolicitud() {
  if (!props.pago?.id || motivo.value.trim().length < 6) return

  enviando.value = true
  errorMsg.value = null

  try {
    await cobroApi.solicitarCambio(props.pago.id, {
      motivo: motivo.value.trim(),
    })

    emit('saved')
    emit('close')
  } catch (err) {
    errorMsg.value = err.response?.data?.message || 'Error al enviar la solicitud de cambio de cobro'
  } finally {
    enviando.value = false
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
  width: 100%; max-width: 620px; display: flex; flex-direction: column;
  box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); overflow: hidden;
}

.modal-header {
  padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--color-border);
  display: flex; justify-content: space-between; align-items: center; background: var(--color-bg-tertiary);
}
.modal-title { font-size: 1.2rem; font-weight: 800; color: var(--color-text-primary); }
.modal-subtitle { font-size: 0.8rem; color: var(--color-text-secondary); }
.modal-close-btn { background: none; border: none; font-size: 1.5rem; color: var(--color-text-muted); cursor: pointer; }

.modal-body { padding: 1.5rem; overflow-y: auto; }

.alert-warning-time {
  display: flex; align-items: flex-start; gap: 0.75rem;
  background: #fffbeb; border: 1px solid #fde68a; border-radius: var(--radius-lg);
  padding: 0.85rem 1rem; font-size: 0.825rem; color: #92400e; margin-bottom: 1.25rem;
}
.alert-warning-time p { margin-top: 0.25rem; line-height: 1.4; color: #78350f; }
.warning-icon { font-size: 1.4rem; }

.summary-box {
  background: var(--color-bg-tertiary); border: 1px solid var(--color-border);
  border-radius: var(--radius-lg); padding: 0.85rem 1rem; display: flex; flex-direction: column; gap: 0.5rem;
}
.summary-row { display: flex; justify-content: space-between; font-size: 0.85rem; }
.summary-label { color: var(--color-text-muted); }
.summary-value { color: var(--color-text-primary); }

.modal-alert-error {
  padding: 0.75rem 1.25rem; background: var(--color-error-bg); border-bottom: 1px solid #fecaca;
  color: var(--color-error); font-size: 0.85rem;
}

.modal-footer {
  padding: 1rem 0 0 0; border-top: 1px solid var(--color-border);
  display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;
}
</style>
