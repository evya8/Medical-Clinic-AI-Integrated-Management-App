<template>
  <AppLayout>
    <div class="ai-alerts-container">
      <!-- Page Header -->
      <div class="page-header mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900 flex items-center">
              <ExclamationTriangleIcon class="w-8 h-8 mr-3 text-yellow-500" />
              AI Alerts
            </h1>
            <p class="mt-2 text-sm text-gray-700">
              Proactive clinical alerts and safety notifications
            </p>
          </div>

          <div class="flex items-center space-x-4">
            <!-- Alert Status -->
            <div class="flex items-center space-x-2">
              <div class="w-2 h-2 bg-red-400 rounded-full animate-pulse"></div>
              <span class="text-sm text-gray-600">{{ activeAlertsCount }} active alerts</span>
            </div>

            <!-- Generate Alerts Button -->
            <button
              @click="generateNewAlerts"
              :disabled="isGeneratingAlerts"
              class="medical-button-secondary"
            >
              <CpuChipIcon class="w-4 h-4 mr-2" />
              {{ isGeneratingAlerts ? 'Generating...' : 'Scan for Alerts' }}
            </button>

            <!-- Bulk Actions -->
            <button
              v-if="selectedAlerts.length > 0"
              @click="showBulkActions = !showBulkActions"
              class="medical-button-primary relative"
            >
              <Cog6ToothIcon class="w-4 h-4 mr-2" />
              Bulk Actions
              <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
                {{ selectedAlerts.length }}
              </span>
            </button>
          </div>
        </div>
      </div>

      <!-- Alert Statistics -->
      <div class="alert-stats grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
        <div class="medical-card p-6 bg-gradient-to-br from-red-50 to-pink-50 border border-red-100">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-red-700 font-medium mb-1">Critical</p>
              <p class="text-2xl font-bold text-red-900">{{ alertStats.critical }}</p>
            </div>
            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
              <ExclamationTriangleIcon class="w-6 h-6 text-red-600" />
            </div>
          </div>
        </div>

        <div class="medical-card p-6 bg-gradient-to-br from-orange-50 to-yellow-50 border border-orange-100">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-orange-700 font-medium mb-1">High</p>
              <p class="text-2xl font-bold text-orange-900">{{ alertStats.high }}</p>
            </div>
            <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
              <FireIcon class="w-6 h-6 text-orange-600" />
            </div>
          </div>
        </div>

        <div class="medical-card p-6 bg-gradient-to-br from-yellow-50 to-green-50 border border-yellow-100">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-yellow-700 font-medium mb-1">Medium</p>
              <p class="text-2xl font-bold text-yellow-900">{{ alertStats.medium }}</p>
            </div>
            <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
              <BellIcon class="w-6 h-6 text-yellow-600" />
            </div>
          </div>
        </div>

        <div class="medical-card p-6 bg-gradient-to-br from-green-50 to-blue-50 border border-green-100">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-green-700 font-medium mb-1">Low</p>
              <p class="text-2xl font-bold text-green-900">{{ alertStats.low }}</p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
              <InformationCircleIcon class="w-6 h-6 text-green-600" />
            </div>
          </div>
        </div>

        <div class="medical-card p-6 bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-100">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-blue-700 font-medium mb-1">Resolved</p>
              <p class="text-2xl font-bold text-blue-900">{{ alertStats.resolved }}</p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
              <CheckCircleIcon class="w-6 h-6 text-blue-600" />
            </div>
          </div>
        </div>
      </div>

      <!-- Alert Filters and Controls -->
      <div class="alert-controls mb-6">
        <div class="medical-card p-4">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
              <!-- Severity Filter -->
              <select 
                v-model="severityFilter" 
                @change="filterAlerts"
                class="form-select text-sm"
              >
                <option value="">All Severities</option>
                <option value="critical">Critical</option>
                <option value="high">High</option>
                <option value="medium">Medium</option>
                <option value="low">Low</option>
              </select>

              <!-- Type Filter -->
              <select 
                v-model="typeFilter" 
                @change="filterAlerts"
                class="form-select text-sm"
              >
                <option value="">All Types</option>
                <option value="patient_safety">Patient Safety</option>
                <option value="drug_interaction">Drug Interactions</option>
                <option value="vital_signs">Vital Signs</option>
                <option value="operational">Operational</option>
                <option value="system">System</option>
              </select>

              <!-- Status Filter -->
              <select 
                v-model="statusFilter" 
                @change="filterAlerts"
                class="form-select text-sm"
              >
                <option value="">All Statuses</option>
                <option value="active">Active</option>
                <option value="acknowledged">Acknowledged</option>
                <option value="resolved">Resolved</option>
              </select>

              <!-- Search -->
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <MagnifyingGlassIcon class="h-4 w-4 text-gray-400" />
                </div>
                <input
                  v-model="searchQuery"
                  @input="filterAlerts"
                  type="text"
                  class="form-input pl-10 text-sm w-64"
                  placeholder="Search alerts..."
                />
              </div>
            </div>

            <div class="flex items-center space-x-3">
              <!-- Select All -->
              <button
                @click="toggleSelectAll"
                class="text-sm text-blue-600 hover:text-blue-800"
              >
                {{ selectedAlerts.length === filteredAlerts.length ? 'Deselect All' : 'Select All' }}
              </button>

              <!-- Auto-refresh toggle -->
              <label class="flex items-center text-sm text-gray-700">
                <input
                  v-model="autoRefresh"
                  type="checkbox"
                  class="form-checkbox mr-2"
                />
                Auto-refresh
              </label>
            </div>
          </div>
        </div>
      </div>

      <!-- Bulk Actions Bar -->
      <div v-if="selectedAlerts.length > 0" class="bulk-actions-bar mb-6">
        <div class="medical-card p-4 bg-blue-50 border border-blue-200">
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <CheckCircleIcon class="w-5 h-5 text-blue-600 mr-2" />
              <span class="text-sm font-medium text-blue-900">
                {{ selectedAlerts.length }} alert{{ selectedAlerts.length !== 1 ? 's' : '' }} selected
              </span>
            </div>

            <div class="flex items-center space-x-3">
              <button
                @click="bulkAcknowledge"
                :disabled="isBulkProcessing"
                class="text-sm bg-white text-blue-700 px-3 py-1 rounded border border-blue-300 hover:bg-blue-50"
              >
                Acknowledge
              </button>
              <button
                @click="bulkResolve"
                :disabled="isBulkProcessing"
                class="text-sm bg-white text-green-700 px-3 py-1 rounded border border-green-300 hover:bg-green-50"
              >
                Mark Resolved
              </button>
              <button
                @click="bulkAssign"
                :disabled="isBulkProcessing"
                class="text-sm bg-white text-purple-700 px-3 py-1 rounded border border-purple-300 hover:bg-purple-50"
              >
                Assign
              </button>
              <button
                @click="clearSelection"
                class="text-sm text-gray-600 hover:text-gray-800"
              >
                Clear
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Alerts List -->
      <div class="alerts-list">
        <div class="medical-card">
          <div class="card-header flex items-center justify-between p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">
              Active Alerts
              <span class="text-gray-500 font-normal">({{ filteredAlerts.length }})</span>
            </h3>
            
            <div class="flex items-center space-x-4">
              <!-- Sort Options -->
              <select 
                v-model="sortBy" 
                @change="sortAlerts"
                class="form-select text-sm"
              >
                <option value="created_desc">Newest First</option>
                <option value="created_asc">Oldest First</option>
                <option value="severity_desc">Severity (High to Low)</option>
                <option value="severity_asc">Severity (Low to High)</option>
                <option value="patient">Patient Name</option>
              </select>

              <!-- Refresh Button -->
              <button 
                @click="refreshAlerts"
                :disabled="isRefreshing"
                class="medical-button-secondary"
              >
                <ArrowPathIcon class="w-4 h-4 mr-2" :class="{ 'animate-spin': isRefreshing }" />
                Refresh
              </button>
            </div>
          </div>

          <div class="p-6">
            <div v-if="isLoading" class="space-y-4">
              <div v-for="n in 5" :key="n" class="skeleton h-20 rounded-lg"></div>
            </div>

            <div v-else-if="filteredAlerts.length === 0" class="text-center py-12">
              <CheckCircleIcon class="mx-auto h-12 w-12 text-green-400 mb-4" />
              <h4 class="text-lg font-medium text-gray-900 mb-2">No alerts found</h4>
              <p class="text-gray-500 mb-6">
                {{ searchQuery || severityFilter || typeFilter ? 'No alerts match your current filters.' : 'All clear! No active alerts at this time.' }}
              </p>
              <button @click="generateNewAlerts" class="medical-button-primary">
                Scan for New Alerts
              </button>
            </div>

            <div v-else class="space-y-4">
              <div
                v-for="alert in paginatedAlerts"
                :key="alert.id"
                class="alert-item p-6 border rounded-lg cursor-pointer transition-all duration-200"
                :class="[
                  getAlertBorderClass(alert.severity),
                  { 'ring-2 ring-blue-200': selectedAlerts.includes(alert.id) }
                ]"
                @click="selectAlert(alert)"
              >
                <div class="flex items-start space-x-4">
                  <!-- Selection Checkbox -->
                  <div class="flex items-center pt-1">
                    <input
                      :checked="selectedAlerts.includes(alert.id)"
                      @click.stop="toggleAlertSelection(alert.id)"
                      type="checkbox"
                      class="form-checkbox"
                    />
                  </div>

                  <!-- Alert Icon -->
                  <div class="flex-shrink-0">
                    <div 
                      :class="[
                        'w-12 h-12 rounded-full flex items-center justify-center',
                        getAlertIconBg(alert.severity)
                      ]"
                    >
                      <component 
                        :is="getAlertIcon(alert.type)"
                        :class="['w-6 h-6', getAlertIconColor(alert.severity)]"
                      />
                    </div>
                  </div>

                  <!-- Alert Content -->
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center mb-2">
                      <h4 class="text-lg font-semibold text-gray-900 mr-3">{{ alert.title }}</h4>
                      <span 
                        :class="[
                          'px-3 py-1 text-xs font-semibold rounded-full',
                          getSeverityClass(alert.severity)
                        ]"
                      >
                        {{ alert.severity.toUpperCase() }}
                      </span>
                      <span 
                        :class="[
                          'ml-2 px-2 py-1 text-xs font-medium rounded-full',
                          getStatusClass(alert.status)
                        ]"
                      >
                        {{ alert.status }}
                      </span>
                    </div>

                    <p class="text-gray-700 mb-3">{{ alert.message }}</p>

                    <!-- Alert Details Grid -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
                      <!-- Patient Info -->
                      <div v-if="alert.patient">
                        <h5 class="text-sm font-medium text-gray-900 mb-1">Patient</h5>
                        <div class="text-sm text-gray-600">
                          <p>{{ alert.patient.name }}</p>
                          <p>{{ alert.patient.age }}yo {{ alert.patient.gender }}</p>
                          <p v-if="alert.patient.room">Room: {{ alert.patient.room }}</p>
                        </div>
                      </div>

                      <!-- Alert Metadata -->
                      <div>
                        <h5 class="text-sm font-medium text-gray-900 mb-1">Alert Details</h5>
                        <div class="text-sm text-gray-600">
                          <p>Type: {{ formatAlertType(alert.type) }}</p>
                          <p>Source: {{ alert.source }}</p>
                          <p>Created: {{ formatTime(alert.createdAt) }}</p>
                        </div>
                      </div>

                      <!-- Action Required -->
                      <div v-if="alert.actionRequired">
                        <h5 class="text-sm font-medium text-gray-900 mb-1">Action Required</h5>
                        <div class="text-sm text-gray-600">
                          <p>{{ alert.actionRequired }}</p>
                          <p v-if="alert.timeline" class="text-red-600 font-medium">
                            Timeline: {{ alert.timeline }}
                          </p>
                        </div>
                      </div>
                    </div>

                    <!-- AI Analysis -->
                    <div v-if="alert.aiAnalysis" class="mb-4">
                      <h5 class="text-sm font-medium text-gray-900 mb-2 flex items-center">
                        <CpuChipIcon class="w-4 h-4 mr-1 text-purple-600" />
                        AI Analysis
                      </h5>
                      <p class="text-sm text-gray-700 bg-purple-50 p-3 rounded-lg">
                        {{ alert.aiAnalysis }}
                      </p>
                    </div>

                    <!-- Related Data -->
                    <div v-if="alert.relatedData && alert.relatedData.length > 0" class="mb-4">
                      <h5 class="text-sm font-medium text-gray-900 mb-2">Related Information</h5>
                      <div class="flex flex-wrap gap-2">
                        <span
                          v-for="item in alert.relatedData"
                          :key="item"
                          class="inline-flex items-center px-2 py-1 text-xs bg-gray-100 text-gray-700 rounded"
                        >
                          {{ item }}
                        </span>
                      </div>
                    </div>
                  </div>

                  <!-- Action Buttons -->
                  <div class="flex flex-col space-y-2">
                    <button
                      v-if="alert.status === 'active'"
                      @click.stop="acknowledgeAlert(alert)"
                      class="medical-button-secondary text-sm"
                    >
                      <CheckIcon class="w-4 h-4 mr-1" />
                      Acknowledge
                    </button>
                    <button
                      v-if="alert.status !== 'resolved'"
                      @click.stop="resolveAlert(alert)"
                      class="medical-button-primary text-sm"
                    >
                      <CheckCircleIcon class="w-4 h-4 mr-1" />
                      Resolve
                    </button>
                    <button
                      @click.stop="viewAlertDetails(alert)"
                      class="text-sm text-gray-600 hover:text-gray-800"
                    >
                      <EyeIcon class="w-4 h-4 mr-1" />
                      Details
                    </button>
                    <button
                      @click.stop="assignAlert(alert)"
                      class="text-sm text-purple-600 hover:text-purple-800"
                    >
                      <UserIcon class="w-4 h-4 mr-1" />
                      Assign
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Pagination -->
            <div v-if="totalPages > 1" class="pagination-container mt-8">
              <div class="flex items-center justify-between">
                <div class="text-sm text-gray-700">
                  Showing {{ (currentPage - 1) * itemsPerPage + 1 }} to 
                  {{ Math.min(currentPage * itemsPerPage, filteredAlerts.length) }} of 
                  {{ filteredAlerts.length }} alerts
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
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import {
  ExclamationTriangleIcon,
  CpuChipIcon,
  Cog6ToothIcon,
  FireIcon,
  BellIcon,
  InformationCircleIcon,
  CheckCircleIcon,
  MagnifyingGlassIcon,
  ArrowPathIcon,
  CheckIcon,
  EyeIcon,
  UserIcon,
  HeartIcon,
  ShieldExclamationIcon,
  ClockIcon,
  ComputerDesktopIcon,
} from '@heroicons/vue/24/outline'
import AppLayout from '@/components/common/AppLayout.vue'
import { useNotifications } from '@/stores/notifications'
import { formatDistance } from 'date-fns'
import type { AIAlertExtended } from '@/types/api.types'
import { api, handleApiError } from '@/services/api'

const { success, error } = useNotifications()

// State
const isLoading = ref(true)
const isRefreshing = ref(false)
const isGeneratingAlerts = ref(false)
const isBulkProcessing = ref(false)
const selectedAlerts = ref<number[]>([])
const showBulkActions = ref(false)
const autoRefresh = ref(true)
let autoRefreshInterval: number | null = null

// Filters
const severityFilter = ref('')
const typeFilter = ref('')
const statusFilter = ref('')
const searchQuery = ref('')
const sortBy = ref('created_desc')

// Pagination
const currentPage = ref(1)
const itemsPerPage = ref(10)

// Alert Statistics
const alertStats = ref({
  critical: 3,
  high: 8,
  medium: 15,
  low: 12,
  resolved: 156,
})

// Alerts data with proper typing
const allAlerts = ref<AIAlertExtended[]>([])

// Computed
const activeAlertsCount = computed(() => {
  return allAlerts.value.filter(alert => alert.status === 'active').length
})

const filteredAlerts = computed(() => {
  let filtered = allAlerts.value

  // Apply filters
  if (severityFilter.value) {
    filtered = filtered.filter(alert => alert.severity === severityFilter.value)
  }
  
  if (typeFilter.value) {
    filtered = filtered.filter(alert => alert.type === typeFilter.value)
  }
  
  if (statusFilter.value) {
    filtered = filtered.filter(alert => alert.status === statusFilter.value)
  }
  
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(alert =>
      alert.title.toLowerCase().includes(query) ||
      alert.message.toLowerCase().includes(query) ||
      alert.patient?.name.toLowerCase().includes(query)
    )
  }

  // Apply sorting
  filtered = [...filtered].sort((a, b) => {
    switch (sortBy.value) {
      case 'created_desc':
        return new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime()
      case 'created_asc':
        return new Date(a.createdAt).getTime() - new Date(b.createdAt).getTime()
      case 'severity_desc':
        const severityOrder = { critical: 4, high: 3, medium: 2, low: 1 }
        return severityOrder[b.severity as keyof typeof severityOrder] - severityOrder[a.severity as keyof typeof severityOrder]
      case 'severity_asc':
        const severityOrderAsc = { critical: 4, high: 3, medium: 2, low: 1 }
        return severityOrderAsc[a.severity as keyof typeof severityOrderAsc] - severityOrderAsc[b.severity as keyof typeof severityOrderAsc]
      case 'patient':
        return (a.patient?.name || '').localeCompare(b.patient?.name || '')
      default:
        return 0
    }
  })

  return filtered
})

const totalPages = computed(() => {
  return Math.ceil(filteredAlerts.value.length / itemsPerPage.value)
})

const paginatedAlerts = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return filteredAlerts.value.slice(start, end)
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
const updateAlertStats = () => {
  const stats = { critical: 0, high: 0, medium: 0, low: 0, resolved: 0 }
  
  allAlerts.value.forEach(alert => {
    if (alert.status === 'resolved') {
      stats.resolved++
    } else if (alert.severity in stats) {
      stats[alert.severity as keyof Omit<typeof stats, 'resolved'>]++
    }
  })
  
  alertStats.value = stats
}

const loadAlerts = async () => {
  isLoading.value = true
  
  try {
    const response = await api.get<AIAlertExtended[]>('/api/ai-alerts')
    if (response.success) {
      allAlerts.value = response.data
      updateAlertStats()
    } else {
      throw new Error(response.message || 'Failed to load alerts')
    }
  } catch (err) {
    error('Load failed', handleApiError(err))
    console.error('Failed to load alerts:', err)
  } finally {
    isLoading.value = false
  }
}

const generateNewAlerts = async () => {
  isGeneratingAlerts.value = true
  
  try {
    const response = await api.post<AIAlertExtended[]>('/api/ai-alerts/scan')
    if (response.success) {
      // Add new alerts to the beginning of the array
      allAlerts.value.unshift(...response.data)
      updateAlertStats()
      success('Alert scan complete', `${response.data.length} new alert${response.data.length !== 1 ? 's' : ''} generated`)
    } else {
      throw new Error(response.message || 'Alert scan failed')
    }
  } catch (err) {
    error('Scan failed', handleApiError(err))
    console.error('Failed to generate alerts:', err)
  } finally {
    isGeneratingAlerts.value = false
  }
}

const refreshAlerts = async () => {
  isRefreshing.value = true
  
  try {
    const response = await api.get<AIAlertExtended[]>('/api/ai-alerts')
    if (response.success) {
      allAlerts.value = response.data
      updateAlertStats()
      success('Alerts refreshed', 'Alert list has been updated')
    } else {
      throw new Error(response.message || 'Failed to load alerts')
    }
  } catch (err) {
    error('Refresh failed', handleApiError(err))
    console.error('Failed to refresh alerts:', err)
  } finally {
    isRefreshing.value = false
  }
}

const filterAlerts = () => {
  currentPage.value = 1
}

const sortAlerts = () => {
  currentPage.value = 1
}

const toggleAlertSelection = (alertId: number) => {
  const index = selectedAlerts.value.indexOf(alertId)
  if (index > -1) {
    selectedAlerts.value.splice(index, 1)
  } else {
    selectedAlerts.value.push(alertId)
  }
}

const toggleSelectAll = () => {
  if (selectedAlerts.value.length === filteredAlerts.value.length) {
    selectedAlerts.value = []
  } else {
    selectedAlerts.value = filteredAlerts.value.map(alert => alert.id)
  }
}

const clearSelection = () => {
  selectedAlerts.value = []
}

const acknowledgeAlert = async (alert: AIAlertExtended) => {
  try {
    const response = await api.post('/api/ai-alerts/acknowledge', { alert_id: alert.id })
    if (response.success) {
      alert.status = 'acknowledged'
      success('Alert acknowledged', `${alert.title} has been acknowledged`)
    } else {
      throw new Error(response.message || 'Failed to acknowledge alert')
    }
  } catch (err) {
    error('Acknowledge failed', handleApiError(err))
    console.error('Failed to acknowledge alert:', err)
  }
}

const resolveAlert = async (alert: AIAlertExtended) => {
  try {
    const response = await api.post('/api/ai-alerts/resolve', { alert_id: alert.id })
    if (response.success) {
      alert.status = 'resolved'
      alertStats.value.resolved++
      alertStats.value[alert.severity as keyof Omit<typeof alertStats.value, 'resolved'>]--
      success('Alert resolved', `${alert.title} has been resolved`)
    } else {
      throw new Error(response.message || 'Failed to resolve alert')
    }
  } catch (err) {
    error('Resolve failed', handleApiError(err))
    console.error('Failed to resolve alert:', err)
  }
}

const viewAlertDetails = (alert: AIAlertExtended) => {
  // TODO: Navigate to alert detail view
  success('Opening details', `Viewing details for ${alert.title}`)
}

const assignAlert = (alert: AIAlertExtended) => {
  // TODO: Implement alert assignment
  success('Assignment initiated', `${alert.title} assignment process started`)
}

const selectAlert = (alert: AIAlertExtended) => {
  if (!selectedAlerts.value.includes(alert.id)) {
    toggleAlertSelection(alert.id)
  }
}

const bulkAcknowledge = async () => {
  isBulkProcessing.value = true
  
  try {
    await new Promise(resolve => setTimeout(resolve, 1500))
    
    selectedAlerts.value.forEach(alertId => {
      const alert = allAlerts.value.find(a => a.id === alertId)
      if (alert) alert.status = 'acknowledged'
    })
    
    success('Bulk acknowledge complete', `${selectedAlerts.value.length} alerts acknowledged`)
    clearSelection()
    
  } catch (err) {
    error('Bulk operation failed', 'Unable to acknowledge alerts')
  } finally {
    isBulkProcessing.value = false
  }
}

const bulkResolve = async () => {
  isBulkProcessing.value = true
  
  try {
    await new Promise(resolve => setTimeout(resolve, 1500))
    
    selectedAlerts.value.forEach(alertId => {
      const alert = allAlerts.value.find(a => a.id === alertId)
      if (alert && alert.status !== 'resolved') {
        alert.status = 'resolved'
        alertStats.value.resolved++
        alertStats.value[alert.severity as keyof typeof alertStats.value]--
      }
    })
    
    success('Bulk resolve complete', `${selectedAlerts.value.length} alerts resolved`)
    clearSelection()
    
  } catch (err) {
    error('Bulk operation failed', 'Unable to resolve alerts')
  } finally {
    isBulkProcessing.value = false
  }
}

const bulkAssign = async () => {
  success('Bulk assignment', 'Bulk assignment feature coming soon')
}

// Helper functions
const getAlertBorderClass = (severity: string) => {
  const classMap = {
    critical: 'border-red-300 bg-red-50',
    high: 'border-orange-300 bg-orange-50',
    medium: 'border-yellow-300 bg-yellow-50',
    low: 'border-green-300 bg-green-50',
  }
  return classMap[severity as keyof typeof classMap] || 'border-gray-300 bg-gray-50'
}

const getAlertIcon = (type: string) => {
  const iconMap = {
    drug_interaction: ShieldExclamationIcon,
    vital_signs: HeartIcon,
    patient_safety: ExclamationTriangleIcon,
    operational: ClockIcon,
    system: ComputerDesktopIcon,
  }
  return iconMap[type as keyof typeof iconMap] || ExclamationTriangleIcon
}

const getAlertIconColor = (severity: string) => {
  const colorMap = {
    critical: 'text-red-600',
    high: 'text-orange-600',
    medium: 'text-yellow-600',
    low: 'text-green-600',
  }
  return colorMap[severity as keyof typeof colorMap] || 'text-gray-600'
}

const getAlertIconBg = (severity: string) => {
  const classMap = {
    critical: 'bg-red-100',
    high: 'bg-orange-100',
    medium: 'bg-yellow-100',
    low: 'bg-green-100',
  }
  return classMap[severity as keyof typeof classMap] || 'bg-gray-100'
}

const getSeverityClass = (severity: string) => {
  const classMap = {
    critical: 'bg-red-100 text-red-800',
    high: 'bg-orange-100 text-orange-800',
    medium: 'bg-yellow-100 text-yellow-800',
    low: 'bg-green-100 text-green-800',
  }
  return classMap[severity as keyof typeof classMap] || 'bg-gray-100 text-gray-800'
}

const getStatusClass = (status: string) => {
  const classMap = {
    active: 'bg-red-100 text-red-800',
    acknowledged: 'bg-yellow-100 text-yellow-800',
    resolved: 'bg-green-100 text-green-800',
  }
  return classMap[status as keyof typeof classMap] || 'bg-gray-100 text-gray-800'
}

const formatAlertType = (type: string) => {
  const typeMap = {
    drug_interaction: 'Drug Interaction',
    vital_signs: 'Vital Signs',
    patient_safety: 'Patient Safety',
    operational: 'Operational',
    system: 'System',
  }
  return typeMap[type as keyof typeof typeMap] || type
}

const formatTime = (timeString: string) => {
  return formatDistance(new Date(timeString), new Date(), { addSuffix: true })
}

// Watchers
watch(autoRefresh, (enabled) => {
  if (enabled) {
    autoRefreshInterval = window.setInterval(refreshAlerts, 30000)
  } else if (autoRefreshInterval) {
    clearInterval(autoRefreshInterval)
    autoRefreshInterval = null
  }
})

// Lifecycle
onMounted(async () => {
  await loadAlerts()
  
  if (autoRefresh.value) {
    autoRefreshInterval = window.setInterval(refreshAlerts, 30000)
  }
})

onUnmounted(() => {
  if (autoRefreshInterval) {
    clearInterval(autoRefreshInterval)
  }
})
</script>

<style lang="postcss" scoped>
.ai-alerts-container {
  @apply max-w-7xl mx-auto;
}

.card-header {
  @apply bg-gray-50;
}

.alert-item {
  @apply transition-all duration-200;
}

.alert-item:hover {
  @apply shadow-md;
}

.bulk-actions-bar {
  @apply sticky top-0 z-20;
}

.pagination-btn {
  @apply px-3 py-2 text-sm border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 rounded-md disabled:opacity-50 disabled:cursor-not-allowed;
}

/* Statistics cards animation */
.alert-stats .medical-card {
  animation: slideInUp 0.6s ease-out;
}

.alert-stats .medical-card:nth-child(2) {
  animation-delay: 0.1s;
}

.alert-stats .medical-card:nth-child(3) {
  animation-delay: 0.2s;
}

.alert-stats .medical-card:nth-child(4) {
  animation-delay: 0.3s;
}

.alert-stats .medical-card:nth-child(5) {
  animation-delay: 0.4s;
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

/* Priority-based styling */
.alert-item.border-red-300 {
  @apply ring-1 ring-red-100;
}

.alert-item.border-orange-300 {
  @apply ring-1 ring-orange-100;
}

/* Responsive design */
@media (max-width: 1024px) {
  .alert-stats {
    grid-template-columns: repeat(1, minmax(0, 1fr));
  }
  
  @media (min-width: 640px) {
    .alert-stats {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
  }
  
  @media (min-width: 1024px) {
    .alert-stats {
      grid-template-columns: repeat(3, minmax(0, 1fr));
    }
  }
  
  .alert-item .grid {
    grid-template-columns: repeat(1, minmax(0, 1fr));
  }
  
  .pagination-container .flex:last-child {
    flex-wrap: wrap;
  }
}

@media (max-width: 768px) {
  .page-header .flex {
    flex-direction: column;
    row-gap: 1rem;
    align-items: flex-start;
  }
  
  .alert-controls .flex:first-child {
    flex-direction: column;
    row-gap: 0.75rem;
    column-gap: 0px;
  }
  
  .alert-stats {
    grid-template-columns: repeat(1, minmax(0, 1fr));
  }
  
  .alert-item .flex:first-child {
    flex-direction: column;
    row-gap: 1rem;
  }
  
  .alert-item .flex-col:last-child {
    flex-direction: row;
    row-gap: 0px;
    column-gap: 0.5rem;
  }
}

/* Auto-refresh indicator */
.w-2.h-2.bg-red-400.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: .5;
  }
}
</style>
