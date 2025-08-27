<template>
  <div class="modal-overlay fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" @click.self="$emit('close')">
    <div class="modal-content bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-hidden">
      <!-- Modal Header -->
      <div class="modal-header flex items-center justify-between p-6 border-b border-gray-200">
        <div>
          <h2 class="text-xl font-semibold text-gray-900">Edit Patient Information</h2>
          <p class="text-sm text-gray-600 mt-1">Update {{ patientFullName }}'s information</p>
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
        <form @submit.prevent="handleSubmit" class="space-y-8">
          <!-- Personal Information Section -->
          <div class="form-section">
            <h3 class="form-section-title">Personal Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="medical-form-label">First Name *</label>
                <input
                  v-model="form.firstName"
                  type="text"
                  required
                  class="medical-input"
                  :class="{ 'border-red-300': errors.firstName }"
                />
                <p v-if="errors.firstName" class="form-error">{{ errors.firstName }}</p>
              </div>

              <div>
                <label class="medical-form-label">Last Name *</label>
                <input
                  v-model="form.lastName"
                  type="text"
                  required
                  class="medical-input"
                  :class="{ 'border-red-300': errors.lastName }"
                />
                <p v-if="errors.lastName" class="form-error">{{ errors.lastName }}</p>
              </div>

              <div>
                <label class="medical-form-label">Date of Birth *</label>
                <input
                  v-model="form.dateOfBirth"
                  type="date"
                  required
                  class="medical-input"
                  :class="{ 'border-red-300': errors.dateOfBirth }"
                />
                <p v-if="errors.dateOfBirth" class="form-error">{{ errors.dateOfBirth }}</p>
              </div>

              <div>
                <label class="medical-form-label">Gender</label>
                <select v-model="form.gender" class="medical-input">
                  <option value="">Select Gender</option>
                  <option value="male">Male</option>
                  <option value="female">Female</option>
                  <option value="other">Other</option>
                  <option value="prefer_not_to_say">Prefer not to say</option>
                </select>
              </div>

              <div>
                <label class="medical-form-label">Blood Type</label>
                <select v-model="form.bloodType" class="medical-input">
                  <option value="">Select Blood Type</option>
                  <option value="A+">A+</option>
                  <option value="A-">A-</option>
                  <option value="B+">B+</option>
                  <option value="B-">B-</option>
                  <option value="AB+">AB+</option>
                  <option value="AB-">AB-</option>
                  <option value="O+">O+</option>
                  <option value="O-">O-</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Contact Information Section -->
          <div class="form-section">
            <h3 class="form-section-title">Contact Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="medical-form-label">Email Address</label>
                <input
                  v-model="form.email"
                  type="email"
                  class="medical-input"
                  :class="{ 'border-red-300': errors.email }"
                />
                <p v-if="errors.email" class="form-error">{{ errors.email }}</p>
              </div>

              <div>
                <label class="medical-form-label">Phone Number</label>
                <input
                  v-model="form.phone"
                  type="tel"
                  class="medical-input"
                  placeholder="(555) 123-4567"
                />
              </div>

              <div class="md:col-span-2">
                <label class="medical-form-label">Address</label>
                <textarea
                  v-model="form.address"
                  rows="3"
                  class="medical-input"
                  placeholder="Street address, city, state, zip code"
                ></textarea>
              </div>
            </div>
          </div>

          <!-- Emergency Contact Section -->
          <div class="form-section">
            <h3 class="form-section-title">Emergency Contact</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="medical-form-label">Contact Name</label>
                <input
                  v-model="form.emergencyContactName"
                  type="text"
                  class="medical-input"
                  placeholder="Full name of emergency contact"
                />
              </div>

              <div>
                <label class="medical-form-label">Contact Phone</label>
                <input
                  v-model="form.emergencyContactPhone"
                  type="tel"
                  class="medical-input"
                  placeholder="(555) 123-4567"
                />
              </div>
            </div>
          </div>

          <!-- Insurance Information Section -->
          <div class="form-section">
            <h3 class="form-section-title">Insurance Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="medical-form-label">Insurance Provider</label>
                <input
                  v-model="form.insuranceProvider"
                  type="text"
                  class="medical-input"
                  placeholder="e.g., Blue Cross Blue Shield"
                />
              </div>

              <div>
                <label class="medical-form-label">Policy Number</label>
                <input
                  v-model="form.insurancePolicyNumber"
                  type="text"
                  class="medical-input"
                  placeholder="Insurance policy number"
                />
              </div>
            </div>
          </div>

          <!-- Medical Information Section -->
          <div class="form-section">
            <h3 class="form-section-title">Medical Information</h3>
            <div class="space-y-6">
              <div>
                <label class="medical-form-label">Known Allergies</label>
                <textarea
                  v-model="form.allergies"
                  rows="3"
                  class="medical-input"
                  placeholder="List any known allergies, medications, or substances. Enter 'None known' if no allergies."
                ></textarea>
                <p class="form-help">Please be specific about allergic reactions and severity.</p>
              </div>

              <div>
                <label class="medical-form-label">Medical Notes</label>
                <textarea
                  v-model="form.medicalNotes"
                  rows="4"
                  class="medical-input"
                  placeholder="Additional medical information, chronic conditions, medications, etc."
                ></textarea>
                <p class="form-help">Include any important medical history or ongoing conditions.</p>
              </div>
            </div>
          </div>
        </form>
      </div>

      <!-- Modal Footer -->
      <div class="modal-footer flex items-center justify-end space-x-4 p-6 border-t border-gray-200 bg-gray-50">
        <button
          type="button"
          @click="$emit('close')"
          class="medical-button-outline"
          :disabled="isSubmitting"
        >
          Cancel
        </button>
        <button
          @click="handleSubmit"
          class="medical-button-primary flex items-center"
          :disabled="isSubmitting || !isFormValid"
        >
          <span v-if="isSubmitting" class="animate-spin -ml-1 mr-2 h-4 w-4 border-2 border-white border-t-transparent rounded-full"></span>
          {{ isSubmitting ? 'Saving...' : 'Save Changes' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, reactive, onMounted } from 'vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import type { Patient } from '@/types/api.types'
import { api, API_ENDPOINTS } from '@/services/api'

interface Props {
  patient: Patient
}

interface Emits {
  (e: 'close'): void
  (e: 'update', patient: Patient): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Form state
const form = reactive({
  firstName: '',
  lastName: '',
  email: '',
  phone: '',
  dateOfBirth: '',
  gender: '',
  address: '',
  emergencyContactName: '',
  emergencyContactPhone: '',
  bloodType: '',
  allergies: '',
  medicalNotes: '',
  insuranceProvider: '',
  insurancePolicyNumber: '',
})

const errors = reactive<Record<string, string>>({})
const isSubmitting = ref(false)

// Computed
const patientFullName = computed(() => {
  return `${props.patient.firstName} ${props.patient.lastName}`.trim()
})

const isFormValid = computed(() => {
  return form.firstName.trim() && 
         form.lastName.trim() && 
         form.dateOfBirth &&
         Object.keys(errors).length === 0
})

// Methods
const initializeForm = () => {
  form.firstName = props.patient.firstName || ''
  form.lastName = props.patient.lastName || ''
  form.email = props.patient.email || ''
  form.phone = props.patient.phone || ''
  form.dateOfBirth = props.patient.dateOfBirth || ''
  form.gender = props.patient.gender || ''
  form.address = props.patient.address || ''
  form.emergencyContactName = props.patient.emergencyContactName || ''
  form.emergencyContactPhone = props.patient.emergencyContactPhone || ''
  form.bloodType = props.patient.bloodType || ''
  form.allergies = props.patient.allergies || ''
  form.medicalNotes = props.patient.medicalNotes || ''
  form.insuranceProvider = props.patient.insuranceProvider || ''
  form.insurancePolicyNumber = props.patient.insurancePolicyNumber || ''
}

const validateForm = () => {
  // Clear previous errors
  Object.keys(errors).forEach(key => delete errors[key])

  // Validate required fields
  if (!form.firstName.trim()) {
    errors.firstName = 'First name is required'
  }

  if (!form.lastName.trim()) {
    errors.lastName = 'Last name is required'
  }

  if (!form.dateOfBirth) {
    errors.dateOfBirth = 'Date of birth is required'
  } else {
    // Validate age (must be reasonable)
    const birthDate = new Date(form.dateOfBirth)
    const today = new Date()
    const age = today.getFullYear() - birthDate.getFullYear()
    
    if (age < 0 || age > 150) {
      errors.dateOfBirth = 'Please enter a valid date of birth'
    }
  }

  // Validate email format if provided
  if (form.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
    errors.email = 'Please enter a valid email address'
  }

  return Object.keys(errors).length === 0
}

const handleSubmit = async () => {
  if (!validateForm()) {
    return
  }

  isSubmitting.value = true

  try {
    // Prepare updated patient data
    const updatedPatientData = {
      ...form,
      id: props.patient.id,
    }

    // Call API to update patient
    const response = await api.put<Patient>(
      API_ENDPOINTS.PATIENTS.UPDATE(props.patient.id),
      updatedPatientData
    )

    if (response.success && response.data) {
      emit('update', response.data)
    } else {
      throw new Error(response.message || 'Failed to update patient')
    }
  } catch (error: any) {
    console.error('Error updating patient:', error)
    // Add error handling - in a real app, you'd show a notification
    alert('Failed to update patient: ' + (error.message || 'Unknown error'))
  } finally {
    isSubmitting.value = false
  }
}

// Initialize form when component mounts
onMounted(() => {
  initializeForm()
})
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

.form-section {
  @apply space-y-4;
}

.form-section-title {
  @apply text-lg font-semibold text-gray-900 pb-2 border-b border-gray-200;
}

.form-error {
  @apply mt-1 text-sm text-red-600;
}

.form-help {
  @apply mt-1 text-sm text-gray-500;
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
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
  
  .modal-header, .modal-body, .modal-footer {
    @apply px-4;
  }
  
  .grid.md\:grid-cols-2 {
    @apply grid-cols-1;
  }
}

/* Focus management */
.modal-overlay {
  /* Trap focus within modal */
  isolation: isolate;
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
</style>
