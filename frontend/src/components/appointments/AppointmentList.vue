<template>
  <div class="appointment-list">
    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
      <span class="ml-3 text-gray-600">Loading appointments...</span>
    </div>

    <!-- Appointment Groups -->
    <div v-else class="space-y-6">
      <div
        v-for="group in appointmentGroups"
        :key="group.date"
        class="appointment-group"
      >
        <!-- Date Header -->
        <div class="date-header sticky top-0 bg-white z-10 pb-3">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
              <CalendarIcon class="w-5 h-5 mr-2 text-gray-500" />
              {{ formatDateHeader(group.date) }}
              <span class="ml-2 text-sm font-normal text-gray-500">
                ({{ group.appointments.length }} {{ group.appointments.length === 1 ? 'appointment' : 'appointments' }})
              </span>
            </h3>
            <div class="text-sm text-gray-500">
              {{ getDayOfWeek(group.date) }}
            </div>
          </div>
          <div class="mt-2 border-b border-gray-200"></div>
        </div>

        <!-- Appointments List -->
        <div class="appointments-list space-y-3">
          <div
            v-for="appointment in group.appointments"
            :key="appointment.id"
            class="appointment-card medical-card p-4 hover:shadow-md transition-all duration-200 cursor-pointer group"
            @click="handleAppointmentClick(appointment)"
          >
            <div class="flex items-start space-x-4">
              <!-- Time and Status -->
              <div class="flex-shrink-0 text-center">
                <div class="text-lg font-semibold text-gray-900">
                  {{ formatTime(appointment.startTime) }}
                </div>
                <div class="text-xs text-gray-500">
                  {{ formatDuration(appointment.startTime, appointment.endTime) }}
                </div>
                <div class="mt-2">
                  <span 
                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                    :class="getStatusClasses(appointment.status)"
                  >
                    <span class="w-1.5 h-1.5 rounded-full mr-1" :class="getStatusDotClass(appointment.status)"></span>
                    {{ getStatusText(appointment.status) }}
                  </span>
                </div>
              </div>

              <!-- Patient Information -->
              <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between">
                  <div class="flex-1">
                    <!-- Patient Name -->
                    <div class="flex items-center mb-2">
                      <div class="flex-shrink-0 w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center mr-3">
                        <span class="text-sm font-medium text-primary-700">
                          {{ getPatientInitials(appointment.patient) }}
                        </span>
                      </div>
                      <div>
                        <h4 class="text-lg font-medium text-gray-900">
                          {{ getPatientName(appointment.patient) }}
                        </h4>
                        <p class="text-sm text-gray-600">
                          {{ appointment.appointmentType || 'General Consultation' }}
                        </p>
                      </div>
                    </div>

                    <!-- Appointment Details -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                      <div class="flex items-center text-gray-600">
                        <UserIcon class="w-4 h-4 mr-2 text-gray-400" />
                        <span>{{ appointment.doctor || 'Not assigned' }}</span>
                      </div>
                      
                      <div class="flex items-center text-gray-600">
                        <FlagIcon class="w-4 h-4 mr-2 text-gray-400" />
                        <span class="capitalize">{{ appointment.priority }} priority</span>
                      </div>
                      
                      <div v-if="appointment.patient?.phone" class="flex items-center text-gray-600">
                        <PhoneIcon class="w-4 h-4 mr-2 text-gray-400" />
                        <span>{{ appointment.patient.phone }}</span>
                      </div>
                      
                      <div v-if="appointment.patient?.email" class="flex items-center text-gray-600">
                        <EnvelopeIcon class="w-4 h-4 mr-2 text-gray-400" />
                        <span>{{ appointment.patient.email }}</span>
                      </div>
                    </div>

                    <!-- Notes -->
                    <div v-if="appointment.notes" class="mt-3 p-3 bg-gray-50 rounded-lg">
                      <p class="text-sm text-gray-700">
                        <span class="font-medium">Notes:</span> {{ appointment.notes }}
                      </p>
                    </div>

                    <!-- Follow-up -->
                    <div v-if="appointment.followUpRequired" class="mt-3 flex items-center text-sm text-amber-600">
                      <ClockIcon class="w-4 h-4 mr-2" />
                      <span>Follow-up required</span>
                      <span v-if="appointment.followUpDate" class="ml-1">
                        on {{ formatDate(appointment.followUpDate) }}
                      </span>
                    </div>
                  </div>

                  <!-- Quick Actions -->
                  <div class="flex items-center space-x-2 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button
                      v-if="canEdit(appointment)"
                      @click.stop="handleEdit(appointment)"
                      class="p-2 text-gray-400 hover:text-blue-600 rounded-full hover:bg-blue-50"
                      title="Edit appointment"
                    >
                      <PencilIcon class="w-4 h-4" />
                    </button>
                    
                    <button
                      v-if="canComplete(appointment)"
                      @click.stop="handleComplete(appointment)"
                      class="p-2 text-gray-400 hover:text-green-600 rounded-full hover:bg-green-50"
                      title="Mark as completed"
                    >
                      <CheckIcon class="w-4 h-4" />
                    </button>
                    
                    <button
                      v-if="canCancel(appointment)"
                      @click.stop="handleCancel(appointment)"
                      class="p-2 text-gray-400 hover:text-red-600 rounded-full hover:bg-red-50"
                      title="Cancel appointment"
                    >
                      <XMarkIcon class="w-4 h-4" />
                    </button>

                    <button
                      @click.stop="handleViewDetails(appointment)"
                      class="p-2 text-gray-400 hover:text-purple-600 rounded-full hover:bg-purple-50"
                      title="View details"
                    >
                      <EyeIcon class="w-4 h-4" />
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="!loading && appointments.length === 0" class="empty-state text-center py-12">
      <CalendarIcon class="w-16 h-16 text-gray-300 mx-auto mb-4" />
      <h3 class="text-lg font-medium text-gray-900 mb-2">No Appointments</h3>
      <p class="text-gray-600">No appointments found for the selected criteria.</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { format, isFuture, differenceInMinutes } from 'date-fns'
import {
  CalendarIcon,
  UserIcon,
  PhoneIcon,
  EnvelopeIcon,
  ClockIcon,
  FlagIcon,
  PencilIcon,
  CheckIcon,
  XMarkIcon,
  EyeIcon,
} from '@heroicons/vue/24/outline'
import type { Appointment, AppointmentStatus, Patient } from '@/types/api.types'

interface Props {
  appointments: Appointment[]
  loading?: boolean
  groupByDate?: boolean
}

interface Emits {
  (e: 'appointment-click', appointment: Appointment): void
  (e: 'appointment-edit', appointment: Appointment): void
  (e: 'appointment-cancel', appointment: Appointment): void
  (e: 'appointment-complete', appointment: Appointment): void
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  groupByDate: true
})

const emit = defineEmits<Emits>()

// Computed
const appointmentGroups = computed(() => {
  if (!props.groupByDate) {
    return [{ date: '', appointments: props.appointments }]
  }

  // Group appointments by date
  const groups = new Map<string, Appointment[]>()
  
  props.appointments.forEach(appointment => {
    const date = appointment.appointmentDate
    if (!groups.has(date)) {
      groups.set(date, [])
    }
    groups.get(date)!.push(appointment)
  })

  // Sort groups by date and sort appointments within each group by time
  return Array.from(groups.entries())
    .map(([date, appointments]) => ({
      date,
      appointments: appointments.sort((a, b) => a.startTime.localeCompare(b.startTime))
    }))
    .sort((a, b) => a.date.localeCompare(b.date))
})

// Methods
const formatDateHeader = (date: string) => {
  try {
    const dateObj = new Date(date)
    const today = new Date()
    const tomorrow = new Date(today)
    tomorrow.setDate(tomorrow.getDate() + 1)

    if (dateObj.toDateString() === today.toDateString()) {
      return 'Today'
    } else if (dateObj.toDateString() === tomorrow.toDateString()) {
      return 'Tomorrow'
    } else {
      return format(dateObj, 'EEEE, MMMM d, yyyy')
    }
  } catch {
    return date
  }
}

const getDayOfWeek = (date: string) => {
  try {
    return format(new Date(date), 'EEEE')
  } catch {
    return ''
  }
}

const formatTime = (time: string) => {
  try {
    const [hours, minutes] = time.split(':')
    const date = new Date()
    date.setHours(parseInt(hours), parseInt(minutes))
    return format(date, 'h:mm a')
  } catch {
    return time
  }
}

const formatDuration = (startTime: string, endTime: string) => {
  try {
    const [startHours, startMinutes] = startTime.split(':').map(Number)
    const [endHours, endMinutes] = endTime.split(':').map(Number)
    
    const start = new Date()
    start.setHours(startHours, startMinutes)
    
    const end = new Date()
    end.setHours(endHours, endMinutes)
    
    const duration = differenceInMinutes(end, start)
    
    if (duration >= 60) {
      const hours = Math.floor(duration / 60)
      const minutes = duration % 60
      return minutes > 0 ? `${hours}h ${minutes}m` : `${hours}h`
    } else {
      return `${duration}m`
    }
  } catch {
    return '30m'
  }
}

const formatDate = (date: string) => {
  try {
    return format(new Date(date), 'MMM dd, yyyy')
  } catch {
    return date
  }
}

const getPatientName = (patient: Patient | undefined) => {
  if (!patient) return 'Unknown Patient'
  return `${patient.firstName || ''} ${patient.lastName || ''}`.trim() || 'Unknown Patient'
}

const getPatientInitials = (patient: Patient | undefined) => {
  if (!patient) return 'UP'
  const firstName = patient.firstName || ''
  const lastName = patient.lastName || ''
  return `${firstName.charAt(0)}${lastName.charAt(0)}`.toUpperCase() || 'UP'
}

const getStatusClasses = (status: AppointmentStatus) => {
  const classMap: Record<AppointmentStatus, string> = {
    'scheduled': 'bg-blue-100 text-blue-800',
    'confirmed': 'bg-green-100 text-green-800',
    'completed': 'bg-gray-100 text-gray-800',
    'cancelled': 'bg-red-100 text-red-800',
    'no-show': 'bg-yellow-100 text-yellow-800'
  }
  return classMap[status] || classMap.scheduled
}

const getStatusDotClass = (status: AppointmentStatus) => {
  const classMap: Record<AppointmentStatus, string> = {
    'scheduled': 'bg-blue-400',
    'confirmed': 'bg-green-400',
    'completed': 'bg-gray-400',
    'cancelled': 'bg-red-400',
    'no-show': 'bg-yellow-400'
  }
  return classMap[status] || classMap.scheduled
}

const getStatusText = (status: AppointmentStatus) => {
  const textMap: Record<AppointmentStatus, string> = {
    'scheduled': 'Scheduled',
    'confirmed': 'Confirmed',
    'completed': 'Completed',
    'cancelled': 'Cancelled',
    'no-show': 'No Show'
  }
  return textMap[status] || 'Unknown'
}

const canEdit = (appointment: Appointment) => {
  return ['scheduled', 'confirmed'].includes(appointment.status)
}

const canComplete = (appointment: Appointment) => {
  return ['scheduled', 'confirmed'].includes(appointment.status) && 
         !isFuture(new Date(appointment.appointmentDate + 'T' + appointment.startTime))
}

const canCancel = (appointment: Appointment) => {
  return ['scheduled', 'confirmed'].includes(appointment.status)
}

// Event handlers
const handleAppointmentClick = (appointment: Appointment) => {
  emit('appointment-click', appointment)
}

const handleEdit = (appointment: Appointment) => {
  emit('appointment-edit', appointment)
}

const handleComplete = (appointment: Appointment) => {
  emit('appointment-complete', appointment)
}

const handleCancel = (appointment: Appointment) => {
  emit('appointment-cancel', appointment)
}

const handleViewDetails = (appointment: Appointment) => {
  emit('appointment-click', appointment)
}
</script>

<style lang="postcss" scoped>
.appointment-list {
  @apply space-y-6;
}

.appointment-group {
  @apply space-y-4;
}

.date-header {
  @apply border-b border-gray-200;
}

.appointment-card {
  @apply border-l-4 transition-all duration-200;
}

.appointment-card:hover {
  transform: translateY(-1px);
}

/* Status-specific left borders */
.appointment-card:has(.bg-blue-100) {
  @apply border-blue-400;
}

.appointment-card:has(.bg-green-100) {
  @apply border-green-400;
}

.appointment-card:has(.bg-gray-100) {
  @apply border-gray-400;
}

.appointment-card:has(.bg-red-100) {
  @apply border-red-400;
}

.appointment-card:has(.bg-yellow-100) {
  @apply border-yellow-400;
}

/* Fallback for browsers that don't support :has() */
@supports not (selector(:has(*))) {
  .appointment-card {
    @apply border-blue-400;
  }
}

.empty-state {
  @apply text-gray-500;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .appointment-card {
    @apply p-3;
  }
  
  .appointment-card .grid.md\:grid-cols-2 {
    @apply grid-cols-1 gap-2;
  }
  
  .appointment-card .group-hover\:opacity-100 {
    @apply opacity-100;
  }
  
  .appointment-card .flex.items-start {
    @apply flex-col space-x-0 space-y-3;
  }
  
  .date-header h3 {
    @apply text-base;
  }
}

/* Print styles */
@media print {
  .appointment-card {
    @apply shadow-none border border-gray-300 break-inside-avoid;
  }
  
  .group-hover\:opacity-100 {
    @apply hidden;
  }
  
  .date-header {
    @apply border-b-2 border-black;
  }
}

/* Loading spinner */
.animate-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

/* Sticky date headers */
.date-header {
  backdrop-filter: blur(8px);
  background: rgba(255, 255, 255, 0.95);
}
</style>
