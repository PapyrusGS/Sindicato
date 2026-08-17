<template>
  <transition name="fade">
    <div v-if="visible" class="app-loader" id="app-loader">
      <div class="app-loader__spinner">
        <div class="app-loader__ring"></div>
        <div class="app-loader__ring"></div>
        <div class="app-loader__ring"></div>
      </div>
      <p v-if="message" class="app-loader__message">{{ message }}</p>
    </div>
  </transition>
</template>

<script setup>
defineProps({
  visible: {
    type: Boolean,
    default: false,
  },
  message: {
    type: String,
    default: '',
  },
})
</script>
