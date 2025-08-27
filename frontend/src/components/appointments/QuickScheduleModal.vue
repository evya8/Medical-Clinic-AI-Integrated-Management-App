<template>
  <div class="modal-overlay fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" @click.self="$emit('close')">
    <div class="modal-content bg-white rounded-lg shadow-xl w-full max-w-lg max-h-[90vh] overflow-hidden">
      <!-- Modal Header -->
      <div class="modal-header flex items-center justify-between p-6 border-b border-gray-200">
        <div>
          <h2 class="text-xl font-semibold text-gray-900">Quick Schedule</h2>
          <p class="text-sm text-gray-600 mt-1">
            {{ initialDate ? `Schedule for ${formatDate(initialDate)}` : 'Schedule a new appointment' }}
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
        <form @submit.prevent="handleSubmit" class="space-y-6">
          <!-- Patient Selection -->
          <div>
            <label class="medical-form-label">Patient *</label>
            <div class="relative">
              <input
                v-model="patientSearch"
                type="text"
                placeholder="Search for a patient..."
                class="medical-input pr-10"
                @input="searchPatients"
                @focus="showPatientDropdown = true"
                required
              />
              <MagnifyingGlassIcon class="w-5 h-5 text-gray-400 absolute right-3 top-1/2 transform -translate-y-1/2" />
              
              <!-- Patient Dropdown -->
              <div
                v-if="showPatientDropdown && searchResults.length > 0"
                class="absolute top-full left-0 right-0 bg-white border border-gray-200 rounded-lg shadow-lg mt-1 max-h-60 overflow-y-auto z-10"
              >
                <div
                  v-for="patient in searchResults"
                  :key="patient.id"
                  class="p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0"
                  @click="selectPatient(patient)"
                >
                  <div class="font-medium text-gray-900">
                    {{ patient.firstName }} {{ patient.lastName }}
                  </div>
                  <div class="text-sm text-gray-600">
                    {{ patient.email }} • {{ patient.phone }}
                  </div>
                </div>
              </div>
            </div>
            <p v-if="errors.patient" class="form-error">{{ errors.patient }}</p>
          </div>

          <!-- Selected Patient Display -->
          <div v-if="selectedPatient" class="selected-patient bg-blue-50 p-4 rounded-lg border border-blue-200">
            <div class="flex items-center">
              <div class="w-10 h-10 bg-primary-500 rounded-full flex items-center justify-center mr-3">
                <span class="text-sm font-medium text-white">
                  {{ getPatientInitials(selectedPatient) }}
                </span>
              </div>
              <div class="flex-1">
                <h3 class="font-medium text-gray-900">
                  {{ selectedPatient.firstName }} {{ selectedPatient.lastName }}
                </h3>
                <p class="text-sm text-gray-600">
                  {{ selectedPatient.email }} • {{ selectedPatient.phone }}
                </p>
              </div>
              <button
                type="button"
                @click="clearSelectedPatient"
                class="p-1 text-gray-400 hover:text-gray-600"
              >
                <XMarkIcon class="w-4 h-4" />
              </button>
            </div>
          </div>

          <!-- Date Selection -->
          <div>
            <label class="medical-form-label">Date *</label>
            <input
              v-model="form.date"
              type="date"
              required
              :min="minDate"
              class="medical-input"
              :class="{ 'border-red-300': errors.date }"
            />
            <p v-if="errors.date" class="form-error">{{ errors.date }}</p>
          </div>

          <!-- Time Selection -->
          <div>
            <label class="medical-form-label">Time *</label>
            <div class="grid grid-cols-3 gap-2">
              <button
                v-for="time in availableTimeSlots"
                :key="time"
                type="button"
                @click="form.time = time"
                class="time-slot-button p-2 text-sm font-medium rounded border transition-all duration-200"
                :class="form.time === time 
                  ? 'bg-primary-500 text-white border-primary-500' 
                  : 'bg-white text-gray-700 border-gray-300 hover:border-primary-300 hover:bg-primary-50'"
              >
                {{ formatTime(time) }}
              </button>
            </div>
            <p v-if="errors.time" class="form-error">{{ errors.time }}</p>
          </div>

          <!-- Appointment Type -->
          <div>
            <label class="medical-form-label">Type *</label>
            <select v-model="form.type" required class="medical-input">
              <option value="">Select type</option>
              <option value="consultation">Consultation</option>
              <option value="follow-up">Follow-up</option>
              <option value="checkup">Checkup</option>
              <option value="urgent">Urgent Care</option>
            </select>
          </div>

          <!-- Doctor Selection -->
          <div>
            <label class="medical-form-label">Doctor</label>
            <select v-model="form.doctorId" class="medical-input">
              <option value="">Auto-assign</option>
              <option v-for="doctor in doctors" :key="doctor.id" :value="doctor.id">
                Dr. {{ doctor.firstName }} {{ doctor.lastName }} - {{ doctor.specialty }}
              </option>
            </select>
          </div>

          <!-- Notes -->
          <div>
            <label class="medical-form-label">Notes</label>
            <textarea
              v-model="form.notes"
              rows="3"
              class="medical-input"
              placeholder="Reason for visit, symptoms, or special instructions..."
            ></textarea>
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
          {{ isSubmitting ? 'Scheduling...' : 'Schedule Appointment' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, reactive, onMounted, watch } from 'vue'
import { format } from 'date-fns'
import { XMarkIcon, MagnifyingGlassIcon } from '@heroicons/vue/24/outline'
import type { Patient, Doctor, Appointment } from '@/types/api.types'
import { api, API_ENDPOINTS } from '@/services/api'

interface Props {
  initialDate?: string
}

interface Emits {
  (e: 'close'): void
  (e: 'scheduled', appointment: Appointment): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// State
const form = reactive({
  date: props.initialDate || '',
  time: '',
  type: 'consultation',
  doctorId: null as number | null,
  notes: ''
})

const errors = reactive<Record<string, string>>({})
const isSubmitting = ref(false)

// Patient search
const patientSearch = ref('')
const selectedPatient = ref<Patient | null>(null)
const showPatientDropdown = ref(false)
const searchResults = ref<Patient[]>([])

// Data
const doctors = ref<Doctor[]>([])
const availableTimeSlots = [
  '09:00', '09:30', '10:00', '10:30', '11:00', '11:30',
  '14:00', '14:30', '15:00', '15:30', '16:00', '16:30'
]

// Computed
const minDate = computed(() => {
  return format(new Date(), 'yyyy-MM-dd')
})

const isFormValid = computed(() => {
  return selectedPatient.value &&
         form.date &&
         form.time &&
         form.type &&
         Object.keys(errors).length === 0
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

const getPatientInitials = (patient: Patient) => {
  const firstName = patient.firstName || ''
  const lastName = patient.lastName || ''
  return `${firstName.charAt(0)}${lastName.charAt(0)}`.toUpperCase()
}

const searchPatients = async () => {
  if (patientSearch.value.length < 2) {
    searchResults.value = []
    return
  }

  try {
    // In a real app, this would search the API
    const response = await api.get<Patient[]>(`${API_ENDPOINTS.PATIENTS.SEARCH}?q=${patientSearch.value}`)
    
    if (response.success && response.data) {
      searchResults.value = response.data
    } else {
      // Mock search results for demo
      searchResults.value = generateMockSearchResults(patientSearch.value)
    }
  } catch (error) {
    console.error('Error searching patients:', error)
    searchResults.value = generateMockSearchResults(patientSearch.value)
  }
}

const generateMockSearchResults = (query: string): Patient[] => {
  const mockPatients = [
    { id: 1, firstName: 'John', lastName: 'Doe', email: 'john.doe@email.com', phone: '(555) 123-4567' },
    { id: 2, firstName: 'Jane', lastName: 'Smith', email: 'jane.smith@email.com', phone: '(555) 987-6543' },
    { id: 3, firstName: 'Bob', lastName: 'Johnson', email: 'bob.johnson@email.com', phone: '(555) 456-7890' },
    { id: 4, firstName: 'Alice', lastName: 'Williams', email: 'alice.williams@email.com', phone: '(555) 321-0987' },
  ]

  return mockPatients
    .filter(patient => 
      `${patient.firstName} ${patient.lastName}`.toLowerCase().includes(query.toLowerCase()) ||
      patient.email.toLowerCase().includes(query.toLowerCase())
    )
    .map(patient => ({
      ...patient,
      dateOfBirth: '1990-01-01',
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString()
    }))
}

const selectPatient = (patient: Patient) => {
  selectedPatient.value = patient
  patientSearch.value = `${patient.firstName} ${patient.lastName}`
  showPatientDropdown.value = false
  searchResults.value = []
}

const clearSelectedPatient = () => {
  selectedPatient.value = null
  patientSearch.value = ''
  showPatientDropdown.value = false
  searchResults.value = []
}

const loadDoctors = async () => {
  try {
    const response = await api.get<Doctor[]>(API_ENDPOINTS.DOCTORS.LIST)
    
    if (response.success && response.data) {
      doctors.value = response.data
    } else {
      // Mock doctors
      doctors.value = [
        {
          id: 1,
          userId: 101,
          firstName: 'Sarah',
          lastName: 'Smith',
          email: 'dr.smith@clinic.com',
          specialization: 'Family Medicine',
          licenseNumber: 'FM12345',
          phone: '(555) 111-2222',
          createdAt: new Date().toISOString(),
          updatedAt: new Date().toISOString()
        },
        {
          id: 2,
          userId: 102,
          firstName: 'Michael',
          lastName: 'Johnson',
          email: 'dr.johnson@clinic.com',
          specialization: 'Cardiology',
          licenseNumber: 'CD67890',
          phone: '(555) 333-4444',
          createdAt: new Date().toISOString(),
          updatedAt: new Date().toISOString()
        }
      ]
    }
  } catch (error) {
    console.error('Error loading doctors:', error)
  }
}

const validateForm = () => {
  // Clear previous errors
  Object.keys(errors).forEach(key => delete errors[key])

  if (!selectedPatient.value) {
    errors.patient = 'Please select a patient'
  }

  if (!form.date) {
    errors.date = 'Date is required'
  }

  if (!form.time) {
    errors.time = 'Please select a time'
  }

  return Object.keys(errors).length === 0
}

const handleSubmit = async () => {
  if (!validateForm()) {
    return
  }

  isSubmitting.value = true

  try {
    const appointmentData = {
      patientId: selectedPatient.value!.id,
      doctorId: form.doctorId !== null ? form.doctorId : doctors.value.length > 0 ? doctors.value[0].id : 1, // Ensure doctorId is always a number
      appointmentDate: form.date,
      startTime: form.time,
      endTime: calculateEndTime(form.time),
      appointmentType: form.type,
      priority: 'normal' as const,
      status: 'scheduled' as const,
      notes: form.notes.trim(),
      followUpRequired: false,
    }

    // In a real app, this would call the API
    const newAppointment: Appointment = {
      id: Date.now(), // Mock ID
      ...appointmentData,
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString(),
      patient: selectedPatient.value!,
      doctor: doctors.value.find(d => d.id === appointmentData.doctorId)?.firstName + ' ' + doctors.value.find(d => d.id === appointmentData.doctorId)?.lastName
    }

    emit('scheduled', newAppointment)
  } catch (error: any) {
    console.error('Error scheduling appointment:', error)
    alert('Failed to schedule appointment: ' + (error.message || 'Unknown error'))
  } finally {
    isSubmitting.value = false
  }
}

const calculateEndTime = (startTime: string): string => {
  const [hours, minutes] = startTime.split(':').map(Number)
  const endDate = new Date()
  endDate.setHours(hours, minutes + 30) // Default 30-minute appointments
  
  return `${endDate.getHours().toString().padStart(2, '0')}:${endDate.getMinutes().toString().padStart(2, '0')}`
}

// Click outside handler
const handleClickOutside = (event: Event) => {
  const target = event.target as Element
  if (!target.closest('.relative')) {
    showPatientDropdown.value = false
  }
}

// Watch for clicks outside
watch(showPatientDropdown, (isOpen) => {
  if (isOpen) {
    document.addEventListener('click', handleClickOutside)
  } else {
    document.removeEventListener('click', handleClickOutside)
  }
})

onMounted(() => {
  loadDoctors()
  
  // Set default date to today if not provided
  if (!form.date) {
    form.date = format(new Date(), 'yyyy-MM-dd')
  }
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

.time-slot-button {
  @apply focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2;
}

.time-slot-button:hover:not(.bg-primary-500) {
  transform: translateY(-1px);
}

.selected-patient {
  border-left: 4px solid theme('colors.primary.500');
}

.form-error {
  @apply mt-1 text-sm text-red-600;
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
  
  .grid.grid-cols-3 {
    @apply grid-cols-2;
  }
}

/* Loading spinner */
.animate-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style>
