import api from './axios'

export const obligacionApi = {
  listar(params = {}) {
    return api.get('/obligaciones', { params })
  },
  crear(data) {
    return api.post('/obligaciones/crear', data)
  },
  actualizar(id, data) {
    return api.put(`/obligaciones/${id}`, data)
  },
  obtenerDetalles(id) {
    return api.get(`/obligaciones/${id}/detalles`)
  },
  obtenerAuxiliares() {
    return api.get('/obligaciones/auxiliares')
  },
}
