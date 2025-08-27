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
              Edit Appointment
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
              <!-- Date and Time -->
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="medical-form-label">Date</label>
                  <input
                    v-model="form.date"
                    type="date"
                    required
                    class="medical-input"
                  />
                </div>
                <div>
                  <label class="medical-form-label">Time</label>
                  <input
                    v-model="form.time"
                    type="time"
                    required
                    class="medical-input"
                  />
                </div>
              </div>

              <!-- Reason -->
              <div>
                <label class="medical-form-label">Reason for Visit</label>
                <textarea
                  v-model="form.reason"
                  rows="3"
                  class="medical-input"
                  placeholder="Enter reason for appointment"
                ></textarea>
              </div>

              <!-- Notes -->
              <div>
                <label class="medical-form-label">Notes</label>
                <textarea
                  v-model="form.notes"
                  rows="3"
                  class="medical-input"
                  placeholder="Additional notes (optional)"
                ></textarea>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
            <button
              type="submit"
              :disabled="isLoading"
              class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:text-sm"
            >
              <span v-if="!isLoading">Update Appointment</span>
              <span v-else class="flex items-center">
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Updating...
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
import { ref, watch } from 'vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import type { Appointment } from '@/types/api.types'

interface Props {
  isOpen: boolean
  appointment: Appointment | null
}

interface Emits {
  (e: 'close'): void
  (e: 'updated', appointment: Appointment): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// State
const isLoading = ref(false)
const form = ref({
  date: '',
  time: '',
  reason: '',
  notes: ''
})

// Watch for appointment changes
watch(
  () => props.appointment,
  (appointment) => {
    if (appointment) {
      const appointmentDate = new Date(appointment.appointmentDate)
      form.value = {
        date: appointmentDate.toISOString().split('T')[0],
        time: appointment.startTime || '',
        reason: appointment.appointmentType || '',
        notes: appointment.notes || ''
      }
    }
  },
  { immediate: true }
)

// Methods
const handleClose = () => {
  if (!isLoading.value) {
    emit('close')
  }
}

const handleSubmit = async () => {
  if (!props.appointment) return

  isLoading.value = true

  try {
    // Mock API call - replace with actual API call
    await new Promise(resolve => setTimeout(resolve, 1000))

    const updatedAppointment: Appointment = {
      ...props.appointment,
      appointmentDate: `${form.value.date}T${form.value.time}:00.000Z`,
      startTime: form.value.time,
      appointmentType: form.value.reason,
      notes: form.value.notes,
      updatedAt: new Date().toISOString()
    }

    emit('updated', updatedAppointment)
    emit('close')
  } catch (error) {
    console.error('Failed to update appointment:', error)
  } finally {
    isLoading.value = false
  }
}
</script>
