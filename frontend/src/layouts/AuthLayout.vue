<template>
  <div class="auth-layout">
    <div class="auth-layout__container">
      <div class="auth-layout__brand">
        <div class="auth-layout__logo">
          <img src="/logo-minibus.svg" alt="Sindicato Minibús Logo" class="brand-minibus-img" />
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
  width: 72px;
  height: 72px;
  margin: 0 auto 0.75rem auto;
}

.brand-minibus-img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1));
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
