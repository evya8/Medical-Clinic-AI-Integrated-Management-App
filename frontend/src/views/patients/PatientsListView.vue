<template>
  <AppLayout>
    <div class="patients-container">
      <!-- Advanced Search Component -->
      <AdvancedSearch
        entity-type="patients"
        initial-search-type="patients"
        @result-select="handleSearchResult"
        class="mb-8"
      />

      <!-- Page Header -->
      <div class="page-header mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900 flex items-center">
              <UsersIcon class="w-8 h-8 mr-3 text-blue-600" />
              Patients
              <span class="text-gray-500 font-normal text-xl ml-3">({{ totalPatients }})</span>
            </h1>
            <p class="mt-2 text-sm text-gray-700">
              Manage patient information and medical records
            </p>
          </div>

          <div class="flex items-center space-x-4">
            <!-- View Toggle -->
            <div class="flex items-center bg-gray-100 rounded-lg p-1">
              <button
                @click="viewMode = 'grid'"
                :class="[
                  'p-2 rounded-md transition-colors',
                  viewMode === 'grid' ? 'bg-white shadow-sm' : 'text-gray-500'
                ]"
              >
                <Squares2X2Icon class="w-4 h-4" />
              </button>
              <button
                @click="viewMode = 'list'"
                :class="[
                  'p-2 rounded-md transition-colors',
                  viewMode === 'list' ? 'bg-white shadow-sm' : 'text-gray-500'
                ]"
              >
                <ListBulletIcon class="w-4 h-4" />
              </button>
            </div>

            <!-- Import Button -->
            <button
              @click="showImportModal = true"
              class="medical-button-secondary"
            >
              <ArrowUpTrayIcon class="w-4 h-4 mr-2" />
              Import
            </button>

            <!-- Export Button -->
            <button
              @click="exportPatients"
              :disabled="isExporting"
              class="medical-button-secondary"
            >
              <ArrowDownTrayIcon class="w-4 h-4 mr-2" />
              {{ isExporting ? 'Exporting...' : 'Export' }}
            </button>

            <!-- Add Patient Button -->
            <button
              @click="showAddModal = true"
              class="medical-button-primary"
            >
              <PlusIcon class="w-4 h-4 mr-2" />
              Add Patient
            </button>
          </div>
        </div>
      </div>

      <!-- Filters Bar -->
      <div class="filters-bar mb-6">
        <div class="medical-card p-4">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
              <!-- Quick Filters -->
              <div class="flex items-center space-x-2">
                <span class="text-sm font-medium text-gray-700">Quick Filters:</span>
                <button
                  v-for="filter in quickFilters"
                  :key="filter.key"
                  @click="applyQuickFilter(filter)"
                  :class="[
                    'px-3 py-1 text-xs font-medium rounded-full transition-colors',
                    activeQuickFilter === filter.key
                      ? 'bg-blue-100 text-blue-800'
                      : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                  ]"
                >
                  {{ filter.label }}
                  <span class="ml-1 text-gray-500">({{ filter.count }})</span>
                </button>
              </div>

              <!-- Age Range Filter -->
              <select 
                v-model="ageRangeFilter" 
                @change="applyFilters"
                class="form-select text-sm"
              >
                <option value="">All Ages</option>
                <option value="child">0-17</option>
                <option value="adult">18-64</option>
                <option value="senior">65+</option>
              </select>

              <!-- Gender Filter -->
              <select 
                v-model="genderFilter" 
                @change="applyFilters"
                class="form-select text-sm"
              >
                <option value="">All Genders</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
                <option value="other">Other</option>
              </select>
            </div>

            <div class="flex items-center space-x-3">
              <!-- Sort Options -->
              <select 
                v-model="sortBy" 
                @change="applySorting"
                class="form-select text-sm"
              >
                <option value="name_asc">Name (A-Z)</option>
                <option value="name_desc">Name (Z-A)</option>
                <option value="age_asc">Age (Youngest)</option>
                <option value="age_desc">Age (Oldest)</option>
                <option value="recent">Recently Added</option>
                <option value="updated">Recently Updated</option>
              </select>

              <!-- Clear Filters -->
              <button
                v-if="hasActiveFilters"
                @click="clearAllFilters"
                class="text-sm text-gray-600 hover:text-gray-800 underline"
              >
                Clear Filters
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Bulk Operations Component -->
      <BulkOperations
        entity-type="patient"
        :selected-items="selectedPatients"
        @clear-selection="clearSelection"
        @items-processed="handleBulkProcessed"
        class="mb-6"
      />

      <!-- Patient Statistics -->
      <div class="patient-stats grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="medical-card p-6 bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-100">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-blue-700 font-medium mb-1">Total Patients</p>
              <p class="text-2xl font-bold text-blue-900">{{ patientStats.total }}</p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
              <UsersIcon class="w-6 h-6 text-blue-600" />
            </div>
          </div>
        </div>

        <div class="medical-card p-6 bg-gradient-to-br from-green-50 to-emerald-50 border border-green-100">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-green-700 font-medium mb-1">New This Month</p>
              <p class="text-2xl font-bold text-green-900">{{ patientStats.newThisMonth }}</p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
              <UserPlusIcon class="w-6 h-6 text-green-600" />
            </div>
          </div>
        </div>

        <div class="medical-card p-6 bg-gradient-to-br from-yellow-50 to-orange-50 border border-yellow-100">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-yellow-700 font-medium mb-1">Appointments Today</p>
              <p class="text-2xl font-bold text-yellow-900">{{ patientStats.appointmentsToday }}</p>
            </div>
            <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
              <CalendarIcon class="w-6 h-6 text-yellow-600" />
            </div>
          </div>
        </div>

        <div class="medical-card p-6 bg-gradient-to-br from-purple-50 to-pink-50 border border-purple-100">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-purple-700 font-medium mb-1">High Priority</p>
              <p class="text-2xl font-bold text-purple-900">{{ patientStats.highPriority }}</p>
            </div>
            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
              <ExclamationTriangleIcon class="w-6 h-6 text-purple-600" />
            </div>
          </div>
        </div>
      </div>

      <!-- Patients List -->
      <div class="patients-list">
        <div class="medical-card">
          <!-- List Header -->
          <div class="list-header p-6 border-b border-gray-200 bg-gray-50">
            <div class="flex items-center justify-between">
              <div class="flex items-center">
                <input
                  :checked="selectedPatients.length === displayedPatients.length"
                  @change="toggleSelectAll"
                  type="checkbox"
                  class="form-checkbox mr-4"
                />
                <h3 class="text-lg font-semibold text-gray-900">
                  {{ selectedPatients.length > 0 
                    ? `${selectedPatients.length} selected` 
                    : `${displayedPatients.length} patients`
                  }}
                </h3>
              </div>

              <div class="flex items-center space-x-3">
                <!-- Items per page -->
                <div class="flex items-center space-x-2">
                  <span class="text-sm text-gray-600">Show:</span>
                  <select 
                    v-model="itemsPerPage" 
                    @change="changePagination"
                    class="form-select text-sm"
                  >
                    <option :value="25">25</option>
                    <option :value="50">50</option>
                    <option :value="100">100</option>
                  </select>
                </div>

                <!-- Refresh -->
                <button 
                  @click="refreshPatients"
                  :disabled="isLoading"
                  class="medical-button-secondary"
                >
                  <ArrowPathIcon class="w-4 h-4 mr-2" :class="{ 'animate-spin': isLoading }" />
                  Refresh
                </button>
              </div>
            </div>
          </div>

          <!-- Loading State -->
          <div v-if="isLoading" class="p-6">
            <div class="space-y-4">
              <div v-for="n in 5" :key="n" class="skeleton h-20 rounded-lg"></div>
            </div>
          </div>

          <!-- Empty State -->
          <div v-else-if="displayedPatients.length === 0" class="text-center py-12">
            <UsersIcon class="mx-auto h-12 w-12 text-gray-400 mb-4" />
            <h4 class="text-lg font-medium text-gray-900 mb-2">
              {{ hasActiveFilters ? 'No patients match your filters' : 'No patients found' }}
            </h4>
            <p class="text-gray-500 mb-6">
              {{ hasActiveFilters 
                ? 'Try adjusting your search criteria or filters.' 
                : 'Get started by adding your first patient.'
              }}
            </p>
            <div class="space-x-4">
              <button 
                v-if="hasActiveFilters"
                @click="clearAllFilters" 
                class="medical-button-secondary"
              >
                Clear Filters
              </button>
              <button @click="showAddModal = true" class="medical-button-primary">
                Add First Patient
              </button>
            </div>
          </div>

          <!-- Patients Grid/List -->
          <div v-else class="p-6">
            <!-- Grid View -->
            <div v-if="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
              <PatientCard
                v-for="patient in displayedPatients"
                :key="patient.id"
                :patient="patient"
                :selected="selectedPatients.includes(patient.id)"
                @select="togglePatientSelection"
                @view="viewPatient"
                @edit="editPatient"
                class="transition-all duration-200 hover:scale-105"
              />
            </div>

            <!-- List View -->
            <div v-else class="space-y-2">
              <div
                v-for="patient in displayedPatients"
                :key="patient.id"
                class="patient-row"
                :class="{ 'selected': selectedPatients.includes(patient.id) }"
              >
                <PatientRow
                  :patient="patient"
                  :selected="selectedPatients.includes(patient.id)"
                  @select="togglePatientSelection"
                  @view="viewPatient"
                  @edit="editPatient"
                />
              </div>
            </div>

            <!-- Pagination -->
            <div v-if="totalPages > 1" class="pagination-container mt-8">
              <div class="flex items-center justify-between">
                <div class="text-sm text-gray-700">
                  Showing {{ (currentPage - 1) * itemsPerPage + 1 }} to 
                  {{ Math.min(currentPage * itemsPerPage, totalPatients) }} of 
                  {{ totalPatients }} patients
                </div>
                
                <div class="flex items-center space-x-2">
                  <button
                    @click="currentPage = Math.max(1, currentPage - 1)"
                    :disabled="currentPage === 1"
                    class="pagination-btn"
                  >
                    Previous
                  </button>
                  
                  <div class="flex items-center space-x-1">
                    <button
                      v-for="page in visiblePages"
                      :key="page"
                      @click="currentPage = page"
                      :class="[
                        'pagination-btn',
                        { 'bg-blue-600 text-white': page === currentPage }
                      ]"
                    >
                      {{ page }}
                    </button>
                  </div>
                  
                  <button
                    @click="currentPage = Math.min(totalPages, currentPage + 1)"
                    :disabled="currentPage === totalPages"
                    class="pagination-btn"
                  >
                    Next
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Add/Edit Patient Modal -->
    <div v-if="showAddModal || showEditModal" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="closeModals"></div>
        
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
          <!-- Patient Edit Modal Component -->
          <PatientEditModal
            :patient="editingPatient"
            :is-editing="showEditModal"
            @save="handlePatientSave"
            @cancel="closeModals"
          />
        </div>
      </div>
    </div>

    <!-- Import Modal -->
    <div v-if="showImportModal" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="showImportModal = false"></div>
        
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
          <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
              Import Patients
            </h3>
            
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Select CSV File
                </label>
                <input
                  ref="fileInput"
                  type="file"
                  accept=".csv"
                  @change="handleFileSelect"
                  class="form-input w-full"
                />
              </div>
              
              <div class="text-xs text-gray-500">
                <p>CSV format: FirstName, LastName, Email, Phone, DateOfBirth, Gender</p>
              </div>
            </div>
          </div>
          
          <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
            <button 
              @click="importPatients"
              :disabled="!selectedFile || isImporting"
              class="medical-button-primary"
            >
              {{ isImporting ? 'Importing...' : 'Import' }}
            </button>
            <button 
              @click="showImportModal = false"
              class="medical-button-secondary mr-3"
            >
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import {
  UsersIcon,
  PlusIcon,
  ArrowUpTrayIcon,
  ArrowDownTrayIcon,
  ArrowPathIcon,
  Squares2X2Icon,
  ListBulletIcon,
  UserPlusIcon,
  CalendarIcon,
  ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline'
import AppLayout from '@/components/common/AppLayout.vue'
import PatientCard from '@/components/patients/PatientCard.vue'
import PatientRow from '@/components/patients/PatientRow.vue'
import PatientEditModal from '@/components/patients/modals/PatientEditModal.vue'
import BulkOperations from '@/components/bulk-operations/BulkOperations.vue'
import AdvancedSearch from '@/components/search/AdvancedSearch.vue'
import { useNotifications } from '@/stores/notifications'
import type { Patient } from '@/types/api.types'

const router = useRouter()
const { success, error } = useNotifications()

// State
const isLoading = ref(true)
const isExporting = ref(false)
const isImporting = ref(false)
const viewMode = ref<'grid' | 'list'>('grid')
const showAddModal = ref(false)
const showEditModal = ref(false)
const showImportModal = ref(false)
const editingPatient = ref<Patient | null>(null)
const selectedPatients = ref<number[]>([])
const selectedFile = ref<File | null>(null)
const fileInput = ref<HTMLInputElement>()

// Filters
const activeQuickFilter = ref('')
const ageRangeFilter = ref('')
const genderFilter = ref('')
const sortBy = ref('name_asc')

// Pagination
const currentPage = ref(1)
const itemsPerPage = ref(25)

// Mock data
const allPatients = ref<Patient[]>([
  {
    id: 1,
    firstName: 'Sarah',
    lastName: 'Johnson',
    email: 'sarah.johnson@email.com',
    phone: '(555) 123-4567',
    dateOfBirth: '1956-03-15',
    gender: 'female',
    address: '123 Main St, Springfield, IL 62701',
    emergencyContactName: 'Michael Johnson',
    emergencyContactPhone: '(555) 987-6543',
    bloodType: 'A+',
    allergies: 'Penicillin, Shellfish',
    medicalNotes: 'Hypertension, well controlled with medication',
    insuranceProvider: 'Blue Cross Blue Shield',
    insurancePolicyNumber: 'BC123456789',
    createdAt: '2023-01-15T10:00:00Z',
    updatedAt: '2024-08-20T14:30:00Z',
  },
  {
    id: 2,
    firstName: 'Michael',
    lastName: 'Chen',
    email: 'michael.chen@email.com',
    phone: '(555) 234-5678',
    dateOfBirth: '2016-07-22',
    gender: 'male',
    address: '456 Oak Ave, Springfield, IL 62702',
    emergencyContactName: 'Lisa Chen',
    emergencyContactPhone: '(555) 876-5432',
    bloodType: 'O-',
    allergies: 'None known',
    medicalNotes: 'Pediatric patient, up to date on vaccinations',
    insuranceProvider: 'Aetna',
    insurancePolicyNumber: 'AE987654321',
    createdAt: '2023-05-20T09:15:00Z',
    updatedAt: '2024-08-19T11:45:00Z',
  },
  {
    id: 3,
    firstName: 'Emma',
    lastName: 'Rodriguez',
    email: 'emma.rodriguez@email.com',
    phone: '(555) 345-6789',
    dateOfBirth: '1989-11-08',
    gender: 'female',
    address: '789 Pine St, Springfield, IL 62703',
    emergencyContactName: 'Carlos Rodriguez',
    emergencyContactPhone: '(555) 765-4321',
    bloodType: 'B+',
    allergies: 'Latex',
    medicalNotes: 'Recent pregnancy, postpartum follow-up',
    insuranceProvider: 'Cigna',
    insurancePolicyNumber: 'CG456789123',
    createdAt: '2024-01-10T08:30:00Z',
    updatedAt: '2024-08-18T16:20:00Z',
  },
  {
    id: 4,
    firstName: 'Robert',
    lastName: 'Wilson',
    email: 'robert.wilson@email.com',
    phone: '(555) 456-7890',
    dateOfBirth: '1978-12-03',
    gender: 'male',
    address: '321 Elm Dr, Springfield, IL 62704',
    emergencyContactName: 'Jennifer Wilson',
    emergencyContactPhone: '(555) 654-3210',
    bloodType: 'AB-',
    allergies: 'Aspirin, Iodine',
    medicalNotes: 'Chronic back pain, physical therapy ongoing',
    insuranceProvider: 'UnitedHealth',
    insurancePolicyNumber: 'UH789123456',
    createdAt: '2023-08-12T13:45:00Z',
    updatedAt: '2024-08-17T10:15:00Z',
  },
])

// Statistics
const patientStats = ref({
  total: 1247,
  newThisMonth: 34,
  appointmentsToday: 18,
  highPriority: 7,
})

// Quick filters
const quickFilters = ref([
  { key: 'all', label: 'All Patients', count: 1247 },
  { key: 'new', label: 'New Patients', count: 34 },
  { key: 'today', label: 'Appointments Today', count: 18 },
  { key: 'high_priority', label: 'High Priority', count: 7 },
  { key: 'pediatric', label: 'Pediatric', count: 89 },
  { key: 'senior', label: 'Senior (65+)', count: 234 },
])

// Computed
const totalPatients = computed(() => filteredPatients.value.length)

const hasActiveFilters = computed(() => {
  return activeQuickFilter.value || ageRangeFilter.value || genderFilter.value
})

const filteredPatients = computed(() => {
  let filtered = allPatients.value

  // Apply quick filter
  if (activeQuickFilter.value === 'new') {
    const thirtyDaysAgo = new Date(Date.now() - 30 * 24 * 60 * 60 * 1000)
    filtered = filtered.filter(p => new Date(p.createdAt) > thirtyDaysAgo)
  } else if (activeQuickFilter.value === 'pediatric') {
    filtered = filtered.filter(p => calculateAge(p.dateOfBirth) < 18)
  } else if (activeQuickFilter.value === 'senior') {
    filtered = filtered.filter(p => calculateAge(p.dateOfBirth) >= 65)
  }

  // Apply age range filter
  if (ageRangeFilter.value) {
    filtered = filtered.filter(p => {
      const age = calculateAge(p.dateOfBirth)
      switch (ageRangeFilter.value) {
        case 'child': return age < 18
        case 'adult': return age >= 18 && age < 65
        case 'senior': return age >= 65
        default: return true
      }
    })
  }

  // Apply gender filter
  if (genderFilter.value) {
    filtered = filtered.filter(p => p.gender === genderFilter.value)
  }

  // Apply sorting
  filtered = [...filtered].sort((a, b) => {
    switch (sortBy.value) {
      case 'name_asc':
        return `${a.lastName} ${a.firstName}`.localeCompare(`${b.lastName} ${b.firstName}`)
      case 'name_desc':
        return `${b.lastName} ${b.firstName}`.localeCompare(`${a.lastName} ${a.firstName}`)
      case 'age_asc':
        return calculateAge(b.dateOfBirth) - calculateAge(a.dateOfBirth)
      case 'age_desc':
        return calculateAge(a.dateOfBirth) - calculateAge(b.dateOfBirth)
      case 'recent':
        return new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime()
      case 'updated':
        return new Date(b.updatedAt).getTime() - new Date(a.updatedAt).getTime()
      default:
        return 0
    }
  })

  return filtered
})

const totalPages = computed(() => {
  return Math.ceil(totalPatients.value / itemsPerPage.value)
})

const displayedPatients = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return filteredPatients.value.slice(start, end)
})

const visiblePages = computed(() => {
  const pages = []
  const total = totalPages.value
  const current = currentPage.value
  
  if (total <= 7) {
    for (let i = 1; i <= total; i++) {
      pages.push(i)
    }
  } else {
    pages.push(1)
    if (current > 4) pages.push('...')
    
    const start = Math.max(2, current - 1)
    const end = Math.min(total - 1, current + 1)
    
    for (let i = start; i <= end; i++) {
      pages.push(i)
    }
    
    if (current < total - 3) pages.push('...')
    pages.push(total)
  }
  
  return pages
})

// Methods
const calculateAge = (dateOfBirth: string): number => {
  const today = new Date()
  const birth = new Date(dateOfBirth)
  let age = today.getFullYear() - birth.getFullYear()
  const monthDiff = today.getMonth() - birth.getMonth()
  if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
    age--
  }
  return age
}

const applyQuickFilter = (filter: any) => {
  activeQuickFilter.value = activeQuickFilter.value === filter.key ? '' : filter.key
  currentPage.value = 1
}

const applyFilters = () => {
  currentPage.value = 1
}

const applySorting = () => {
  currentPage.value = 1
}

const clearAllFilters = () => {
  activeQuickFilter.value = ''
  ageRangeFilter.value = ''
  genderFilter.value = ''
  sortBy.value = 'name_asc'
  currentPage.value = 1
}

const changePagination = () => {
  currentPage.value = 1
}

const togglePatientSelection = (patientId: number) => {
  const index = selectedPatients.value.indexOf(patientId)
  if (index > -1) {
    selectedPatients.value.splice(index, 1)
  } else {
    selectedPatients.value.push(patientId)
  }
}

const toggleSelectAll = () => {
  if (selectedPatients.value.length === displayedPatients.value.length) {
    selectedPatients.value = []
  } else {
    selectedPatients.value = displayedPatients.value.map(p => p.id)
  }
}

const clearSelection = () => {
  selectedPatients.value = []
}

const handleSearchResult = (result: any) => {
  if (result.type === 'patient') {
    viewPatient({ id: result.id })
  }
}

const viewPatient = (patient: { id: number }) => {
  router.push(`/patients/${patient.id}`)
}

const editPatient = (patient: Patient) => {
  editingPatient.value = { ...patient }
  showEditModal.value = true
}

const handlePatientSave = (patientData: any) => {
  if (showEditModal.value && editingPatient.value) {
    // Update existing patient
    const index = allPatients.value.findIndex(p => p.id === editingPatient.value!.id)
    if (index !== -1) {
      allPatients.value[index] = { ...allPatients.value[index], ...patientData }
      success('Patient updated', `${patientData.firstName} ${patientData.lastName} has been updated`)
    }
  } else {
    // Add new patient
    const newPatient: Patient = {
      id: Date.now(),
      ...patientData,
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString(),
    }
    allPatients.value.unshift(newPatient)
    patientStats.value.total++
    success('Patient added', `${patientData.firstName} ${patientData.lastName} has been added`)
  }
  closeModals()
}

const closeModals = () => {
  showAddModal.value = false
  showEditModal.value = false
  editingPatient.value = null
}

const refreshPatients = async () => {
  isLoading.value = true
  
  try {
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 1000))
    success('Patients refreshed', 'Patient list has been updated')
  } catch (err) {
    error('Refresh failed', 'Unable to refresh patient list')
  } finally {
    isLoading.value = false
  }
}

const exportPatients = async () => {
  isExporting.value = true
  
  try {
    await new Promise(resolve => setTimeout(resolve, 2000))
    
    // Generate CSV
    const headers = ['First Name', 'Last Name', 'Email', 'Phone', 'Date of Birth', 'Gender']
    const rows = filteredPatients.value.map(p => [
      p.firstName,
      p.lastName,
      p.email || '',
      p.phone || '',
      p.dateOfBirth,
      p.gender || ''
    ])
    
    const csv = [headers, ...rows]
      .map(row => row.map(cell => `"${cell}"`).join(','))
      .join('\n')
    
    // Download CSV
    const blob = new Blob([csv], { type: 'text/csv' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `patients-export-${new Date().toISOString().split('T')[0]}.csv`
    link.click()
    window.URL.revokeObjectURL(url)
    
    success('Export complete', `${filteredPatients.value.length} patients exported`)
  } catch (err) {
    error('Export failed', 'Unable to export patients')
  } finally {
    isExporting.value = false
  }
}

const handleFileSelect = (event: Event) => {
  const target = event.target as HTMLInputElement
  selectedFile.value = target.files?.[0] || null
}

const importPatients = async () => {
  if (!selectedFile.value) return
  
  isImporting.value = true
  
  try {
    await new Promise(resolve => setTimeout(resolve, 2000))
    
    // Simulate import process
    const importCount = Math.floor(Math.random() * 20) + 10
    patientStats.value.total += importCount
    patientStats.value.newThisMonth += importCount
    
    success('Import complete', `${importCount} patients imported successfully`)
    showImportModal.value = false
    selectedFile.value = null
    if (fileInput.value) fileInput.value.value = ''
    
  } catch (err) {
    error('Import failed', 'Unable to import patients')
  } finally {
    isImporting.value = false
  }
}

// Lifecycle
onMounted(async () => {
  await refreshPatients()
})
</script>

<style lang="postcss" scoped>
.patients-container {
  @apply max-w-7xl mx-auto;
}

.patient-row.selected {
  @apply bg-blue-50 border-blue-200;
}

.pagination-btn {
  @apply px-3 py-2 text-sm border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 rounded-md disabled:opacity-50 disabled:cursor-not-allowed;
}

/* Statistics cards animation */
.patient-stats .medical-card {
  animation: slideInUp 0.6s ease-out;
}

.patient-stats .medical-card:nth-child(2) {
  animation-delay: 0.1s;
}

.patient-stats .medical-card:nth-child(3) {
  animation-delay: 0.2s;
}

.patient-stats .medical-card:nth-child(4) {
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

/* Responsive design */
@media (max-width: 1024px) {
  .patient-stats {
    @apply grid-cols-1 sm:grid-cols-2;
  }
  
  .filters-bar .flex:first-child {
    @apply flex-col space-y-4 space-x-0;
  }
}

@media (max-width: 768px) {
  .page-header .flex {
    @apply flex-col space-y-4 items-start;
  }
  
  .patient-stats {
    @apply grid-cols-1;
  }
  
  .filters-bar .flex {
    @apply flex-col space-y-3 items-start;
  }
  
  .list-header .flex {
    @apply flex-col space-y-3 items-start;
  }
  
  .pagination-container .flex:last-child {
    @apply flex-wrap;
  }
}

/* Grid hover animation */
.transition-all.duration-200.hover\:scale-105:hover {
  transform: scale(1.02);
}
</style>
