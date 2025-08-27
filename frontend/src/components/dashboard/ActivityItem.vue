<template>
  <div class="activity-item flex items-start space-x-3 py-3 first:pt-0 last:pb-0">
    <!-- Activity Icon -->
    <div class="flex-shrink-0">
      <div class="w-8 h-8 rounded-full flex items-center justify-center" :class="iconBackgroundClass">
        <component :is="iconComponent" class="w-4 h-4" :class="iconColorClass" />
      </div>
    </div>

    <!-- Activity Content -->
    <div class="flex-1 min-w-0">
      <div class="text-sm text-gray-900">
        <span>{{ activity.title }}</span>
        <span v-if="activity.user" class="font-medium text-gray-700 ml-1">{{ activity.user.name }}</span>
      </div>
      <div v-if="activity.description" class="text-xs text-gray-600 mt-0.5">
        {{ activity.description }}
      </div>
      <div class="text-xs text-gray-500 mt-1">
        {{ relativeTime }}
      </div>
    </div>

    <!-- Time -->
    <div class="flex-shrink-0 text-xs text-gray-400">
      {{ formattedTime }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { formatDistanceToNow, format, parseISO } from 'date-fns'
import {
  CalendarIcon,
  UserIcon,
  CogIcon,
  ClipboardDocumentListIcon,
  BellIcon,
} from '@heroicons/vue/24/outline'
import type { ActivityItemData } from '@/types/api.types'

interface Props {
  activity: ActivityItemData
}

const props = defineProps<Props>()

// Icon mapping based on activity type
const iconMap = {
  appointment: CalendarIcon,
  patient: UserIcon,
  system: CogIcon,
  alert: BellIcon,
}

// Computed
const iconComponent = computed(() => {
  return iconMap[props.activity.type] || ClipboardDocumentListIcon
})

const iconBackgroundClass = computed(() => {
  const colorMap = {
    appointment: 'bg-blue-100',
    patient: 'bg-green-100',
    system: 'bg-gray-100',
    alert: 'bg-yellow-100',
  }
  return colorMap[props.activity.type] || 'bg-gray-100'
})

const iconColorClass = computed(() => {
  const colorMap = {
    appointment: 'text-blue-600',
    patient: 'text-green-600',
    system: 'text-gray-600',
    alert: 'text-yellow-600',
  }
  return colorMap[props.activity.type] || 'text-gray-600'
})

const relativeTime = computed(() => {
  try {
    const date = parseISO(props.activity.timestamp)
    return formatDistanceToNow(date, { addSuffix: true })
  } catch {
    return 'Unknown time'
  }
})

const formattedTime = computed(() => {
  try {
    const date = parseISO(props.activity.timestamp)
    return format(date, 'HH:mm')
  } catch {
    return '--:--'
  }
})
</script>

<style lang="postcss" scoped>
.activity-item {
  @apply transition-colors duration-200;
}

.activity-item:hover {
  @apply bg-gray-50 -mx-3 px-3 rounded-lg;
}

/* Subtle fade-in animation */
@keyframes fadeInLeft {
  from {
    opacity: 0;
    transform: translateX(-10px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

.activity-item {
  animation: fadeInLeft 0.3s ease-out;
}

/* Stagger animation for multiple items */
.activity-item:nth-child(1) { animation-delay: 0ms; }
.activity-item:nth-child(2) { animation-delay: 100ms; }
.activity-item:nth-child(3) { animation-delay: 200ms; }
.activity-item:nth-child(4) { animation-delay: 300ms; }
.activity-item:nth-child(5) { animation-delay: 400ms; }
.activity-item:nth-child(6) { animation-delay: 500ms; }

/* Mobile responsive adjustments */
@media (max-width: 640px) {
  .activity-item {
    @apply py-2;
  }
  
  .activity-item .w-8.h-8 {
    @apply w-6 h-6;
  }
  
  .activity-item .w-4.h-4 {
    @apply w-3 h-3;
  }
}

/* Accessibility improvements */
.activity-item:focus-within {
  @apply bg-gray-50 -mx-3 px-3 rounded-lg;
}

/* Add subtle border for recent activities */
.activity-item.recent {
  @apply border-l-2 border-blue-200 pl-2;
}
</style>
