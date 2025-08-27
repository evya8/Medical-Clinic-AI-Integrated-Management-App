<template>
  <form @submit.prevent="handleSubmit" class="patient-intake-form space-y-6">
    <div class="form-header mb-8">
      <h3 class="text-lg font-semibold text-gray-900 mb-2">Patient Information</h3>
      <p class="text-sm text-gray-600">Please fill out all required fields marked with an asterisk (*)</p>
    </div>

    <!-- Personal Information Section -->
    <div class="form-section">
      <h4 class="form-section-title">Personal Information</h4>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label class="form-label required">First Name</label>
          <input
            v-model="formData.firstName"
            type="text"
            required
            class="form-input"
            :class="{ 'border-red-300': errors.firstName }"
            placeholder="Enter first name"
          />
          <p v-if="errors.firstName" class="form-error">{{ errors.firstName }}</p>
        </div>
        
        <div>
          <label class="form-label required">Last Name</label>
          <input
            v-model="formData.lastName"
            type="text"
            required
            class="form-input"
            :class="{ 'border-red-300': errors.lastName }"
            placeholder="Enter last name"
          />
          <p v-if="errors.lastName" class="form-error">{{ errors.lastName }}</p>
        </div>

        <div>
          <label class="form-label required">Date of Birth</label>
          <input
            v-model="formData.dateOfBirth"
            type="date"
            required
            class="form-input"
            :class="{ 'border-red-300': errors.dateOfBirth }"
            :max="maxDate"
          />
          <p v-if="errors.dateOfBirth" class="form-error">{{ errors.dateOfBirth }}</p>
        </div>

        <div>
          <label class="form-label required">Gender</label>
          <select
            v-model="formData.gender"
            required
            class="form-select"
            :class="{ 'border-red-300': errors.gender }"
          >
            <option value="">Select gender</option>
            <option value="male">Male</option>
            <option value="female">Female</option>
            <option value="other">Other</option>
            <option value="prefer_not_to_say">Prefer not to say</option>
          </select>
          <p v-if="errors.gender" class="form-error">{{ errors.gender }}</p>
        </div>
      </div>
    </div>

    <!-- Contact Information Section -->
    <div class="form-section">
      <h4 class="form-section-title">Contact Information</h4>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label class="form-label">Email</label>
          <input
            v-model="formData.email"
            type="email"
            class="form-input"
            :class="{ 'border-red-300': errors.email }"
            placeholder="patient@example.com"
          />
          <p v-if="errors.email" class="form-error">{{ errors.email }}</p>
        </div>

        <div>
          <label class="form-label required">Phone Number</label>
          <input
            v-model="formData.phone"
            type="tel"
            required
            class="form-input"
            :class="{ 'border-red-300': errors.phone }"
            placeholder="(555) 123-4567"
          />
          <p v-if="errors.phone" class="form-error">{{ errors.phone }}</p>
        </div>

        <div class="md:col-span-2">
          <label class="form-label">Address</label>
          <textarea
            v-model="formData.address"
            rows="3"
            class="form-textarea"
            :class="{ 'border-red-300': errors.address }"
            placeholder="Street address, city, state, ZIP code"
          ></textarea>
          <p v-if="errors.address" class="form-error">{{ errors.address }}</p>
        </div>
      </div>
    </div>

    <!-- Emergency Contact Section -->
    <div class="form-section">
      <h4 class="form-section-title">Emergency Contact</h4>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label class="form-label required">Emergency Contact Name</label>
          <input
            v-model="formData.emergencyContactName"
            type="text"
            required
            class="form-input"
            :class="{ 'border-red-300': errors.emergencyContactName }"
            placeholder="Full name"
          />
          <p v-if="errors.emergencyContactName" class="form-error">{{ errors.emergencyContactName }}</p>
        </div>

        <div>
          <label class="form-label required">Emergency Contact Phone</label>
          <input
            v-model="formData.emergencyContactPhone"
            type="tel"
            required
            class="form-input"
            :class="{ 'border-red-300': errors.emergencyContactPhone }"
            placeholder="(555) 123-4567"
          />
          <p v-if="errors.emergencyContactPhone" class="form-error">{{ errors.emergencyContactPhone }}</p>
        </div>

        <div>
          <label class="form-label">Relationship</label>
          <input
            v-model="formData.emergencyContactRelationship"
            type="text"
            class="form-input"
            placeholder="e.g., Spouse, Parent, Sibling"
          />
        </div>
      </div>
    </div>

    <!-- Medical Information Section -->
    <div class="form-section">
      <h4 class="form-section-title">Medical Information</h4>
      <div class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="form-label">Blood Type</label>
            <select
              v-model="formData.bloodType"
              class="form-select"
            >
              <option value="">Unknown</option>
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

        <div>
          <label class="form-label">Known Allergies</label>
          <textarea
            v-model="formData.allergies"
            rows="3"
            class="form-textarea"
            :class="{ 'border-red-300': errors.allergies }"
            placeholder="List any known allergies to medications, foods, or substances"
          ></textarea>
          <p v-if="errors.allergies" class="form-error">{{ errors.allergies }}</p>
        </div>

        <div>
          <label class="form-label">Current Medications</label>
          <textarea
            v-model="formData.medications"
            rows="4"
            class="form-textarea"
            placeholder="List current medications, dosages, and frequency"
          ></textarea>
        </div>

        <div>
          <label class="form-label">Medical History</label>
          <textarea
            v-model="formData.medicalHistory"
            rows="4"
            class="form-textarea"
            placeholder="Previous medical conditions, surgeries, significant family history"
          ></textarea>
        </div>
      </div>
    </div>

    <!-- Insurance Information Section -->
    <div class="form-section">
      <h4 class="form-section-title">Insurance Information</h4>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label class="form-label">Insurance Provider</label>
          <select
            v-model="formData.insuranceProvider"
            class="form-select"
          >
            <option value="">No insurance</option>
            <option value="Blue Cross Blue Shield">Blue Cross Blue Shield</option>
            <option value="Aetna">Aetna</option>
            <option value="Cigna">Cigna</option>
            <option value="UnitedHealth">UnitedHealth</option>
            <option value="Medicare">Medicare</option>
            <option value="Medicaid">Medicaid</option>
            <option value="Other">Other</option>
          </select>
        </div>

        <div>
          <label class="form-label">Policy Number</label>
          <input
            v-model="formData.insurancePolicyNumber"
            type="text"
            class="form-input"
            placeholder="Insurance policy number"
          />
        </div>
      </div>
    </div>

    <!-- Form Actions -->
    <div class="form-actions pt-6 border-t border-gray-200">
      <div class="flex items-center justify-between">
        <button
          type="button"
          @click="resetForm"
          class="medical-button-secondary"
        >
          Reset Form
        </button>
        
        <div class="flex items-center space-x-4">
          <button
            v-if="!isEditMode"
            type="button"
            @click="saveAsDraft"
            :disabled="isSubmitting"
            class="medical-button-secondary"
          >
            <DocumentIcon class="w-4 h-4 mr-2" />
            Save as Draft
          </button>
          
          <button
            type="submit"
            :disabled="isSubmitting || !isFormValid"
            class="medical-button-primary"
          >
            <span v-if="isSubmitting">
              <div class="inline-flex items-center">
                <div class="animate-spin -ml-1 mr-3 h-4 w-4 border-2 border-white border-t-transparent rounded-full"></div>
                {{ isEditMode ? 'Updating...' : 'Creating...' }}
              </div>
            </span>
            <span v-else>
              {{ isEditMode ? 'Update Patient' : 'Create Patient' }}
            </span>
          </button>
        </div>
      </div>
    </div>
  </form>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { DocumentIcon } from '@heroicons/vue/24/outline'
import type { Patient } from '@/types/api.types'
import { useNotifications } from '@/stores/notifications'

interface PatientFormData {
  firstName: string
  lastName: string
  email: string
  phone: string
  dateOfBirth: string
  gender: 'male' | 'female' | 'other' | 'prefer_not_to_say' | ''
  address: string
  emergencyContactName: string
  emergencyContactPhone: string
  emergencyContactRelationship: string
  bloodType: string
  allergies: string
  medications: string
  medicalHistory: string
  insuranceProvider: string
  insurancePolicyNumber: string
}

interface Props {
  initialData?: Partial<Patient>
  isEditMode?: boolean
  onSubmit: (data: PatientFormData) => Promise<void>
  onCancel?: () => void
}

const props = withDefaults(defineProps<Props>(), {
  isEditMode: false,
})

const emit = defineEmits<{
  submit: [data: PatientFormData]
  cancel: []
  draft: [data: PatientFormData]
}>()

const { success, error } = useNotifications()

// Form state
const isSubmitting = ref(false)
const formData = ref<PatientFormData>({
  firstName: '',
  lastName: '',
  email: '',
  phone: '',
  dateOfBirth: '',
  gender: '',
  address: '',
  emergencyContactName: '',
  emergencyContactPhone: '',
  emergencyContactRelationship: '',
  bloodType: '',
  allergies: '',
  medications: '',
  medicalHistory: '',
  insuranceProvider: '',
  insurancePolicyNumber: '',
})

// Form validation errors
const errors = ref<Partial<Record<keyof PatientFormData, string>>>({})

// Computed properties
const maxDate = computed(() => {
  // Maximum date is today
  return new Date().toISOString().split('T')[0]
})

const isFormValid = computed(() => {
  return formData.value.firstName && 
         formData.value.lastName && 
         formData.value.dateOfBirth && 
         formData.value.gender &&
         formData.value.phone &&
         formData.value.emergencyContactName &&
         formData.value.emergencyContactPhone &&
         Object.keys(errors.value).length === 0
})

// Validation rules
const validateForm = (): boolean => {
  errors.value = {}

  // Required fields
  if (!formData.value.firstName.trim()) {
    errors.value.firstName = 'First name is required'
  }

  if (!formData.value.lastName.trim()) {
    errors.value.lastName = 'Last name is required'
  }

  if (!formData.value.dateOfBirth) {
    errors.value.dateOfBirth = 'Date of birth is required'
  } else {
    const birthDate = new Date(formData.value.dateOfBirth)
    const today = new Date()
    if (birthDate > today) {
      errors.value.dateOfBirth = 'Date of birth cannot be in the future'
    }
  }

  if (!formData.value.gender) {
    errors.value.gender = 'Gender selection is required'
  }

  if (!formData.value.phone.trim()) {
    errors.value.phone = 'Phone number is required'
  } else if (!/^[\d\s\-\(\)\+]+$/.test(formData.value.phone)) {
    errors.value.phone = 'Please enter a valid phone number'
  }

  // Email validation (optional but must be valid if provided)
  if (formData.value.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.value.email)) {
    errors.value.email = 'Please enter a valid email address'
  }

  // Emergency contact validation
  if (!formData.value.emergencyContactName.trim()) {
    errors.value.emergencyContactName = 'Emergency contact name is required'
  }

  if (!formData.value.emergencyContactPhone.trim()) {
    errors.value.emergencyContactPhone = 'Emergency contact phone is required'
  }

  return Object.keys(errors.value).length === 0
}

// Methods
const handleSubmit = async () => {
  if (!validateForm()) {
    error('Validation Error', 'Please correct the highlighted fields')
    return
  }

  isSubmitting.value = true
  
  try {
    await props.onSubmit(formData.value)
    emit('submit', formData.value)
    
    if (!props.isEditMode) {
      success('Patient Created', 'Patient information has been saved successfully')
      resetForm()
    } else {
      success('Patient Updated', 'Patient information has been updated successfully')
    }
  } catch (err) {
    error(
      props.isEditMode ? 'Update Failed' : 'Creation Failed',
      err instanceof Error ? err.message : 'An error occurred while saving patient information'
    )
  } finally {
    isSubmitting.value = false
  }
}

const saveAsDraft = () => {
  emit('draft', formData.value)
  success('Draft Saved', 'Patient information has been saved as draft')
}

const resetForm = () => {
  formData.value = {
    firstName: '',
    lastName: '',
    email: '',
    phone: '',
    dateOfBirth: '',
    gender: '',
    address: '',
    emergencyContactName: '',
    emergencyContactPhone: '',
    emergencyContactRelationship: '',
    bloodType: '',
    allergies: '',
    medications: '',
    medicalHistory: '',
    insuranceProvider: '',
    insurancePolicyNumber: '',
  }
  errors.value = {}
}

// Initialize form with existing data if editing
watch(() => props.initialData, (newData) => {
  if (newData && props.isEditMode) {
    formData.value = {
      firstName: newData.firstName || '',
      lastName: newData.lastName || '',
      email: newData.email || '',
      phone: newData.phone || '',
      dateOfBirth: newData.dateOfBirth || '',
      gender: (newData.gender as PatientFormData['gender']) || '',
      address: newData.address || '',
      emergencyContactName: newData.emergencyContactName || '',
      emergencyContactPhone: newData.emergencyContactPhone || '',
      emergencyContactRelationship: '', // Not in Patient type, add to form only
      bloodType: newData.bloodType || '',
      allergies: newData.allergies || '',
      medications: '', // Add to Patient type if needed
      medicalHistory: newData.medicalNotes || '',
      insuranceProvider: newData.insuranceProvider || '',
      insurancePolicyNumber: newData.insurancePolicyNumber || '',
    }
  }
}, { immediate: true })

// Real-time validation
watch(formData, () => {
  // Clear errors as user types
  Object.keys(errors.value).forEach(key => {
    const fieldKey = key as keyof PatientFormData
    if (formData.value[fieldKey]) {
      delete errors.value[fieldKey]
    }
  })
}, { deep: true })
</script>

<style lang="postcss" scoped>
.form-section {
  @apply bg-gray-50 p-6 rounded-lg border border-gray-200;
}

.form-section-title {
  @apply text-base font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-300;
}

.form-label {
  @apply block text-sm font-medium text-gray-700 mb-2;
}

.form-label.required::after {
  content: ' *';
  @apply text-red-500;
}

.form-input,
.form-select,
.form-textarea {
  @apply w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors;
}

.form-input:invalid,
.form-select:invalid,
.form-textarea:invalid {
  @apply border-red-300 focus:ring-red-500 focus:border-red-500;
}

.form-error {
  @apply text-red-600 text-xs mt-1;
}

.form-actions {
  @apply sticky bottom-0 bg-white;
}

/* Mobile responsive */
@media (max-width: 768px) {
  .form-actions .flex {
    @apply flex-col space-y-4;
  }
  
  .form-actions .flex > div {
    @apply flex-col space-x-0 space-y-3;
  }
}

/* Animation for form sections */
.form-section {
  animation: slideInUp 0.3s ease-out;
}

.form-section:nth-child(2) {
  animation-delay: 0.1s;
}

.form-section:nth-child(3) {
  animation-delay: 0.2s;
}

.form-section:nth-child(4) {
  animation-delay: 0.3s;
}

@keyframes slideInUp {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
