<template>
  <div
    class="appointment-card group flex items-center p-4 bg-gray-50 rounded-lg cursor-pointer transition-all duration-200 hover:bg-gray-100 hover:shadow-sm border-l-4"
    :class="statusBorderClass"
    @click="$emit('click', appointment)"
  >
    <!-- Time -->
    <div class="flex-shrink-0 text-center mr-4">
      <div class="text-lg font-semibold text-gray-900">
        {{ appointmentTime }}
      </div>
      <div class="text-xs text-gray-500">
        {{ appointmentDuration }}
      </div>
    </div>

    <!-- Patient Info -->
    <div class="flex-1 min-w-0">
      <div class="flex items-center">
        <!-- Patient Avatar -->
        <div class="flex-shrink-0 w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center mr-3">
          <span class="text-sm font-medium text-primary-700">
            {{ patientInitials }}
          </span>
        </div>
        
        <!-- Patient Details -->
        <div class="flex-1 min-w-0">
          <h4 class="text-sm font-medium text-gray-900 truncate">
            {{ patientName }}
          </h4>
          <p class="text-sm text-gray-500 truncate">
            {{ appointment.reason || 'General consultation' }}
          </p>
        </div>
      </div>
    </div>

    <!-- Status & Actions -->
    <div class="flex items-center space-x-3">
      <!-- Status Badge -->
      <span
        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
        :class="statusClasses"
      >
        <span class="w-2 h-2 rounded-full mr-1" :class="statusDotClass"></span>
        {{ statusText }}
      </span>

      <!-- Quick Actions -->
      <div class="flex items-center space-x-1 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
        <button
          class="p-1 text-gray-400 hover:text-gray-600 rounded"
          @click.stop="handleEdit"
          title="Edit appointment"
        >
          <PencilIcon class="w-4 h-4" />
        </button>
        <button
          class="p-1 text-gray-400 hover:text-red-600 rounded"
          @click.stop="handleCancel"
          title="Cancel appointment"
        >
          <XMarkIcon class="w-4 h-4" />
        </button>
      </div>

      <!-- Arrow -->
      <ChevronRightIcon class="w-5 h-5 text-gray-400" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { format, parseISO } from 'date-fns'
import {
  PencilIcon,
  XMarkIcon,
  ChevronRightIcon,
} from '@heroicons/vue/24/outline'
import type { Appointment, AppointmentStatus } from '@/types/api.types'

interface Props {
  appointment: Appointment
}

interface Emits {
  (e: 'click', appointment: Appointment): void
  (e: 'edit', appointment: Appointment): void
  (e: 'cancel', appointment: Appointment): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Computed
const patientName = computed(() => {
  const patient = props.appointment.patient
  if (!patient) return 'Unknown Patient'
  return `${patient.firstName} ${patient.lastName}`.trim()
})

const patientInitials = computed(() => {
  const patient = props.appointment.patient
  if (!patient) return 'UP'
  
  const firstName = patient.firstName || ''
  const lastName = patient.lastName || ''
  
  return `${firstName.charAt(0)}${lastName.charAt(0)}`.toUpperCase()
})

const appointmentTime = computed(() => {
  try {
    // If Appointment doesn't have scheduledAt, try to get it from AppointmentFormData
    // You may need to update the Appointment type to include scheduledAt if it's always present
    const date = parseISO(
      (props.appointment as any).scheduledAt ||
      (props.appointment as any).date ||
      ''
    )
    return format(date, 'HH:mm')
  } catch {
    return '00:00'
  }
})

const appointmentDuration = computed(() => {
  // Default to 30min appointments for now
  return '30min'
})

const statusText = computed(() => {
  const statusMap: Record<AppointmentStatus, string> = {
    scheduled: 'Scheduled',
    completed: 'Completed',
    cancelled: 'Cancelled',
    confirmed: 'Confirmed',
    'no-show': 'No Show',
  }
  return statusMap[props.appointment.status] || 'Unknown'
})

const statusClasses = computed(() => {
  const classMap: Record<AppointmentStatus, string> = {
    scheduled: 'text-blue-800 bg-blue-100',
    confirmed: 'text-blue-800 bg-blue-100',
    completed: 'text-green-800 bg-green-100',
    cancelled: 'text-red-800 bg-red-100',
    'no-show': 'text-yellow-800 bg-yellow-100',
  }
  return classMap[props.appointment.status] || 'text-gray-800 bg-gray-100'
})

const statusDotClass = computed(() => {
  const classMap: Record<AppointmentStatus, string> = {
    scheduled: 'bg-blue-400',
    completed: 'bg-green-400',
    cancelled: 'bg-red-400',
    confirmed: 'bg-blue-400',
    'no-show': 'bg-yellow-400',
  }
  return classMap[props.appointment.status] || 'bg-gray-400'
})

const statusBorderClass = computed(() => {
  const classMap: Record<AppointmentStatus, string> = {
    scheduled: 'border-blue-500',
    completed: 'border-green-500',
    cancelled: 'border-red-500',
    confirmed: 'border-blue-500',
    'no-show': 'border-yellow-500',
  }
  return classMap[props.appointment.status] || 'border-gray-500'
})

// Methods
const handleEdit = () => {
  emit('edit', props.appointment)
}

const handleCancel = () => {
  emit('cancel', props.appointment)
}
</script>

<style scoped>
/* Subtle hover effects */
.appointment-card:hover {
  transform: translateY(-1px);
}

/* Show actions on hover */
.appointment-card .opacity-0 {
  transition: opacity 0.2s ease-in-out;
}

.appointment-card:hover .opacity-0 {
  opacity: 1;
}

/* Mobile responsive adjustments */
@media (max-width: 640px) {
  .appointment-card {
    padding: 0.75rem;
  }
  
  /* Hide quick actions on mobile to save space */
  .appointment-card .opacity-0 {
    display: none;
  }
}

/* Accessibility improvements */
.appointment-card:focus-within {
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5);
}
</style>
