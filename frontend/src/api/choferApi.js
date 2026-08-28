import api from './axios'

export const choferApi = {
  obtenerMiPerfil() {
    return api.get('/chofer/mi-perfil')
  },
  obtenerMiHistorial() {
    return api.get('/chofer/mi-historial')
  },
}
