<template>
  <RouterLink
    :to="to"
    class="sidebar-item group flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors duration-200"
    :class="itemClasses"
    active-class="sidebar-item-active"
  >
    <!-- Icon -->
    <component
      :is="icon"
      class="flex-shrink-0 w-5 h-5 transition-colors duration-200"
      :class="iconClasses"
    />
    
    <!-- Label -->
    <Transition name="fade">
      <span v-show="!collapsed" class="ml-3 truncate">
        {{ label }}
      </span>
    </Transition>
    
    <!-- Badge/Notification -->
    <Transition name="fade">
      <span
        v-if="!collapsed && (badge || notifications)"
        class="ml-auto inline-flex items-center justify-center"
      >
        <span
          v-if="notifications"
          class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-red-500 rounded-full"
        >
          {{ notifications > 99 ? '99+' : notifications }}
        </span>
        <span
          v-else-if="badge"
          class="inline-flex items-center justify-center px-2 py-1 text-xs font-medium rounded-full"
          :class="badgeClasses"
        >
          {{ badge }}
        </span>
      </span>
    </Transition>
    
    <!-- Collapsed state tooltip -->
    <div
      v-if="collapsed"
      class="sidebar-tooltip absolute left-full ml-2 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50 whitespace-nowrap"
    >
      {{ label }}
    </div>
  </RouterLink>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink, useRoute } from 'vue-router'

interface Props {
  to: string
  icon: any // Component
  label: string
  collapsed?: boolean
  badge?: string | number
  badgeColor?: 'blue' | 'green' | 'yellow' | 'red' | 'purple' | 'gray'
  notifications?: number
}

const props = withDefaults(defineProps<Props>(), {
  collapsed: false,
  badgeColor: 'blue',
})

const route = useRoute()

// Computed classes
const isActive = computed(() => route.path.startsWith(props.to))

const itemClasses = computed(() => [
  {
    'text-gray-900 bg-gray-100': isActive.value,
    'text-gray-600 hover:text-gray-900 hover:bg-gray-50': !isActive.value,
  },
  props.collapsed ? 'justify-center relative' : '',
])

const iconClasses = computed(() => [
  {
    'text-gray-500': isActive.value,
    'text-gray-400 group-hover:text-gray-500': !isActive.value,
  },
])

const badgeClasses = computed(() => {
  const colorClasses = {
    blue: 'text-blue-800 bg-blue-100',
    green: 'text-green-800 bg-green-100',
    yellow: 'text-yellow-800 bg-yellow-100',
    red: 'text-red-800 bg-red-100',
    purple: 'text-purple-800 bg-purple-100',
    gray: 'text-gray-800 bg-gray-100',
  }
  
  return colorClasses[props.badgeColor] || colorClasses.blue
})
</script>

<style scoped>
.sidebar-item {
  @apply relative;
}

.sidebar-item-active {
  @apply text-primary-700 bg-primary-50;
}

.sidebar-item-active .icon {
  @apply text-primary-500;
}

.sidebar-tooltip {
  /* Position tooltip properly */
  top: 50%;
  transform: translateY(-50%);
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

/* Ensure tooltip appears above other elements */
.sidebar-tooltip {
  z-index: 60;
}
</style>
