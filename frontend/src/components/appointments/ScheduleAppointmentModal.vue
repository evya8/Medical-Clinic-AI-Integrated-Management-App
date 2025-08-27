<template>
  <div class="modal-overlay fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" @click.self="$emit('close')">
    <div class="modal-content bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-hidden">
      <!-- Modal Header -->
      <div class="modal-header flex items-center justify-between p-6 border-b border-gray-200">
        <div>
          <h2 class="text-xl font-semibold text-gray-900">Schedule Appointment</h2>
          <p class="text-sm text-gray-600 mt-1">Schedule a new appointment for {{ patientFullName }}</p>
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
          <!-- Appointment Type -->
          <div>
            <label class="medical-form-label">Appointment Type *</label>
            <select
              v-model="form.appointmentType"
              required
              class="medical-input"
              :class="{ 'border-red-300': errors.appointmentType }"
            >
              <option value="">Select appointment type</option>
              <option value="consultation">General Consultation</option>
              <option value="follow-up">Follow-up Visit</option>
              <option value="checkup">Annual Checkup</option>
              <option value="urgent">Urgent Care</option>
              <option value="procedure">Medical Procedure</option>
              <option value="vaccination">Vaccination</option>
              <option value="laboratory">Laboratory Work</option>
              <option value="other">Other</option>
            </select>
            <p v-if="errors.appointmentType" class="form-error">{{ errors.appointmentType }}</p>
          </div>

          <!-- Doctor Selection -->
          <div>
            <label class="medical-form-label">Doctor *</label>
            <select
              v-model="form.doctorId"
              required
              class="medical-input"
              :class="{ 'border-red-300': errors.doctorId }"
              @change="loadAvailableSlots"
            >
              <option value="">Select a doctor</option>
              <option 
                v-for="doctor in doctors" 
                :key="doctor.id" 
                :value="doctor.id"
              >
                Dr. {{ doctor.firstName }} {{ doctor.lastName }} - {{ doctor.specialty }}
              </option>
            </select>
            <p v-if="errors.doctorId" class="form-error">{{ errors.doctorId }}</p>
          </div>

          <!-- Date Selection -->
          <div>
            <label class="medical-form-label">Appointment Date *</label>
            <input
              v-model="form.appointmentDate"
              type="date"
              required
              :min="minDate"
              class="medical-input"
              :class="{ 'border-red-300': errors.appointmentDate }"
              @change="loadAvailableSlots"
            />
            <p v-if="errors.appointmentDate" class="form-error">{{ errors.appointmentDate }}</p>
          </div>

          <!-- Time Selection -->
          <div v-if="availableSlots.length > 0">
            <label class="medical-form-label">Available Time Slots *</label>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
              <button
                v-for="slot in availableSlots"
                :key="slot"
                type="button"
                @click="form.startTime = slot"
                class="time-slot-button p-3 text-sm font-medium rounded-lg border transition-all duration-200"
                :class="form.startTime === slot 
                  ? 'bg-primary-500 text-white border-primary-500' 
                  : 'bg-white text-gray-700 border-gray-300 hover:border-primary-300 hover:bg-primary-50'"
              >
                {{ formatTime(slot) }}
              </button>
            </div>
            <p v-if="errors.startTime" class="form-error">{{ errors.startTime }}</p>
          </div>

          <!-- Loading Available Slots -->
          <div v-else-if="loadingSlots && form.doctorId && form.appointmentDate" class="flex items-center justify-center py-8">
            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-primary-600"></div>
            <span class="ml-3 text-gray-600">Loading available time slots...</span>
          </div>

          <!-- No Slots Available -->
          <div v-else-if="form.doctorId && form.appointmentDate && !loadingSlots" class="text-center py-8">
            <ClockIcon class="w-8 h-8 text-gray-300 mx-auto mb-2" />
            <p class="text-gray-600">No available time slots for the selected date.</p>
            <p class="text-sm text-gray-500 mt-1">Please try a different date.</p>
          </div>

          <!-- Priority Level -->
          <div>
            <label class="medical-form-label">Priority Level</label>
            <select v-model="form.priority" class="medical-input">
              <option value="low">Low - Routine care</option>
              <option value="normal">Normal - Standard appointment</option>
              <option value="high">High - Needs attention soon</option>
              <option value="urgent">Urgent - Immediate attention required</option>
            </select>
          </div>

          <!-- Appointment Notes -->
          <div>
            <label class="medical-form-label">Appointment Notes</label>
            <textarea
              v-model="form.notes"
              rows="3"
              class="medical-input"
              placeholder="Reason for visit, symptoms, or any special instructions..."
            ></textarea>
            <p class="form-help">Provide context for the appointment to help the doctor prepare.</p>
          </div>

          <!-- Duration -->
          <div>
            <label class="medical-form-label">Expected Duration</label>
            <select v-model="form.duration" class="medical-input">
              <option value="15">15 minutes</option>
              <option value="30">30 minutes</option>
              <option value="45">45 minutes</option>
              <option value="60">1 hour</option>
              <option value="90">1.5 hours</option>
              <option value="120">2 hours</option>
            </select>
          </div>

          <!-- Follow-up Settings -->
          <div class="follow-up-section bg-gray-50 p-4 rounded-lg">
            <label class="flex items-center">
              <input
                v-model="form.followUpRequired"
                type="checkbox"
                class="medical-checkbox"
              />
              <span class="ml-2 text-sm font-medium text-gray-700">Follow-up appointment required</span>
            </label>
            
            <div v-if="form.followUpRequired" class="mt-4">
              <label class="medical-form-label">Follow-up Date</label>
              <input
                v-model="form.followUpDate"
                type="date"
                :min="minFollowUpDate"
                class="medical-input"
              />
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
          {{ isSubmitting ? 'Scheduling...' : 'Schedule Appointment' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, reactive, onMounted, watch } from 'vue'
import { format, addDays } from 'date-fns'
import { XMarkIcon, ClockIcon } from '@heroicons/vue/24/outline'
import type { Patient, Doctor, Appointment } from '@/types/api.types'
import { api, API_ENDPOINTS } from '@/services/api'

interface Props {
  patient: Patient
}

interface Emits {
  (e: 'close'): void
  (e: 'scheduled', appointment: Appointment): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Form state
const form = reactive({
  appointmentType: 'consultation',
  doctorId: null as number | null,
  appointmentDate: '',
  startTime: '',
  duration: '30',
  priority: 'normal',
  notes: '',
  followUpRequired: false,
  followUpDate: '',
})

const errors = reactive<Record<string, string>>({})
const isSubmitting = ref(false)
const loadingSlots = ref(false)
const doctors = ref<Doctor[]>([])
const availableSlots = ref<string[]>([])

// Computed
const patientFullName = computed(() => {
  return `${props.patient.firstName} ${props.patient.lastName}`.trim()
})

const minDate = computed(() => {
  return format(new Date(), 'yyyy-MM-dd')
})

const minFollowUpDate = computed(() => {
  if (form.appointmentDate) {
    return format(addDays(new Date(form.appointmentDate), 1), 'yyyy-MM-dd')
  }
  return format(addDays(new Date(), 1), 'yyyy-MM-dd')
})

const isFormValid = computed(() => {
  return form.appointmentType &&
         form.doctorId &&
         form.appointmentDate &&
         form.startTime &&
         Object.keys(errors).length === 0
})

// Methods
const loadDoctors = async () => {
  try {
    const response = await api.get<Doctor[]>(API_ENDPOINTS.DOCTORS.LIST)
    if (response.success && response.data) {
      doctors.value = response.data
    }
  } catch (error) {
    console.error('Error loading doctors:', error)
  }
}

const loadAvailableSlots = async () => {
  if (!form.doctorId || !form.appointmentDate) {
    availableSlots.value = []
    return
  }

  loadingSlots.value = true
  try {
    const response = await api.get<string[]>(
      `${API_ENDPOINTS.DOCTORS.AVAILABILITY(form.doctorId)}?date=${form.appointmentDate}`
    )
    
    if (response.success && response.data) {
      availableSlots.value = response.data
    } else {
      availableSlots.value = []
    }
  } catch (error) {
    console.error('Error loading available slots:', error)
    // Fallback to mock time slots for demo
    availableSlots.value = generateMockTimeSlots()
  } finally {
    loadingSlots.value = false
  }
}

const generateMockTimeSlots = (): string[] => {
  // Generate time slots from 9:00 AM to 5:00 PM in 30-minute intervals
  const slots = []
  for (let hour = 9; hour < 17; hour++) {
    slots.push(`${hour.toString().padStart(2, '0')}:00`)
    if (hour < 16) { // Don't add :30 for 4:30 PM (last slot should be 4:00 PM)
      slots.push(`${hour.toString().padStart(2, '0')}:30`)
    }
  }
  return slots
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

const validateForm = () => {
  // Clear previous errors
  Object.keys(errors).forEach(key => delete errors[key])

  // Validate required fields
  if (!form.appointmentType) {
    errors.appointmentType = 'Appointment type is required'
  }

  if (!form.doctorId) {
    errors.doctorId = 'Please select a doctor'
  }

  if (!form.appointmentDate) {
    errors.appointmentDate = 'Appointment date is required'
  }

  if (!form.startTime) {
    errors.startTime = 'Please select a time slot'
  }

  // Validate appointment date is not in the past
  if (form.appointmentDate && new Date(form.appointmentDate) < new Date()) {
    errors.appointmentDate = 'Appointment date cannot be in the past'
  }

  return Object.keys(errors).length === 0
}

const handleSubmit = async () => {
  if (!validateForm()) {
    return
  }

  isSubmitting.value = true

  try {
    // Calculate end time based on duration
    const startTime = new Date(`2000-01-01 ${form.startTime}`)
    const endTime = new Date(startTime.getTime() + parseInt(form.duration) * 60000)
    
    const appointmentData = {
      patientId: props.patient.id,
      doctorId: form.doctorId,
      appointmentDate: form.appointmentDate,
      startTime: form.startTime,
      endTime: format(endTime, 'HH:mm'),
      appointmentType: form.appointmentType,
      priority: form.priority,
      status: 'scheduled',
      notes: form.notes.trim() || null,
      followUpRequired: form.followUpRequired,
      followUpDate: form.followUpRequired ? form.followUpDate : null,
    }

    // Call API to create appointment
    const response = await api.post<Appointment>(
      API_ENDPOINTS.APPOINTMENTS.CREATE,
      appointmentData
    )

    if (response.success && response.data) {
      emit('scheduled', response.data)
    } else {
      throw new Error(response.message || 'Failed to schedule appointment')
    }
  } catch (error: any) {
    console.error('Error scheduling appointment:', error)
    // Add error handling
    alert('Failed to schedule appointment: ' + (error.message || 'Unknown error'))
  } finally {
    isSubmitting.value = false
  }
}

// Watch for changes that require reloading slots
watch([() => form.doctorId, () => form.appointmentDate], () => {
  form.startTime = '' // Clear selected time when doctor or date changes
  loadAvailableSlots()
})

// Initialize
onMounted(() => {
  loadDoctors()
  // Set default date to tomorrow
  form.appointmentDate = format(addDays(new Date(), 1), 'yyyy-MM-dd')
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

.form-error {
  @apply mt-1 text-sm text-red-600;
}

.form-help {
  @apply mt-1 text-sm text-gray-500;
}

.follow-up-section {
  @apply border border-gray-200;
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
  
  .modal-header, .modal-body, .modal-footer {
    @apply px-4;
  }
  
  .grid.md\:grid-cols-3 {
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
