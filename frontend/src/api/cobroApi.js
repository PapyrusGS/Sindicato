import api from './axios'

export const cobroApi = {
  obtenerChoferesElegibles() {
    return api.get('/cobros/choferes')
  },
  obtenerEstadoCuenta(choferId) {
    return api.get(`/cobros/estado-cuenta/${choferId}`)
  },
  procesarCobro(data) {
    return api.post('/cobros/procesar', data)
  },
  obtenerHistorial(params = {}) {
    return api.get('/cobros/historial', { params })
  },
}
