<template>
  <AppLayout>
    <div class="ai-summaries-container">
      <!-- Page Header -->
      <div class="page-header mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900 flex items-center">
              <DocumentTextIcon class="w-8 h-8 mr-3 text-blue-600" />
              AI Summaries
            </h1>
            <p class="mt-2 text-sm text-gray-700">
              Automated clinical documentation and appointment summaries
            </p>
          </div>

          <div class="flex items-center space-x-4">
            <!-- Summary Stats -->
            <div class="flex items-center space-x-2">
              <div class="w-2 h-2 bg-green-400 rounded-full"></div>
              <span class="text-sm text-gray-600">{{ summaryStats.today }} generated today</span>
            </div>

            <!-- Generate Summary Button -->
            <button
              @click="showGenerateModal = true"
              class="medical-button-primary"
            >
              <PlusIcon class="w-4 h-4 mr-2" />
              Generate Summary
            </button>
          </div>
        </div>
      </div>

      <!-- Summary Statistics -->
      <div class="summary-stats grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="medical-card p-6 bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-100">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-blue-700 font-medium mb-1">SOAP Notes</p>
              <p class="text-2xl font-bold text-blue-900">{{ summaryStats.soap }}</p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
              <DocumentTextIcon class="w-6 h-6 text-blue-600" />
            </div>
          </div>
        </div>

        <div class="medical-card p-6 bg-gradient-to-br from-green-50 to-emerald-50 border border-green-100">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-green-700 font-medium mb-1">Billing Notes</p>
              <p class="text-2xl font-bold text-green-900">{{ summaryStats.billing }}</p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
              <CurrencyDollarIcon class="w-6 h-6 text-green-600" />
            </div>
          </div>
        </div>

        <div class="medical-card p-6 bg-gradient-to-br from-purple-50 to-pink-50 border border-purple-100">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-purple-700 font-medium mb-1">Patient Letters</p>
              <p class="text-2xl font-bold text-purple-900">{{ summaryStats.patient }}</p>
            </div>
            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
              <UserIcon class="w-6 h-6 text-purple-600" />
            </div>
          </div>
        </div>

        <div class="medical-card p-6 bg-gradient-to-br from-yellow-50 to-orange-50 border border-yellow-100">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-yellow-700 font-medium mb-1">Custom Reports</p>
              <p class="text-2xl font-bold text-yellow-900">{{ summaryStats.custom }}</p>
            </div>
            <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
              <ClipboardDocumentListIcon class="w-6 h-6 text-yellow-600" />
            </div>
          </div>
        </div>
      </div>

      <!-- Summary Templates & Recent Summaries -->
      <div class="summaries-grid grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left Column: Quick Templates -->
        <div class="lg:col-span-1">
          <div class="medical-card">
            <div class="card-header p-6 border-b border-gray-200">
              <h3 class="text-lg font-semibold text-gray-900">Quick Templates</h3>
            </div>
            <div class="p-6">
              <div class="space-y-4">
                <button
                  v-for="template in quickTemplates"
                  :key="template.type"
                  @click="generateFromTemplate(template)"
                  class="template-button"
                >
                  <div class="flex items-center space-x-3">
                    <component 
                      :is="template.icon"
                      :class="['w-6 h-6', template.iconColor]"
                    />
                    <div class="flex-1 text-left">
                      <p class="font-medium text-gray-900">{{ template.name }}</p>
                      <p class="text-xs text-gray-500">{{ template.description }}</p>
                    </div>
                    <ChevronRightIcon class="w-4 h-4 text-gray-400" />
                  </div>
                </button>
              </div>
            </div>
          </div>

          <!-- AI Performance Metrics -->
          <div class="medical-card mt-8">
            <div class="card-header p-6 border-b border-gray-200">
              <h3 class="text-lg font-semibold text-gray-900">AI Performance</h3>
            </div>
            <div class="p-6 space-y-6">
              <!-- Accuracy -->
              <div>
                <div class="flex items-center justify-between mb-2">
                  <span class="text-sm font-medium text-gray-700">Summary Accuracy</span>
                  <span class="text-sm text-gray-500">{{ aiPerformance.accuracy }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                  <div 
                    class="bg-green-600 h-2 rounded-full transition-all duration-500"
                    :style="{ width: aiPerformance.accuracy + '%' }"
                  ></div>
                </div>
              </div>

              <!-- Processing Speed -->
              <div>
                <div class="flex items-center justify-between mb-2">
                  <span class="text-sm font-medium text-gray-700">Avg Processing Time</span>
                  <span class="text-sm text-gray-500">{{ aiPerformance.avgTime }}s</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                  <div 
                    class="bg-blue-600 h-2 rounded-full transition-all duration-500"
                    :style="{ width: Math.min(100, (10 - aiPerformance.avgTime) * 20) + '%' }"
                  ></div>
                </div>
              </div>

              <!-- User Satisfaction -->
              <div>
                <div class="flex items-center justify-between mb-2">
                  <span class="text-sm font-medium text-gray-700">User Satisfaction</span>
                  <span class="text-sm text-gray-500">{{ aiPerformance.satisfaction }}/5</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                  <div 
                    class="bg-purple-600 h-2 rounded-full transition-all duration-500"
                    :style="{ width: (aiPerformance.satisfaction / 5) * 100 + '%' }"
                  ></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column: Recent Summaries -->
        <div class="lg:col-span-2">
          <div class="medical-card">
            <div class="card-header flex items-center justify-between p-6 border-b border-gray-200">
              <h3 class="text-lg font-semibold text-gray-900">Recent Summaries</h3>
              <div class="flex items-center space-x-4">
                <!-- Filter -->
                <select 
                  v-model="summaryFilter" 
                  @change="filterSummaries"
                  class="form-select text-sm"
                >
                  <option value="">All Types</option>
                  <option value="soap">SOAP Notes</option>
                  <option value="billing">Billing Notes</option>
                  <option value="patient">Patient Letters</option>
                  <option value="custom">Custom Reports</option>
                </select>

                <!-- Refresh -->
                <button 
                  @click="refreshSummaries"
                  :disabled="isRefreshing"
                  class="medical-button-secondary"
                >
                  <ArrowPathIcon class="w-4 h-4 mr-2" :class="{ 'animate-spin': isRefreshing }" />
                  Refresh
                </button>
              </div>
            </div>

            <div class="p-6">
              <div v-if="isLoadingSummaries" class="space-y-4">
                <div v-for="n in 5" :key="n" class="skeleton h-24 rounded-lg"></div>
              </div>

              <div v-else-if="filteredSummaries.length === 0" class="text-center py-12">
                <DocumentTextIcon class="mx-auto h-12 w-12 text-gray-400 mb-4" />
                <h4 class="text-lg font-medium text-gray-900 mb-2">No summaries found</h4>
                <p class="text-gray-500 mb-6">Generate your first AI summary to get started.</p>
                <button @click="showGenerateModal = true" class="medical-button-primary">
                  Generate Summary
                </button>
              </div>

              <div v-else class="space-y-4">
                <div
                  v-for="summary in filteredSummaries"
                  :key="summary.id"
                  class="summary-item p-6 border rounded-lg hover:shadow-md transition-shadow cursor-pointer"
                  @click="viewSummary(summary)"
                >
                  <div class="flex items-start justify-between">
                    <div class="flex-1">
                      <div class="flex items-center mb-3">
                        <component 
                          :is="getSummaryIcon(summary.type)"
                          :class="['w-5 h-5 mr-2', getSummaryIconColor(summary.type)]"
                        />
                        <h4 class="font-medium text-gray-900">{{ summary.title }}</h4>
                        <span 
                          :class="[
                            'ml-2 px-2 py-1 text-xs font-medium rounded-full',
                            getSummaryTypeClass(summary.type)
                          ]"
                        >
                          {{ summary.type.toUpperCase() }}
                        </span>
                      </div>

                      <p class="text-sm text-gray-600 mb-3 line-clamp-2">{{ summary.preview }}</p>

                      <div class="flex items-center space-x-4 text-xs text-gray-500">
                        <span>Patient: {{ summary.patientName }}</span>
                        <span>•</span>
                        <span>{{ formatDate(summary.createdAt) }}</span>
                        <span>•</span>
                        <span>{{ summary.wordCount }} words</span>
                        <span>•</span>
                        <div class="flex items-center">
                          <StarIcon class="w-3 h-3 text-yellow-500 mr-1" />
                          <span>{{ summary.rating }}/5</span>
                        </div>
                      </div>
                    </div>

                    <!-- Actions -->
                    <div class="ml-4 flex items-center space-x-2">
                      <button
                        @click.stop="editSummary(summary)"
                        class="p-2 text-gray-400 hover:text-gray-600 rounded-full hover:bg-gray-100"
                        title="Edit Summary"
                      >
                        <PencilIcon class="w-4 h-4" />
                      </button>
                      <button
                        @click.stop="downloadSummary(summary)"
                        class="p-2 text-gray-400 hover:text-gray-600 rounded-full hover:bg-gray-100"
                        title="Download"
                      >
                        <ArrowDownTrayIcon class="w-4 h-4" />
                      </button>
                      <button
                        @click.stop="shareSummary(summary)"
                        class="p-2 text-gray-400 hover:text-gray-600 rounded-full hover:bg-gray-100"
                        title="Share"
                      >
                        <ShareIcon class="w-4 h-4" />
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Load More -->
              <div v-if="hasMoreSummaries" class="text-center mt-6">
                <button
                  @click="loadMoreSummaries"
                  :disabled="isLoadingMore"
                  class="medical-button-secondary"
                >
                  {{ isLoadingMore ? 'Loading...' : 'Load More Summaries' }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Generate Summary Modal -->
      <div v-if="showGenerateModal" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center">
          <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="closeGenerateModal"></div>
          
          <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
            <form @submit.prevent="generateSummary">
              <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-6">
                  Generate AI Summary
                </h3>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                  <!-- Left Column: Input Settings -->
                  <div class="space-y-6">
                    <!-- Summary Type -->
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">
                        Summary Type
                      </label>
                      <select 
                        v-model="generateForm.type" 
                        required
                        class="form-select w-full"
                      >
                        <option value="">Select type...</option>
                        <option value="soap">SOAP Note</option>
                        <option value="billing">Billing Summary</option>
                        <option value="patient">Patient-Friendly Letter</option>
                        <option value="custom">Custom Report</option>
                      </select>
                    </div>

                    <!-- Patient Selection -->
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">
                        Patient
                      </label>
                      <select 
                        v-model="generateForm.patientId" 
                        required
                        class="form-select w-full"
                      >
                        <option value="">Choose patient...</option>
                        <option 
                          v-for="patient in availablePatients" 
                          :key="patient.id" 
                          :value="patient.id"
                        >
                          {{ patient.firstName }} {{ patient.lastName }}
                        </option>
                      </select>
                    </div>

                    <!-- Appointment/Visit -->
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">
                        Appointment/Visit
                      </label>
                      <select 
                        v-model="generateForm.appointmentId" 
                        class="form-select w-full"
                      >
                        <option value="">Select appointment...</option>
                        <option 
                          v-for="appointment in recentAppointments" 
                          :key="appointment.id" 
                          :value="appointment.id"
                        >
                          {{ formatDate(appointment.appointmentDate) }} - {{ appointment.appointmentType }}
                        </option>
                      </select>
                    </div>

                    <!-- Language -->
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">
                        Language
                      </label>
                      <select v-model="generateForm.language" class="form-select w-full">
                        <option value="en">English</option>
                        <option value="es">Spanish</option>
                        <option value="fr">French</option>
                        <option value="de">German</option>
                      </select>
                    </div>
                  </div>

                  <!-- Right Column: Content Input -->
                  <div class="space-y-6">
                    <!-- Clinical Notes -->
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">
                        Clinical Notes / Visit Summary
                      </label>
                      <textarea 
                        v-model="generateForm.clinicalNotes"
                        required
                        rows="6"
                        class="form-textarea w-full"
                        placeholder="Enter clinical notes, visit details, diagnosis, treatment plan..."
                      ></textarea>
                    </div>

                    <!-- Special Instructions -->
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">
                        Special Instructions (Optional)
                      </label>
                      <textarea 
                        v-model="generateForm.instructions"
                        rows="3"
                        class="form-textarea w-full"
                        placeholder="Any specific formatting, focus areas, or requirements..."
                      ></textarea>
                    </div>

                    <!-- Options -->
                    <div class="space-y-3">
                      <div class="flex items-center">
                        <input
                          id="includeDiagnostic"
                          v-model="generateForm.options.includeDiagnosticCodes"
                          type="checkbox"
                          class="form-checkbox"
                        />
                        <label for="includeDiagnostic" class="ml-2 text-sm text-gray-700">
                          Include diagnostic codes (ICD-10)
                        </label>
                      </div>
                      <div class="flex items-center">
                        <input
                          id="includeProcedure"
                          v-model="generateForm.options.includeProcedureCodes"
                          type="checkbox"
                          class="form-checkbox"
                        />
                        <label for="includeProcedure" class="ml-2 text-sm text-gray-700">
                          Include procedure codes (CPT)
                        </label>
                      </div>
                      <div class="flex items-center">
                        <input
                          id="includeFollowUp"
                          v-model="generateForm.options.includeFollowUp"
                          type="checkbox"
                          class="form-checkbox"
                        />
                        <label for="includeFollowUp" class="ml-2 text-sm text-gray-700">
                          Include follow-up recommendations
                        </label>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button 
                  type="submit"
                  :disabled="isGenerating"
                  class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
                >
                  {{ isGenerating ? 'Generating...' : 'Generate Summary' }}
                </button>
                <button 
                  type="button"
                  @click="closeGenerateModal"
                  :disabled="isGenerating"
                  class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                >
                  Cancel
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- View/Edit Summary Modal -->
      <div v-if="showViewModal && selectedSummary" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center">
          <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="closeViewModal"></div>
          
          <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
              <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                  {{ selectedSummary.title }}
                </h3>
                <div class="flex items-center space-x-2">
                  <span :class="[
                    'px-2 py-1 text-xs font-medium rounded-full',
                    getSummaryTypeClass(selectedSummary.type)
                  ]">
                    {{ selectedSummary.type.toUpperCase() }}
                  </span>
                  <button @click="closeViewModal" class="text-gray-400 hover:text-gray-600">
                    <XMarkIcon class="w-5 h-5" />
                  </button>
                </div>
              </div>

              <!-- Summary Content -->
              <div class="bg-gray-50 p-6 rounded-lg mb-6">
                <div class="prose max-w-none">
                  <div v-html="formatSummaryContent(selectedSummary.content)"></div>
                </div>
              </div>

              <!-- Summary Actions -->
              <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4 text-sm text-gray-500">
                  <span>Created: {{ formatDate(selectedSummary.createdAt) }}</span>
                  <span>•</span>
                  <span>{{ selectedSummary.wordCount }} words</span>
                  <span>•</span>
                  <span>Patient: {{ selectedSummary.patientName }}</span>
                </div>

                <div class="flex items-center space-x-3">
                  <button
                    @click="editSummary(selectedSummary)"
                    class="medical-button-secondary"
                  >
                    <PencilIcon class="w-4 h-4 mr-2" />
                    Edit
                  </button>
                  <button
                    @click="downloadSummary(selectedSummary)"
                    class="medical-button-secondary"
                  >
                    <ArrowDownTrayIcon class="w-4 h-4 mr-2" />
                    Download
                  </button>
                  <button
                    @click="shareSummary(selectedSummary)"
                    class="medical-button-primary"
                  >
                    <ShareIcon class="w-4 h-4 mr-2" />
                    Share
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import {
  DocumentTextIcon,
  PlusIcon,
  CurrencyDollarIcon,
  UserIcon,
  ClipboardDocumentListIcon,
  ChevronRightIcon,
  ArrowPathIcon,
  StarIcon,
  PencilIcon,
  ArrowDownTrayIcon,
  ShareIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'
import AppLayout from '@/components/common/AppLayout.vue'
import { useNotifications } from '@/stores/notifications'
import { format } from 'date-fns'
import type { AppointmentSummary, SummaryGenerateForm, Patient, Appointment } from '@/types/api.types'
import { api, handleApiError } from '@/services/api'

const { success, error } = useNotifications()

// State with proper typing
const showGenerateModal = ref(false)
const showViewModal = ref(false)
const selectedSummary = ref<AppointmentSummary | null>(null)
const isLoadingSummaries = ref(true)
const isRefreshing = ref(false)
const isGenerating = ref(false)
const isLoadingMore = ref(false)
const summaryFilter = ref('')
const hasMoreSummaries = ref(true)

// Statistics
const summaryStats = ref({
  soap: 45,
  billing: 32,
  patient: 28,
  custom: 15,
  today: 8,
})

// AI Performance
const aiPerformance = ref({
  accuracy: 94,
  avgTime: 2.3,
  satisfaction: 4.7,
})

// Quick Templates
const quickTemplates = ref([
  {
    type: 'soap',
    name: 'SOAP Note',
    description: 'Structured clinical documentation',
    icon: DocumentTextIcon,
    iconColor: 'text-blue-600',
  },
  {
    type: 'patient',
    name: 'Patient Letter',
    description: 'Patient-friendly visit summary',
    icon: UserIcon,
    iconColor: 'text-purple-600',
  },
  {
    type: 'custom',
    name: 'Custom Report',
    description: 'Flexible format for specific needs',
    icon: ClipboardDocumentListIcon,
    iconColor: 'text-yellow-600',
  },
])

// Data with proper typing
const recentSummaries = ref<AppointmentSummary[]>([])
const availablePatients = ref<Pick<Patient, 'id' | 'firstName' | 'lastName'>[]>([])
const recentAppointments = ref<Pick<Appointment, 'id' | 'appointmentDate' | 'appointmentType'>[]>([])

// Generate form with proper typing
const generateForm = ref<SummaryGenerateForm>({
  type: '',
  patientId: '',
  appointmentId: '',
  language: 'en',
  clinicalNotes: '',
  instructions: '',
  options: {
    includeDiagnosticCodes: false,
    includeProcedureCodes: false,
    includeFollowUp: true,
  },
})

// Computed
const filteredSummaries = computed(() => {
  if (!summaryFilter.value) return recentSummaries.value
  return recentSummaries.value.filter(summary => summary.type === summaryFilter.value)
})

// Methods
const generateFromTemplate = (template: any) => {
  generateForm.value.type = template.type
  showGenerateModal.value = true
}

const generateSummary = async () => {
  isGenerating.value = true
  
  try {
    const response = await api.post<AppointmentSummary>('/api/ai-summaries/generate', {
      type: generateForm.value.type,
      patient_id: generateForm.value.patientId,
      appointment_id: generateForm.value.appointmentId,
      language: generateForm.value.language,
      clinical_notes: generateForm.value.clinicalNotes,
      instructions: generateForm.value.instructions,
      options: generateForm.value.options,
    })
    
    if (response.success) {
      recentSummaries.value.unshift(response.data)
      summaryStats.value[response.data.type as keyof typeof summaryStats.value]++
      summaryStats.value.today++
      
      success('Summary generated', 'AI summary has been created successfully')
      closeGenerateModal()
    } else {
      throw new Error(response.message || 'Summary generation failed')
    }
    
  } catch (err) {
    error('Generation failed', handleApiError(err))
    console.error('Failed to generate summary:', err)
  } finally {
    isGenerating.value = false
  }
}

const viewSummary = (summary: AppointmentSummary) => {
  selectedSummary.value = summary
  showViewModal.value = true
}

const editSummary = (summary: AppointmentSummary) => {
  // TODO: Implement edit functionality
  success('Edit mode', `Opening editor for ${summary.title}`)
}

const downloadSummary = (summary: AppointmentSummary) => {
  // Create and download text file
  const content = `${summary.title}\n\n${summary.content}`
  const blob = new Blob([content], { type: 'text/plain' })
  const url = window.URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `${summary.title.replace(/[^a-z0-9]/gi, '_').toLowerCase()}.txt`
  link.click()
  window.URL.revokeObjectURL(url)
  
  success('Download started', 'Summary is being downloaded')
}

const shareSummary = (summary: AppointmentSummary) => {
  // TODO: Implement share functionality
  success('Share initiated', `Preparing to share ${summary.title}`)
}

const refreshSummaries = async () => {
  isRefreshing.value = true
  
  try {
    const response = await api.get<AppointmentSummary[]>('/api/ai-summaries')
    if (response.success) {
      recentSummaries.value = response.data
      updateSummaryStats()
      success('Summaries updated', 'Summary list has been refreshed')
    } else {
      throw new Error(response.message || 'Failed to load summaries')
    }
  } catch (err) {
    error('Refresh failed', handleApiError(err))
    console.error('Failed to refresh summaries:', err)
  } finally {
    isRefreshing.value = false
  }
}

const updateSummaryStats = () => {
  const stats = { soap: 0, billing: 0, patient: 0, custom: 0, today: 0 }
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  
  recentSummaries.value.forEach(summary => {
    if (summary.type in stats) {
      stats[summary.type as keyof Omit<typeof stats, 'today'>]++
    }
    
    const summaryDate = new Date(summary.createdAt)
    summaryDate.setHours(0, 0, 0, 0)
    
    if (summaryDate.getTime() === today.getTime()) {
      stats.today++
    }
  })
  
  summaryStats.value = stats
}

const loadSummaries = async () => {
  isLoadingSummaries.value = true
  
  try {
    const response = await api.get<AppointmentSummary[]>('/api/ai-summaries')
    if (response.success) {
      recentSummaries.value = response.data
      updateSummaryStats()
    } else {
      throw new Error(response.message || 'Failed to load summaries')
    }
  } catch (err) {
    error('Load failed', handleApiError(err))
    console.error('Failed to load summaries:', err)
  } finally {
    isLoadingSummaries.value = false
  }
}

const loadAvailableData = async () => {
  try {
    // Load patients
    const patientsResponse = await api.get<Patient[]>('/api/patients')
    if (patientsResponse.success) {
      availablePatients.value = patientsResponse.data.map(patient => ({
        id: patient.id,
        firstName: patient.firstName,
        lastName: patient.lastName
      }))
    }
    
    // Load recent appointments
    const appointmentsResponse = await api.get<Appointment[]>('/api/appointments/recent')
    if (appointmentsResponse.success) {
      recentAppointments.value = appointmentsResponse.data.map(appointment => ({
        id: appointment.id,
        appointmentDate: appointment.appointmentDate,
        appointmentType: appointment.appointmentType || 'consultation'
      }))
    }
  } catch (err) {
    console.error('Failed to load available data:', err)
  }
}

const loadMoreSummaries = async () => {
  isLoadingMore.value = true
  
  try {
    await new Promise(resolve => setTimeout(resolve, 1000))
    // Add more mock summaries
    const moreSummaries = Array(5).fill(0).map((_, i) => ({
      id: Date.now() + i,
      type: ['soap', 'billing', 'patient', 'custom'][Math.floor(Math.random() * 4)],
      title: `Summary ${Date.now() + i}`,
      preview: 'Additional summary content...',
      content: 'Mock summary content...',
      patientName: `Patient ${i + 1}`,
      createdAt: new Date(Date.now() - (i + 1) * 24 * 60 * 60 * 1000).toISOString(),
      wordCount: Math.floor(Math.random() * 200) + 150,
      rating: 4.0 + Math.random(),
    }))
    
    recentSummaries.value.push(
      ...moreSummaries.filter(
        (s): s is Omit<typeof s, 'type'> & { type: "soap" | "patient" | "custom" } =>
          s.type === "soap" || s.type === "patient" || s.type === "custom"
      )
    )
    hasMoreSummaries.value = recentSummaries.value.length < 50
    
  } finally {
    isLoadingMore.value = false
  }
}

const closeGenerateModal = () => {
  showGenerateModal.value = false
  generateForm.value = {
    type: '',
    patientId: '',
    appointmentId: '',
    language: 'en',
    clinicalNotes: '',
    instructions: '',
    options: {
      includeDiagnosticCodes: false,
      includeProcedureCodes: false,
      includeFollowUp: true,
    },
  }
}

const closeViewModal = () => {
  showViewModal.value = false
  selectedSummary.value = null
}

// Helper functions
const getSummaryIcon = (type: string) => {
  const iconMap = {
    soap: DocumentTextIcon,
    billing: CurrencyDollarIcon,
    patient: UserIcon,
    custom: ClipboardDocumentListIcon,
  }
  return iconMap[type as keyof typeof iconMap] || DocumentTextIcon
}

const getSummaryIconColor = (type: string) => {
  const colorMap = {
    soap: 'text-blue-600',
    billing: 'text-green-600',
    patient: 'text-purple-600',
    custom: 'text-yellow-600',
  }
  return colorMap[type as keyof typeof colorMap] || 'text-gray-600'
}

const getSummaryTypeClass = (type: string) => {
  const classMap = {
    soap: 'bg-blue-100 text-blue-800',
    billing: 'bg-green-100 text-green-800',
    patient: 'bg-purple-100 text-purple-800',
    custom: 'bg-yellow-100 text-yellow-800',
  }
  return classMap[type as keyof typeof classMap] || 'bg-gray-100 text-gray-800'
}

const formatDate = (dateString: string) => {
  return format(new Date(dateString), 'MMM d, yyyy h:mm a')
}

const formatSummaryContent = (content: string) => {
  return content.replace(/\n/g, '<br>')
}


// Lifecycle
onMounted(async () => {
  await Promise.all([
    loadSummaries(),
    loadAvailableData()
  ])
})
</script>

<style lang="postcss" scoped>
.ai-summaries-container {
  @apply max-w-7xl mx-auto;
}

.card-header {
  @apply bg-gray-50;
}

.template-button {
  @apply w-full p-4 border border-gray-200 rounded-lg hover:shadow-md hover:border-blue-200 transition-all duration-200 text-left;
}

.template-button:hover {
  @apply bg-blue-50;
}

.summary-item {
  @apply transition-all duration-200;
}

.summary-item:hover {
  @apply border-blue-200 bg-blue-50;
}

/* Statistics cards animation */
.summary-stats .medical-card {
  animation: slideInUp 0.6s ease-out;
}

.summary-stats .medical-card:nth-child(2) {
  animation-delay: 0.1s;
}

.summary-stats .medical-card:nth-child(3) {
  animation-delay: 0.2s;
}

.summary-stats .medical-card:nth-child(4) {
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

/* Progress bar animations */
.bg-green-600,
.bg-blue-600,
.bg-purple-600 {
  transition: width 0.5s ease-in-out;
}

/* Line clamp for text truncation */
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Prose styling for summary content */
.prose {
  @apply text-gray-700 leading-relaxed;
}

/* Responsive design */
@media (max-width: 1024px) {
  .summaries-grid {
    @apply grid-cols-1;
  }
  
  .summary-stats {
    @apply grid-cols-1 sm:grid-cols-2;
  }
}

@media (max-width: 768px) {
  .page-header .flex {
    @apply flex-col space-y-4 items-start;
  }
  
  .summary-stats {
    @apply grid-cols-1;
  }
  
  .summary-item .flex {
    @apply flex-col space-y-3;
  }
  
  .summary-item .ml-4 {
    @apply ml-0 flex-row space-x-2;
  }
}
</style>
