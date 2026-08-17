import api from './axios'

export const authApi = {
  /**
   * Iniciar sesión.
   * @param {Object} credentials - { email, password }
   * @returns {Promise}
   */
  login(credentials) {
    return api.post('/auth/login', credentials)
  },

  /**
   * Registrar un nuevo usuario.
   * @param {Object} data - { name, email, password, password_confirmation }
   * @returns {Promise}
   */
  register(data) {
    return api.post('/auth/register', data)
  },

  /**
   * Cerrar sesión.
   * @returns {Promise}
   */
  logout() {
    return api.post('/auth/logout')
  },

  /**
   * Obtener usuario autenticado.
   * @returns {Promise}
   */
  me() {
    return api.get('/auth/me')
  },
}
