<template>
  <AppLayout>
    <div class="ai-triage-container">
      <!-- Page Header -->
      <div class="page-header mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900 flex items-center">
              <HeartIcon class="w-8 h-8 mr-3 text-red-600" />
              AI Patient Triage
            </h1>
            <p class="mt-2 text-sm text-gray-700">
              Intelligent patient prioritization and urgency assessment
            </p>
          </div>

          <div class="flex items-center space-x-4">
            <!-- Triage Queue Status -->
            <div class="flex items-center space-x-2">
              <div class="w-2 h-2 bg-green-400 rounded-full"></div>
              <span class="text-sm text-gray-600">{{ triageQueue.length }} in queue</span>
            </div>

            <!-- New Triage Button -->
            <button
              @click="showTriageModal = true"
              class="medical-button-primary"
            >
              <PlusIcon class="w-4 h-4 mr-2" />
              New Triage
            </button>
          </div>
        </div>
      </div>

      <!-- Triage Statistics -->
      <div class="triage-stats grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="medical-card p-6 bg-gradient-to-br from-red-50 to-pink-50 border border-red-100">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-red-700 font-medium mb-1">Critical</p>
              <p class="text-2xl font-bold text-red-900">{{ triageStats.critical }}</p>
            </div>
            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
              <ExclamationTriangleIcon class="w-6 h-6 text-red-600" />
            </div>
          </div>
        </div>

        <div class="medical-card p-6 bg-gradient-to-br from-orange-50 to-yellow-50 border border-orange-100">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-orange-700 font-medium mb-1">Urgent</p>
              <p class="text-2xl font-bold text-orange-900">{{ triageStats.urgent }}</p>
            </div>
            <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
              <ClockIcon class="w-6 h-6 text-orange-600" />
            </div>
          </div>
        </div>

        <div class="medical-card p-6 bg-gradient-to-br from-yellow-50 to-green-50 border border-yellow-100">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-yellow-700 font-medium mb-1">Standard</p>
              <p class="text-2xl font-bold text-yellow-900">{{ triageStats.standard }}</p>
            </div>
            <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
              <UserIcon class="w-6 h-6 text-yellow-600" />
            </div>
          </div>
        </div>

        <div class="medical-card p-6 bg-gradient-to-br from-green-50 to-blue-50 border border-green-100">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-green-700 font-medium mb-1">Low Priority</p>
              <p class="text-2xl font-bold text-green-900">{{ triageStats.low }}</p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
              <CheckCircleIcon class="w-6 h-6 text-green-600" />
            </div>
          </div>
        </div>
      </div>

      <!-- Triage Queue -->
      <div class="triage-queue-container">
        <div class="medical-card">
          <div class="card-header flex items-center justify-between p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Triage Queue</h3>
            <div class="flex items-center space-x-4">
              <!-- Filter Controls -->
              <select 
                v-model="priorityFilter" 
                @change="filterTriageQueue"
                class="form-select text-sm"
              >
                <option value="">All Priorities</option>
                <option value="critical">Critical</option>
                <option value="urgent">Urgent</option>
                <option value="standard">Standard</option>
                <option value="low">Low Priority</option>
              </select>

              <!-- Refresh Button -->
              <button 
                @click="refreshTriageQueue"
                :disabled="isRefreshing"
                class="medical-button-secondary"
              >
                <ArrowPathIcon class="w-4 h-4 mr-2" :class="{ 'animate-spin': isRefreshing }" />
                Refresh
              </button>
            </div>
          </div>

          <div class="p-6">
            <div v-if="isLoadingQueue" class="space-y-4">
              <div v-for="n in 5" :key="n" class="skeleton h-20 rounded-lg"></div>
            </div>

            <div v-else-if="filteredTriageQueue.length === 0" class="text-center py-12">
              <HeartIcon class="mx-auto h-12 w-12 text-gray-400 mb-4" />
              <h4 class="text-lg font-medium text-gray-900 mb-2">No patients in queue</h4>
              <p class="text-gray-500 mb-6">All patients have been triaged or queue is empty.</p>
              <button @click="showTriageModal = true" class="medical-button-primary">
                Start New Triage
              </button>
            </div>

            <div v-else class="space-y-4">
              <div
                v-for="patient in filteredTriageQueue"
                :key="patient.id"
                class="triage-item p-6 border rounded-lg"
                :class="getTriageBorderClass(patient.priority)"
              >
                <div class="flex items-start justify-between">
                  <div class="flex-1">
                    <div class="flex items-center mb-3">
                      <h4 class="text-lg font-semibold text-gray-900 mr-3">{{ patient.name }}</h4>
                      <span 
                        :class="[
                          'px-3 py-1 text-xs font-semibold rounded-full',
                          getPriorityClass(patient.priority)
                        ]"
                      >
                        {{ patient.priority.toUpperCase() }}
                      </span>
                      <span class="ml-3 text-sm text-gray-500">
                        Score: {{ patient.urgencyScore }}/5
                      </span>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-4">
                      <!-- Patient Info -->
                      <div>
                        <h5 class="text-sm font-medium text-gray-900 mb-2">Patient Information</h5>
                        <div class="space-y-1 text-sm text-gray-600">
                          <p>Age: {{ patient.age }} • {{ patient.gender }}</p>
                          <p>Chief Complaint: {{ patient.chiefComplaint }}</p>
                          <p>Arrival: {{ formatTime(patient.arrivalTime) }}</p>
                        </div>
                      </div>

                      <!-- AI Assessment -->
                      <div>
                        <h5 class="text-sm font-medium text-gray-900 mb-2">AI Assessment</h5>
                        <div class="space-y-1 text-sm">
                          <p class="text-gray-600">Confidence: {{ Math.round(patient.confidence * 100) }}%</p>
                          <p class="text-gray-600">Wait Time: {{ patient.estimatedWait }} min</p>
                          <p v-if="patient.redFlags.length > 0" class="text-red-600 font-medium">
                            🚩 {{ patient.redFlags.length }} red flag{{ patient.redFlags.length !== 1 ? 's' : '' }}
                          </p>
                        </div>
                      </div>

                      <!-- Recommendations -->
                      <div>
                        <h5 class="text-sm font-medium text-gray-900 mb-2">Recommendations</h5>
                        <div class="space-y-1">
                          <div v-if="patient.recommendedSpecialty" class="text-sm text-blue-600">
                            → {{ patient.recommendedSpecialty }}
                          </div>
                          <div v-if="patient.suggestedTests.length > 0" class="text-xs text-gray-600">
                            Tests: {{ patient.suggestedTests.join(', ') }}
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- AI Reasoning -->
                    <div v-if="patient.aiReasoning" class="mb-4">
                      <h5 class="text-sm font-medium text-gray-900 mb-2">AI Analysis</h5>
                      <p class="text-sm text-gray-700 bg-gray-50 p-3 rounded-lg">
                        {{ patient.aiReasoning }}
                      </p>
                    </div>

                    <!-- Red Flags -->
                    <div v-if="patient.redFlags.length > 0" class="mb-4">
                      <h5 class="text-sm font-medium text-red-900 mb-2 flex items-center">
                        <ExclamationTriangleIcon class="w-4 h-4 mr-1" />
                        Red Flags
                      </h5>
                      <div class="space-y-1">
                        <div 
                          v-for="flag in patient.redFlags" 
                          :key="flag"
                          class="text-sm text-red-700 bg-red-50 p-2 rounded border border-red-200"
                        >
                          ⚠️ {{ flag }}
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Action Buttons -->
                  <div class="ml-6 flex flex-col space-y-2">
                    <button
                      @click="assignToDoctor(patient)"
                      class="medical-button-primary text-sm"
                    >
                      <UserIcon class="w-4 h-4 mr-1" />
                      Assign
                    </button>
                    <button
                      @click="viewPatientDetail(patient)"
                      class="medical-button-secondary text-sm"
                    >
                      <EyeIcon class="w-4 h-4 mr-1" />
                      Details
                    </button>
                    <button
                      @click="retriage(patient)"
                      class="text-sm text-gray-600 hover:text-gray-800"
                    >
                      <ArrowPathIcon class="w-4 h-4 mr-1" />
                      Re-assess
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- New Triage Modal -->
      <div v-if="showTriageModal" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center">
          <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="closeTriageModal"></div>
          
          <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
            <form @submit.prevent="performTriage">
              <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                  <div class="w-full">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-6">
                      New Patient Triage Assessment
                    </h3>

                    <!-- Patient Selection -->
                    <div class="mb-6">
                      <label class="block text-sm font-medium text-gray-700 mb-2">
                        Select Patient
                      </label>
                      <select 
                        v-model="triageForm.patientId" 
                        required
                        class="form-select w-full"
                      >
                        <option value="">Choose a patient...</option>
                        <option 
                          v-for="patient in availablePatients" 
                          :key="patient.id" 
                          :value="patient.id"
                        >
                          {{ patient.firstName }} {{ patient.lastName }} - {{ calculateAge(patient.dateOfBirth) }}yo {{ patient.gender }}
                        </option>
                      </select>
                    </div>

                    <!-- Chief Complaint -->
                    <div class="mb-6">
                      <label class="block text-sm font-medium text-gray-700 mb-2">
                        Chief Complaint
                      </label>
                      <textarea 
                        v-model="triageForm.chiefComplaint"
                        required
                        rows="3"
                        class="form-textarea w-full"
                        placeholder="Describe the patient's primary concern..."
                      ></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                      <!-- Symptoms -->
                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                          Current Symptoms
                        </label>
                        <textarea 
                          v-model="triageForm.symptoms"
                          rows="4"
                          class="form-textarea w-full"
                          placeholder="List current symptoms, severity, duration..."
                        ></textarea>
                      </div>

                      <!-- Vital Signs -->
                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                          Vital Signs
                        </label>
                        <div class="space-y-3">
                          <input 
                            v-model="triageForm.vitalSigns.bloodPressure"
                            type="text" 
                            placeholder="Blood Pressure (e.g., 120/80)"
                            class="form-input w-full text-sm"
                          />
                          <input 
                            v-model="triageForm.vitalSigns.heartRate"
                            type="number" 
                            placeholder="Heart Rate (BPM)"
                            class="form-input w-full text-sm"
                          />
                          <input 
                            v-model="triageForm.vitalSigns.temperature"
                            type="number" 
                            step="0.1"
                            placeholder="Temperature (°F)"
                            class="form-input w-full text-sm"
                          />
                          <input 
                            v-model="triageForm.vitalSigns.oxygenSaturation"
                            type="number" 
                            placeholder="O2 Saturation (%)"
                            class="form-input w-full text-sm"
                          />
                        </div>
                      </div>
                    </div>

                    <!-- Medical History -->
                    <div class="mb-6">
                      <label class="block text-sm font-medium text-gray-700 mb-2">
                        Relevant Medical History
                      </label>
                      <textarea 
                        v-model="triageForm.medicalHistory"
                        rows="2"
                        class="form-textarea w-full"
                        placeholder="Previous conditions, medications, allergies..."
                      ></textarea>
                    </div>

                    <!-- Pain Scale -->
                    <div class="mb-6">
                      <label class="block text-sm font-medium text-gray-700 mb-2">
                        Pain Level (1-10 scale)
                      </label>
                      <select v-model="triageForm.painLevel" class="form-select">
                        <option value="">Not applicable</option>
                        <option v-for="n in 10" :key="n" :value="n">{{ n }} - {{ getPainDescription(n) }}</option>
                      </select>
                    </div>
                  </div>
                </div>
              </div>

              <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button 
                  type="submit"
                  :disabled="isProcessingTriage"
                  class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
                >
                  {{ isProcessingTriage ? 'Analyzing...' : 'Analyze with AI' }}
                </button>
                <button 
                  type="button"
                  @click="closeTriageModal"
                  :disabled="isProcessingTriage"
                  class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                >
                  Cancel
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import {
  HeartIcon,
  PlusIcon,
  ExclamationTriangleIcon,
  ClockIcon,
  UserIcon,
  CheckCircleIcon,
  ArrowPathIcon,
  EyeIcon,
} from '@heroicons/vue/24/outline'
import AppLayout from '@/components/common/AppLayout.vue'
import { useNotifications } from '@/stores/notifications'
import { formatDistance } from 'date-fns'
import type { TriagePatient, TriageFormData, Patient } from '@/types/api.types'
import { api, handleApiError, API_ENDPOINTS } from '@/services/api'

const { success, error } = useNotifications()

// State
const showTriageModal = ref(false)
const isLoadingQueue = ref(true)
const isRefreshing = ref(false)
const isProcessingTriage = ref(false)
const priorityFilter = ref('')

// Triage Statistics
const triageStats = ref({
  critical: 2,
  urgent: 5,
  standard: 12,
  low: 8,
})

// Triage Queue with proper typing
const triageQueue = ref<TriagePatient[]>([])

// Available patients for new triage with proper typing
const availablePatients = ref<Pick<Patient, 'id' | 'firstName' | 'lastName' | 'dateOfBirth' | 'gender'>[]>([])

// Triage form with proper typing
const triageForm = ref<TriageFormData>({
  patientId: '',
  chiefComplaint: '',
  symptoms: '',
  vitalSigns: {
    bloodPressure: '',
    heartRate: null,
    temperature: null,
    oxygenSaturation: null,
  },
  medicalHistory: '',
  painLevel: '',
})

// Computed
const filteredTriageQueue = computed(() => {
  if (!priorityFilter.value) return triageQueue.value
  return triageQueue.value.filter(patient => patient.priority === priorityFilter.value)
})

// Methods
const refreshTriageQueue = async () => {
  isRefreshing.value = true
  
  try {
    const response = await api.get<TriagePatient[]>('/ai-triage/queue')
    if (response.success && response.data) {
      triageQueue.value = response.data
      updateTriageStats()
      success('Queue updated', 'Triage queue has been refreshed')
    } else {
      throw new Error(response.message || 'Failed to fetch triage queue')
    }
  } catch (err) {
    error('Update failed', handleApiError(err))
    console.error('Failed to refresh triage queue:', err)
  } finally {
    isRefreshing.value = false
  }
}

const filterTriageQueue = () => {
  // Filter is reactive through computed property
}

const performTriage = async () => {
  isProcessingTriage.value = true
  
  try {
    // Call AI triage API
    const response = await api.post<TriagePatient>('/ai-triage/analyze', {
      patient_id: triageForm.value.patientId,
      chief_complaint: triageForm.value.chiefComplaint,
      symptoms: triageForm.value.symptoms,
      vital_signs: triageForm.value.vitalSigns,
      medical_history: triageForm.value.medicalHistory,
      pain_level: triageForm.value.painLevel
    })
    
    if (response.success && response.data) {
      // Add new triage result to queue
      triageQueue.value.unshift(response.data)
      updateTriageStats()
      
      success('Triage complete', `Patient assessed with ${response.data.priority} priority`)
      closeTriageModal()
    } else {
      throw new Error(response.message || 'Triage analysis failed')
    }
    
  } catch (err) {
    error('Triage failed', handleApiError(err))
    console.error('AI triage error:', err)
  } finally {
    isProcessingTriage.value = false
  }
}

const assignToDoctor = async (patient: TriagePatient) => {
  try {
    const response = await api.post<{success: boolean; message?: string}>('/ai-triage/assign', {
      triage_id: patient.id,
      doctor_id: null // Let system assign based on specialty
    })
    
    if (response.success) {
      success('Patient assigned', `${patient.name} has been assigned to available doctor`)
      // Remove from queue
      const index = triageQueue.value.findIndex(p => p.id === patient.id)
      if (index !== -1) {
        triageQueue.value.splice(index, 1)
        updateTriageStats()
      }
    } else {
      throw new Error(response.message || 'Assignment failed')
    }
  } catch (err) {
    error('Assignment failed', handleApiError(err))
    console.error('Failed to assign patient:', err)
  }
}

const viewPatientDetail = (patient: TriagePatient) => {
  // Navigate to patient detail view - need to get actual patient ID first
  success('Opening details', `Viewing details for ${patient.name}`)
  // TODO: Navigate to /patients/{actualPatientId}
}

const retriage = async (patient: TriagePatient) => {
  try {
    const response = await api.post<TriagePatient>('/ai-triage/re-assess', {
      triage_id: patient.id
    })
    
    if (response.success) {
      // Update patient in queue with new assessment
      const index = triageQueue.value.findIndex(p => p.id === patient.id)
      if (index !== -1) {
        triageQueue.value[index] = { ...patient, ...response.data }
        updateTriageStats()
      }
      success('Re-assessment completed', `${patient.name} has been re-assessed by AI`)
    } else {
      throw new Error(response.message || 'Re-assessment failed')
    }
  } catch (err) {
    error('Re-assessment failed', handleApiError(err))
    console.error('Failed to re-assess patient:', err)
  }
}

const updateTriageStats = () => {
  const stats = { critical: 0, urgent: 0, standard: 0, low: 0 }
  
  triageQueue.value.forEach(patient => {
    if (patient.priority in stats) {
      stats[patient.priority as keyof typeof stats]++
    }
  })
  
  triageStats.value = stats
}

const loadTriageQueue = async () => {
  isLoadingQueue.value = true
  
  try {
    const response = await api.get<TriagePatient[]>('/ai-triage/queue')
    if (response.success && response.data) {
      triageQueue.value = response.data
      updateTriageStats()
    } else {
      throw new Error(response.message || 'Failed to load triage queue')
    }
  } catch (err) {
    error('Load failed', handleApiError(err))
    console.error('Failed to load triage queue:', err)
  } finally {
    isLoadingQueue.value = false
  }
}

const loadAvailablePatients = async () => {
  try {
    const response = await api.get<Patient[]>(API_ENDPOINTS.PATIENTS.LIST)
    if (response.success && response.data) {
      // Transform to required format for triage
      availablePatients.value = response.data.map(patient => ({
        id: patient.id,
        firstName: patient.firstName,
        lastName: patient.lastName,
        dateOfBirth: patient.dateOfBirth,
        gender: patient.gender || 'other'
      }))
    }
  } catch (err) {
    console.error('Failed to load available patients:', err)
  }
}

const closeTriageModal = () => {
  showTriageModal.value = false
  triageForm.value = {
    patientId: '',
    chiefComplaint: '',
    symptoms: '',
    vitalSigns: {
      bloodPressure: '',
      heartRate: null,
      temperature: null,
      oxygenSaturation: null,
    },
    medicalHistory: '',
    painLevel: '',
  }
}

// Helper functions
const getTriageBorderClass = (priority: string) => {
  const classMap = {
    critical: 'border-red-300 bg-red-50',
    urgent: 'border-orange-300 bg-orange-50',
    standard: 'border-yellow-300 bg-yellow-50',
    low: 'border-green-300 bg-green-50',
  }
  return classMap[priority as keyof typeof classMap] || 'border-gray-300 bg-gray-50'
}

const getPriorityClass = (priority: string) => {
  const classMap = {
    critical: 'bg-red-100 text-red-800',
    urgent: 'bg-orange-100 text-orange-800',
    standard: 'bg-yellow-100 text-yellow-800',
    low: 'bg-green-100 text-green-800',
  }
  return classMap[priority as keyof typeof classMap] || 'bg-gray-100 text-gray-800'
}

const getPainDescription = (level: number) => {
  const descriptions = [
    '', 'Minimal', 'Mild', 'Moderate', 'Moderate', 'Moderate', 'Severe', 'Severe', 'Very Severe', 'Extreme', 'Unbearable'
  ]
  return descriptions[level] || ''
}

const formatTime = (timeString: string) => {
  return formatDistance(new Date(timeString), new Date(), { addSuffix: true })
}

const calculateAge = (dateOfBirth: string) => {
  const today = new Date()
  const birthDate = new Date(dateOfBirth)
  let age = today.getFullYear() - birthDate.getFullYear()
  const monthDiff = today.getMonth() - birthDate.getMonth()
  
  if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
    age--
  }
  
  return age
}

// Lifecycle
onMounted(async () => {
  await Promise.all([
    loadTriageQueue(),
    loadAvailablePatients()
  ])
})
</script>

<style lang="postcss" scoped>
.ai-triage-container {
  @apply max-w-7xl mx-auto;
}

.card-header {
  @apply bg-gray-50;
}

.triage-item {
  @apply transition-all duration-200;
}

.triage-item:hover {
  @apply shadow-md;
}

/* Priority-based styling */
.triage-item.border-red-300 {
  @apply ring-2 ring-red-100;
}

.triage-item.border-orange-300 {
  @apply ring-2 ring-orange-100;
}

/* Statistics cards animation */
.triage-stats .medical-card {
  animation: slideInUp 0.6s ease-out;
}

.triage-stats .medical-card:nth-child(2) {
  animation-delay: 0.1s;
}

.triage-stats .medical-card:nth-child(3) {
  animation-delay: 0.2s;
}

.triage-stats .medical-card:nth-child(4) {
  animation-delay: 0.3s;
}

@keyframes slideInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Modal animations */
.fixed {
  animation: fadeIn 0.2s ease-in-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

/* Responsive design */
@media (max-width: 1024px) {
  .triage-stats {
    @apply grid-cols-1 sm:grid-cols-2;
  }
  
  .triage-item .grid {
    @apply grid-cols-1;
  }
}

@media (max-width: 768px) {
  .page-header .flex {
    @apply flex-col space-y-4 items-start;
  }
  
  .triage-stats {
    @apply grid-cols-1;
  }
  
  .triage-item .flex:first-child {
    @apply flex-col space-y-4;
  }
  
  .triage-item .ml-6 {
    @apply ml-0 flex-row space-y-0 space-x-2;
  }
}
</style>
