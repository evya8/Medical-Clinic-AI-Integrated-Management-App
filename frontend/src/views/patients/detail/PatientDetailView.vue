<template>
  <AppLayout>
    <div class="patient-detail-container">
      <!-- Loading State -->
      <div v-if="isLoading" class="flex items-center justify-center py-12">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
        <span class="ml-3 text-gray-600">Loading patient information...</span>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="medical-card p-6 border-red-200 bg-red-50">
        <div class="flex items-center">
          <ExclamationTriangleIcon class="w-5 h-5 text-red-500 mr-3" />
          <div>
            <h3 class="text-red-800 font-medium">Error Loading Patient</h3>
            <p class="text-red-700 mt-1">{{ error }}</p>
          </div>
        </div>
        <div class="mt-4">
          <button @click="loadPatient" class="medical-button-secondary mr-3">
            Try Again
          </button>
          <RouterLink to="/patients" class="medical-button-outline">
            Back to Patients
          </RouterLink>
        </div>
      </div>

      <!-- Patient Detail Content -->
      <div v-else-if="patient" class="space-y-6">
        <!-- Patient Header -->
        <div class="patient-header medical-card p-6">
          <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center mb-4 lg:mb-0">
              <!-- Patient Avatar -->
              <div class="flex-shrink-0 w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center mr-6">
                <span class="text-2xl font-medium text-primary-700">
                  {{ patientInitials }}
                </span>
              </div>
              
              <!-- Patient Basic Info -->
              <div>
                <h1 class="text-2xl font-bold text-gray-900">
                  {{ patientFullName }}
                </h1>
                <div class="flex flex-wrap items-center mt-2 text-sm text-gray-600 space-x-4">
                  <span>ID: {{ patient.id.toString().padStart(4, '0') }}</span>
                  <span>{{ patientAge }}</span>
                  <span>{{ patientGender }}</span>
                  <span v-if="patient.bloodType" class="inline-flex items-center px-2 py-1 rounded-full bg-red-100 text-red-800 text-xs font-medium">
                    {{ patient.bloodType }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Quick Actions -->
            <div class="flex items-center space-x-3">
              <button
                @click="openEditModal"
                class="medical-button-secondary flex items-center"
              >
                <PencilIcon class="w-4 h-4 mr-2" />
                Edit Patient
              </button>
              <button
                @click="scheduleAppointment"
                class="medical-button-primary flex items-center"
              >
                <CalendarIcon class="w-4 h-4 mr-2" />
                Schedule Appointment
              </button>
            </div>
          </div>

          <!-- Medical Alerts -->
          <div v-if="hasAllergies || hasMedicalAlerts" class="mt-6 pt-4 border-t border-gray-200">
            <div class="flex flex-wrap gap-3">
              <!-- Allergies Alert -->
              <div v-if="hasAllergies" class="inline-flex items-center px-3 py-1 rounded-full bg-yellow-100 text-yellow-800 text-sm font-medium">
                <ExclamationTriangleIcon class="w-4 h-4 mr-2" />
                Allergies: {{ patient.allergies }}
              </div>
              
              <!-- Other Medical Alerts -->
              <div v-for="alert in medicalAlerts" :key="alert.id" :class="getAlertClasses(alert.priority)">
                <ExclamationCircleIcon class="w-4 h-4 mr-2" />
                {{ alert.message }}
              </div>
            </div>
          </div>
        </div>

        <!-- Contact Information Quick View -->
        <div class="contact-quick-view medical-card p-4">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="flex items-center">
              <EnvelopeIcon class="w-4 h-4 text-gray-400 mr-3" />
              <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide">Email</p>
                <p class="font-medium">{{ patient.email || 'Not provided' }}</p>
              </div>
            </div>
            <div class="flex items-center">
              <PhoneIcon class="w-4 h-4 text-gray-400 mr-3" />
              <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide">Phone</p>
                <p class="font-medium">{{ patient.phone || 'Not provided' }}</p>
              </div>
            </div>
            <div class="flex items-center">
              <MapPinIcon class="w-4 h-4 text-gray-400 mr-3" />
              <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide">Address</p>
                <p class="font-medium text-sm">{{ patientAddress || 'Not provided' }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Tab Navigation -->
        <div class="tabs-container">
          <div class="medical-card">
            <!-- Tab Headers -->
            <div class="border-b border-gray-200">
              <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                <button
                  v-for="tab in tabs"
                  :key="tab.id"
                  @click="activeTab = tab.id"
                  :class="[
                    'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200',
                    activeTab === tab.id
                      ? 'border-primary-500 text-primary-600'
                      : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                  ]"
                >
                  <component :is="tab.icon" class="w-5 h-5 mr-2 inline" />
                  {{ tab.name }}
                  <span v-if="tab.badge" class="ml-2 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-primary-600 bg-primary-100 rounded-full">
                    {{ tab.badge }}
                  </span>
                </button>
              </nav>
            </div>

            <!-- Tab Content -->
            <div class="tab-content p-6">
              <!-- Basic Information Tab -->
              <div v-show="activeTab === 'basic'" class="tab-panel">
                <BasicInformationTab :patient="patient" @update="handlePatientUpdate" />
              </div>

              <!-- Medical History Tab -->
              <div v-show="activeTab === 'medical'" class="tab-panel">
                <MedicalHistoryTab :patient="patient" :medical-history="medicalHistory" />
              </div>

              <!-- Appointments Tab -->
              <div v-show="activeTab === 'appointments'" class="tab-panel">
                <AppointmentsTab :patient="patient" :appointments="appointments" @schedule="scheduleAppointment" />
              </div>

              <!-- Medical Records Tab -->
              <div v-show="activeTab === 'records'" class="tab-panel">
                <MedicalRecordsTab :patient="patient" :records="medicalRecords" />
              </div>

              <!-- Billing Tab -->
              <div v-show="activeTab === 'billing'" class="tab-panel">
                <BillingTab :patient="patient" :billing-history="billingHistory" />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Patient Modal -->
    <PatientEditModal
      v-if="showEditModal"
      :patient="patient"
      @close="closeEditModal"
      @update="handlePatientUpdate"
    />

    <!-- Schedule Appointment Modal -->
    <ScheduleAppointmentModal
      v-if="showScheduleModal"
      :patient="patient"
      @close="closeScheduleModal"
      @scheduled="handleAppointmentScheduled"
    />
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { differenceInYears } from 'date-fns'
import {
  PencilIcon,
  CalendarIcon,
  EnvelopeIcon,
  PhoneIcon,
  MapPinIcon,
  UserIcon,
  HeartIcon,
  CreditCardIcon,
  ExclamationTriangleIcon,
  ExclamationCircleIcon,
  ClipboardDocumentListIcon,
} from '@heroicons/vue/24/outline'

import AppLayout from '@/components/common/AppLayout.vue'
import BasicInformationTab from './tabs/BasicInformationTab.vue'
import MedicalHistoryTab from './tabs/MedicalHistoryTab.vue'
import AppointmentsTab from './tabs/AppointmentsTab.vue'
import MedicalRecordsTab from './tabs/MedicalRecordsTab.vue'
import BillingTab from './tabs/BillingTab.vue'
import PatientEditModal from '@/components/patients/modals/PatientEditModal.vue'
import ScheduleAppointmentModal from '@/components/appointments/ScheduleAppointmentModal.vue'

import type { Patient, Appointment, MedicalRecord, MedicalAlert, MedicalHistoryEntry} from '@/types/api.types'
import { api, API_ENDPOINTS } from '@/services/api'
import { useNotifications } from '@/stores/notifications'

const route = useRoute()
const router = useRouter()
const { addNotification } = useNotifications()

// State
const patient = ref<Patient | null>(null)
const isLoading = ref(true)
const error = ref<string | null>(null)
const activeTab = ref('basic')
const showEditModal = ref(false)
const showScheduleModal = ref(false)

// Tab data
const medicalHistory = ref<MedicalHistoryEntry[]>([])
const appointments = ref<Appointment[]>([])
const medicalRecords = ref<MedicalRecord[]>([])
const medicalAlerts = ref<MedicalAlert[]>([])

// Tab configuration
const tabs = [
  { id: 'basic', name: 'Basic Info', icon: UserIcon, badge: null },
  { id: 'medical', name: 'Medical History', icon: HeartIcon, badge: null },
  { id: 'appointments', name: 'Appointments', icon: CalendarIcon, badge: null },
  { id: 'records', name: 'Medical Records', icon: ClipboardDocumentListIcon, badge: null },
  { id: 'billing', name: 'Billing', icon: CreditCardIcon, badge: null },
]

// Computed
const patientId = computed(() => parseInt(route.params.id as string))

const patientFullName = computed(() => {
  if (!patient.value) return ''
  return `${patient.value.firstName} ${patient.value.lastName}`.trim()
})

const patientInitials = computed(() => {
  if (!patient.value) return 'P'
  const firstName = patient.value.firstName || ''
  const lastName = patient.value.lastName || ''
  return `${firstName.charAt(0)}${lastName.charAt(0)}`.toUpperCase()
})

const patientAge = computed(() => {
  if (!patient.value?.dateOfBirth) return 'Unknown age'
  try {
    const birthDate = new Date(patient.value.dateOfBirth)
    const age = differenceInYears(new Date(), birthDate)
    return `${age} years old`
  } catch {
    return 'Unknown age'
  }
})

const patientGender = computed(() => {
  if (!patient.value?.gender) return 'Gender not specified'
  
  const genderMap = {
    male: 'Male',
    female: 'Female',
    other: 'Other',
    prefer_not_to_say: 'Prefer not to say',
  }
  
  return genderMap[patient.value.gender] || 'Other'
})

const patientAddress = computed(() => {
  if (!patient.value?.address) return null
  return patient.value.address
})

const hasAllergies = computed(() => {
  return patient.value?.allergies && 
         patient.value.allergies.toLowerCase() !== 'none known' && 
         patient.value.allergies.toLowerCase() !== 'none'
})

const hasMedicalAlerts = computed(() => {
  return medicalAlerts.value.length > 0
})

// Methods
const loadPatient = async () => {
  if (!patientId.value) {
    error.value = 'Invalid patient ID'
    isLoading.value = false
    return
  }

  try {
    isLoading.value = true
    error.value = null

    // Load patient basic information
    const patientResponse = await api.get<Patient>(API_ENDPOINTS.PATIENTS.GET(patientId.value))
    if (patientResponse.success && patientResponse.data) {
      patient.value = patientResponse.data
    } else {
      throw new Error('Failed to load patient information')
    }

    // Load additional data in parallel
    await Promise.all([
      loadAppointments(),
      loadMedicalRecords(),
      loadMedicalAlerts(),
    ])

  } catch (err: any) {
    error.value = err.message || 'Failed to load patient information'
    console.error('Error loading patient:', err)
  } finally {
    isLoading.value = false
  }
}

const loadAppointments = async () => {
  try {
    const response = await api.get<Appointment[]>(API_ENDPOINTS.APPOINTMENTS.BY_PATIENT(patientId.value))
    if (response.success && response.data) {
      appointments.value = response.data
      // Update tab badge
      const appointmentTab = tabs.find(tab => tab.id === 'appointments')
      if (appointmentTab) {
        appointmentTab.badge = appointments.value.length
      }
    }
  } catch (err) {
    console.error('Error loading appointments:', err)
  }
}

const loadMedicalRecords = async () => {
  try {
    // This would be a real API call in production
    // const response = await api.get<MedicalRecord[]>(`/patients/${patientId.value}/medical-records`)
    // For now, we'll use mock data
    medicalRecords.value = []
  } catch (err) {
    console.error('Error loading medical records:', err)
  }
}

const loadMedicalAlerts = async () => {
  try {
    // This would be a real API call in production
    medicalAlerts.value = []
  } catch (err) {
    console.error('Error loading medical alerts:', err)
  }
}

const getAlertClasses = (priority: string) => {
  const baseClasses = 'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium'
  switch (priority) {
    case 'high':
      return `${baseClasses} bg-red-100 text-red-800`
    case 'medium':
      return `${baseClasses} bg-yellow-100 text-yellow-800`
    case 'low':
      return `${baseClasses} bg-blue-100 text-blue-800`
    default:
      return `${baseClasses} bg-gray-100 text-gray-800`
  }
}

const openEditModal = () => {
  showEditModal.value = true
}

const closeEditModal = () => {
  showEditModal.value = false
  // Remove edit query parameter
  if (route.query.edit) {
    router.replace({ path: route.path })
  }
}

const scheduleAppointment = () => {
  showScheduleModal.value = true
}

const closeScheduleModal = () => {
  showScheduleModal.value = false
}

const handlePatientUpdate = (updatedPatient: Patient) => {
  patient.value = updatedPatient
  closeEditModal()
  addNotification({
    type: 'success',
    title: 'Patient Updated',
    message: 'Patient information has been successfully updated.',
  })
}

const handleAppointmentScheduled = (appointment: Appointment) => {
  appointments.value.unshift(appointment)
  closeScheduleModal()
  addNotification({
    type: 'success',
    title: 'Appointment Scheduled',
    message: 'New appointment has been scheduled successfully.',
  })
}

// Lifecycle
onMounted(() => {
  loadPatient()
  
  // Check if edit modal should be opened
  if (route.query.edit === 'true') {
    showEditModal.value = true
  }
})

// Watch for route changes
watch(() => route.params.id, () => {
  if (route.params.id) {
    loadPatient()
  }
})

// Watch for query changes (like edit parameter)
watch(() => route.query.edit, (newValue) => {
  if (newValue === 'true') {
    showEditModal.value = true
  }
})
</script>

<style lang="postcss" scoped>
.patient-detail-container {
  @apply max-w-7xl mx-auto;
}

.patient-header {
  @apply relative;
}

.contact-quick-view {
  @apply bg-gray-50 border-gray-200;
}

.tab-panel {
  @apply min-h-[400px];
}

/* Smooth tab transitions */
.tab-content {
  @apply relative;
}

.tab-panel {
  @apply animate-fade-in;
}

@keyframes fade-in {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fade-in {
  animation: fade-in 0.3s ease-out forwards;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .patient-header {
    @apply p-4;
  }
  
  .tabs-container .medical-card {
    @apply mx-0 rounded-none;
  }
  
  .tab-content {
    @apply p-4;
  }
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
