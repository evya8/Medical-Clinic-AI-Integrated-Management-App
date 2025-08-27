<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 z-50 overflow-y-auto"
    aria-labelledby="modal-title"
    role="dialog"
    aria-modal="true"
  >
    <!-- Background overlay -->
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <div
        class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
        @click="handleClose"
      ></div>

      <!-- Modal panel -->
      <div
        class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full"
      >
        <!-- Header -->
        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
          <div class="flex items-center justify-between">
            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
              All Appointments - {{ formatDate(date) }}
            </h3>
            <button
              type="button"
              class="bg-white rounded-md text-gray-400 hover:text-gray-600"
              @click="handleClose"
            >
              <XMarkIcon class="h-6 w-6" />
            </button>
          </div>
        </div>

        <!-- Content -->
        <div class="px-4 pb-4 sm:px-6">
          <div v-if="appointments.length === 0" class="text-center py-8">
            <CalendarIcon class="mx-auto h-12 w-12 text-gray-400 mb-4" />
            <p class="text-gray-500">No appointments scheduled for this day.</p>
          </div>

          <div v-else class="space-y-3">
            <div
              v-for="appointment in sortedAppointments"
              :key="appointment.id"
              class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 cursor-pointer transition-colors"
              @click="handleAppointmentClick(appointment)"
            >
              <!-- Appointment Header -->
              <div class="flex items-center justify-between mb-2">
                <div class="flex items-center">
                  <div class="w-3 h-3 rounded-full mr-3" :class="getStatusColor(appointment.status)"></div>
                  <h4 class="text-sm font-medium text-gray-900">
                    {{ appointment.patient?.firstName }} {{ appointment.patient?.lastName }}
                  </h4>
                </div>
                <span class="text-sm text-gray-500">{{ appointment.startTime }}</span>
              </div>

              <!-- Appointment Details -->
              <div class="space-y-1">
                <p class="text-sm text-gray-700">{{ appointment.appointmentType }}</p>
                <p v-if="appointment.notes" class="text-xs text-gray-500">{{ appointment.notes }}</p>
              </div>

              <!-- Status Badge -->
              <div class="mt-2 flex items-center justify-between">
                <span 
                  :class="[
                    'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium',
                    getStatusBadgeClass(appointment.status)
                  ]"
                >
                  {{ appointment.status.replace('_', ' ').toUpperCase() }}
                </span>
                
                <div class="flex items-center space-x-2">
                  <button
                    type="button"
                    class="text-xs text-primary-600 hover:text-primary-800"
                    @click.stop="$emit('edit-appointment', appointment)"
                  >
                    Edit
                  </button>
                  <button
                    type="button"
                    class="text-xs text-gray-600 hover:text-gray-800"
                    @click.stop="$emit('view-patient', appointment.patient)"
                  >
                    View Patient
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
          <button
            type="button"
            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 sm:ml-3 sm:w-auto sm:text-sm"
            @click="$emit('add-appointment', date)"
          >
            Add New Appointment
          </button>
          <button
            type="button"
            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
            @click="handleClose"
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
import { format, parseISO } from 'date-fns'
import { XMarkIcon, CalendarIcon } from '@heroicons/vue/24/outline'
import type { Appointment, Patient } from '@/types/api.types'

interface Props {
  isOpen: boolean
  date: string
  appointments: Appointment[]
}

interface Emits {
  (e: 'close'): void
  (e: 'appointment-click', appointment: Appointment): void
  (e: 'edit-appointment', appointment: Appointment): void
  (e: 'view-patient', patient: Patient | undefined): void
  (e: 'add-appointment', date: string): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Computed
const sortedAppointments = computed(() => {
  return [...props.appointments].sort((a, b) => {
    try {
      const timeA = parseISO(a.appointmentDate).getTime()
      const timeB = parseISO(b.appointmentDate).getTime()
      return timeA - timeB
    } catch {
      return 0
    }
  })
})

// Methods
const handleClose = () => {
  emit('close')
}

const handleAppointmentClick = (appointment: Appointment) => {
  emit('appointment-click', appointment)
}

const formatDate = (dateString: string) => {
  try {
    return format(parseISO(dateString), 'EEEE, MMMM dd, yyyy')
  } catch {
    return dateString
  }
}

const getStatusColor = (status: string) => {
  const colorMap = {
    scheduled: 'bg-blue-400',
    confirmed: 'bg-green-400',
    completed: 'bg-gray-400',
    cancelled: 'bg-red-400',
    'no-show': 'bg-orange-400'
  }
  return colorMap[status as keyof typeof colorMap] || 'bg-gray-400'
}

const getStatusBadgeClass = (status: string) => {
  const classMap = {
    scheduled: 'bg-blue-100 text-blue-800',
    confirmed: 'bg-green-100 text-green-800',
    completed: 'bg-gray-100 text-gray-800',
    cancelled: 'bg-red-100 text-red-800',
    'no-show': 'bg-orange-100 text-orange-800'
  }
  return classMap[status as keyof typeof classMap] || 'bg-gray-100 text-gray-800'
}
</script>
