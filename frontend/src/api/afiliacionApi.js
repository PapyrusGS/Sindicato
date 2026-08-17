import api from './axios'

export const afiliacionApi = {
  listar(params = {}) {
    return api.get('/afiliacion', { params })
  },
  obtenerAuxiliares() {
    return api.get('/afiliacion/auxiliares')
  },
  registrar(data) {
    return api.post('/afiliacion/registrar', data)
  },
  actualizar(id, data) {
    return api.put(`/afiliacion/${id}`, data)
  },
  cambiarEstado(id, estado) {
    return api.patch(`/afiliacion/${id}/estado`, { estado })
  },
}
