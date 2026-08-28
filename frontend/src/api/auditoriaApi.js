import api from './axios'

export const auditoriaApi = {
  listar(params = {}) {
    return api.get('/auditorias', { params })
  },
  obtenerAuxiliares() {
    return api.get('/auditorias/auxiliares')
  },
}
