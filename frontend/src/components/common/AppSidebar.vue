<template>
  <aside 
    class="sidebar fixed inset-y-0 left-0 z-50 flex flex-col bg-white shadow-lg border-r border-gray-200 transition-all duration-300 ease-in-out"
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
            class="ml-3 text-lg font-semibold text-gray-200 truncate"
          >
            MediCore Clinic
          </span>
        </Transition>
      </div>
    </div>
    
    <!-- Navigation Menu -->
    <nav class="sidebar-nav flex-1 min-h-0 px-2 py-4 space-y-1 overflow-y-auto">
      <!-- Main Dashboard Section -->
      <SidebarSection title="Overview" :collapsed="isCollapsed">
        <SidebarItem 
          to="/dashboard"
          :icon="HomeIcon"
          label="Dashboard"
          :collapsed="isCollapsed"
        />
        <SidebarItem 
          to="/ai-dashboard"
          :icon="CpuChipIcon"
          label="AI Dashboard"
          :collapsed="isCollapsed"
          :badge="aiAlertCount"
        />
      </SidebarSection>
      
      <!-- Patient Management -->
      <SidebarSection title="Patient Care" :collapsed="isCollapsed">
        <SidebarItem 
          to="/patients"
          :icon="UsersIcon"
          label="All Patients"
          :collapsed="isCollapsed"
          :badge="newPatientsCount"
        />
        <SidebarItem 
          to="/appointments"
          :icon="CalendarIcon"
          label="Appointments"
          :collapsed="isCollapsed"
          :badge="todayAppointmentsCount"
        />
      </SidebarSection>

      <!-- AI Features Section -->
      <SidebarSection title="AI Features" :collapsed="isCollapsed">
        <SidebarItem 
          to="/ai-features/triage"
          :icon="HeartIcon"
          label="AI Triage"
          :collapsed="isCollapsed"
          :badge="triageQueueCount"
          badge-color="red"
        />
        <SidebarItem 
          to="/ai-features/summaries"
          :icon="DocumentTextIcon"
          label="AI Summaries"
          :collapsed="isCollapsed"
        />
        <SidebarItem 
          to="/ai-features/alerts"
          :icon="ExclamationTriangleIcon"
          label="AI Alerts"
          :collapsed="isCollapsed"
          :badge="activeAlertsCount"
          badge-color="yellow"
        />
      </SidebarSection>

      <!-- Analytics & Reports -->
      <SidebarSection title="Analytics" :collapsed="isCollapsed">
        <SidebarItem 
          to="/reports/analytics"
          :icon="ChartBarIcon"
          label="Reports & Analytics"
          :collapsed="isCollapsed"
        />
      </SidebarSection>

      <!-- Administration (Only for admins) -->
      <SidebarSection 
        v-if="userStore.hasRole('admin')" 
        title="Administration" 
        :collapsed="isCollapsed"
      >
        <SidebarItem 
          to="/admin/users"
          :icon="UserGroupIcon"
          label="User Management"
          :collapsed="isCollapsed"
        />
        <SidebarItem 
          to="/admin/settings"
          :icon="Cog6ToothIcon"
          label="System Settings"
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
        <div class="space-y-2">
          <button
            class="quick-action-btn"
            @click="$emit('new-patient')"
          >
            <PlusIcon class="w-4 h-4 mr-2" />
            New Patient
          </button>
          <button
            class="quick-action-btn"
            @click="$emit('schedule-appointment')"
          >
            <PlusIcon class="w-4 h-4 mr-2" />
            Schedule Appointment
          </button>
          <button
            class="quick-action-btn"
            @click="$emit('ai-triage')"
          >
            <HeartIcon class="w-4 h-4 mr-2" />
            Quick Triage
          </button>
        </div>
      </div>

      <!-- System Status (Only for non-collapsed state) -->
      <div v-if="!isCollapsed" class="pt-4">
        <div class="px-3 pb-2">
          <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">
            System Status
          </h3>
        </div>
        <div class="px-3 py-2 bg-gray-50 rounded-lg">
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs text-gray-600">AI Models</span>
            <div class="flex items-center">
              <div class="w-2 h-2 bg-green-400 rounded-full mr-1"></div>
              <span class="text-xs text-green-700">Online</span>
            </div>
          </div>
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs text-gray-600">Database</span>
            <div class="flex items-center">
              <div class="w-2 h-2 bg-green-400 rounded-full mr-1"></div>
              <span class="text-xs text-green-700">Healthy</span>
            </div>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-xs text-gray-600">Last Backup</span>
            <span class="text-xs text-gray-600">{{ lastBackupTime }}</span>
          </div>
        </div>
      </div>
    </nav>
    
    <!-- User Profile Section (Only for non-collapsed state) -->
    <div v-if="!isCollapsed" class="user-section px-4 py-3 border-t border-gray-200">
      <div class="flex items-center">
        <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center">
          <UserIcon class="w-4 h-4 text-gray-600" />
        </div>
        <div class="ml-3 min-w-0 flex-1">
          <p class="text-sm font-medium text-gray-900 truncate">
            {{ userStore.userName }}
          </p>
          <p class="text-xs text-gray-500 truncate">
            {{ userStore.userRole }}
          </p>
        </div>
      </div>
    </div>
    
    <!-- Sidebar Footer / Toggle Button -->
    <div class="sidebar-footer p-4 border-t border-gray-200">
      <button
        class="toggle-btn"
        @click="$emit('toggle')"
        :title="isCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
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
import { computed } from 'vue'
import { 
  HomeIcon,
  ChartBarIcon, 
  UsersIcon, 
  CalendarIcon,
  CpuChipIcon,
  HeartIcon,
  DocumentTextIcon,
  ExclamationTriangleIcon,
  UserGroupIcon,
  Cog6ToothIcon,
  PlusIcon,
  UserIcon,
  ChevronLeftIcon 
} from '@heroicons/vue/24/outline'
import SidebarSection from './SidebarSection.vue'
import SidebarItem from './SidebarItem.vue'
import { useAuthStore } from '@/stores/auth'

interface Props {
  isCollapsed: boolean
}

interface Emits {
  (e: 'toggle'): void
  (e: 'new-patient'): void
  (e: 'schedule-appointment'): void
  (e: 'ai-triage'): void
}

defineProps<Props>()
defineEmits<Emits>()

const userStore = useAuthStore()

// Mock data for badges - in real app, these would come from stores/API
const aiAlertCount = computed(() => 3)
const newPatientsCount = computed(() => 5)
const todayAppointmentsCount = computed(() => 12)
const triageQueueCount = computed(() => 4)
const activeAlertsCount = computed(() => 8)

const lastBackupTime = computed(() => {
  return new Date().toLocaleTimeString('en-US', {
    hour: 'numeric',
    minute: '2-digit',
  })
})
</script>

<style lang="postcss" scoped>
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

/* Quick action buttons */
.quick-action-btn {
  @apply flex items-center w-full px-3 py-2 text-sm text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 hover:border-gray-300 transition-all duration-200;
}

.quick-action-btn:hover {
  @apply shadow-sm;
}

/* Toggle button */
.toggle-btn {
  @apply flex items-center justify-center w-full p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors duration-200;
}

/* User section styling */
.user-section {
  @apply bg-gray-50;
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
@media (max-width: 1024px) {
  .sidebar {
    transform: translateX(-100%);
    transition: transform 0.3s ease-in-out;
  }
  
  .sidebar.mobile-open {
    transform: translateX(0);
  }

  /* Mobile overlay */
  .sidebar.mobile-open::before {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: -1;
  }
}

/* Hover effects for better UX */
.sidebar-nav .space-y-1 > * {
  @apply transition-all duration-200;
}

/* System status indicators */
.w-2.h-2.bg-green-400 {
  @apply animate-pulse;
}

/* Enhanced spacing for sections */
.sidebar-nav .space-y-1 > div:not(:first-child) {
  @apply pt-2;
}

/* Responsive text sizing */
@media (max-width: 768px) {
  .sidebar-header span {
    @apply text-base;
  }
  
  .quick-action-btn {
    @apply text-xs;
  }
}

/* Dark mode support (if needed in future) */
@media (prefers-color-scheme: dark) {
  .sidebar {
    @apply bg-gray-900 border-gray-700;
  }
  
  .sidebar-header,
  .sidebar-footer,
  .user-section {
    @apply border-gray-700;
  }
  
  .user-section {
    @apply bg-gray-800;
  }
}

/* Animation for collapsed/expanded states */
.sidebar {
  transition: width 0.3s ease-in-out;
}

/* Ensure proper spacing in collapsed state */
.sidebar.w-16 .sidebar-nav {
  @apply px-2;
}

/* Badge positioning improvements */
.sidebar-nav :deep(.sidebar-item-badge) {
  @apply absolute -top-1 -right-1;
}

/* Improve accessibility */
.toggle-btn:focus,
.quick-action-btn:focus {
  @apply outline-none ring-2 ring-blue-500 ring-offset-2;
}

/* Loading state for dynamic content */
.sidebar-nav .skeleton {
  @apply bg-gray-200 rounded animate-pulse;
}
</style>
