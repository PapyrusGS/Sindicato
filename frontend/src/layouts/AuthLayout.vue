<template>
  <div class="auth-layout">
    <div class="auth-layout__container">
      <div class="auth-layout__brand">
        <div class="auth-layout__logo">
          <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="24" cy="24" r="22" stroke="currentColor" stroke-width="2.5" />
            <path d="M24 8C24 8 32 16 32 24C32 32 24 40 24 40" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
            <path d="M24 8C24 8 16 16 16 24C16 32 24 40 24 40" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
            <circle cx="24" cy="24" r="6" stroke="currentColor" stroke-width="2" />
            <line x1="2" y1="24" x2="46" y2="24" stroke="currentColor" stroke-width="1.5" />
          </svg>
        </div>
        <h1 class="auth-layout__title">Sindicato de Choferes</h1>
        <p class="auth-layout__subtitle">Plataforma Oficial de Gestión</p>
      </div>

      <router-view v-slot="{ Component }">
        <transition name="fade" mode="out-in">
          <component :is="Component" />
        </transition>
      </router-view>
    </div>
  </div>
</template>

<script setup>
</script>

<style scoped>
.auth-layout {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: #f8fafc;
  padding: 2rem 1rem;
}

.auth-layout__container {
  width: 100%;
  max-width: 440px;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.auth-layout__brand {
  text-align: center;
  margin-bottom: 1.5rem;
}

.auth-layout__logo {
  width: 52px;
  height: 52px;
  color: var(--color-primary-600);
  margin: 0 auto 0.75rem auto;
}

.auth-layout__title {
  font-size: 1.5rem;
  font-weight: 800;
  color: var(--color-text-primary);
  margin-bottom: 0.25rem;
}

.auth-layout__subtitle {
  font-size: 0.9rem;
  color: var(--color-text-secondary);
}
</style>
