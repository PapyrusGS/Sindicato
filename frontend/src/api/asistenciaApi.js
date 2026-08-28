import api from './axios'

export const asistenciaApi = {
  obtenerHoy(params = {}) {
    return api.get('/asistencias/hoy', { params })
  },
  guardar(data) {
    return api.post('/asistencias/guardar', data)
  },
  obtenerHistorialPorFecha(params = {}) {
    return api.get('/asistencias/historial-fecha', { params })
  },
}
