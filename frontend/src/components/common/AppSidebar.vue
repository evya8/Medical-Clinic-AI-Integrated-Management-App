<template>
  <aside 
    class="sidebar fixed inset-y-0 left-0 z-50 bg-white shadow-lg border-r border-gray-200 transition-all duration-300 ease-in-out"
    :class="isCollapsed ? 'w-16' : 'w-64'"
  >
    <!-- Sidebar Header -->
    <div class="sidebar-header flex items-center h-16 px-4 border-b border-gray-200">
      <div class="flex items-center min-w-0">
        <!-- Clinic Logo -->
        <div class="flex items-center justify-center w-8 h-8 bg-primary-500 rounded-lg flex-shrink-0">
          <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
          </svg>
        </div>
        
        <!-- Clinic Name -->
        <Transition name="fade">
          <span 
            v-show="!isCollapsed" 
            class="ml-3 text-lg font-semibold text-gray-900 truncate"
          >
            MediCore Clinic
          </span>
        </Transition>
      </div>
    </div>
    
    <!-- Navigation Menu -->
    <nav class="sidebar-nav flex-1 px-2 py-4 space-y-1 overflow-y-auto">
      <!-- Main Section -->
      <SidebarSection title="Overview" :collapsed="isCollapsed">
        <SidebarItem 
          to="/dashboard"
          :icon="ChartBarIcon"
          label="Dashboard"
          :collapsed="isCollapsed"
        />
      </SidebarSection>
      
      <!-- Patient Management -->
      <SidebarSection title="Patients" :collapsed="isCollapsed">
        <SidebarItem 
          to="/patients"
          :icon="UsersIcon"
          label="All Patients"
          :collapsed="isCollapsed"
        />
      </SidebarSection>
      
      <!-- Quick Actions (Only for non-collapsed state) -->
      <div v-if="!isCollapsed" class="pt-4">
        <div class="px-3 pb-2">
          <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">
            Quick Actions
          </h3>
        </div>
        <div class="space-y-1">
          <button
            class="medical-button-secondary w-full text-left text-sm"
            @click="$emit('new-patient')"
          >
            <PlusIcon class="w-4 h-4 mr-2 inline" />
            New Patient
          </button>
        </div>
      </div>
    </nav>
    
    <!-- Sidebar Footer / Toggle Button -->
    <div class="sidebar-footer p-4 border-t border-gray-200">
      <button
        class="flex items-center justify-center w-full p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors duration-200"
        @click="$emit('toggle')"
      >
        <ChevronLeftIcon 
          class="w-5 h-5 transition-transform duration-300"
          :class="{ 'rotate-180': isCollapsed }"
        />
        <span v-show="!isCollapsed" class="ml-2 text-sm">
          Collapse
        </span>
      </button>
    </div>
  </aside>
</template>

<script setup lang="ts">
import { 
  ChartBarIcon, 
  UsersIcon, 
  PlusIcon,
  ChevronLeftIcon 
} from '@heroicons/vue/24/outline'
import SidebarSection from './SidebarSection.vue'
import SidebarItem from './SidebarItem.vue'

interface Props {
  isCollapsed: boolean
}

interface Emits {
  (e: 'toggle'): void
  (e: 'new-patient'): void
}

defineProps<Props>()
defineEmits<Emits>()
</script>

<style scoped>
.sidebar {
  /* Ensure sidebar stays above main content */
  z-index: 50;
}

.sidebar-nav {
  /* Custom scrollbar for webkit browsers */
  scrollbar-width: thin;
  scrollbar-color: rgb(209 213 219) transparent;
}

.sidebar-nav::-webkit-scrollbar {
  width: 6px;
}

.sidebar-nav::-webkit-scrollbar-track {
  background: transparent;
}

.sidebar-nav::-webkit-scrollbar-thumb {
  background-color: rgb(209 213 219);
  border-radius: 3px;
}

.sidebar-nav::-webkit-scrollbar-thumb:hover {
  background-color: rgb(156 163 175);
}

/* Fade transition for text elements */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
  .sidebar {
    transform: translateX(-100%);
    transition: transform 0.3s ease-in-out;
  }
  
  .sidebar.mobile-open {
    transform: translateX(0);
  }
}
</style>
