<template>
  <div class="mobile-login">
    <div class="login-header">
      <div class="app-logo">🚍</div>
      <h1 class="app-title">Sindicato Móvil</h1>
      <p class="app-subtitle">Sistema de Control Operativo, Asistencias & Tesorería</p>
    </div>

    <form @submit.prevent="handleLogin" class="login-form">
      <div v-if="errorMessage" class="alert alert-error">
        <span>{{ errorMessage }}</span>
      </div>

      <div class="form-group">
        <label class="form-label">Nombre de Usuario</label>
        <input
          v-model="username"
          type="text"
          class="form-input"
          placeholder="ej. juan.perez"
          required
        />
      </div>

      <div class="form-group">
        <label class="form-label">Contraseña</label>
        <input
          v-model="password"
          type="password"
          class="form-input"
          placeholder="••••••••"
          required
        />
      </div>

      <!-- Configuración de Dirección IP del Servidor en Red Local -->
      <div class="server-config-box">
        <div class="server-config-header" @click="showServerInput = !showServerInput">
          <span>⚙️ Servidor Backend: <small class="font-mono">{{ apiUrl }}</small></span>
          <span>{{ showServerInput ? '▲' : '▼' }}</span>
        </div>

        <div v-if="showServerInput" class="server-input-group">
          <label class="form-label">Servidor Backend / Dominio (API)</label>
          <input
            v-model="apiUrl"
            type="text"
            class="form-input font-mono"
            placeholder="https://papserver.site/api"
            @change="saveApiUrl"
          />
          <small class="text-muted">Servidor Propio: https://papserver.site/api o IP: http://181.114.124.70/api</small>
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-block btn-lg" :disabled="submitting">
        <span v-if="submitting">Ingresando...</span>
        <span v-else>Iniciar Sesión</span>
      </button>
    </form>

    <div class="login-footer">
      <small class="text-muted">Sindicato de Choferes — Versión Móvil 1.0</small>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import apiMobile from '@/services/axiosMobile'
import { mobileStorage } from '@/services/storage'

const emit = defineEmits(['login-success'])

const username = ref('')
const password = ref('')
const apiUrl = ref(mobileStorage.getApiUrl())
const showServerInput = ref(false)
const submitting = ref(false)
const errorMessage = ref('')

function saveApiUrl() {
  mobileStorage.setApiUrl(apiUrl.value)
  apiUrl.value = mobileStorage.getApiUrl()
}

async function handleLogin() {
  submitting.value = true
  errorMessage.value = ''
  saveApiUrl()

  try {
    const res = await apiMobile.post('/auth/login', {
      username: username.value,
      password: password.value,
    })

    const { token, usuario } = res.data.data
    mobileStorage.setToken(token)
    mobileStorage.setUser(usuario)

    emit('login-success', usuario)
  } catch (err) {
    console.error('Error de login móvil:', err)
    errorMessage.value = err.response?.data?.message || 'Error de conexión con el servidor API. Verifique su red Wi-Fi o IP.'
  } finally {
    submitting.value = false
  }
}
</script>

<style scoped>
.mobile-login {
  min-height: 100vh;
  background: var(--color-bg-secondary, #f8fafc);
  padding: 2rem 1.5rem;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.login-header {
  text-align: center;
  margin-bottom: 2rem;
}
.app-logo { font-size: 3.5rem; margin-bottom: 0.5rem; }
.app-title { font-size: 1.6rem; font-weight: 800; color: #1e293b; }
.app-subtitle { font-size: 0.85rem; color: #64748b; margin-top: 0.25rem; }

.login-form {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 1rem;
  padding: 1.5rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}

.form-group { margin-bottom: 1.25rem; }
.form-label { display: block; font-size: 0.8rem; font-weight: 600; color: #334155; margin-bottom: 0.35rem; }
.form-input {
  width: 100%; padding: 0.75rem 1rem; border: 1px solid #cbd5e1;
  border-radius: 0.6rem; font-size: 0.95rem; color: #0f172a;
}
.form-input:focus { border-color: #2563eb; outline: none; }

.server-config-box {
  margin-bottom: 1.25rem;
  background: #f1f5f9;
  border: 1px solid #cbd5e1;
  border-radius: 0.6rem;
  padding: 0.75rem;
  font-size: 0.8rem;
}
.server-config-header {
  display: flex; justify-content: space-between; align-items: center;
  cursor: pointer; font-weight: 600; color: #475569;
}
.server-input-group { margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid #cbd5e1; }

.btn-block { width: 100%; }
.btn-lg { padding: 0.85rem 1rem; font-size: 1rem; font-weight: 700; border-radius: 0.6rem; border: none; cursor: pointer; }
.btn-primary { background: #2563eb; color: #ffffff; }

.alert-error {
  padding: 0.75rem; background: #fef2f2; border: 1px solid #fecaca;
  color: #dc2626; border-radius: 0.6rem; font-size: 0.85rem; margin-bottom: 1rem;
}

.login-footer { text-align: center; margin-top: 2rem; }
</style>
