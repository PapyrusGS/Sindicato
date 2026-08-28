<template>
  <transition name="fade">
    <div v-if="visible" class="modal-backdrop" @click.self="cerrar">
      <div class="modal-container">
        <!-- Header -->
        <div class="modal-header">
          <div>
            <div class="modal-header-top">
              <span :class="getBadgeClass(auditoriaItem?.accion)">
                {{ auditoriaItem?.accion }}
              </span>
              <span class="auditoria-id font-mono">ID Auditoría: #{{ auditoriaItem?.id_auditoria }}</span>
            </div>
            <h2 class="modal-title">Inspección de Registro de Auditoría</h2>
            <p class="modal-subtitle">Detalle técnico de trazabilidad de cambios en la base de datos</p>
          </div>
          <button class="modal-close-btn" @click="cerrar">&times;</button>
        </div>

        <div v-if="auditoriaItem" class="modal-body">
          <!-- Grid Ejecutor & Afectado -->
          <div class="info-grid">
            <div class="info-card">
              <span class="info-card__title">👤 Quién realizó la acción (Ejecutor)</span>
              <div class="info-card__content">
                <strong>{{ auditoriaItem.enriquecido?.ejecutor_nombre || 'Sistema' }}</strong>
                <span class="text-muted">CI: {{ auditoriaItem.enriquecido?.ejecutor_ci || 'N/A' }}</span>
                <small class="ip-tag">IP: {{ auditoriaItem.direccion_ip || '127.0.0.1' }}</small>
              </div>
            </div>

            <div class="info-card">
              <span class="info-card__title">🚘 A quién se le hizo (Afectado / Entidad)</span>
              <div class="info-card__content">
                <strong>{{ auditoriaItem.enriquecido?.afectado_nombre }}</strong>
                <span class="text-muted">{{ auditoriaItem.enriquecido?.afectado_detalle }}</span>
                <small class="table-tag font-mono">Tabla: {{ auditoriaItem.tabla_nombre }} (ID: {{ auditoriaItem.registro_id }})</small>
              </div>
            </div>
          </div>

          <!-- Renglón de Fecha -->
          <div class="date-box">
            <span>📅 Fecha y Hora Exacta:</span>
            <strong>{{ formatDate(auditoriaItem.fecha_a) }}</strong>
          </div>

          <!-- Comparación de Cambios -->
          <div class="diff-section">
            <h3 class="diff-title">
              🔄 Atributo Modificado: <code class="campo-code">{{ auditoriaItem.campo || 'Registro Completo' }}</code>
            </h3>

            <div class="diff-grid">
              <div class="diff-box diff-box--before">
                <div class="diff-box__header">🔴 Valor Anterior (Previo)</div>
                <div class="diff-box__body font-mono">
                  {{ auditoriaItem.valor_anterior || '— (Ninguno)' }}
                </div>
              </div>

              <div class="diff-box diff-box--after">
                <div class="diff-box__header">🟢 Valor Nuevo (Asignado)</div>
                <div class="diff-box__body font-mono">
                  {{ auditoriaItem.valor_nuevo || '— (Ninguno)' }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" @click="cerrar">Cerrar Inspección</button>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup>
defineProps({
  visible: { type: Boolean, default: false },
  auditoriaItem: { type: Object, default: null },
})

const emit = defineEmits(['close'])

function cerrar() {
  emit('close')
}

function getBadgeClass(accion) {
  switch (accion) {
    case 'CREACION': return 'badge badge-success'
    case 'MODIFICACION': return 'badge badge-admin'
    case 'DESACTIVACION': case 'ELIMINACION': return 'badge badge-tesorero'
    case 'REACTIVACION': return 'badge badge-chofer'
    default: return 'badge'
  }
}

function formatDate(dateStr) {
  if (!dateStr) return 'N/A'
  const d = new Date(dateStr)
  return d.toLocaleString('es-BO', { dateStyle: 'full', timeStyle: 'medium' })
}
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
.modal-header-top { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.35rem; }
.auditoria-id { font-size: 0.75rem; color: var(--color-text-muted); font-weight: 700; }
.modal-title { font-size: 1.2rem; font-weight: 800; color: var(--color-text-primary); }
.modal-subtitle { font-size: 0.8rem; color: var(--color-text-secondary); }
.modal-close-btn { background: none; border: none; font-size: 1.5rem; color: var(--color-text-muted); cursor: pointer; }

.modal-body { padding: 1.5rem; overflow-y: auto; flex: 1; }

.info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.info-card {
  background: var(--color-bg-tertiary); border: 1px solid var(--color-border);
  border-radius: var(--radius-xl); padding: 1rem; display: flex; flex-direction: column;
}
.info-card__title { font-size: 0.75rem; font-weight: 700; color: var(--color-text-secondary); margin-bottom: 0.5rem; }
.info-card__content { display: flex; flex-direction: column; font-size: 0.85rem; }
.ip-tag, .table-tag { font-size: 0.7rem; color: var(--color-text-muted); margin-top: 0.25rem; }

.date-box {
  margin-top: 1rem; padding: 0.75rem 1rem; background: var(--color-primary-50);
  border: 1px solid var(--color-primary-200); border-radius: var(--radius-lg);
  display: flex; justify-content: space-between; font-size: 0.85rem; color: var(--color-primary-900);
}

.diff-section { margin-top: 1.25rem; }
.diff-title { font-size: 0.95rem; font-weight: 700; color: var(--color-text-primary); margin-bottom: 0.75rem; }
.campo-code { background: var(--color-bg-tertiary); padding: 0.2rem 0.5rem; border-radius: var(--radius-md); border: 1px solid var(--color-border); font-size: 0.85rem; color: var(--color-primary-700); }

.diff-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.diff-box { border-radius: var(--radius-xl); border: 1px solid var(--color-border); overflow: hidden; }
.diff-box--before { border-color: #fecaca; }
.diff-box--after { border-color: #bbf7d0; }
.diff-box__header { padding: 0.5rem 0.75rem; font-size: 0.75rem; font-weight: 700; }
.diff-box--before .diff-box__header { background: var(--color-error-bg); color: var(--color-error); }
.diff-box--after .diff-box__header { background: var(--color-success-bg); color: var(--color-success); }
.diff-box__body { padding: 0.75rem; font-size: 0.85rem; word-break: break-all; white-space: pre-wrap; background: #ffffff; min-height: 70px; }

.modal-footer { padding: 1rem 1.5rem; border-top: 1px solid var(--color-border); display: flex; justify-content: flex-end; background: var(--color-bg-tertiary); }
</style>
