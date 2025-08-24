<template>
  <AppLayout>
    <div class="patients-container">
      <!-- Page Header -->
      <div class="page-header flex items-center justify-between mb-8">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Patients</h1>
          <p class="mt-2 text-sm text-gray-700">
            Manage your patient records and medical history
          </p>
        </div>
        <div class="flex items-center space-x-3">
          <button
            class="medical-button-secondary"
            @click="exportPatients"
          >
            <DocumentArrowDownIcon class="w-4 h-4 mr-2" />
            Export
          </button>
          <button
            class="medical-button-primary"
            @click="handleNewPatient"
          >
            <UserPlusIcon class="w-4 h-4 mr-2" />
            New Patient
          </button>
        </div>
      </div>

      <!-- Search and Filters -->
      <div class="search-filters medical-card p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <!-- Search Input -->
          <div class="md:col-span-2">
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
              </div>
              <input
                v-model="searchQuery"
                type="search"
                placeholder="Search patients by name, email, or phone..."
                class="medical-input w-full pl-10"
                @input="handleSearch"
              />
            </div>
          </div>

          <!-- Age Filter -->
          <div>
            <select v-model="filters.ageGroup" class="medical-input">
              <option value="">All Ages</option>
              <option value="child">Child (0-17)</option>
              <option value="adult">Adult (18-64)</option>
              <option value="senior">Senior (65+)</option>
            </select>
          </div>

          <!-- Gender Filter -->
          <div>
            <select v-model="filters.gender" class="medical-input">
              <option value="">All Genders</option>
              <option value="male">Male</option>
              <option value="female">Female</option>
              <option value="other">Other</option>
            </select>
          </div>
        </div>

        <!-- Quick Stats -->
        <div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div class="text-center">
            <div class="text-2xl font-bold text-primary-600">{{ totalPatients }}</div>
            <div class="text-sm text-gray-500">Total Patients</div>
          </div>
          <div class="text-center">
            <div class="text-2xl font-bold text-green-600">{{ newThisMonth }}</div>
            <div class="text-sm text-gray-500">New This Month</div>
          </div>
          <div class="text-center">
            <div class="text-2xl font-bold text-blue-600">{{ activePatients }}</div>
            <div class="text-sm text-gray-500">Active Patients</div>
          </div>
        </div>
      </div>

      <!-- Patients List -->
      <div class="patients-list medical-card">
        <!-- Table Header -->
        <div class="table-header p-6 border-b border-gray-200 bg-gray-50">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-medium text-gray-900">
              Patient Records
              <span class="ml-2 text-sm font-normal text-gray-500">
                ({{ filteredPatients.length }} {{ filteredPatients.length === 1 ? 'patient' : 'patients' }})
              </span>
            </h3>
            <div class="flex items-center space-x-2">
              <button
                class="p-2 text-gray-400 hover:text-gray-600 rounded"
                :class="{ 'text-primary-600': viewMode === 'grid' }"
                @click="viewMode = 'grid'"
                title="Grid view"
              >
                <Squares2X2Icon class="w-5 h-5" />
              </button>
              <button
                class="p-2 text-gray-400 hover:text-gray-600 rounded"
                :class="{ 'text-primary-600': viewMode === 'list' }"
                @click="viewMode = 'list'"
                title="List view"
              >
                <ListBulletIcon class="w-5 h-5" />
              </button>
            </div>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="isLoading" class="p-6">
          <div class="space-y-4">
            <div v-for="n in 5" :key="n" class="skeleton h-16 rounded-lg"></div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else-if="filteredPatients.length === 0" class="p-12 text-center">
          <UsersIcon class="mx-auto h-12 w-12 text-gray-400 mb-4" />
          <h4 class="text-lg font-medium text-gray-900 mb-2">No patients found</h4>
          <p class="text-gray-500 mb-6">
            {{ searchQuery ? 'Try adjusting your search criteria.' : 'Get started by adding your first patient.' }}
          </p>
          <button
            v-if="!searchQuery"
            class="medical-button-primary"
            @click="handleNewPatient"
          >
            <UserPlusIcon class="w-4 h-4 mr-2" />
            Add First Patient
          </button>
        </div>

        <!-- Patients Grid/List -->
        <div v-else class="p-6">
          <!-- Grid View -->
          <div v-if="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <PatientCard
              v-for="patient in paginatedPatients"
              :key="patient.id"
              :patient="patient"
              @click="handlePatientClick(patient)"
              @edit="handleEditPatient(patient)"
            />
          </div>

          <!-- List View -->
          <div v-else class="overflow-hidden">
            <table class="medical-table w-full">
              <thead>
                <tr>
                  <th class="text-left">Patient</th>
                  <th class="text-left">Contact</th>
                  <th class="text-left">Age</th>
                  <th class="text-left">Last Visit</th>
                  <th class="text-left">Status</th>
                  <th class="text-center">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <PatientRow
                  v-for="patient in paginatedPatients"
                  :key="patient.id"
                  :patient="patient"
                  @click="handlePatientClick(patient)"
                  @edit="handleEditPatient(patient)"
                  @view-records="handleViewRecords(patient)"
                />
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div v-if="totalPages > 1" class="mt-6 flex items-center justify-between">
            <div class="text-sm text-gray-700">
              Showing {{ (currentPage - 1) * itemsPerPage + 1 }} to 
              {{ Math.min(currentPage * itemsPerPage, filteredPatients.length) }} of 
              {{ filteredPatients.length }} results
            </div>
            <div class="flex items-center space-x-2">
              <button
                class="medical-button-secondary px-3 py-1 text-sm"
                :disabled="currentPage === 1"
                @click="currentPage--"
              >
                Previous
              </button>
              <span class="text-sm text-gray-500">
                Page {{ currentPage }} of {{ totalPages }}
              </span>
              <button
                class="medical-button-secondary px-3 py-1 text-sm"
                :disabled="currentPage === totalPages"
                @click="currentPage++"
              >
                Next
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import {
  MagnifyingGlassIcon,
  UserPlusIcon,
  DocumentArrowDownIcon,
  UsersIcon,
  Squares2X2Icon,
  ListBulletIcon,
} from '@heroicons/vue/24/outline'
import AppLayout from '@/components/common/AppLayout.vue'
import PatientCard from '@/components/patients/PatientCard.vue'
import PatientRow from '@/components/patients/PatientRow.vue'
import { useNotifications } from '@/stores/notifications'
import type { Patient } from '@/types/api.types'

// Router and notifications
const router = useRouter()
const { success } = useNotifications()

// State
const isLoading = ref(false)
const searchQuery = ref('')
const viewMode = ref<'grid' | 'list'>('grid')
const currentPage = ref(1)
const itemsPerPage = 12

// Filters
const filters = ref({
  ageGroup: '',
  gender: '',
})

// Mock patients data (will be replaced with API calls in Phase 2)
const patients = ref<Patient[]>([
  {
    id: 1,
    firstName: 'John',
    lastName: 'Doe',
    email: 'john.doe@email.com',
    phone: '+1 (555) 123-4567',
    dateOfBirth: '1985-01-15',
    gender: 'male',
    address: '123 Main St, City, ST 12345',
    allergies: 'None known',
    createdAt: '2024-01-15T00:00:00Z',
    updatedAt: '2024-01-15T00:00:00Z',
  },
  {
    id: 2,
    firstName: 'Jane',
    lastName: 'Smith',
    email: 'jane.smith@email.com',
    phone: '+1 (555) 987-6543',
    dateOfBirth: '1978-03-22',
    gender: 'female',
    address: '456 Oak Ave, City, ST 12345',
    allergies: 'Penicillin',
    createdAt: '2024-02-01T00:00:00Z',
    updatedAt: '2024-02-01T00:00:00Z',
  },
  {
    id: 3,
    firstName: 'Bob',
    lastName: 'Johnson',
    email: 'bob.johnson@email.com',
    phone: '+1 (555) 456-7890',
    dateOfBirth: '1992-07-10',
    gender: 'male',
    address: '789 Pine St, City, ST 12345',
    createdAt: '2024-03-01T00:00:00Z',
    updatedAt: '2024-03-01T00:00:00Z',
  },
])

// Computed
const filteredPatients = computed(() => {
  let filtered = [...patients.value]

  // Search filter
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(patient =>
      `${patient.firstName} ${patient.lastName}`.toLowerCase().includes(query) ||
      patient.email?.toLowerCase().includes(query) ||
      patient.phone?.includes(query)
    )
  }

  // Age group filter
  if (filters.value.ageGroup) {
    const currentYear = new Date().getFullYear()
    filtered = filtered.filter(patient => {
      const birthYear = new Date(patient.dateOfBirth).getFullYear()
      const age = currentYear - birthYear

      switch (filters.value.ageGroup) {
        case 'child': return age < 18
        case 'adult': return age >= 18 && age < 65
        case 'senior': return age >= 65
        default: return true
      }
    })
  }

  // Gender filter
  if (filters.value.gender) {
    filtered = filtered.filter(patient => patient.gender === filters.value.gender)
  }

  return filtered
})

const paginatedPatients = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage
  const end = start + itemsPerPage
  return filteredPatients.value.slice(start, end)
})

const totalPages = computed(() => Math.ceil(filteredPatients.value.length / itemsPerPage))

const totalPatients = computed(() => patients.value.length)
const newThisMonth = computed(() => {
  const thisMonth = new Date().getMonth()
  const thisYear = new Date().getFullYear()
  return patients.value.filter(patient => {
    const createdDate = new Date(patient.createdAt)
    return createdDate.getMonth() === thisMonth && createdDate.getFullYear() === thisYear
  }).length
})
const activePatients = computed(() => patients.value.length) // Mock: all patients are active

// Methods
const handleSearch = () => {
  currentPage.value = 1 // Reset to first page when searching
}

const handleNewPatient = () => {
  router.push('/patients/new')
}

const handlePatientClick = (patient: Patient) => {
  router.push(`/patients/${patient.id}`)
}

const handleEditPatient = (patient: Patient) => {
  router.push(`/patients/${patient.id}/edit`)
}

const handleViewRecords = (patient: Patient) => {
  router.push(`/patients/${patient.id}/records`)
}

const exportPatients = () => {
  success('Export Started', 'Patient data export will be available for download shortly.')
  // TODO: Implement export functionality
}

const loadPatients = async () => {
  isLoading.value = true
  // Simulate API loading
  await new Promise(resolve => setTimeout(resolve, 1000))
  isLoading.value = false
}

// Watch for filter changes
watch(() => [filters.value.ageGroup, filters.value.gender], () => {
  currentPage.value = 1 // Reset to first page when filters change
})

// Lifecycle
onMounted(() => {
  loadPatients()
})
</script>

<style scoped>
.patients-container {
  @apply max-w-7xl mx-auto;
}

.page-header {
  @apply flex-col sm:flex-row sm:items-center;
}

/* Mobile responsive adjustments */
@media (max-width: 640px) {
  .patients-container {
    @apply px-0;
  }

  .page-header {
    @apply space-y-4;
  }

  .search-filters .grid {
    @apply grid-cols-1 gap-3;
  }
}

/* Table responsive */
@media (max-width: 768px) {
  .overflow-hidden {
    @apply overflow-x-auto;
  }

  .medical-table {
    @apply min-w-full;
  }
}
</style>
