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
        class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full"
      >
        <!-- Header -->
        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
          <div class="flex items-center justify-between">
            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
              Reschedule Appointment
            </h3>
            <button
              type="button"
              class="bg-white rounded-md text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
              @click="handleClose"
            >
              <XMarkIcon class="h-6 w-6" />
            </button>
          </div>
        </div>

        <!-- Form -->
        <form @submit.prevent="handleSubmit">
          <div class="px-4 pb-4 sm:px-6">
            <div class="space-y-4">
              <!-- Current appointment info -->
              <div class="bg-gray-50 p-4 rounded-lg">
                <h4 class="text-sm font-medium text-gray-900 mb-2">Current Appointment</h4>
                <p class="text-sm text-gray-600">
                  {{ formatDate(currentAppointment?.appointmentDate) }} at {{ currentAppointment?.startTime }}
                </p>
                <p class="text-sm text-gray-500">{{ currentAppointment?.appointmentType }}</p>
              </div>

              <!-- New Date and Time -->
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="medical-form-label">New Date</label>
                  <input
                    v-model="form.date"
                    type="date"
                    required
                    class="medical-input"
                    :min="minDate"
                  />
                </div>
                <div>
                  <label class="medical-form-label">New Time</label>
                  <select v-model="form.time" required class="medical-input">
                    <option value="">Select time</option>
                    <option v-for="slot in availableTimeSlots" :key="slot" :value="slot">
                      {{ slot }}
                    </option>
                  </select>
                </div>
              </div>

              <!-- Reason for reschedule -->
              <div>
                <label class="medical-form-label">Reason for Reschedule</label>
                <select v-model="form.reason" required class="medical-input">
                  <option value="">Select reason</option>
                  <option value="patient_request">Patient Request</option>
                  <option value="doctor_unavailable">Doctor Unavailable</option>
                  <option value="emergency">Emergency</option>
                  <option value="illness">Illness</option>
                  <option value="other">Other</option>
                </select>
              </div>

              <!-- Notes -->
              <div v-if="form.reason === 'other'">
                <label class="medical-form-label">Additional Notes</label>
                <textarea
                  v-model="form.notes"
                  rows="3"
                  class="medical-input"
                  placeholder="Please specify the reason..."
                ></textarea>
              </div>

              <!-- Notification preferences -->
              <div class="space-y-3">
                <h4 class="text-sm font-medium text-gray-900">Notify Patient</h4>
                <div class="space-y-2">
                  <label class="flex items-center">
                    <input
                      v-model="form.notifyEmail"
                      type="checkbox"
                      class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                    />
                    <span class="ml-2 text-sm text-gray-700">Send email notification</span>
                  </label>
                  <label class="flex items-center">
                    <input
                      v-model="form.notifySms"
                      type="checkbox"
                      class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                    />
                    <span class="ml-2 text-sm text-gray-700">Send SMS notification</span>
                  </label>
                </div>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
            <button
              type="submit"
              :disabled="isLoading"
              class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
            >
              <span v-if="!isLoading">Reschedule Appointment</span>
              <span v-else class="flex items-center">
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Rescheduling...
              </span>
            </button>
            <button
              type="button"
              class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
              @click="handleClose"
            >
              Cancel
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { format, addDays } from 'date-fns'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import type { Appointment } from '@/types/api.types'

interface Props {
  isOpen: boolean
  appointment: Appointment | null
}

interface Emits {
  (e: 'close'): void
  (e: 'rescheduled', appointment: Appointment): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// State
const isLoading = ref(false)
const form = ref({
  date: '',
  time: '',
  reason: '',
  notes: '',
  notifyEmail: true,
  notifySms: false
})

// Computed
const currentAppointment = computed(() => props.appointment)

const minDate = computed(() => {
  return format(addDays(new Date(), 1), 'yyyy-MM-dd')
})

const availableTimeSlots = computed(() => {
  // Mock time slots - in real app, this would come from API
  return [
    '08:00', '08:30', '09:00', '09:30', '10:00', '10:30',
    '11:00', '11:30', '13:00', '13:30', '14:00', '14:30',
    '15:00', '15:30', '16:00', '16:30', '17:00'
  ]
})

// Watch for appointment changes
watch(
  () => props.appointment,
  (appointment) => {
    if (appointment) {
      // Reset form when appointment changes
      form.value = {
        date: '',
        time: '',
        reason: '',
        notes: '',
        notifyEmail: true,
        notifySms: false
      }
    }
  },
  { immediate: true }
)

// Methods
const formatDate = (dateString?: string) => {
  if (!dateString) return 'Unknown date'
  try {
    return format(new Date(dateString), 'MMMM dd, yyyy')
  } catch {
    return 'Invalid date'
  }
}

const handleClose = () => {
  if (!isLoading.value) {
    emit('close')
  }
}

const handleSubmit = async () => {
  if (!currentAppointment.value) return

  isLoading.value = true

  try {
    // Mock API call - replace with actual API call
    await new Promise(resolve => setTimeout(resolve, 1500))

    const rescheduledAppointment: Appointment = {
      ...currentAppointment.value,
      appointmentDate: `${form.value.date}T${form.value.time}:00.000Z`,
      startTime: form.value.time,
      notes: form.value.notes || currentAppointment.value.notes,
      updatedAt: new Date().toISOString()
    }

    emit('rescheduled', rescheduledAppointment)
    emit('close')
  } catch (error) {
    console.error('Failed to reschedule appointment:', error)
  } finally {
    isLoading.value = false
  }
}
</script>
