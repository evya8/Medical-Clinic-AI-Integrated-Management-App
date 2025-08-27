<template>
  <div class="advanced-search-container">
    <!-- Search Bar -->
    <div class="search-bar-container relative mb-6">
      <div class="medical-card p-4">
        <div class="flex items-center space-x-4">
          <!-- Search Input -->
          <div class="flex-1 relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
            </div>
            <input
              v-model="searchQuery"
              @input="handleSearch"
              @focus="showSuggestions = true"
              @keydown.enter="performSearch"
              @keydown.escape="showSuggestions = false"
              type="text"
              class="form-input pl-10 pr-4 py-3 w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-lg"
              :placeholder="searchPlaceholder"
            />
            
            <!-- Clear Button -->
            <button
              v-if="searchQuery"
              @click="clearSearch"
              class="absolute inset-y-0 right-0 pr-3 flex items-center"
            >
              <XMarkIcon class="h-5 w-5 text-gray-400 hover:text-gray-600" />
            </button>
          </div>

          <!-- Search Type Selector -->
          <div class="relative">
            <select 
              v-model="selectedSearchType"
              @change="handleSearchTypeChange"
              class="form-select rounded-lg border-gray-300 text-sm font-medium"
            >
              <option value="all">All Types</option>
              <option value="patients">Patients</option>
              <option value="appointments">Appointments</option>
              <option value="doctors">Doctors</option>
              <option value="medical_records">Medical Records</option>
            </select>
          </div>

          <!-- Advanced Filters Toggle -->
          <button
            @click="showAdvancedFilters = !showAdvancedFilters"
            class="advanced-filters-toggle"
            :class="{ 'active': showAdvancedFilters || hasActiveFilters }"
          >
            <AdjustmentsHorizontalIcon class="w-5 h-5 mr-2" />
            Filters
            <span v-if="activeFiltersCount > 0" class="filter-badge">{{ activeFiltersCount }}</span>
          </button>

          <!-- Search Button -->
          <button
            @click="performSearch"
            :disabled="isSearching"
            class="medical-button-primary"
          >
            <MagnifyingGlassIcon class="w-4 h-4 mr-2" />
            {{ isSearching ? 'Searching...' : 'Search' }}
          </button>
        </div>

        <!-- Search Suggestions Dropdown -->
        <div 
          v-if="showSuggestions && searchSuggestions.length > 0"
          class="search-suggestions absolute left-4 right-4 top-full mt-1 bg-white rounded-lg border border-gray-200 shadow-lg z-20 max-h-80 overflow-y-auto"
        >
          <div class="p-2">
            <div class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">
              Suggestions
            </div>
            <div
              v-for="(suggestion, index) in searchSuggestions"
              :key="suggestion.id"
              @click="selectSuggestion(suggestion)"
              class="search-suggestion-item"
              :class="{ 'bg-blue-50': selectedSuggestionIndex === index }"
            >
              <div class="flex items-center">
                <component 
                  :is="getSuggestionIcon(suggestion.type)"
                  class="w-4 h-4 mr-3 text-gray-500"
                />
                <div class="flex-1">
                  <div class="text-sm font-medium text-gray-900">{{ suggestion.title }}</div>
                  <div class="text-xs text-gray-500">{{ suggestion.subtitle }}</div>
                </div>
                <div class="text-xs text-gray-400 capitalize">{{ suggestion.type }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Advanced Filters Panel -->
    <div v-if="showAdvancedFilters" class="advanced-filters-panel mb-6">
      <div class="medical-card p-6">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-lg font-semibold text-gray-900">Advanced Filters</h3>
          <div class="flex items-center space-x-3">
            <!-- Saved Searches -->
            <div class="relative">
              <button
                @click="showSavedSearches = !showSavedSearches"
                class="text-sm text-blue-600 hover:text-blue-800 flex items-center"
              >
                <BookmarkIcon class="w-4 h-4 mr-1" />
                Saved Searches
              </button>
            </div>
            
            <!-- Clear Filters -->
            <button
              v-if="hasActiveFilters"
              @click="clearAllFilters"
              class="text-sm text-gray-600 hover:text-gray-800"
            >
              Clear All Filters
            </button>
          </div>
        </div>

        <!-- Filter Sections -->
        <div class="filters-grid grid grid-cols-1 lg:grid-cols-3 gap-8">
          <!-- General Filters -->
          <div class="filter-section">
            <h4 class="filter-section-title">General</h4>
            <div class="space-y-4">
              <!-- Date Range -->
              <div>
                <label class="filter-label">Date Range</label>
                <div class="grid grid-cols-2 gap-2">
                  <input
                    v-model="filters.dateFrom"
                    type="date"
                    class="form-input text-sm"
                  />
                  <input
                    v-model="filters.dateTo"
                    type="date"
                    class="form-input text-sm"
                  />
                </div>
              </div>

              <!-- Status -->
              <div>
                <label class="filter-label">Status</label>
                <div class="space-y-2">
                  <label
                    v-for="status in availableStatuses"
                    :key="status.value"
                    class="filter-checkbox-label"
                  >
                    <input
                      v-model="filters.status"
                      :value="status.value"
                      type="checkbox"
                      class="form-checkbox"
                    />
                    <span>{{ status.label }}</span>
                  </label>
                </div>
              </div>
            </div>
          </div>

          <!-- Patient-Specific Filters -->
          <div v-if="selectedSearchType === 'all' || selectedSearchType === 'patients'" class="filter-section">
            <h4 class="filter-section-title">Patient Filters</h4>
            <div class="space-y-4">
              <!-- Age Range -->
              <div>
                <label class="filter-label">Age Range</label>
                <div class="grid grid-cols-2 gap-2">
                  <input
                    v-model.number="filters.ageMin"
                    type="number"
                    placeholder="Min age"
                    class="form-input text-sm"
                  />
                  <input
                    v-model.number="filters.ageMax"
                    type="number"
                    placeholder="Max age"
                    class="form-input text-sm"
                  />
                </div>
              </div>

              <!-- Gender -->
              <div>
                <label class="filter-label">Gender</label>
                <div class="space-y-2">
                  <label
                    v-for="gender in availableGenders"
                    :key="gender"
                    class="filter-checkbox-label"
                  >
                    <input
                      v-model="filters.gender"
                      :value="gender"
                      type="checkbox"
                      class="form-checkbox"
                    />
                    <span class="capitalize">{{ gender.replace('_', ' ') }}</span>
                  </label>
                </div>
              </div>

              <!-- Insurance Provider -->
              <div>
                <label class="filter-label">Insurance Provider</label>
                <select v-model="filters.insuranceProvider" class="form-select text-sm">
                  <option value="">Any Provider</option>
                  <option v-for="provider in insuranceProviders" :key="provider" :value="provider">
                    {{ provider }}
                  </option>
                </select>
              </div>
            </div>
          </div>

          <!-- Appointment-Specific Filters -->
          <div v-if="selectedSearchType === 'all' || selectedSearchType === 'appointments'" class="filter-section">
            <h4 class="filter-section-title">Appointment Filters</h4>
            <div class="space-y-4">
              <!-- Appointment Type -->
              <div>
                <label class="filter-label">Appointment Type</label>
                <select v-model="filters.appointmentType" class="form-select text-sm">
                  <option value="">Any Type</option>
                  <option v-for="type in appointmentTypes" :key="type" :value="type">
                    {{ type }}
                  </option>
                </select>
              </div>

              <!-- Priority -->
              <div>
                <label class="filter-label">Priority</label>
                <div class="space-y-2">
                  <label
                    v-for="priority in appointmentPriorities"
                    :key="priority"
                    class="filter-checkbox-label"
                  >
                    <input
                      v-model="filters.priority"
                      :value="priority"
                      type="checkbox"
                      class="form-checkbox"
                    />
                    <span class="capitalize">{{ priority }}</span>
                  </label>
                </div>
              </div>

              <!-- Doctor -->
              <div>
                <label class="filter-label">Doctor</label>
                <select v-model="filters.doctorId" class="form-select text-sm">
                  <option value="">Any Doctor</option>
                  <option v-for="doctor in doctors" :key="doctor.id" :value="doctor.id">
                    Dr. {{ doctor.name }}
                  </option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <!-- Filter Actions -->
        <div class="filter-actions mt-8 pt-6 border-t border-gray-200">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
              <button
                @click="applyFilters"
                class="medical-button-primary"
              >
                Apply Filters
              </button>
              
              <button
                v-if="hasActiveFilters"
                @click="saveCurrentSearch"
                class="medical-button-secondary"
              >
                <BookmarkIcon class="w-4 h-4 mr-2" />
                Save Search
              </button>
            </div>

            <div class="text-sm text-gray-500">
              {{ activeFiltersCount }} filter{{ activeFiltersCount !== 1 ? 's' : '' }} active
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Active Filters Display -->
    <div v-if="hasActiveFilters && !showAdvancedFilters" class="active-filters mb-6">
      <div class="flex items-center space-x-2 flex-wrap">
        <span class="text-sm font-medium text-gray-700">Active filters:</span>
        <div
          v-for="filter in activeFiltersList"
          :key="filter.key"
          class="active-filter-tag"
        >
          <span>{{ filter.label }}: {{ filter.value }}</span>
          <button @click="removeFilter(filter.key)" class="ml-2">
            <XMarkIcon class="w-3 h-3" />
          </button>
        </div>
        <button
          @click="clearAllFilters"
          class="text-xs text-gray-500 hover:text-gray-700 underline ml-2"
        >
          Clear all
        </button>
      </div>
    </div>

    <!-- Search Results -->
    <div v-if="searchResults.length > 0 || isSearching" class="search-results">
      <!-- Results Header -->
      <div class="results-header mb-6">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-lg font-semibold text-gray-900">
              Search Results
              <span v-if="!isSearching" class="text-gray-500 font-normal">
                ({{ totalResults.toLocaleString() }} found)
              </span>
            </h3>
            <p v-if="searchQuery" class="text-sm text-gray-600 mt-1">
              Results for "<span class="font-medium">{{ searchQuery }}</span>"
            </p>
          </div>
          
          <div class="flex items-center space-x-3">
            <!-- Sort Options -->
            <select v-model="sortBy" @change="handleSortChange" class="form-select text-sm">
              <option value="relevance">Sort by Relevance</option>
              <option value="date">Sort by Date</option>
              <option value="name">Sort by Name</option>
            </select>
            
            <!-- Export Results -->
            <button
              @click="exportResults"
              :disabled="searchResults.length === 0"
              class="medical-button-secondary"
            >
              <DocumentArrowDownIcon class="w-4 h-4 mr-2" />
              Export
            </button>
          </div>
        </div>
      </div>

      <!-- Results List -->
      <div v-if="isSearching" class="space-y-4">
        <div v-for="n in 5" :key="n" class="skeleton h-20 rounded-lg"></div>
      </div>
      
      <div v-else class="space-y-4">
        <div
          v-for="result in searchResults"
          :key="result.id"
          class="search-result-item"
          @click="selectResult(result)"
        >
          <div class="flex items-start space-x-4">
            <div class="result-icon">
              <component :is="getResultIcon(result.type)" class="w-5 h-5 text-gray-500" />
            </div>
            <div class="flex-1">
              <div class="flex items-start justify-between">
                <div>
                  <h4 class="text-base font-medium text-gray-900">{{ result.title }}</h4>
                  <p class="text-sm text-gray-600 mt-1">{{ result.description }}</p>
                  <div class="flex items-center mt-2 space-x-4 text-xs text-gray-500">
                    <span class="capitalize">{{ result.type.replace('_', ' ') }}</span>
                    <span>•</span>
                    <span>{{ formatDate(result.date) }}</span>
                    <span v-if="result.status" class="result-status" :class="`status-${result.status}`">
                      {{ result.status }}
                    </span>
                  </div>
                </div>
                <button class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                  View Details →
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Load More / Pagination -->
      <div v-if="hasMoreResults" class="text-center mt-8">
        <button
          @click="loadMoreResults"
          :disabled="isLoadingMore"
          class="medical-button-secondary"
        >
          {{ isLoadingMore ? 'Loading...' : 'Load More Results' }}
        </button>
      </div>
    </div>

    <!-- No Results -->
    <div v-else-if="hasSearched && !isSearching" class="no-results text-center py-12">
      <MagnifyingGlassIcon class="mx-auto h-12 w-12 text-gray-400 mb-4" />
      <h3 class="text-lg font-medium text-gray-900 mb-2">No results found</h3>
      <p class="text-gray-500 mb-6">
        We couldn't find anything matching your search criteria.
      </p>
      <div class="space-x-4">
        <button @click="clearSearch" class="medical-button-secondary">
          Clear Search
        </button>
        <button @click="showSearchTips = true" class="text-blue-600 hover:text-blue-800 text-sm">
          Search Tips
        </button>
      </div>
    </div>

    <!-- Save Search Modal -->
    <div v-if="showSaveSearchModal" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75"></div>
        <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left shadow-xl transform sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
          <div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
              Save Search
            </h3>
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">Search Name</label>
                <input
                  v-model="saveSearchForm.name"
                  type="text"
                  class="form-input mt-1"
                  placeholder="e.g., Overdue appointments"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Description (optional)</label>
                <textarea
                  v-model="saveSearchForm.description"
                  class="form-textarea mt-1"
                  rows="2"
                  placeholder="Brief description of this search"
                ></textarea>
              </div>
            </div>
          </div>
          <div class="mt-6 flex justify-end space-x-3">
            <button @click="cancelSaveSearch" class="medical-button-secondary">
              Cancel
            </button>
            <button @click="confirmSaveSearch" class="medical-button-primary">
              Save Search
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import {
  MagnifyingGlassIcon,
  AdjustmentsHorizontalIcon,
  XMarkIcon,
  BookmarkIcon,
  DocumentArrowDownIcon,
  UsersIcon,
  CalendarIcon,
  UserIcon,
  DocumentTextIcon,
} from '@heroicons/vue/24/outline'
import { useNotifications } from '@/stores/notifications'

// Props
interface Props {
  entityTypes?: string[]
  initialSearchType?: string
  onResultSelect?: (result: any) => void
}

const props = withDefaults(defineProps<Props>(), {
  entityTypes: () => ['all', 'patients', 'appointments', 'doctors', 'medical_records'],
  initialSearchType: 'all',
})

const { success, error } = useNotifications()

// State
const searchQuery = ref('')
const selectedSearchType = ref(props.initialSearchType)
const showSuggestions = ref(false)
const selectedSuggestionIndex = ref(-1)
const showAdvancedFilters = ref(false)
const showSavedSearches = ref(false)
const isSearching = ref(false)
const isLoadingMore = ref(false)
const hasSearched = ref(false)
const sortBy = ref('relevance')
const showSaveSearchModal = ref(false)
const showSearchTips = ref(false)

// Search suggestions
const searchSuggestions = ref([
  { id: 1, title: 'John Doe', subtitle: 'john.doe@email.com', type: 'patient' },
  { id: 2, title: 'Cardiology Appointment', subtitle: 'Today 2:00 PM', type: 'appointment' },
  { id: 3, title: 'Dr. Sarah Johnson', subtitle: 'Cardiologist', type: 'doctor' },
])

// Search results
const searchResults = ref<any[]>([])
const totalResults = ref(0)
const hasMoreResults = ref(false)

// Filters
const filters = ref({
  dateFrom: '',
  dateTo: '',
  status: [] as string[],
  ageMin: null as number | null,
  ageMax: null as number | null,
  gender: [] as string[],
  insuranceProvider: '',
  appointmentType: '',
  priority: [] as string[],
  doctorId: '',
})

// Save search form
const saveSearchForm = ref({
  name: '',
  description: '',
})

// Mock data
const availableStatuses = [
  { value: 'active', label: 'Active' },
  { value: 'completed', label: 'Completed' },
  { value: 'cancelled', label: 'Cancelled' },
  { value: 'scheduled', label: 'Scheduled' },
]

const availableGenders = ['male', 'female', 'other', 'prefer_not_to_say']
const insuranceProviders = ['Blue Cross', 'Aetna', 'Cigna', 'UnitedHealth', 'Medicare']
const appointmentTypes = ['Consultation', 'Follow-up', 'Emergency', 'Procedure', 'Check-up']
const appointmentPriorities = ['low', 'normal', 'high', 'urgent']
const doctors = [
  { id: 1, name: 'Sarah Johnson' },
  { id: 2, name: 'Michael Chen' },
  { id: 3, name: 'Emily Rodriguez' },
]

// Computed
const searchPlaceholder = computed(() => {
  const typeMap = {
    all: 'Search patients, appointments, doctors...',
    patients: 'Search patients by name, email, or phone...',
    appointments: 'Search appointments by patient or date...',
    doctors: 'Search doctors by name or specialty...',
    medical_records: 'Search medical records...',
  }
  return typeMap[selectedSearchType.value as keyof typeof typeMap] || 'Search...'
})

const hasActiveFilters = computed(() => {
  return Object.values(filters.value).some(value => {
    if (Array.isArray(value)) return value.length > 0
    return value !== '' && value !== null
  })
})

const activeFiltersCount = computed(() => {
  let count = 0
  Object.entries(filters.value).forEach(([_, value]) => {
    if (Array.isArray(value) && value.length > 0) count++
    else if (value !== '' && value !== null) count++
  })
  return count
})

const activeFiltersList = computed(() => {
  const list: Array<{ key: string; label: string; value: string }> = []
  
  Object.entries(filters.value).forEach(([key, value]) => {
    if (Array.isArray(value) && value.length > 0) {
      list.push({
        key,
        label: getFilterLabel(key),
        value: value.join(', ')
      })
    } else if (value !== '' && value !== null) {
      list.push({
        key,
        label: getFilterLabel(key),
        value: String(value)
      })
    }
  })
  
  return list
})

// Methods
const handleSearch = () => {
  if (searchQuery.value.length > 2) {
    // Simulate search suggestions
    showSuggestions.value = true
  } else {
    showSuggestions.value = false
  }
}

const handleSearchTypeChange = () => {
  clearAllFilters()
  if (searchQuery.value) {
    performSearch()
  }
}

const performSearch = async () => {
  if (!searchQuery.value && !hasActiveFilters.value) return
  
  isSearching.value = true
  hasSearched.value = true
  showSuggestions.value = false
  
  try {
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    // Mock search results
    searchResults.value = generateMockResults()
    totalResults.value = Math.floor(Math.random() * 500) + 50
    hasMoreResults.value = searchResults.value.length < totalResults.value
    
    success('Search completed', `Found ${totalResults.value} results`)
  } catch (err) {
    error('Search failed', 'An error occurred while searching')
  } finally {
    isSearching.value = false
  }
}

const clearSearch = () => {
  searchQuery.value = ''
  searchResults.value = []
  totalResults.value = 0
  hasSearched.value = false
  showSuggestions.value = false
}

const clearAllFilters = () => {
  filters.value = {
    dateFrom: '',
    dateTo: '',
    status: [],
    ageMin: null,
    ageMax: null,
    gender: [],
    insuranceProvider: '',
    appointmentType: '',
    priority: [],
    doctorId: '',
  }
}

const applyFilters = () => {
  performSearch()
  showAdvancedFilters.value = false
}

const removeFilter = (filterKey: string) => {
  if (Array.isArray(filters.value[filterKey as keyof typeof filters.value])) {
    (filters.value[filterKey as keyof typeof filters.value] as any[]).length = 0
  } else {
    (filters.value as any)[filterKey] = ''
  }
  performSearch()
}

const saveCurrentSearch = () => {
  showSaveSearchModal.value = true
}

const confirmSaveSearch = () => {
  // Save search logic here
  success('Search saved', `"${saveSearchForm.value.name}" has been saved`)
  showSaveSearchModal.value = false
  saveSearchForm.value = { name: '', description: '' }
}

const cancelSaveSearch = () => {
  showSaveSearchModal.value = false
  saveSearchForm.value = { name: '', description: '' }
}

const selectSuggestion = (suggestion: any) => {
  searchQuery.value = suggestion.title
  showSuggestions.value = false
  performSearch()
}

const selectResult = (result: any) => {
  if (props.onResultSelect) {
    props.onResultSelect(result)
  }
}

const handleSortChange = () => {
  // Re-sort results
  performSearch()
}

const exportResults = () => {
  // Export search results
  success('Export started', 'Search results are being exported')
}

const loadMoreResults = async () => {
  isLoadingMore.value = true
  
  try {
    await new Promise(resolve => setTimeout(resolve, 1000))
    const newResults = generateMockResults()
    searchResults.value.push(...newResults)
    hasMoreResults.value = searchResults.value.length < totalResults.value
  } finally {
    isLoadingMore.value = false
  }
}

const getSuggestionIcon = (type: string) => {
  const iconMap = {
    patient: UsersIcon,
    appointment: CalendarIcon,
    doctor: UserIcon,
    medical_record: DocumentTextIcon,
  }
  return iconMap[type as keyof typeof iconMap] || DocumentTextIcon
}

const getResultIcon = (type: string) => getSuggestionIcon(type)

const getFilterLabel = (key: string) => {
  const labelMap = {
    dateFrom: 'Date From',
    dateTo: 'Date To',
    status: 'Status',
    ageMin: 'Min Age',
    ageMax: 'Max Age',
    gender: 'Gender',
    insuranceProvider: 'Insurance Provider',
    appointmentType: 'Appointment Type',
    priority: 'Priority',
    doctorId: 'Doctor',
  }
  return labelMap[key as keyof typeof labelMap] || key
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString()
}

const generateMockResults = () => {
  const resultTypes = ['patient', 'appointment', 'doctor', 'medical_record']
  const results = []
  
  for (let i = 0; i < 10; i++) {
    const type = resultTypes[Math.floor(Math.random() * resultTypes.length)]
    results.push({
      id: Date.now() + i,
      type,
      title: generateMockTitle(type),
      description: generateMockDescription(type),
      date: new Date(Date.now() - Math.random() * 30 * 24 * 60 * 60 * 1000).toISOString(),
      status: availableStatuses[Math.floor(Math.random() * availableStatuses.length)].value,
    })
  }
  
  return results
}

const generateMockTitle = (type: string) => {
  const titleMap = {
    patient: `Patient ${Math.floor(Math.random() * 1000)}`,
    appointment: `Appointment with Dr. ${Math.floor(Math.random() * 10)}`,
    doctor: `Dr. ${Math.floor(Math.random() * 100)}`,
    medical_record: `Medical Record ${Math.floor(Math.random() * 500)}`,
  }
  return titleMap[type as keyof typeof titleMap] || 'Unknown'
}

const generateMockDescription = (type: string) => {
  const descMap = {
    patient: 'Patient information and medical history',
    appointment: 'Scheduled appointment details',
    doctor: 'Doctor profile and specialization',
    medical_record: 'Medical record and documentation',
  }
  return descMap[type as keyof typeof descMap] || 'No description'
}

// Watchers
watch(searchQuery, (newValue) => {
  if (newValue.length === 0) {
    showSuggestions.value = false
  }
})

onMounted(() => {
  // Initialize component
})
</script>

<style lang="postcss" scoped>
.advanced-search-container {
  @apply max-w-6xl mx-auto;
}

.advanced-filters-toggle {
  @apply inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 relative;
}

.advanced-filters-toggle.active {
  @apply bg-blue-50 border-blue-300 text-blue-700;
}

.filter-badge {
  @apply absolute -top-2 -right-2 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-500 rounded-full;
}

.search-suggestions {
  @apply z-20;
}

.search-suggestion-item {
  @apply px-3 py-2 cursor-pointer hover:bg-gray-50 rounded transition-colors;
}

.filter-section-title {
  @apply text-sm font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-200;
}

.filter-label {
  @apply block text-sm font-medium text-gray-700 mb-2;
}

.filter-checkbox-label {
  @apply flex items-center text-sm text-gray-700 cursor-pointer;
}

.filter-checkbox-label input {
  @apply mr-2 rounded;
}

.active-filter-tag {
  @apply inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800;
}

.search-result-item {
  @apply medical-card p-6 cursor-pointer hover:shadow-md transition-shadow;
}

.result-icon {
  @apply w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center flex-shrink-0;
}

.result-status {
  @apply inline-flex items-center px-2 py-1 rounded-full text-xs font-medium;
}

.result-status.status-active {
  @apply bg-green-100 text-green-800;
}

.result-status.status-completed {
  @apply bg-blue-100 text-blue-800;
}

.result-status.status-cancelled {
  @apply bg-red-100 text-red-800;
}

.result-status.status-scheduled {
  @apply bg-yellow-100 text-yellow-800;
}

/* Responsive design */
@media (max-width: 1024px) {
  .filters-grid {
    @apply grid-cols-1 lg:grid-cols-2;
  }
}

@media (max-width: 768px) {
  .search-bar-container .flex {
    @apply flex-col space-y-4 space-x-0;
  }
  
  .filters-grid {
    @apply grid-cols-1;
  }
  
  .results-header .flex {
    @apply flex-col space-y-4 items-start;
  }
}

/* Animation */
.advanced-filters-panel {
  animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
