import api from './axios'

export const notificacionApi = {
  obtenerNotificaciones() {
    return api.get('/notificaciones')
  },
  marcarLeida(id) {
    return api.patch(`/notificaciones/${id}/leer`)
  },
  marcarTodasLeidas() {
    return api.patch('/notificaciones/marcar-todas')
  },
}
