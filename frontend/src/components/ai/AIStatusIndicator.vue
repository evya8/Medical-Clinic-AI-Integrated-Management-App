<template>
  <div class="ai-status-indicator flex items-center space-x-2">
    <div 
      :class="[
        'w-2 h-2 rounded-full transition-all duration-300',
        statusClasses[status]
      ]"
    ></div>
    <span :class="['text-sm font-medium', textClasses[status]]">
      {{ statusText }}
    </span>
    <div v-if="showDetails" class="text-xs text-gray-500">
      {{ details }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  status: 'online' | 'processing' | 'offline' | 'error'
  modelName?: string
  responseTime?: number
  showDetails?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  showDetails: false,
})

const statusClasses = {
  online: 'bg-green-400',
  processing: 'bg-blue-400 animate-pulse',
  offline: 'bg-gray-400',
  error: 'bg-red-400 animate-pulse'
}

const textClasses = {
  online: 'text-green-700',
  processing: 'text-blue-700',
  offline: 'text-gray-700',
  error: 'text-red-700'
}

const statusText = computed(() => {
  const baseText = {
    online: 'Online',
    processing: 'Processing',
    offline: 'Offline',
    error: 'Error'
  }
  
  if (props.modelName) {
    return `${props.modelName} ${baseText[props.status]}`
  }
  
  return `AI ${baseText[props.status]}`
})

const details = computed(() => {
  if (props.responseTime && props.status === 'online') {
    return `Response time: ${props.responseTime}ms`
  }
  return ''
})
</script>

<style scoped>
@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
