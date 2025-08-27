<template>
  <div class="modal-overlay fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" @click.self="$emit('close')">
    <div class="modal-content bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-hidden">
      <!-- Modal Header -->
      <div class="modal-header flex items-center justify-between p-6 border-b border-gray-200">
        <div>
          <h2 class="text-xl font-semibold text-gray-900">Appointment Details</h2>
          <p class="text-sm text-gray-600 mt-1">
            {{ formatDate(appointment.appointmentDate) }} at {{ formatTime(appointment.startTime) }}
          </p>
        </div>
        <button
          @click="$emit('close')"
          class="p-2 text-gray-400 hover:text-gray-600 rounded-full hover:bg-gray-100"
        >
          <XMarkIcon class="w-6 h-6" />
        </button>
      </div>

      <!-- Modal Body -->
      <div class="modal-body overflow-y-auto flex-1 p-6">
        <div class="space-y-8">
          <!-- Appointment Status -->
          <div class="status-section">
            <div class="flex items-center justify-between">
              <h3 class="text-lg font-semibold text-gray-900">Status</h3>
              <span 
                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
                :class="getStatusClasses(appointment.status)"
              >
                <span class="w-2 h-2 rounded-full mr-2" :class="getStatusDotClass(appointment.status)"></span>
                {{ getStatusText(appointment.status) }}
              </span>
            </div>
            <div class="mt-4 grid grid-cols-2 gap-4">
              <div>
                <label class="text-sm font-medium text-gray-500 uppercase tracking-wide">Type</label>
                <p class="mt-1 text-sm text-gray-900 capitalize">{{ appointment.appointmentType }}</p>
              </div>
              <div>
                <label class="text-sm font-medium text-gray-500 uppercase tracking-wide">Priority</label>
                <p class="mt-1 text-sm text-gray-900">
                  <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium capitalize"
                        :class="getPriorityClasses(appointment.priority)">
                    {{ appointment.priority }} priority
                  </span>
                </p>
              </div>
            </div>
          </div>

          <!-- Patient Information -->
          <div class="patient-section">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Patient Information</h3>
            <div class="medical-card p-4 bg-gray-50">
              <div class="flex items-center mb-4">
                <div class="w-12 h-12 bg-primary-100 rounded-full flex items-center justify-center mr-4">
                  <span class="text-lg font-medium text-primary-700">
                    {{ getPatientInitials() }}
                  </span>
                </div>
                <div>
                  <h4 class="text-lg font-semibold text-gray-900">{{ getPatientName() }}</h4>
                  <p class="text-sm text-gray-600">Patient ID: #{{ appointment.patientId.toString().padStart(4, '0') }}</p>
                </div>
              </div>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div v-if="appointment.patient?.email" class="flex items-center text-sm text-gray-600">
                  <EnvelopeIcon class="w-4 h-4 mr-3 text-gray-400" />
                  <a :href="`mailto:${appointment.patient.email}`" class="text-primary-600 hover:text-primary-700">
                    {{ appointment.patient.email }}
                  </a>
                </div>
                
                <div v-if="appointment.patient?.phone" class="flex items-center text-sm text-gray-600">
                  <PhoneIcon class="w-4 h-4 mr-3 text-gray-400" />
                  <a :href="`tel:${appointment.patient.phone}`" class="text-primary-600 hover:text-primary-700">
                    {{ appointment.patient.phone }}
                  </a>
                </div>
                
                <div v-if="appointment.patient?.dateOfBirth" class="flex items-center text-sm text-gray-600">
                  <CalendarIcon class="w-4 h-4 mr-3 text-gray-400" />
                  <span>{{ formatAge(appointment.patient.dateOfBirth) }}</span>
                </div>
                
                <div class="flex items-center text-sm text-gray-600">
                  <ClockIcon class="w-4 h-4 mr-3 text-gray-400" />
                  <span>{{ formatDuration() }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Doctor Information -->
          <div class="doctor-section" v-if="appointment.doctor">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Doctor</h3>
            <div class="medical-card p-4 bg-blue-50">
              <div class="flex items-center">
                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                  <UserIcon class="w-5 h-5 text-blue-600" />
                </div>
                <div>
                  <p class="font-semibold text-gray-900">{{ appointment.doctor }}</p>
                  <p class="text-sm text-gray-600">Attending physician</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Appointment Details -->
          <div class="details-section">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Appointment Details</h3>
            <div class="space-y-4">
              <!-- Notes -->
              <div v-if="appointment.notes">
                <label class="text-sm font-medium text-gray-500 uppercase tracking-wide">Notes</label>
                <div class="mt-2 p-3 bg-gray-50 rounded-lg">
                  <p class="text-sm text-gray-700">{{ appointment.notes }}</p>
                </div>
              </div>

              <!-- Diagnosis -->
              <div v-if="appointment.diagnosis">
                <label class="text-sm font-medium text-gray-500 uppercase tracking-wide">Diagnosis</label>
                <div class="mt-2 p-3 bg-green-50 rounded-lg border-l-4 border-green-400">
                  <p class="text-sm text-gray-700">{{ appointment.diagnosis }}</p>
                </div>
              </div>

              <!-- Treatment -->
              <div v-if="appointment.treatmentNotes">
                <label class="text-sm font-medium text-gray-500 uppercase tracking-wide">Treatment Notes</label>
                <div class="mt-2 p-3 bg-blue-50 rounded-lg border-l-4 border-blue-400">
                  <p class="text-sm text-gray-700">{{ appointment.treatmentNotes }}</p>
                </div>
              </div>

              <!-- Follow-up -->
              <div v-if="appointment.followUpRequired" class="follow-up-section bg-amber-50 p-4 rounded-lg border border-amber-200">
                <div class="flex items-center mb-2">
                  <ClockIcon class="w-5 h-5 text-amber-600 mr-2" />
                  <span class="font-medium text-amber-800">Follow-up Required</span>
                </div>
                <p v-if="appointment.followUpDate" class="text-sm text-amber-700">
                  Scheduled for {{ formatDate(appointment.followUpDate) }}
                </p>
                <p v-else class="text-sm text-amber-700">
                  Follow-up appointment needs to be scheduled
                </p>
              </div>
            </div>
          </div>

          <!-- Appointment History -->
          <div class="history-section">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Timeline</h3>
            <div class="space-y-3">
              <div class="timeline-item flex items-center text-sm">
                <div class="w-2 h-2 bg-blue-400 rounded-full mr-3"></div>
                <span class="text-gray-600">Created on {{ formatDate(appointment.createdAt) }}</span>
              </div>
              
              <div v-if="appointment.updatedAt !== appointment.createdAt" class="timeline-item flex items-center text-sm">
                <div class="w-2 h-2 bg-green-400 rounded-full mr-3"></div>
                <span class="text-gray-600">Last updated {{ formatDate(appointment.updatedAt) }}</span>
              </div>
              
              <div class="timeline-item flex items-center text-sm">
                <div class="w-2 h-2 bg-gray-400 rounded-full mr-3"></div>
                <span class="text-gray-600">Status: {{ getStatusText(appointment.status) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="modal-footer flex items-center justify-between p-6 border-t border-gray-200 bg-gray-50">
        <div class="flex items-center space-x-3">
          <RouterLink 
            :to="`/patients/${appointment.patientId}`"
            class="medical-button-outline text-sm"
            @click="$emit('close')"
          >
            View Patient Profile
          </RouterLink>
        </div>
        
        <div class="flex items-center space-x-3">
          <button
            v-if="canEdit"
            @click="$emit('edit')"
            class="medical-button-secondary flex items-center"
          >
            <PencilIcon class="w-4 h-4 mr-2" />
            Edit
          </button>
          
          <button
            v-if="canComplete"
            @click="$emit('completed', appointment)"
            class="medical-button-success flex items-center"
          >
            <CheckIcon class="w-4 h-4 mr-2" />
            Mark Complete
          </button>
          
          <button
            v-if="canCancel"
            @click="$emit('cancelled', appointment)"
            class="medical-button-danger flex items-center"
          >
            <XMarkIcon class="w-4 h-4 mr-2" />
            Cancel
          </button>
          
          <button
            @click="$emit('close')"
            class="medical-button-outline"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { format, differenceInYears, differenceInMinutes } from 'date-fns'
import { RouterLink } from 'vue-router'
import {
  XMarkIcon,
  EnvelopeIcon,
  PhoneIcon,
  CalendarIcon,
  ClockIcon,
  UserIcon,
  PencilIcon,
  CheckIcon,
} from '@heroicons/vue/24/outline'
import type { Appointment } from '@/types/api.types'

interface Props {
  appointment: Appointment
}

interface Emits {
  (e: 'close'): void
  (e: 'updated', appointment: Appointment): void
  (e: 'cancelled', appointment: Appointment): void
  (e: 'completed', appointment: Appointment): void
  (e: 'edit'): void
}

const props = defineProps<Props>()
defineEmits<Emits>()

// Computed
const canEdit = computed(() => {
  return ['scheduled', 'confirmed'].includes(props.appointment.status)
})

const canComplete = computed(() => {
  return ['scheduled', 'confirmed'].includes(props.appointment.status)
})

const canCancel = computed(() => {
  return ['scheduled', 'confirmed'].includes(props.appointment.status)
})

// Methods
const formatDate = (date: string) => {
  try {
    return format(new Date(date), 'EEEE, MMMM d, yyyy')
  } catch {
    return date
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

const formatAge = (dateOfBirth: string) => {
  try {
    const age = differenceInYears(new Date(), new Date(dateOfBirth))
    return `${age} years old`
  } catch {
    return 'Age unknown'
  }
}

const formatDuration = () => {
  try {
    const [startHours, startMinutes] = props.appointment.startTime.split(':').map(Number)
    const [endHours, endMinutes] = props.appointment.endTime.split(':').map(Number)
    
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
      return `${duration} minutes`
    }
  } catch {
    return '30 minutes'
  }
}

const getPatientName = () => {
  if (!props.appointment.patient) return 'Unknown Patient'
  return `${props.appointment.patient.firstName || ''} ${props.appointment.patient.lastName || ''}`.trim()
}

const getPatientInitials = () => {
  if (!props.appointment.patient) return 'UP'
  const firstName = props.appointment.patient.firstName || ''
  const lastName = props.appointment.patient.lastName || ''
  return `${firstName.charAt(0)}${lastName.charAt(0)}`.toUpperCase()
}

const getStatusClasses = (status: string) => {
  const classMap: Record<string, string> = {
    'scheduled': 'bg-blue-100 text-blue-800',
    'confirmed': 'bg-green-100 text-green-800',
    'completed': 'bg-gray-100 text-gray-800',
    'cancelled': 'bg-red-100 text-red-800',
    'no-show': 'bg-yellow-100 text-yellow-800'
  }
  return classMap[status] || classMap.scheduled
}

const getStatusDotClass = (status: string) => {
  const classMap: Record<string, string> = {
    'scheduled': 'bg-blue-400',
    'confirmed': 'bg-green-400',
    'completed': 'bg-gray-400',
    'cancelled': 'bg-red-400',
    'no-show': 'bg-yellow-400'
  }
  return classMap[status] || classMap.scheduled
}

const getStatusText = (status: string) => {
  const textMap: Record<string, string> = {
    'scheduled': 'Scheduled',
    'confirmed': 'Confirmed',
    'completed': 'Completed',
    'cancelled': 'Cancelled',
    'no-show': 'No Show'
  }
  return textMap[status] || 'Unknown'
}

const getPriorityClasses = (priority: string) => {
  const classMap: Record<string, string> = {
    'low': 'bg-gray-100 text-gray-800',
    'normal': 'bg-blue-100 text-blue-800',
    'high': 'bg-orange-100 text-orange-800',
    'urgent': 'bg-red-100 text-red-800'
  }
  return classMap[priority] || classMap.normal
}
</script>

<style lang="postcss" scoped>
.modal-overlay {
  animation: fadeIn 0.3s ease-out;
}

.modal-content {
  animation: slideUp 0.3s ease-out;
  display: flex;
  flex-direction: column;
}

.modal-body {
  flex: 1;
  overflow-y: auto;
}

.timeline-item {
  @apply relative;
}

.timeline-item:not(:last-child)::after {
  content: '';
  position: absolute;
  left: 4px;
  top: 20px;
  width: 1px;
  height: 12px;
  background-color: theme('colors.gray.300');
}

.follow-up-section {
  @apply relative;
}

.follow-up-section::before {
  content: '';
  position: absolute;
  left: -4px;
  top: 0;
  bottom: 0;
  width: 4px;
  background-color: theme('colors.amber.400');
  border-radius: 2px;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(20px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .modal-content {
    @apply max-w-full mx-4 max-h-[95vh];
  }
  
  .modal-footer {
    @apply flex-col space-y-3;
  }
  
  .modal-footer div {
    @apply justify-center;
  }
  
  .grid.md\:grid-cols-2 {
    @apply grid-cols-1;
  }
}
</style>
