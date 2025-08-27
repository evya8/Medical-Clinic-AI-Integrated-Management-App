<template>
  <span 
    :class="[
      'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium transition-all duration-200',
      getBadgeClasses(),
      sizeClasses[size]
    ]"
  >
    <component 
      v-if="showIcon"
      :is="getIcon()"
      :class="['mr-1', getIconSize()]"
    />
    {{ displayText }}
  </span>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import {
  ExclamationTriangleIcon,
  FireIcon,
  ClockIcon,
  InformationCircleIcon,
  CheckCircleIcon,
} from '@heroicons/vue/24/outline'

export type PriorityLevel = 'critical' | 'urgent' | 'high' | 'medium' | 'normal' | 'low'
export type SeverityLevel = 'critical' | 'high' | 'medium' | 'low'
export type StatusLevel = 'active' | 'acknowledged' | 'resolved' | 'dismissed'

interface Props {
  // Priority-based badge
  priority?: PriorityLevel
  // Severity-based badge
  severity?: SeverityLevel
  // Status-based badge
  status?: StatusLevel
  // Custom text override
  text?: string
  // Show icon
  showIcon?: boolean
  // Size variant
  size?: 'xs' | 'sm' | 'md' | 'lg'
  // Pulse animation for urgent items
  animate?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  showIcon: true,
  size: 'sm',
  animate: false,
})

const sizeClasses = {
  xs: 'px-2 py-0.5 text-xs',
  sm: 'px-2.5 py-0.5 text-xs',
  md: 'px-3 py-1 text-sm',
  lg: 'px-4 py-1.5 text-sm',
}

const displayText = computed(() => {
  if (props.text) return props.text
  
  if (props.priority) {
    const priorityMap: Record<PriorityLevel, string> = {
      critical: 'Critical',
      urgent: 'Urgent',
      high: 'High',
      medium: 'Medium', 
      normal: 'Normal',
      low: 'Low'
    }
    return priorityMap[props.priority]
  }
  
  if (props.severity) {
    const severityMap: Record<SeverityLevel, string> = {
      critical: 'Critical',
      high: 'High',
      medium: 'Medium',
      low: 'Low'
    }
    return severityMap[props.severity]
  }
  
  if (props.status) {
    const statusMap: Record<StatusLevel, string> = {
      active: 'Active',
      acknowledged: 'Acknowledged',
      resolved: 'Resolved',
      dismissed: 'Dismissed'
    }
    return statusMap[props.status]
  }
  
  return 'Unknown'
})

const getBadgeClasses = () => {
  const baseClasses = props.animate ? 'animate-pulse' : ''
  
  if (props.priority) {
    const priorityClasses: Record<PriorityLevel, string> = {
      critical: 'bg-red-100 text-red-800 border border-red-200',
      urgent: 'bg-orange-100 text-orange-800 border border-orange-200',
      high: 'bg-yellow-100 text-yellow-800 border border-yellow-200',
      medium: 'bg-blue-100 text-blue-800 border border-blue-200',
      normal: 'bg-green-100 text-green-800 border border-green-200',
      low: 'bg-gray-100 text-gray-800 border border-gray-200'
    }
    return `${priorityClasses[props.priority]} ${baseClasses}`
  }
  
  if (props.severity) {
    const severityClasses: Record<SeverityLevel, string> = {
      critical: 'bg-red-100 text-red-800 border border-red-200',
      high: 'bg-orange-100 text-orange-800 border border-orange-200',
      medium: 'bg-yellow-100 text-yellow-800 border border-yellow-200',
      low: 'bg-green-100 text-green-800 border border-green-200'
    }
    return `${severityClasses[props.severity]} ${baseClasses}`
  }
  
  if (props.status) {
    const statusClasses: Record<StatusLevel, string> = {
      active: 'bg-red-100 text-red-800 border border-red-200',
      acknowledged: 'bg-yellow-100 text-yellow-800 border border-yellow-200',
      resolved: 'bg-green-100 text-green-800 border border-green-200',
      dismissed: 'bg-gray-100 text-gray-800 border border-gray-200'
    }
    return `${statusClasses[props.status]} ${baseClasses}`
  }
  
  return `bg-gray-100 text-gray-800 border border-gray-200 ${baseClasses}`
}

const getIcon = () => {
  if (props.priority) {
    const priorityIcons: Record<PriorityLevel, any> = {
      critical: ExclamationTriangleIcon,
      urgent: FireIcon,
      high: ClockIcon,
      medium: InformationCircleIcon,
      normal: CheckCircleIcon,
      low: InformationCircleIcon
    }
    return priorityIcons[props.priority]
  }
  
  if (props.severity) {
    const severityIcons: Record<SeverityLevel, any> = {
      critical: ExclamationTriangleIcon,
      high: FireIcon,
      medium: ClockIcon,
      low: InformationCircleIcon
    }
    return severityIcons[props.severity]
  }
  
  if (props.status) {
    const statusIcons: Record<StatusLevel, any> = {
      active: ExclamationTriangleIcon,
      acknowledged: ClockIcon,
      resolved: CheckCircleIcon,
      dismissed: InformationCircleIcon
    }
    return statusIcons[props.status]
  }
  
  return InformationCircleIcon
}

const getIconSize = () => {
  const iconSizes = {
    xs: 'w-3 h-3',
    sm: 'w-3 h-3', 
    md: 'w-4 h-4',
    lg: 'w-4 h-4',
  }
  return iconSizes[props.size]
}
</script>

<style scoped>
@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.75;
  }
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
