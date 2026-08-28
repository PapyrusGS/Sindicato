import api from './axios'

export const sancionApi = {
  listar(params = {}) {
    return api.get('/sanciones', { params })
  },
  registrar(data) {
    return api.post('/sanciones/registrar', data)
  },
  actualizar(id, data) {
    return api.put(`/sanciones/${id}`, data)
  },
  obtenerAuxiliares() {
    return api.get('/sanciones/auxiliares')
  },
}
