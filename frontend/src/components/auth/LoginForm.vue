<template>
  <form class="login-form" @submit.prevent="handleSubmit" id="login-form">
    <div class="login-form__field">
      <label for="login-username" class="login-form__label">Nombre de Usuario</label>
      <div class="login-form__input-wrapper">
        <svg class="login-form__input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
          <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
          <circle cx="12" cy="7" r="4" />
        </svg>
        <input
          id="login-username"
          v-model="form.username"
          type="text"
          placeholder="ej. admin o miguel.flores"
          class="login-form__input"
          required
          autocomplete="username"
        />
      </div>
    </div>

    <div class="login-form__field">
      <label for="login-password" class="login-form__label">Contraseña</label>
      <div class="login-form__input-wrapper">
        <svg class="login-form__input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
          <rect x="3" y="11" width="18" height="11" rx="2" />
          <path d="M7 11V7a5 5 0 0 1 10 0v4" />
        </svg>
        <input
          id="login-password"
          v-model="form.password"
          :type="showPassword ? 'text' : 'password'"
          placeholder="••••••••"
          class="login-form__input"
          required
          autocomplete="current-password"
        />
        <button
          type="button"
          class="login-form__toggle-password"
          @click="showPassword = !showPassword"
          id="btn-toggle-password"
        >
          <svg v-if="!showPassword" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
            <circle cx="12" cy="12" r="3" />
          </svg>
          <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" />
            <line x1="1" y1="1" x2="23" y2="23" />
          </svg>
        </button>
      </div>
    </div>

    <transition name="fade">
      <div v-if="error" class="login-form__error" id="login-error">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
          <circle cx="12" cy="12" r="10" />
          <line x1="15" y1="9" x2="9" y2="15" />
          <line x1="9" y1="9" x2="15" y2="15" />
        </svg>
        {{ error }}
      </div>
    </transition>

    <button
      type="submit"
      class="login-form__submit"
      :disabled="loading"
      id="btn-login"
    >
      <AppLoader v-if="loading" :visible="true" />
      <span v-else>Iniciar Sesión</span>
    </button>

    <div class="login-form__demo-users">
      <p class="login-form__demo-title">Credenciales de prueba (clave: <code>password</code>):</p>
      <div class="login-form__demo-tags">
        <button type="button" class="login-form__demo-tag" @click="setCredentials('admin')">admin (Admin)</button>
        <button type="button" class="login-form__demo-tag" @click="setCredentials('miguel.flores')">miguel.flores (Chofer + Inspector)</button>
        <button type="button" class="login-form__demo-tag" @click="setCredentials('pedro.condori')">pedro.condori (Chofer + Tesorero)</button>
        <button type="button" class="login-form__demo-tag" @click="setCredentials('roberto.choque')">roberto.choque (Chofer + Jefe Grupo)</button>
      </div>
    </div>
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

function setCredentials(user) {
  form.username = user
  form.password = 'password'
}

async function handleSubmit() {
  try {
    await login(form)
  } catch {
    // Error manejado por el store
  }
}
</script>

<style scoped>
.login-form__demo-users {
  margin-top: 1.5rem;
  padding-top: 1rem;
  border-top: 1px solid var(--color-border);
}
.login-form__demo-title {
  font-size: 0.75rem;
  color: var(--color-text-muted);
  margin-bottom: 0.5rem;
}
.login-form__demo-title code {
  color: var(--color-primary-400);
}
.login-form__demo-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem;
}
.login-form__demo-tag {
  font-size: 0.7rem;
  padding: 0.25rem 0.5rem;
  background: var(--color-bg-glass);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  color: var(--color-text-secondary);
  transition: all var(--transition-fast);
}
.login-form__demo-tag:hover {
  border-color: var(--color-primary-500);
  color: var(--color-primary-300);
  background: rgba(99, 102, 241, 0.1);
}
</style>
