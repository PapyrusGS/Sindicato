import apiMobile from './axiosMobile'
import { mobileStorage } from './storage'

export const offlineSyncService = {
  // Obtener la cantidad de asistencias guardadas localmente pendientes de enviar
  getPendientesCount() {
    return mobileStorage.getOfflineAsistencias().length
  },

  // Guardar asistencia localmente cuando no hay señal o en modo offline
  guardarAsistenciaLocal(payload) {
    mobileStorage.saveOfflineAsistencia(payload)
    return {
      success: true,
      offline: true,
      message: 'Asistencia guardada localmente en la memoria del celular',
      pendientes: this.getPendientesCount(),
    }
  },

  // Probar conectividad con el servidor Laravel
  async probarConexion() {
    try {
      await apiMobile.get('/auth/me')
      return true
    } catch (err) {
      if (err.response) return true // El servidor respondió (ej. 401), hay conexión
      return false // Sin respuesta de red
    }
  },

  // Sincronizar todas las asistencias guardadas localmente al servidor
  async sincronizarPendientes() {
    const queue = mobileStorage.getOfflineAsistencias()
    if (queue.length === 0) {
      return { success: true, count: 0, message: 'No hay asistencias pendientes por enviar' }
    }

    let enviadasExito = 0
    let errores = 0

    for (const item of queue) {
      try {
        await apiMobile.post('/asistencias/guardar', {
          lugar_id: item.lugar_id,
          fecha: item.fecha,
          asistencias: item.asistencias,
        })
        enviadasExito++
      } catch (err) {
        console.error('Error al sincronizar item local:', err)
        errores++
      }
    }

    if (enviadasExito > 0) {
      mobileStorage.clearOfflineAsistencias()
    }

    return {
      success: enviadasExito > 0,
      count: enviadasExito,
      errores,
      message: `Se sincronizaron ${enviadasExito} asistencias con el servidor exitosamente.`,
    }
  }
}
