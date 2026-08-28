<template>
  <form class="login-card" @submit.prevent="handleSubmit" id="login-form">
    <h2 class="login-card__title">Iniciar Sesión</h2>
    <p class="login-card__subtitle">Ingrese sus credenciales de acceso</p>

    <div class="form-group">
      <label for="login-username" class="form-label">Nombre de Usuario</label>
      <div class="input-wrapper">
        <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
          <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
          <circle cx="12" cy="7" r="4" />
        </svg>
        <input
          id="login-username"
          v-model="form.username"
          type="text"
          placeholder="Nombre de usuario..."
          class="form-control input-has-icon"
          required
          autocomplete="username"
        />
      </div>
    </div>

    <div class="form-group">
      <label for="login-password" class="form-label">Contraseña</label>
      <div class="input-wrapper">
        <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
          <rect x="3" y="11" width="18" height="11" rx="2" />
          <path d="M7 11V7a5 5 0 0 1 10 0v4" />
        </svg>
        <input
          id="login-password"
          v-model="form.password"
          :type="showPassword ? 'text' : 'password'"
          placeholder="••••••••"
          class="form-control input-has-icon"
          required
          autocomplete="current-password"
        />
        <button
          type="button"
          class="toggle-password-btn"
          @click="showPassword = !showPassword"
          id="btn-toggle-password"
        >
          <svg v-if="!showPassword" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
            <circle cx="12" cy="12" r="3" />
          </svg>
          <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" />
            <line x1="1" y1="1" x2="23" y2="23" />
          </svg>
        </button>
      </div>
    </div>

    <transition name="fade">
      <div v-if="error" class="login-card__error" id="login-error">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
        <span>{{ error }}</span>
      </div>
    </transition>

    <button
      type="submit"
      class="btn btn-primary login-card__submit"
      :disabled="loading"
      id="btn-login"
    >
      <AppLoader v-if="loading" :visible="true" />
      <span v-else>Iniciar Sesión</span>
    </button>
  </form>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useAuth } from '@/composables/useAuth'
import AppLoader from '@/components/common/AppLoader.vue'

const { login, error, loading } = useAuth()

const form = reactive({
  username: '',
  password: '',
})
const showPassword = ref(false)

async function handleSubmit() {
  try {
    await login(form)
  } catch {
    // Error manejado por el store
  }
}
</script>

<style scoped>
.login-card {
  background: #ffffff;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-2xl);
  padding: 2rem;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08);
  width: 100%;
}
.login-card__title { font-size: 1.35rem; font-weight: 800; color: var(--color-text-primary); margin-bottom: 0.25rem; }
.login-card__subtitle { font-size: 0.85rem; color: var(--color-text-secondary); margin-bottom: 1.5rem; }

.input-wrapper { position: relative; display: flex; align-items: center; }
.input-icon { position: absolute; left: 0.85rem; width: 18px; height: 18px; color: var(--color-text-muted); }
.input-has-icon { padding-left: 2.5rem; }
.toggle-password-btn { position: absolute; right: 0.85rem; background: none; border: none; color: var(--color-text-muted); cursor: pointer; display: flex; }
.toggle-password-btn svg { width: 18px; height: 18px; }

.login-card__submit { width: 100%; margin-top: 1rem; padding: 0.75rem; font-size: 0.95rem; font-weight: 700; }

.login-card__error {
  background: var(--color-error-bg);
  border: 1px solid #fecaca;
  color: var(--color-error);
  padding: 0.75rem;
  border-radius: var(--radius-md);
  font-size: 0.85rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 1rem;
}
.login-card__error svg { width: 18px; height: 18px; flex-shrink: 0; }
</style>
