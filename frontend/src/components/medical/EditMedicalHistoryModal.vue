<template>
  <div
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
              Edit Medical History Entry
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

        <!-- Form -->
        <form @submit.prevent="handleSubmit">
          <div class="px-4 pb-4 sm:px-6">
            <div class="space-y-4">
              <!-- Entry Type -->
              <div>
                <label class="medical-form-label">Entry Type</label>
                <select v-model="form.type" required class="medical-input">
                  <option value="">Select type</option>
                  <option value="visit">Visit/Consultation</option>
                  <option value="diagnosis">Diagnosis</option>
                  <option value="treatment">Treatment</option>
                  <option value="surgery">Surgery/Procedure</option>
                  <option value="emergency">Emergency</option>
                  <option value="test">Test/Lab Result</option>
                </select>
              </div>

              <!-- Date -->
              <div>
                <label class="medical-form-label">Date</label>
                <input
                  v-model="form.date"
                  type="date"
                  required
                  class="medical-input"
                  :max="maxDate"
                />
              </div>

              <!-- Title -->
              <div>
                <label class="medical-form-label">Title</label>
                <input
                  v-model="form.title"
                  type="text"
                  required
                  class="medical-input"
                  placeholder="Brief description of this entry"
                />
              </div>

              <!-- Description -->
              <div>
                <label class="medical-form-label">Description</label>
                <textarea
                  v-model="form.description"
                  rows="4"
                  class="medical-input"
                  placeholder="Detailed description of the condition, treatment, or findings..."
                ></textarea>
              </div>

              <!-- Conditional fields based on type -->
              <div v-if="form.type === 'diagnosis'">
                <label class="medical-form-label">Diagnosis Details</label>
                <textarea
                  v-model="form.diagnosis"
                  rows="3"
                  class="medical-input"
                  placeholder="Diagnosis codes, severity, stage, etc..."
                ></textarea>
              </div>

              <div v-if="['treatment', 'surgery'].includes(form.type)">
                <label class="medical-form-label">Treatment Details</label>
                <textarea
                  v-model="form.treatment"
                  rows="3"
                  class="medical-input"
                  placeholder="Treatment plan, procedures performed, etc..."
                ></textarea>
              </div>

              <!-- Medications -->
              <div v-if="['visit', 'diagnosis', 'treatment'].includes(form.type)">
                <label class="medical-form-label">Medications Prescribed</label>
                <textarea
                  v-model="form.medications"
                  rows="3"
                  class="medical-input"
                  placeholder="List medications, dosages, and instructions (one per line)"
                ></textarea>
              </div>

              <!-- Doctor -->
              <div>
                <label class="medical-form-label">Doctor/Provider</label>
                <input
                  v-model="form.doctor"
                  type="text"
                  class="medical-input"
                  placeholder="Doctor or healthcare provider name"
                />
              </div>

              <!-- Status -->
              <div>
                <label class="medical-form-label">Status</label>
                <select v-model="form.status" class="medical-input">
                  <option value="active">Active</option>
                  <option value="resolved">Resolved</option>
                </select>
              </div>

              <!-- Follow-up -->
              <div class="space-y-3">
                <div class="flex items-center">
                  <input
                    v-model="form.requiresFollowUp"
                    type="checkbox"
                    class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                  />
                  <label class="ml-2 text-sm font-medium text-gray-700">
                    Requires follow-up
                  </label>
                </div>

                <div v-if="form.requiresFollowUp">
                  <label class="medical-form-label">Follow-up Date</label>
                  <input
                    v-model="form.followUpDate"
                    type="date"
                    class="medical-input"
                    :min="form.date"
                  />
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
              <span v-if="!isLoading">Update Entry</span>
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
import { ref, computed, watch } from 'vue'
import { format } from 'date-fns'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import type { Patient, MedicalHistoryEntry } from '@/types/api.types'

interface Props {
  patient: Patient
  entry: MedicalHistoryEntry
}

interface Emits {
  (e: 'close'): void
  (e: 'updated', entry: MedicalHistoryEntry): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// State
const isLoading = ref(false)
const form = ref({
  type: '',
  date: '',
  title: '',
  description: '',
  diagnosis: '',
  treatment: '',
  medications: '',
  doctor: '',
  status: 'active',
  requiresFollowUp: false,
  followUpDate: ''
})

// Computed
const maxDate = computed(() => {
  return format(new Date(), 'yyyy-MM-dd')
})

// Watch for entry changes
watch(
  () => props.entry,
  (entry) => {
    form.value = {
      type: entry.type,
      date: entry.date,
      title: entry.title,
      description: entry.description || '',
      diagnosis: entry.diagnosis || '',
      treatment: entry.treatment || '',
      medications: entry.medications?.join('\n') || '',
      doctor: entry.doctor || '',
      status: entry.status || 'active',
      requiresFollowUp: !!entry.followUpDate,
      followUpDate: entry.followUpDate || ''
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
  isLoading.value = true

  try {
    // Mock API call - replace with actual API call
    await new Promise(resolve => setTimeout(resolve, 1500))

    // Parse medications into array
    const medicationsArray = form.value.medications
      ? form.value.medications.split('\n').filter(med => med.trim())
      : []

    const updatedEntry: MedicalHistoryEntry = {
      ...props.entry,
      type: form.value.type as any,
      date: form.value.date,
      title: form.value.title,
      description: form.value.description || undefined,
      diagnosis: form.value.diagnosis || undefined,
      treatment: form.value.treatment || undefined,
      medications: medicationsArray.length > 0 ? medicationsArray : undefined,
      doctor: form.value.doctor || undefined,
      status: form.value.status as any,
      followUpDate: form.value.requiresFollowUp ? form.value.followUpDate : undefined,
      updatedAt: new Date().toISOString()
    }

    emit('updated', updatedEntry)
    emit('close')
  } catch (error) {
    console.error('Failed to update medical history entry:', error)
  } finally {
    isLoading.value = false
  }
}
</script>
