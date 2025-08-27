<template>
  <div class="appointments-tab">
    <!-- Appointments Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h3 class="text-lg font-semibold text-gray-900">Appointments</h3>
        <p class="text-sm text-gray-600">Manage this patient's appointment history and schedule new appointments</p>
      </div>
      <button
        @click="$emit('schedule')"
        class="medical-button-primary flex items-center"
      >
        <CalendarPlusIcon class="w-4 h-4 mr-2" />
        Schedule Appointment
      </button>
    </div>

    <!-- Quick Stats -->
    <div v-if="appointments.length > 0" class="quick-stats grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <div class="stat-card medical-card p-4 text-center">
        <div class="text-2xl font-bold text-blue-600">{{ appointmentStats.total }}</div>
        <div class="text-sm text-gray-600">Total</div>
      </div>
      
      <div class="stat-card medical-card p-4 text-center">
        <div class="text-2xl font-bold text-green-600">{{ appointmentStats.completed }}</div>
        <div class="text-sm text-gray-600">Completed</div>
      </div>
      
      <div class="stat-card medical-card p-4 text-center">
        <div class="text-2xl font-bold text-yellow-600">{{ appointmentStats.upcoming }}</div>
        <div class="text-sm text-gray-600">Upcoming</div>
      </div>
      
      <div class="stat-card medical-card p-4 text-center">
        <div class="text-2xl font-bold text-red-600">{{ appointmentStats.noShow }}</div>
        <div class="text-sm text-gray-600">No-Shows</div>
      </div>
    </div>

    <!-- Filter Controls -->
    <div class="filter-controls medical-card p-4 mb-6">
      <div class="flex flex-col sm:flex-row gap-4">
        <div class="flex-1">
          <label class="medical-form-label">Filter by Status</label>
          <select v-model="selectedStatus" class="medical-input">
            <option value="">All Statuses</option>
            <option value="scheduled">Scheduled</option>
            <option value="confirmed">Confirmed</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
            <option value="no-show">No Show</option>
          </select>
        </div>
        
        <div class="flex-1">
          <label class="medical-form-label">Date Range</label>
          <select v-model="selectedDateRange" class="medical-input">
            <option value="">All Time</option>
            <option value="upcoming">Upcoming</option>
            <option value="past-month">Past Month</option>
            <option value="past-3-months">Past 3 Months</option>
            <option value="past-year">Past Year</option>
          </select>
        </div>
        
        <div class="flex-1">
          <label class="medical-form-label">Sort By</label>
          <select v-model="sortBy" class="medical-input">
            <option value="date-desc">Date (Newest First)</option>
            <option value="date-asc">Date (Oldest First)</option>
            <option value="status">Status</option>
            <option value="doctor">Doctor</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Appointments List -->
    <div v-if="filteredAppointments.length > 0" class="appointments-list space-y-4">
      <div
        v-for="appointment in filteredAppointments"
        :key="appointment.id"
        class="appointment-card medical-card p-4 border-l-4 group hover:shadow-md transition-all duration-200"
        :class="getAppointmentBorderColor(appointment.status)"
      >
        <div class="flex items-start justify-between">
          <div class="flex-1">
            <!-- Appointment Header -->
            <div class="flex items-center space-x-3 mb-3">
              <component
                :is="getAppointmentIcon(appointment.status)"
                class="w-5 h-5 flex-shrink-0"
                :class="getAppointmentIconColor(appointment.status)"
              />
              <div>
                <h4 class="font-semibold text-gray-900">
                  {{ appointment.appointmentType || 'General Consultation' }}
                </h4>
                <div class="flex items-center space-x-4 text-sm text-gray-600">
                  <span>{{ formatAppointmentDateTime(appointment) }}</span>
                  <span v-if="appointment.doctor">• Dr. {{ appointment.doctor }}</span>
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                        :class="getStatusClasses(appointment.status)">
                    {{ getStatusText(appointment.status) }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Appointment Details -->
            <div class="pl-8 space-y-2">
              <!-- Notes -->
              <div v-if="appointment.notes">
                <span class="text-sm font-medium text-gray-500">Notes:</span>
                <p class="text-gray-700">{{ appointment.notes }}</p>
              </div>

              <!-- Diagnosis -->
              <div v-if="appointment.diagnosis">
                <span class="text-sm font-medium text-gray-500">Diagnosis:</span>
                <p class="text-gray-700">{{ appointment.diagnosis }}</p>
              </div>

              <!-- Treatment Notes -->
              <div v-if="appointment.treatmentNotes">
                <span class="text-sm font-medium text-gray-500">Treatment:</span>
                <p class="text-gray-700">{{ appointment.treatmentNotes }}</p>
              </div>

              <!-- Follow-up -->
              <div v-if="appointment.followUpRequired" class="flex items-center text-sm">
                <ClockIcon class="w-4 h-4 text-yellow-500 mr-2" />
                <span class="text-yellow-700">
                  Follow-up required
                  <span v-if="appointment.followUpDate">
                    on {{ formatDate(appointment.followUpDate) }}
                  </span>
                </span>
              </div>
            </div>
          </div>

          <!-- Appointment Actions -->
          <div class="flex items-center space-x-2 opacity-0 group-hover:opacity-100 transition-opacity">
            <button
              v-if="canReschedule(appointment)"
              @click="rescheduleAppointment(appointment)"
              class="p-2 text-gray-400 hover:text-blue-600 rounded"
              title="Reschedule appointment"
            >
              <CalendarIcon class="w-4 h-4" />
            </button>
            
            <button
              @click="viewAppointmentDetails(appointment)"
              class="p-2 text-gray-400 hover:text-green-600 rounded"
              title="View details"
            >
              <EyeIcon class="w-4 h-4" />
            </button>
            
            <button
              v-if="canCancel(appointment)"
              @click="cancelAppointment(appointment)"
              class="p-2 text-gray-400 hover:text-red-600 rounded"
              title="Cancel appointment"
            >
              <XMarkIcon class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="empty-state medical-card p-8 text-center">
      <CalendarIcon class="w-12 h-12 text-gray-300 mx-auto mb-4" />
      <h3 class="text-lg font-medium text-gray-900 mb-2">
        {{ appointments.length === 0 ? 'No Appointments' : 'No Matching Appointments' }}
      </h3>
      <p class="text-gray-600 mb-6">
        {{ appointments.length === 0 
          ? 'This patient hasn\'t had any appointments yet.' 
          : 'Try adjusting your filters to see more appointments.'
        }}
      </p>
      <button
        @click="$emit('schedule')"
        class="medical-button-primary flex items-center mx-auto"
      >
        <CalendarPlusIcon class="w-4 h-4 mr-2" />
        Schedule First Appointment
      </button>
    </div>

    <!-- Appointment Detail Modal -->
    <AppointmentDetailModal
      v-if="showDetailModal && selectedAppointment"
      :appointment="selectedAppointment"
      @close="showDetailModal = false"
      @updated="handleAppointmentUpdated"
    />

    <!-- Reschedule Modal -->
    <RescheduleAppointmentModal
      v-if="showRescheduleModal && selectedAppointment"
      :appointment="selectedAppointment"
      @close="showRescheduleModal = false"
      @rescheduled="handleAppointmentRescheduled"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { format, isFuture, isAfter, subMonths} from 'date-fns'
import {
  CalendarIcon,
  PlusIcon,
  ClockIcon,
  EyeIcon,
  XMarkIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
  XCircleIcon,
} from '@heroicons/vue/24/outline'

// Create CalendarPlusIcon alias for backward compatibility
const CalendarPlusIcon = PlusIcon

import AppointmentDetailModal from '@/components/appointments/AppointmentDetailModal.vue'
import RescheduleAppointmentModal from '@/components/appointments/RescheduleAppointmentModal.vue'
import type { Patient, Appointment } from '@/types/api.types'

interface Props {
  patient: Patient
  appointments: Appointment[]
}

interface Emits {
  (e: 'schedule'): void
  (e: 'update', appointments: Appointment[]): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// State
const selectedStatus = ref('')
const selectedDateRange = ref('')
const sortBy = ref('date-desc')
const showDetailModal = ref(false)
const showRescheduleModal = ref(false)
const selectedAppointment = ref<Appointment | null>(null)

// Computed
const appointmentStats = computed(() => {
  const total = props.appointments.length
  const completed = props.appointments.filter(app => app.status === 'completed').length
  const upcoming = props.appointments.filter(app => 
    ['scheduled', 'confirmed'].includes(app.status) && 
    isFuture(new Date(app.appointmentDate + ' ' + app.startTime))
  ).length
  const noShow = props.appointments.filter(app => app.status === 'no-show').length

  return { total, completed, upcoming, noShow }
})

const filteredAppointments = computed(() => {
  let filtered = [...props.appointments]

  // Filter by status
  if (selectedStatus.value) {
    filtered = filtered.filter(app => app.status === selectedStatus.value)
  }

  // Filter by date range
  if (selectedDateRange.value) {
    const now = new Date()
    const appointmentDate = (app: Appointment) => new Date(app.appointmentDate + ' ' + app.startTime)

    switch (selectedDateRange.value) {
      case 'upcoming':
        filtered = filtered.filter(app => isFuture(appointmentDate(app)))
        break
      case 'past-month':
        filtered = filtered.filter(app => 
          isAfter(appointmentDate(app), subMonths(now, 1)) && !isFuture(appointmentDate(app))
        )
        break
      case 'past-3-months':
        filtered = filtered.filter(app => 
          isAfter(appointmentDate(app), subMonths(now, 3)) && !isFuture(appointmentDate(app))
        )
        break
      case 'past-year':
        filtered = filtered.filter(app => 
          isAfter(appointmentDate(app), subMonths(now, 12)) && !isFuture(appointmentDate(app))
        )
        break
    }
  }

  // Sort appointments
  switch (sortBy.value) {
    case 'date-desc':
      filtered.sort((a, b) => 
        new Date(b.appointmentDate + ' ' + b.startTime).getTime() - 
        new Date(a.appointmentDate + ' ' + a.startTime).getTime()
      )
      break
    case 'date-asc':
      filtered.sort((a, b) => 
        new Date(a.appointmentDate + ' ' + a.startTime).getTime() - 
        new Date(b.appointmentDate + ' ' + b.startTime).getTime()
      )
      break
    case 'status':
      filtered.sort((a, b) => a.status.localeCompare(b.status))
      break
    case 'doctor':
      filtered.sort((a, b) => (a.doctor || '').localeCompare(b.doctor || ''))
      break
  }

  return filtered
})

// Methods
const formatAppointmentDateTime = (appointment: Appointment) => {
  try {
    const date = format(new Date(appointment.appointmentDate), 'MMM dd, yyyy')
    const time = format(new Date(`2000-01-01 ${appointment.startTime}`), 'h:mm a')
    return `${date} at ${time}`
  } catch {
    return 'Invalid date'
  }
}

const formatDate = (date: string) => {
  try {
    return format(new Date(date), 'MMM dd, yyyy')
  } catch {
    return 'Invalid date'
  }
}

const getAppointmentIcon = (status: string) => {
  const iconMap = {
    scheduled: CalendarIcon,
    confirmed: CalendarIcon,
    completed: CheckCircleIcon,
    cancelled: XCircleIcon,
    'no-show': ExclamationTriangleIcon,
  }
  return iconMap[status] || CalendarIcon
}

const getAppointmentIconColor = (status: string) => {
  const colorMap = {
    scheduled: 'text-blue-500',
    confirmed: 'text-green-500',
    completed: 'text-green-600',
    cancelled: 'text-red-500',
    'no-show': 'text-yellow-500',
  }
  return colorMap[status] || 'text-gray-500'
}

const getAppointmentBorderColor = (status: string) => {
  const colorMap = {
    scheduled: 'border-blue-500',
    confirmed: 'border-green-500',
    completed: 'border-green-600',
    cancelled: 'border-red-500',
    'no-show': 'border-yellow-500',
  }
  return colorMap[status] || 'border-gray-500'
}

const getStatusClasses = (status: string) => {
  const classMap = {
    scheduled: 'bg-blue-100 text-blue-800',
    confirmed: 'bg-green-100 text-green-800',
    completed: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
    'no-show': 'bg-yellow-100 text-yellow-800',
  }
  return classMap[status] || 'bg-gray-100 text-gray-800'
}

const getStatusText = (status: string) => {
  const textMap = {
    scheduled: 'Scheduled',
    confirmed: 'Confirmed',
    completed: 'Completed',
    cancelled: 'Cancelled',
    'no-show': 'No Show',
  }
  return textMap[status] || status
}

const canReschedule = (appointment: Appointment) => {
  return ['scheduled', 'confirmed'].includes(appointment.status)
}

const canCancel = (appointment: Appointment) => {
  return ['scheduled', 'confirmed'].includes(appointment.status) && 
         isFuture(new Date(appointment.appointmentDate + ' ' + appointment.startTime))
}

const viewAppointmentDetails = (appointment: Appointment) => {
  selectedAppointment.value = appointment
  showDetailModal.value = true
}

const rescheduleAppointment = (appointment: Appointment) => {
  selectedAppointment.value = appointment
  showRescheduleModal.value = true
}

const cancelAppointment = async (appointment: Appointment) => {
  if (confirm('Are you sure you want to cancel this appointment?')) {
    // In a real app, this would call an API
    const updatedAppointments = props.appointments.map(app =>
      app.id === appointment.id ? { ...app, status: 'cancelled' } : app
    )
    emit('update', updatedAppointments)
  }
}

const handleAppointmentUpdated = (updatedAppointment: Appointment) => {
  const updatedAppointments = props.appointments.map(app =>
    app.id === updatedAppointment.id ? updatedAppointment : app
  )
  emit('update', updatedAppointments)
  showDetailModal.value = false
  selectedAppointment.value = null
}

const handleAppointmentRescheduled = (rescheduledAppointment: Appointment) => {
  const updatedAppointments = props.appointments.map(app =>
    app.id === rescheduledAppointment.id ? rescheduledAppointment : app
  )
  emit('update', updatedAppointments)
  showRescheduleModal.value = false
  selectedAppointment.value = null
}
</script>

<style lang="postcss" scoped>
.appointments-tab {
  @apply space-y-6;
}

.appointment-card {
  @apply transition-all duration-200 hover:shadow-md;
}

.appointment-card:hover {
  transform: translateY(-2px);
}

.stat-card {
  @apply transition-all duration-200 hover:shadow-md;
}

.stat-card:hover {
  transform: translateY(-2px);
}

.empty-state {
  @apply border-2 border-dashed border-gray-200 bg-gray-50;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .quick-stats {
    @apply grid-cols-2;
  }
  
  .filter-controls .flex-row {
    @apply flex-col;
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
  
  .filter-controls {
    @apply hidden;
  }
}
</style>
