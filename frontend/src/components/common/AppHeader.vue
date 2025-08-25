<template>
  <header class="app-header bg-white shadow-sm border-b border-gray-200 h-16 flex items-center justify-between px-6">
    <!-- Left Side - Mobile Menu Button + Page Title -->
    <div class="flex items-center">
      <!-- Mobile Sidebar Toggle -->
      <button
        class="md:hidden p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100"
        @click="$emit('toggle-sidebar')"
      >
        <Bars3Icon class="w-6 h-6" />
      </button>
      
      <!-- Page Title -->
      <div class="ml-4 md:ml-0">
        <h1 class="text-xl font-semibold text-gray-900">
          {{ pageTitle }}
        </h1>
        <p v-if="pageSubtitle" class="text-sm text-gray-500 mt-1">
          {{ pageSubtitle }}
        </p>
      </div>
    </div>

    <!-- Right Side - User Menu + Actions -->
    <div class="flex items-center space-x-4">
      <!-- Search (Desktop) -->
      <div class="hidden md:block relative">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
          <MagnifyingGlassIcon class="h-4 w-4 text-gray-400" />
        </div>
        <input
          type="search"
          placeholder="Search patients..."
          class="medical-input w-64 pl-10 text-sm"
          v-model="searchQuery"
          @keyup.enter="handleSearch"
        />
      </div>

      <!-- Notifications -->
      <div class="relative notifications-container">
        <button
          class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-full relative"
          @click="showNotifications = !showNotifications"
        >
          <BellIcon class="w-6 h-6" />
          <span
            v-if="unreadNotifications > 0"
            class="absolute -top-1 -right-1 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-red-500 rounded-full min-w-[1rem] h-4"
          >
            {{ unreadNotifications > 99 ? '99+' : unreadNotifications }}
          </span>
        </button>
        
        <!-- Notifications Dropdown -->
        <Transition name="dropdown">
          <div
            v-if="showNotifications"
            class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 z-50"
            @click.stop
          >
            <div class="p-4 border-b border-gray-200">
              <h3 class="text-sm font-medium text-gray-900">Notifications</h3>
            </div>
            <div class="max-h-64 overflow-y-auto">
              <div v-if="notifications.length === 0" class="p-4 text-sm text-gray-500 text-center">
                No new notifications
              </div>
              <div v-else class="divide-y divide-gray-100">
                <div
                  v-for="notification in notifications.slice(0, 5)"
                  :key="notification.id"
                  class="p-4 hover:bg-gray-50 cursor-pointer"
                  @click="handleNotificationClick(notification)"
                >
                  <div class="flex">
                    <div class="flex-1">
                      <p class="text-sm font-medium text-gray-900">
                        {{ notification.title }}
                      </p>
                      <p class="text-xs text-gray-500 mt-1">
                        {{ notification.message }}
                      </p>
                    </div>
                    <div class="ml-2">
                      <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="p-2 border-t border-gray-200">
              <button class="w-full text-center text-sm text-primary-600 hover:text-primary-800 py-2">
                View all notifications
              </button>
            </div>
          </div>
        </Transition>
      </div>

      <!-- User Menu -->
      <div class="relative">
        <Menu as="div" class="relative inline-block text-left">
          <div>
            <MenuButton class="flex items-center space-x-3 p-2 rounded-full hover:bg-gray-100 transition-colors duration-200">
              <!-- User Avatar -->
              <div class="w-8 h-8 bg-primary-500 rounded-full flex items-center justify-center">
                <span class="text-sm font-medium text-white">
                  {{ userInitials }}
                </span>
              </div>
              <!-- User Name (Desktop) -->
              <div class="hidden md:block text-left">
                <p class="text-sm font-medium text-gray-900">
                  {{ userDisplayName }}
                </p>
                <p class="text-xs text-gray-500">
                  {{ userRole }}
                </p>
              </div>
              <!-- Dropdown Arrow -->
              <ChevronDownIcon class="w-4 h-4 text-gray-400" />
            </MenuButton>
          </div>

          <Transition
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="transform scale-95 opacity-0"
            enter-to-class="transform scale-100 opacity-100"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="transform scale-100 opacity-100"
            leave-to-class="transform scale-95 opacity-0"
          >
            <MenuItems class="absolute right-0 mt-2 w-56 origin-top-right bg-white divide-y divide-gray-100 rounded-md shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none z-50">
              <div class="px-4 py-3">
                <p class="text-sm">Signed in as</p>
                <p class="text-sm font-medium text-gray-900 truncate">
                  {{ props.user?.email }}
                </p>
              </div>
              
              <div class="py-1">
                <MenuItem v-slot="{ active }">
                  <button
                    class="group flex w-full items-center px-4 py-2 text-sm"
                    :class="active ? 'bg-gray-100 text-gray-900' : 'text-gray-700'"
                    @click="handleProfileClick"
                  >
                    <UserIcon class="mr-3 h-4 w-4" />
                    Your Profile
                  </button>
                </MenuItem>
                <MenuItem v-slot="{ active }">
                  <button
                    class="group flex w-full items-center px-4 py-2 text-sm"
                    :class="active ? 'bg-gray-100 text-gray-900' : 'text-gray-700'"
                    @click="handleSettingsClick"
                  >
                    <CogIcon class="mr-3 h-4 w-4" />
                    Settings
                  </button>
                </MenuItem>
              </div>
              
              <div class="py-1">
                <MenuItem v-slot="{ active }">
                  <button
                    class="group flex w-full items-center px-4 py-2 text-sm"
                    :class="active ? 'bg-gray-100 text-gray-900' : 'text-gray-700'"
                    @click="$emit('logout')"
                  >
                    <ArrowRightOnRectangleIcon class="mr-3 h-4 w-4" />
                    Sign out
                  </button>
                </MenuItem>
              </div>
            </MenuItems>
          </Transition>
        </Menu>
      </div>
    </div>
  </header>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue'
import {
  Bars3Icon,
  MagnifyingGlassIcon,
  BellIcon,
  ChevronDownIcon,
  UserIcon,
  CogIcon,
  ArrowRightOnRectangleIcon,
} from '@heroicons/vue/24/outline'
import type { User, Notification } from '@/types/api.types'

interface Props {
  user: User | null
  sidebarCollapsed: boolean
}

interface Emits {
  (e: 'toggle-sidebar'): void
  (e: 'logout'): void
}

const props = defineProps<Props>()
defineEmits<Emits>()

// State
const showNotifications = ref(false)
const searchQuery = ref('')
const notifications = ref<Notification[]>([])

// Mock notifications for demo
const mockNotifications: Notification[] = [
  {
    id: '1',
    type: 'info',
    title: 'New patient registered',
    message: 'John Doe has been registered for tomorrow\'s appointment',
  },
  {
    id: '2',
    type: 'warning',
    title: 'Appointment reminder',
    message: 'Dr. Smith has 3 appointments in the next hour',
  },
]

// Computed
const route = useRoute()

const pageTitle = computed(() => {
  if (route.meta?.title) {
    return route.meta.title.replace(' - MediCore Clinic', '')
  }
  return route.name as string || 'Dashboard'
})

const pageSubtitle = computed(() => {
  const now = new Date()
  return now.toLocaleDateString('en-US', { 
    weekday: 'long', 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric' 
  })
})

const userDisplayName = computed(() => {
  if (!props.user) return 'User'
  
  if (props.user.firstName || props.user.lastName) {
    return `${props.user.firstName || ''} ${props.user.lastName || ''}`.trim()
  }
  
  return props.user.username || props.user.email || 'User'
})

const userInitials = computed(() => {
  if (!props.user) return 'U'
  
  const name = userDisplayName.value
  const names = name.split(' ')
  
  if (names.length >= 2) {
    return (names[0][0] + names[names.length - 1][0]).toUpperCase()
  }
  
  return name.substring(0, 2).toUpperCase()
})

const userRole = computed(() => {
  if (!props.user) return ''
  
  const roleNames = {
    admin: 'Administrator',
    doctor: 'Doctor',
    staff: 'Staff Member',
    receptionist: 'Receptionist',
  }
  
  return roleNames[props.user.role] || props.user.role
})

const unreadNotifications = computed(() => notifications.value.length)

// Methods
const handleSearch = () => {
  if (searchQuery.value.trim()) {
    console.log('Searching for:', searchQuery.value)
    // TODO: Implement search functionality
  }
}

const handleNotificationClick = (notification: Notification) => {
  console.log('Notification clicked:', notification)
  // TODO: Handle notification click
  showNotifications.value = false
}

const handleProfileClick = () => {
  console.log('Profile clicked')
  // TODO: Navigate to profile page
}

const handleSettingsClick = () => {
  console.log('Settings clicked')
  // TODO: Navigate to settings page
}

// Click outside handler for notifications dropdown
const handleClickOutside = (event: Event) => {
  const target = event.target as Element
  if (!target.closest('.notifications-container')) {
    showNotifications.value = false
  }
}

// Lifecycle
onMounted(() => {
  notifications.value = mockNotifications
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
.app-header {
  /* Ensure header stays above content */
  z-index: 40;
}

/* Dropdown animations */
.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 0.2s ease;
  transform-origin: top right;
}

.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: scale(0.95) translateY(-10px);
}

/* Mobile responsive adjustments */
@media (max-width: 768px) {
  .app-header {
    @apply px-4;
  }
}
</style>
