<template>
  <div class="app-layout">
    <!-- Sidebar -->
    <AppSidebar 
      :is-collapsed="sidebarCollapsed"
      @toggle="toggleSidebar"
      @new-patient="handleNewPatient"
    />
    
    <!-- Main Content Area -->
    <div 
      class="main-content transition-all duration-300 ease-in-out"
      :class="sidebarCollapsed ? 'sidebar-collapsed' : 'sidebar-expanded'"
    >
      <!-- Header -->
      <AppHeader 
        :user="currentUser"
        :sidebar-collapsed="sidebarCollapsed"
        @toggle-sidebar="toggleSidebar"
        @logout="handleLogout"
      />
      
      <!-- Page Content -->
      <main class="content-area flex-1 p-6">
        <Transition name="page" mode="out-in">
          <slot />
        </Transition>
      </main>
    </div>
    
    <!-- Global Notifications -->
    <NotificationContainer />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import AppSidebar from './AppSidebar.vue'
import AppHeader from './AppHeader.vue'
import NotificationContainer from './NotificationContainer.vue'
import { useAuthStore } from '@/stores/auth'

// Router
const router = useRouter()

// Store
const authStore = useAuthStore()

// State
const sidebarCollapsed = ref(false)

// Computed
const currentUser = computed(() => authStore.user)

// Methods
const toggleSidebar = () => {
  sidebarCollapsed.value = !sidebarCollapsed.value
  // Save preference to localStorage
  localStorage.setItem('sidebarCollapsed', String(sidebarCollapsed.value))
}

const handleLogout = async () => {
  await authStore.logout()
  router.push('/login')
}

const handleNewPatient = () => {
  router.push('/patients/new')
}

// Initialize sidebar state from localStorage
onMounted(() => {
  const savedState = localStorage.getItem('sidebarCollapsed')
  if (savedState !== null) {
    sidebarCollapsed.value = savedState === 'true'
  }
})
</script>

<style scoped>
.app-layout {
  @apply min-h-screen bg-gray-50 flex;
}

.main-content {
  @apply flex-1 flex flex-col min-h-screen;
}

.main-content.sidebar-expanded {
  @apply ml-64;
}

.main-content.sidebar-collapsed {
  @apply ml-16;
}

.content-area {
  @apply overflow-auto;
}

/* Page transitions */
.page-enter-active,
.page-leave-active {
  transition: all 0.25s ease-in-out;
}

.page-enter-from {
  opacity: 0;
  transform: translateY(10px);
}

.page-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

/* Responsive design */
@media (max-width: 768px) {
  .main-content.sidebar-expanded,
  .main-content.sidebar-collapsed {
    @apply ml-0;
  }
  
  .content-area {
    @apply p-4;
  }
}
</style>
