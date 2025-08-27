<template>
  <div class="metric-card medical-card p-6">
    <div class="flex items-center">
      <div class="flex-shrink-0">
        <div class="metric-icon w-8 h-8 rounded-lg flex items-center justify-center" :class="iconBackgroundClass">
          <component :is="iconComponent" class="w-5 h-5" :class="iconTextClass" />
        </div>
      </div>
      <div class="ml-5 w-0 flex-1">
        <dl>
          <dt class="text-sm font-medium text-gray-500 truncate">
            {{ title }}
          </dt>
          <dd>
            <div class="text-lg font-medium text-gray-900">
              {{ formattedValue }}
            </div>
          </dd>
        </dl>
      </div>
      <div v-if="showTrend && previous !== undefined" class="flex-shrink-0">
        <div class="flex items-center">
          <component
            :is="trendIcon"
            class="w-5 h-5 mr-1"
            :class="trendColorClass"
          />
          <span class="text-sm font-medium" :class="trendColorClass">
            {{ trendPercentage }}%
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import {
  UsersIcon,
  CalendarIcon,
  BellIcon,
  CpuChipIcon,
  ChartBarIcon,
  HeartIcon,
  ArrowUpIcon,
  ArrowDownIcon,
} from '@heroicons/vue/24/outline'


interface Props {
  title: string
  value: string | number
  previous?: number
  icon: string
  color: 'blue' | 'green' | 'yellow' | 'red' | 'purple' | 'gray'
  showTrend?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  showTrend: true,
})

// Icon mapping
const iconMap = {
  UsersIcon,
  CalendarIcon,
  BellIcon,
  CpuChipIcon,
  ChartBarIcon,
  HeartIcon,
}

// Computed
const iconComponent = computed(() => {
  return iconMap[props.icon as keyof typeof iconMap] || ChartBarIcon
})

const formattedValue = computed(() => {
  if (typeof props.value === 'number') {
    if (props.value >= 1000) {
      return (props.value / 1000).toFixed(1) + 'k'
    }
    return props.value.toString()
  }
  return props.value
})

const iconBackgroundClass = computed(() => {
  const colorClasses = {
    blue: 'bg-blue-100',
    green: 'bg-green-100',
    yellow: 'bg-yellow-100',
    red: 'bg-red-100',
    purple: 'bg-purple-100',
    gray: 'bg-gray-100',
  }
  return colorClasses[props.color]
})

const iconTextClass = computed(() => {
  const colorClasses = {
    blue: 'text-blue-600',
    green: 'text-green-600',
    yellow: 'text-yellow-600',
    red: 'text-red-600',
    purple: 'text-purple-600',
    gray: 'text-gray-600',
  }
  return colorClasses[props.color]
})

const trendPercentage = computed(() => {
  if (props.previous === undefined || typeof props.value !== 'number') {
    return 0
  }
  
  if (props.previous === 0) {
    return props.value > 0 ? 100 : 0
  }
  
  return Math.abs(Math.round(((props.value - props.previous) / props.previous) * 100))
})

const isPositiveTrend = computed(() => {
  if (props.previous === undefined || typeof props.value !== 'number') {
    return true
  }
  return props.value >= props.previous
})

const trendIcon = computed(() => {
  return isPositiveTrend.value ? ArrowUpIcon : ArrowDownIcon
})

const trendColorClass = computed(() => {
  return isPositiveTrend.value ? 'text-green-600' : 'text-red-600'
})
</script>

<style lang="postcss" scoped>
.metric-card {
  @apply transition-all duration-200 hover:shadow-md;
}

.metric-icon {
  @apply transition-colors duration-200;
}

/* Add subtle pulse animation for real-time metrics */
@keyframes pulse-soft {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.8; }
}

.metric-card:hover .metric-icon {
  animation: pulse-soft 2s infinite;
}
</style>
