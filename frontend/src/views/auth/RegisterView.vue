<template>
  <div class="register-view">
    <form class="register-form" @submit.prevent="handleSubmit" id="register-form">
      <div class="register-form__field">
        <label for="register-name" class="register-form__label">Nombre Completo</label>
        <div class="register-form__input-wrapper">
          <svg class="register-form__input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
            <circle cx="12" cy="7" r="4" />
          </svg>
          <input
            id="register-name"
            v-model="form.name"
            type="text"
            placeholder="Juan Pérez"
            class="register-form__input"
            required
          />
        </div>
      </div>

      <div class="register-form__field">
        <label for="register-email" class="register-form__label">Correo Electrónico</label>
        <div class="register-form__input-wrapper">
          <svg class="register-form__input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <rect x="2" y="4" width="20" height="16" rx="2" />
            <path d="M22 4L12 13L2 4" />
          </svg>
          <input
            id="register-email"
            v-model="form.email"
            type="email"
            placeholder="correo@ejemplo.com"
            class="register-form__input"
            required
          />
        </div>
      </div>

      <div class="register-form__field">
        <label for="register-password" class="register-form__label">Contraseña</label>
        <div class="register-form__input-wrapper">
          <svg class="register-form__input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <rect x="3" y="11" width="18" height="11" rx="2" />
            <path d="M7 11V7a5 5 0 0 1 10 0v4" />
          </svg>
          <input
            id="register-password"
            v-model="form.password"
            type="password"
            placeholder="••••••••"
            class="register-form__input"
            required
          />
        </div>
      </div>

      <div class="register-form__field">
        <label for="register-password-confirm" class="register-form__label">Confirmar Contraseña</label>
        <div class="register-form__input-wrapper">
          <svg class="register-form__input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <rect x="3" y="11" width="18" height="11" rx="2" />
            <path d="M7 11V7a5 5 0 0 1 10 0v4" />
            <polyline points="9 16 11 18 15 14" />
          </svg>
          <input
            id="register-password-confirm"
            v-model="form.password_confirmation"
            type="password"
            placeholder="••••••••"
            class="register-form__input"
            required
          />
        </div>
      </div>

      <transition name="fade">
        <div v-if="error" class="register-form__error" id="register-error">
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
        class="register-form__submit"
        :disabled="loading"
        id="btn-register"
      >
        <span v-if="loading">Registrando...</span>
        <span v-else>Crear Cuenta</span>
      </button>

      <p class="register-form__footer">
        ¿Ya tienes cuenta?
        <router-link to="/login" class="register-form__link" id="link-login">Inicia sesión</router-link>
      </p>
    </form>
  </div>
</template>

<script setup>
import { reactive } from 'vue'
import { useAuth } from '@/composables/useAuth'

const { register, error, loading } = useAuth()

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

async function handleSubmit() {
  try {
    await register(form)
  } catch {
    // Error manejado por el store
  }
}
</script>
