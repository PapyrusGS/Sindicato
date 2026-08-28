<template>
  <transition name="fade">
    <div v-if="visible" class="modal-backdrop" @click.self="cerrar">
      <div class="modal-container">
        <!-- Header -->
        <div class="modal-header">
          <div>
            <h2 class="modal-title">Desglose de Choferes Asignados</h2>
            <p class="modal-subtitle">{{ detallesData?.obligacion?.concepto || 'Cargando concepto...' }}</p>
          </div>
          <button class="modal-close-btn" @click="cerrar">&times;</button>
        </div>

        <AppLoader v-if="loading" :visible="true" message="Cargando choferes del grupo..." style="padding: 3rem 0;" />

        <div v-else class="modal-body">
          <!-- Summary Metrics Cards -->
          <div class="metrics-grid">
            <div class="metric-card">
              <span class="metric-label">Monto por Chofer</span>
              <span class="metric-value text-primary">Bs. {{ Number(detallesData?.obligacion?.monto_individual || 0).toFixed(2) }}</span>
            </div>

            <div class="metric-card">
              <span class="metric-label">Total Recaudado</span>
              <span class="metric-value text-success">
                Bs. {{ Number(detallesData?.recaudacion?.total_pagado || 0).toFixed(2) }}
                <small style="font-size:0.75rem; font-weight:normal;"> / {{ Number(detallesData?.recaudacion?.total_esperado || 0).toFixed(2) }} Bs.</small>
              </span>
            </div>

            <div class="metric-card">
              <span class="metric-label">Choferes Pendientes</span>
              <span class="metric-value text-danger">
                {{ detallesData?.recaudacion?.pendientes }} <small style="font-size:0.75rem; font-weight:normal;">de {{ detallesData?.choferes?.length }}</small>
              </span>
            </div>
          </div>

          <!-- Choferes Data Table -->
          <table class="data-table" style="margin-top: 1.25rem;">
            <thead>
              <tr>
                <th>Chofer</th>
                <th>CI</th>
                <th>Monto Asignado</th>
                <th>Monto Pagado</th>
                <th>Estado Pago</th>
                <th>Fecha de Pago</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="c in detallesData?.choferes" :key="c.id">
                <td><strong>{{ c.nombre_completo }}</strong></td>
                <td class="font-mono">{{ c.ci }}</td>
                <td>Bs. {{ Number(c.monto_asignado).toFixed(2) }}</td>
                <td>
                  <strong :class="c.monto_pagado > 0 ? 'text-success' : 'text-muted'">
                    Bs. {{ Number(c.monto_pagado).toFixed(2) }}
                  </strong>
                </td>
                <td>
                  <span :class="c.estado_pago === 'PAGADO' ? 'badge badge-success' : 'badge badge-tesorero'">
                    {{ c.estado_pago === 'PAGADO' ? '✓ PAGADO' : '⏳ PENDIENTE' }}
                  </span>
                </td>
                <td>
                  <span class="text-muted" style="font-size:0.8rem;">{{ c.fecha_pago || '—' }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" @click="cerrar">Cerrar</button>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { ref, watch } from 'vue'
import { obligacionApi } from '@/api/obligacionApi'
import AppLoader from '@/components/common/AppLoader.vue'

const props = defineProps({
  visible: { type: Boolean, default: false },
  obligacionId: { type: Number, default: null },
})

const emit = defineEmits(['close'])

const loading = ref(false)
const detallesData = ref(null)

watch(() => props.obligacionId, async (newId) => {
  if (newId && props.visible) {
    loading.value = true
    try {
      const res = await obligacionApi.obtenerDetalles(newId)
      detallesData.value = res.data.data
    } catch (err) {
      console.error('Error al cargar detalles de la obligación:', err)
    } finally {
      loading.value = false
    }
  }
}, { immediate: true })

function cerrar() {
  emit('close')
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
  width: 100%; max-width: 760px; max-height: 90vh; display: flex; flex-direction: column;
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

.metrics-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; }
.metric-card {
  background: var(--color-bg-tertiary); border: 1px solid var(--color-border);
  border-radius: var(--radius-xl); padding: 1rem; display: flex; flex-direction: column;
}
.metric-label { font-size: 0.75rem; color: var(--color-text-secondary); font-weight: 600; }
.metric-value { font-size: 1.1rem; font-weight: 800; margin-top: 0.2rem; }

.modal-footer { padding: 1rem 1.5rem; border-top: 1px solid var(--color-border); display: flex; justify-content: flex-end; background: var(--color-bg-tertiary); }
</style>
