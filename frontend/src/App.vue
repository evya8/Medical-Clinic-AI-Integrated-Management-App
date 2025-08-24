<template>
  <div id="app" class="app">
    <!-- Router View - All pages will be rendered here -->
    <RouterView />
  </div>
</template>

<script setup lang="ts">
import { RouterView } from 'vue-router'
import { onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

// Initialize authentication on app startup
onMounted(async () => {
  // Check if user is already logged in (token in localStorage)
  await authStore.initializeAuth()
})
</script>

<style scoped>
.app {
  min-height: 100vh;
  background-color: var(--gray-50);
}
</style>
