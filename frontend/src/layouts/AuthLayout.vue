<template>
  <div class="auth-layout">
    <div class="auth-layout__background">
      <div class="auth-layout__gradient-orb auth-layout__gradient-orb--1"></div>
      <div class="auth-layout__gradient-orb auth-layout__gradient-orb--2"></div>
      <div class="auth-layout__gradient-orb auth-layout__gradient-orb--3"></div>
    </div>
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
        <p class="auth-layout__subtitle">Sistema de Gestión</p>
      </div>
      <router-view v-slot="{ Component }">
        <transition name="fade-slide" mode="out-in">
          <component :is="Component" />
        </transition>
      </router-view>
    </div>
  </div>
</template>

<script setup>
// Layout de autenticación — no requiere lógica
</script>
