<template>
  <form @submit.prevent="handleSubmit" class="appointment-form space-y-6">
    <div class="form-header mb-8">
      <h3 class="text-lg font-semibold text-gray-900 mb-2">
        {{ isEditMode ? 'Edit Appointment' : 'Schedule New Appointment' }}
      </h3>
      <p class="text-sm text-gray-600">Fill out the appointment details below</p>
    </div>

    <!-- Patient Selection -->
    <div class="form-section">
      <h4 class="form-section-title">Patient Information</h4>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label class="form-label required">Patient</label>
          <div class="relative">
            <input
              v-model="patientSearchQuery"
              @input="searchPatients"
              @focus="showPatientSuggestions = true"
              type="text"
              required
              class="form-input"
              :class="{ 'border-red-300': errors.patientId }"
              placeholder="Search for patient..."
            />
            
            <!-- Patient Suggestions Dropdown -->
            <div 
              v-if="showPatientSuggestions && filteredPatients.length > 0"
              class="absolute z-20 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto"
            >
              <div
                v-for="patient in filteredPatients"
                :key="patient.id"
                @click="selectPatient(patient)"
                class="p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0"
              >
                <div class="font-medium text-gray-900">{{ patient.firstName }} {{ patient.lastName }}</div>
                <div class="text-sm text-gray-600">{{ patient.email }} • {{ patient.phone }}</div>
                <div class="text-xs text-gray-500">DOB: {{ formatDate(patient.dateOfBirth) }}</div>
              </div>
            </div>
          </div>
          <p v-if="errors.patientId" class="form-error">{{ errors.patientId }}</p>
        </div>

        <div v-if="selectedPatient">
          <label class="form-label">Selected Patient</label>
          <div class="patient-info-card p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <div class="font-medium text-blue-900">{{ selectedPatient.firstName }} {{ selectedPatient.lastName }}</div>
            <div class="text-sm text-blue-700">{{ selectedPatient.email }}</div>
            <div class="text-sm text-blue-700">{{ selectedPatient.phone }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Appointment Details -->
    <div class="form-section">
      <h4 class="form-section-title">Appointment Details</h4>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label class="form-label required">Doctor</label>
          <select
            v-model="formData.doctorId"
            @change="loadDoctorAvailability"
            required
            class="form-select"
            :class="{ 'border-red-300': errors.doctorId }"
          >
            <option value="">Select a doctor</option>
            <option 
              v-for="doctor in availableDoctors" 
              :key="doctor.id" 
              :value="doctor.id"
            >
              Dr. {{ doctor.firstName }} {{ doctor.lastName }} - {{ doctor.specialty }}
            </option>
          </select>
          <p v-if="errors.doctorId" class="form-error">{{ errors.doctorId }}</p>
        </div>

        <div>
          <label class="form-label required">Appointment Type</label>
          <select
            v-model="formData.appointmentType"
            required
            class="form-select"
            :class="{ 'border-red-300': errors.appointmentType }"
          >
            <option value="">Select type</option>
            <option value="consultation">Consultation</option>
            <option value="follow-up">Follow-up</option>
            <option value="procedure">Procedure</option>
            <option value="emergency">Emergency</option>
            <option value="check-up">Check-up</option>
            <option value="vaccination">Vaccination</option>
          </select>
          <p v-if="errors.appointmentType" class="form-error">{{ errors.appointmentType }}</p>
        </div>

        <div>
          <label class="form-label required">Priority</label>
          <select
            v-model="formData.priority"
            required
            class="form-select"
            :class="{ 'border-red-300': errors.priority }"
          >
            <option value="">Select priority</option>
            <option value="low">Low</option>
            <option value="normal">Normal</option>
            <option value="high">High</option>
            <option value="urgent">Urgent</option>
          </select>
          <p v-if="errors.priority" class="form-error">{{ errors.priority }}</p>
        </div>

        <div>
          <label class="form-label">Duration (minutes)</label>
          <select v-model="formData.duration" class="form-select">
            <option value="15">15 minutes</option>
            <option value="30">30 minutes</option>
            <option value="45">45 minutes</option>
            <option value="60">60 minutes</option>
            <option value="90">90 minutes</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Date & Time Selection -->
    <div class="form-section">
      <h4 class="form-section-title">Date & Time</h4>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label class="form-label required">Appointment Date</label>
          <input
            v-model="formData.appointmentDate"
            @change="loadAvailableSlots"
            type="date"
            required
            class="form-input"
            :class="{ 'border-red-300': errors.appointmentDate }"
            :min="minDate"
          />
          <p v-if="errors.appointmentDate" class="form-error">{{ errors.appointmentDate }}</p>
        </div>

        <div>
          <label class="form-label required">Time Slot</label>
          <select
            v-model="formData.timeSlot"
            required
            class="form-select"
            :class="{ 'border-red-300': errors.timeSlot }"
            :disabled="!availableTimeSlots.length"
          >
            <option value="">{{ availableTimeSlots.length ? 'Select time' : 'Select date first' }}</option>
            <option 
              v-for="slot in availableTimeSlots" 
              :key="slot.time" 
              :value="slot.time"
              :disabled="!slot.available"
            >
              {{ formatTimeSlot(slot.time) }} {{ slot.available ? '' : '(Unavailable)' }}
            </option>
          </select>
          <p v-if="errors.timeSlot" class="form-error">{{ errors.timeSlot }}</p>
        </div>
      </div>

      <!-- Available Slots Grid (Alternative UI) -->
      <div v-if="formData.appointmentDate && formData.doctorId && availableTimeSlots.length > 0" class="mt-6">
        <label class="form-label">Available Time Slots</label>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
          <button
            v-for="slot in availableTimeSlots.filter((s: TimeSlot) => s.available)"
            :key="slot.time"
            type="button"
            @click="formData.timeSlot = slot.time"
            :class="[
              'p-3 text-sm border rounded-lg transition-colors',
              formData.timeSlot === slot.time 
                ? 'bg-blue-600 text-white border-blue-600' 
                : 'bg-white text-gray-700 border-gray-300 hover:bg-blue-50 hover:border-blue-300'
            ]"
          >
            {{ formatTimeSlot(slot.time) }}
          </button>
        </div>
      </div>
    </div>

    <!-- Additional Information -->
    <div class="form-section">
      <h4 class="form-section-title">Additional Information</h4>
      <div class="space-y-6">
        <div>
          <label class="form-label">Reason for Visit</label>
          <textarea
            v-model="formData.notes"
            rows="3"
            class="form-textarea"
            placeholder="Brief description of the reason for this appointment"
          ></textarea>
        </div>

        <div>
          <label class="form-label">Special Instructions</label>
          <textarea
            v-model="formData.specialInstructions"
            rows="2"
            class="form-textarea"
            placeholder="Any special requirements or instructions"
          ></textarea>
        </div>

        <!-- Follow-up Options -->
        <div class="flex items-center space-x-4">
          <div class="flex items-center">
            <input
              id="followUpRequired"
              v-model="formData.followUpRequired"
              type="checkbox"
              class="form-checkbox"
            />
            <label for="followUpRequired" class="ml-2 text-sm text-gray-700">
              Follow-up appointment required
            </label>
          </div>
          
          <div v-if="formData.followUpRequired">
            <input
              v-model="formData.followUpDate"
              type="date"
              class="form-input text-sm"
              :min="minFollowUpDate"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Form Actions -->
    <div class="form-actions pt-6 border-t border-gray-200">
      <div class="flex items-center justify-between">
        <button
          type="button"
          @click="emit('cancel')"
          class="medical-button-secondary"
        >
          Cancel
        </button>
        
        <div class="flex items-center space-x-4">
          <button
            v-if="!isEditMode"
            type="button"
            @click="saveAsDraft"
            :disabled="isSubmitting"
            class="medical-button-secondary"
          >
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
                {{ isEditMode ? 'Updating...' : 'Scheduling...' }}
              </div>
            </span>
            <span v-else>
              {{ isEditMode ? 'Update Appointment' : 'Schedule Appointment' }}
            </span>
          </button>
        </div>
      </div>
    </div>
  </form>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import type { Patient, Doctor, Appointment } from '@/types/api.types'
import { useNotifications } from '@/stores/notifications'
import { format } from 'date-fns'

interface AppointmentFormData {
  patientId: number | ''
  doctorId: number | ''
  appointmentDate: string
  timeSlot: string
  appointmentType: string
  priority: 'low' | 'normal' | 'high' | 'urgent' | ''
  duration: string
  notes: string
  specialInstructions: string
  followUpRequired: boolean
  followUpDate: string
}

interface TimeSlot {
  time: string
  available: boolean
}

interface Props {
  initialData?: Partial<Appointment>
  isEditMode?: boolean
  onSubmit: (data: AppointmentFormData) => Promise<void>
  availablePatients?: Patient[]
  availableDoctors?: Doctor[]
}

const props = withDefaults(defineProps<Props>(), {
  isEditMode: false,
  availablePatients: () => [],
  availableDoctors: () => [],
})

const emit = defineEmits<{
  submit: [data: AppointmentFormData]
  cancel: []
  draft: [data: AppointmentFormData]
}>()

const { success, error } = useNotifications()

// Form state
const isSubmitting = ref(false)
const showPatientSuggestions = ref(false)
const patientSearchQuery = ref('')
const selectedPatient = ref<Patient | null>(null)

const formData = ref<AppointmentFormData>({
  patientId: '',
  doctorId: '',
  appointmentDate: '',
  timeSlot: '',
  appointmentType: '',
  priority: '',
  duration: '30',
  notes: '',
  specialInstructions: '',
  followUpRequired: false,
  followUpDate: '',
})

const errors = ref<Partial<Record<keyof AppointmentFormData, string>>>({})
const availableTimeSlots = ref<TimeSlot[]>([])

// Computed properties
const minDate = computed(() => {
  return new Date().toISOString().split('T')[0]
})

const minFollowUpDate = computed(() => {
  if (formData.value.appointmentDate) {
    const appointmentDate = new Date(formData.value.appointmentDate)
    appointmentDate.setDate(appointmentDate.getDate() + 1)
    return appointmentDate.toISOString().split('T')[0]
  }
  return minDate.value
})

const filteredPatients = computed(() => {
  if (!patientSearchQuery.value || patientSearchQuery.value.length < 2) {
    return props.availablePatients.slice(0, 10)
  }
  
  const query = patientSearchQuery.value.toLowerCase()
  return props.availablePatients.filter(patient => 
    `${patient.firstName} ${patient.lastName}`.toLowerCase().includes(query) ||
    patient.email?.toLowerCase().includes(query) ||
    patient.phone?.includes(query)
  )
})

const isFormValid = computed(() => {
  return formData.value.patientId && 
         formData.value.doctorId && 
         formData.value.appointmentDate && 
         formData.value.timeSlot &&
         formData.value.appointmentType &&
         formData.value.priority &&
         Object.keys(errors.value).length === 0
})

// Methods
const searchPatients = () => {
  if (patientSearchQuery.value.length >= 2) {
    showPatientSuggestions.value = true
  } else {
    showPatientSuggestions.value = false
  }
}

const selectPatient = (patient: Patient) => {
  selectedPatient.value = patient
  formData.value.patientId = patient.id
  patientSearchQuery.value = `${patient.firstName} ${patient.lastName}`
  showPatientSuggestions.value = false
}

const loadDoctorAvailability = () => {
  if (formData.value.appointmentDate) {
    loadAvailableSlots()
  }
}

const loadAvailableSlots = async () => {
  if (!formData.value.doctorId || !formData.value.appointmentDate) {
    availableTimeSlots.value = []
    return
  }

  try {
    // In real implementation, this would be an API call
    // const response = await api.get(`/appointments/available-slots`, {
    //   doctorId: formData.value.doctorId,
    //   date: formData.value.appointmentDate,
    //   duration: formData.value.duration
    // })
    
    // Mock available time slots
    const mockSlots: TimeSlot[] = [
      { time: '08:00', available: true },
      { time: '08:30', available: true },
      { time: '09:00', available: false },
      { time: '09:30', available: true },
      { time: '10:00', available: true },
      { time: '10:30', available: false },
      { time: '11:00', available: true },
      { time: '11:30', available: true },
      { time: '14:00', available: true },
      { time: '14:30', available: true },
      { time: '15:00', available: false },
      { time: '15:30', available: true },
      { time: '16:00', available: true },
      { time: '16:30', available: true },
    ]
    
    availableTimeSlots.value = mockSlots
  } catch (err) {
    console.error('Failed to load available time slots:', err)
    availableTimeSlots.value = []
  }
}

const validateForm = (): boolean => {
  errors.value = {}

  // Required field validation
  if (!formData.value.patientId) {
    errors.value.patientId = 'Patient selection is required'
  }

  if (!formData.value.doctorId) {
    errors.value.doctorId = 'Doctor selection is required'
  }

  if (!formData.value.appointmentDate) {
    errors.value.appointmentDate = 'Appointment date is required'
  } else {
    const appointmentDate = new Date(formData.value.appointmentDate)
    const today = new Date()
    today.setHours(0, 0, 0, 0)
    
    if (appointmentDate < today) {
      errors.value.appointmentDate = 'Appointment date cannot be in the past'
    }
  }

  if (!formData.value.timeSlot) {
    errors.value.timeSlot = 'Time slot selection is required'
  }

  if (!formData.value.appointmentType) {
    errors.value.appointmentType = 'Appointment type is required'
  }

  if (!formData.value.priority) {
    errors.value.priority = 'Priority selection is required'
  }

  // Follow-up date validation
  if (formData.value.followUpRequired && formData.value.followUpDate) {
    const followUpDate = new Date(formData.value.followUpDate)
    const appointmentDate = new Date(formData.value.appointmentDate)
    
    if (followUpDate <= appointmentDate) {
      errors.value.followUpDate = 'Follow-up date must be after appointment date'
    }
  }

  return Object.keys(errors.value).length === 0
}

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
      success('Appointment Scheduled', 'Appointment has been scheduled successfully')
      resetForm()
    } else {
      success('Appointment Updated', 'Appointment has been updated successfully')
    }
  } catch (err) {
    error(
      props.isEditMode ? 'Update Failed' : 'Scheduling Failed',
      err instanceof Error ? err.message : 'An error occurred while processing the appointment'
    )
  } finally {
    isSubmitting.value = false
  }
}

const saveAsDraft = () => {
  emit('draft', formData.value)
  success('Draft Saved', 'Appointment information has been saved as draft')
}

const resetForm = () => {
  formData.value = {
    patientId: '',
    doctorId: '',
    appointmentDate: '',
    timeSlot: '',
    appointmentType: '',
    priority: '',
    duration: '30',
    notes: '',
    specialInstructions: '',
    followUpRequired: false,
    followUpDate: '',
  }
  selectedPatient.value = null
  patientSearchQuery.value = ''
  errors.value = {}
  availableTimeSlots.value = []
}

// Helper functions
const formatDate = (dateString: string) => {
  return format(new Date(dateString), 'MMM d, yyyy')
}

const formatTimeSlot = (time: string) => {
  const [hours, minutes] = time.split(':')
  const hour24 = parseInt(hours)
  const hour12 = hour24 > 12 ? hour24 - 12 : hour24 === 0 ? 12 : hour24
  const ampm = hour24 >= 12 ? 'PM' : 'AM'
  return `${hour12}:${minutes} ${ampm}`
}

// Click outside handler for patient suggestions
const handleClickOutside = (event: Event) => {
  const target = event.target as Element
  if (!target.closest('.relative')) {
    showPatientSuggestions.value = false
  }
}

// Initialize form if editing
watch(() => props.initialData, (newData) => {
  if (newData && props.isEditMode) {
    formData.value = {
      patientId: newData.patientId || '',
      doctorId: newData.doctorId || '',
      appointmentDate: newData.appointmentDate || '',
      timeSlot: newData.startTime || '',
      appointmentType: newData.appointmentType || '',
      priority: (newData.priority as AppointmentFormData['priority']) || '',
      duration: '30', // Calculate from start/end time if available
      notes: newData.notes || '',
      specialInstructions: '', // Add to Appointment type if needed
      followUpRequired: newData.followUpRequired || false,
      followUpDate: newData.followUpDate || '',
    }
    
    // Load related data
    if (newData.patientId) {
      const patient = props.availablePatients.find(p => p.id === newData.patientId)
      if (patient) {
        selectedPatient.value = patient
        patientSearchQuery.value = `${patient.firstName} ${patient.lastName}`
      }
    }
    
    if (newData.doctorId && newData.appointmentDate) {
      loadAvailableSlots()
    }
  }
}, { immediate: true })

// Global click handler
document.addEventListener('click', handleClickOutside)
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
.form-textarea,
.form-checkbox {
  @apply transition-colors;
}

.form-input,
.form-select,
.form-textarea {
  @apply w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500;
}

.form-checkbox {
  @apply rounded text-blue-600 focus:ring-blue-500;
}

.form-error {
  @apply text-red-600 text-xs mt-1;
}

.patient-info-card {
  animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateX(10px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

/* Time slot buttons */
.grid button {
  @apply transition-all duration-200;
}

.grid button:hover:not(:disabled) {
  @apply transform scale-105;
}

/* Mobile responsive */
@media (max-width: 768px) {
  .form-actions .flex {
    @apply flex-col space-y-4;
  }
  
  .form-actions .flex > div {
    @apply flex-col space-x-0 space-y-3;
  }
  
  .grid {
    @apply grid-cols-2 md:grid-cols-3;
  }
}
</style>
