<template>
  <footer class="app-footer">
    <p>&copy; {{ currentYear }} Sindicato de Choferes — Sistema de Gestión</p>
  </footer>
</template>

<script setup>
const currentYear = new Date().getFullYear()
</script>
