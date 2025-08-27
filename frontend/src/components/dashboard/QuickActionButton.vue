<template>
  <button
    class="quick-action-button flex flex-col items-center justify-center p-4 bg-white border border-gray-200 rounded-lg hover:border-primary-300 hover:bg-primary-50 transition-all duration-200 group"
    @click="$emit('click')"
  >
    <!-- Icon -->
    <div class="flex items-center justify-center w-10 h-10 bg-gray-100 rounded-lg mb-3 group-hover:bg-primary-100 transition-colors duration-200">
      <component
        :is="iconComponent"
        class="w-5 h-5 text-gray-600 group-hover:text-primary-600 transition-colors duration-200"
      />
    </div>
    
    <!-- Label -->
    <span class="text-sm font-medium text-gray-700 group-hover:text-primary-700 transition-colors duration-200 text-center">
      {{ label }}
    </span>
  </button>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import {
  UserPlusIcon,
  CalendarIcon,
  ClipboardDocumentListIcon,
  ChartBarIcon,
  HeartIcon,
  CogIcon,
  BellIcon,
  DocumentTextIcon,
  PlusIcon,
} from '@heroicons/vue/24/outline'

interface Props {
  icon: string
  label: string
}

interface Emits {
  (e: 'click'): void
}

const props = defineProps<Props>()
defineEmits<Emits>()

// Icon mapping
const iconMap = {
  UserPlusIcon,
  CalendarIcon,
  CalendarPlusIcon: CalendarIcon, // Alias for backward compatibility
  ClipboardDocumentListIcon,
  ChartBarIcon,
  HeartIcon,
  CogIcon,
  BellIcon,
  DocumentTextIcon,
  PlusIcon,
}

// Computed
const iconComponent = computed(() => {
  return iconMap[props.icon as keyof typeof iconMap] || ChartBarIcon
})
</script>

<style lang="postcss" scoped>
.quick-action-button {
  @apply focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2;
  min-height: 4.5rem;
}

.quick-action-button:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.quick-action-button:active {
  transform: translateY(0);
}

/* Mobile responsive adjustments */
@media (max-width: 640px) {
  .quick-action-button {
    @apply p-3;
    min-height: 4rem;
  }
  
  .quick-action-button .w-10.h-10 {
    @apply w-8 h-8 mb-2;
  }
  
  .quick-action-button .w-5.h-5 {
    @apply w-4 h-4;
  }
  
  .quick-action-button span {
    @apply text-xs;
  }
}

/* Accessibility improvements */
.quick-action-button:focus-visible {
  @apply ring-2 ring-primary-500 ring-offset-2;
}

/* Subtle animation on load */
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.quick-action-button {
  animation: fadeInUp 0.3s ease-out;
}

/* Stagger animation delays for multiple buttons */
.quick-action-button:nth-child(1) { animation-delay: 0ms; }
.quick-action-button:nth-child(2) { animation-delay: 50ms; }
.quick-action-button:nth-child(3) { animation-delay: 100ms; }
.quick-action-button:nth-child(4) { animation-delay: 150ms; }
</style>
