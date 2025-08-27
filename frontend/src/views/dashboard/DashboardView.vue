<template>
  <AppLayout>
    <div class="dashboard-container">
      <!-- Welcome Section -->
      <div class="welcome-section mb-8">
        <div class="medical-card p-6">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="text-2xl font-bold text-gray-900 mb-2">
                Welcome back, {{ userDisplayName }}
              </h2>
              <p class="text-gray-600">
                Here's what's happening at your clinic today
              </p>
            </div>
            <div class="hidden md:block">
              <div class="text-right">
                <p class="text-sm text-gray-500">{{ currentDate }}</p>
                <p class="text-lg font-semibold text-primary-600">{{ currentTime }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Metrics Cards -->
      <div class="metrics-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <MetricCard
          title="Total Patients"
          :value="metrics.totalPatients"
          :previous="metrics.previousPatients"
          icon="UsersIcon"
          color="blue"
        />
        <MetricCard
          title="Today's Appointments"
          :value="metrics.todayAppointments"
          :previous="metrics.previousAppointments"
          icon="CalendarIcon"
          color="green"
        />
        <MetricCard
          title="Pending Alerts"
          :value="metrics.pendingAlerts"
          :previous="metrics.previousAlerts"
          icon="BellIcon"
          color="yellow"
        />
        <MetricCard
          title="System Status"
          :value="systemStatusText"
          icon="CpuChipIcon"
          :color="systemStatusColor"
        />
      </div>

      <!-- Main Content Grid -->
      <div class="main-content-grid grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left Column: Today's Schedule + Quick Actions -->
        <div class="lg:col-span-2 space-y-8">
          <!-- Today's Schedule -->
          <div class="medical-card">
            <div class="card-header flex items-center justify-between p-6 border-b border-gray-200">
              <h3 class="text-lg font-semibold text-gray-900">Today's Schedule</h3>
              <RouterLink
                to="/appointments"
                class="text-sm text-primary-600 hover:text-primary-800 font-medium"
              >
                View all →
              </RouterLink>
            </div>
            <div class="p-6">
              <div v-if="isLoadingAppointments" class="space-y-4">
                <div v-for="n in 3" :key="n" class="skeleton h-16 rounded-lg"></div>
              </div>
              <div v-else-if="todayAppointments.length === 0" class="text-center py-8">
                <CalendarIcon class="mx-auto h-12 w-12 text-gray-400 mb-4" />
                <h4 class="text-lg font-medium text-gray-900 mb-2">No appointments today</h4>
                <p class="text-gray-500">Schedule some appointments to see them here.</p>
              </div>
              <div v-else class="space-y-4">
                <AppointmentCard
                  v-for="appointment in todayAppointments.slice(0, 5)"
                  :key="appointment.id"
                  :appointment="appointment"
                  @click="handleAppointmentClick(appointment)"
                />
              </div>
            </div>
          </div>

          <!-- Quick Actions -->
          <div class="medical-card">
            <div class="card-header p-6 border-b border-gray-200">
              <h3 class="text-lg font-semibold text-gray-900">Quick Actions</h3>
            </div>
            <div class="p-6">
              <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <QuickActionButton
                  icon="UserPlusIcon"
                  label="New Patient"
                  @click="handleNewPatient"
                />
                <QuickActionButton
                  icon="CalendarPlusIcon"
                  label="Schedule"
                  @click="handleScheduleAppointment"
                />
                <QuickActionButton
                  icon="ClipboardDocumentListIcon"
                  label="View Records"
                  @click="handleViewRecords"
                />
                <QuickActionButton
                  icon="ChartBarIcon"
                  label="Reports"
                  @click="handleViewReports"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column: Recent Activity + System Info -->
        <div class="space-y-8">
          <!-- Recent Activity -->
          <div class="medical-card">
            <div class="card-header p-6 border-b border-gray-200">
              <h3 class="text-lg font-semibold text-gray-900">Recent Activity</h3>
            </div>
            <div class="p-6">
              <div v-if="isLoadingActivity" class="space-y-3">
                <div v-for="n in 4" :key="n" class="skeleton h-12 rounded"></div>
              </div>
              <div v-else class="space-y-4">
                <ActivityItem
                  v-for="activity in recentActivity.slice(0, 6)"
                  :key="activity.id"
                  :activity="activity"
                />
              </div>
            </div>
          </div>

          <!-- System Information -->
          <div class="medical-card">
            <div class="card-header p-6 border-b border-gray-200">
              <h3 class="text-lg font-semibold text-gray-900">System Information</h3>
            </div>
            <div class="p-6 space-y-4">
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Server Status</span>
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                  <span class="w-2 h-2 bg-green-400 rounded-full mr-1"></span>
                  Online
                </span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Last Backup</span>
                <span class="text-sm text-gray-900">{{ lastBackupTime }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Active Users</span>
                <span class="text-sm text-gray-900">{{ activeUsers }}</span>
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
import { RouterLink, useRouter } from 'vue-router'
import {
  CalendarIcon,
} from '@heroicons/vue/24/outline'
import AppLayout from '@/components/common/AppLayout.vue'
import MetricCard from '@/components/dashboard/MetricCard.vue'
import AppointmentCard from '@/components/dashboard/AppointmentCard.vue'
import QuickActionButton from '@/components/dashboard/QuickActionButton.vue'
import ActivityItem from '@/components/dashboard/ActivityItem.vue'
import { useAuthStore } from '@/stores/auth'
import { useNotifications } from '@/stores/notifications'
import type { Appointment, DashboardMetrics, ActivityItemData } from '@/types/api.types'

// Router and Stores
const router = useRouter()
const authStore = useAuthStore()
const { success } = useNotifications()

// State
const isLoadingAppointments = ref(false)
const isLoadingActivity = ref(false)
const currentTime = ref('')

// Mock data (will be replaced with API calls in Phase 2)
const metrics = ref<DashboardMetrics & { 
  previousPatients: number
  previousAppointments: number
  previousAlerts: number
}>({
  totalPatients: 247,
  todayAppointments: 12,
  pendingAlerts: 3,
  systemStatus: 'healthy',
  previousPatients: 235,
  previousAppointments: 8,
  previousAlerts: 5,
})

const todayAppointments = ref<Appointment[]>([
  {
    id: 1,
    patientId: 1,
    doctorId: 1,
    appointmentDate: '2024-08-24',
    startTime: '09:00',
    endTime: '09:30',
    appointmentType: 'Annual checkup',
    priority: 'normal',
    status: 'scheduled',
    notes: 'Annual checkup appointment',
    followUpRequired: false,
    patient: {
      id: 1,
      firstName: 'John',
      lastName: 'Doe',
      email: 'john.doe@email.com',
      dateOfBirth: '1985-01-15',
      createdAt: '2024-01-01T00:00:00Z',
      updatedAt: '2024-01-01T00:00:00Z',
    },
    createdAt: '2024-08-20T00:00:00Z',
    updatedAt: '2024-08-20T00:00:00Z',
  },
  {
    id: 2,
    patientId: 2,
    doctorId: 1,
    appointmentDate: '2024-08-24',
    startTime: '10:30',
    endTime: '11:00',
    appointmentType: 'Follow-up consultation',
    priority: 'normal',
    status: 'scheduled',
    notes: 'Follow-up consultation',
    followUpRequired: false,
    patient: {
      id: 2,
      firstName: 'Jane',
      lastName: 'Smith',
      email: 'jane.smith@email.com',
      dateOfBirth: '1978-03-22',
      createdAt: '2024-01-01T00:00:00Z',
      updatedAt: '2024-01-01T00:00:00Z',
    },
    createdAt: '2024-08-20T00:00:00Z',
    updatedAt: '2024-08-20T00:00:00Z',
  },
])

const recentActivity = ref<ActivityItemData[]>([
  {
    id: '1',
    type: 'appointment',
    title: 'New appointment scheduled',
    description: 'Annual checkup appointment for John Doe',
    timestamp: '2024-08-24T08:30:00Z',
    user: {
      name: 'Dr. Smith',
    },
  },
  {
    id: '2',
    type: 'patient',
    title: 'Patient record updated',
    description: 'Updated contact information for Jane Smith',
    timestamp: '2024-08-24T08:15:00Z',
    user: {
      name: 'Nurse Johnson',
    },
  },
  {
    id: '3',
    type: 'system',
    title: 'System backup completed',
    description: 'Daily backup completed successfully',
    timestamp: '2024-08-24T07:00:00Z',
    user: {
      name: 'System',
    },
  },
])

// Computed
const userDisplayName = computed(() => authStore.userName)

const currentDate = computed(() => {
  return new Date().toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
})

const systemStatusText = computed(() => {
  const statusMap = {
    healthy: 'Healthy',
    warning: 'Warning',
    error: 'Error',
  }
  return statusMap[metrics.value.systemStatus] || 'Unknown'
})

const systemStatusColor = computed((): 'green' | 'yellow' | 'red' | 'gray' | 'blue' | 'purple' => {
  const colorMap: Record<string, 'green' | 'yellow' | 'red' | 'gray'> = {
    healthy: 'green',
    warning: 'yellow',
    error: 'red',
  }
  return colorMap[metrics.value.systemStatus] || 'gray'
})

const lastBackupTime = computed(() => {
  return new Date().toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  })
})

const activeUsers = computed(() => 5) // Mock data

// Methods
const updateTime = () => {
  currentTime.value = new Date().toLocaleTimeString('en-US', {
    hour: 'numeric',
    minute: '2-digit',
  })
}

const handleAppointmentClick = (appointment: Appointment) => {
  router.push(`/appointments/${appointment.id}`)
}

const handleNewPatient = () => {
  router.push('/patients/new')
}

const handleScheduleAppointment = () => {
  router.push('/appointments/new')
}

const handleViewRecords = () => {
  router.push('/patients')
}

const handleViewReports = () => {
  router.push('/reports')
}

// Lifecycle
onMounted(async () => {
  // Update time immediately and then every minute
  updateTime()
  setInterval(updateTime, 60000)

  // Show welcome notification
  success('Welcome back!', `Good ${getTimeOfDay()}, ${userDisplayName.value}`)

  // Load dashboard data (mock for now)
  await loadDashboardData()
})

const getTimeOfDay = (): string => {
  const hour = new Date().getHours()
  if (hour < 12) return 'morning'
  if (hour < 17) return 'afternoon'
  return 'evening'
}

const loadDashboardData = async () => {
  // Simulate API loading
  isLoadingAppointments.value = true
  isLoadingActivity.value = true

  // Simulate network delay
  await new Promise(resolve => setTimeout(resolve, 1000))

  isLoadingAppointments.value = false
  isLoadingActivity.value = false
}
</script>

<style lang="postcss" scoped>
.dashboard-container {
  @apply max-w-7xl mx-auto;
}

.card-header {
  @apply bg-gray-50;
}

/* Responsive grid adjustments */
@media (max-width: 1024px) {
  .main-content-grid {
    @apply grid-cols-1;
  }
}

@media (max-width: 768px) {
  .metrics-grid {
    @apply grid-cols-1 sm:grid-cols-2;
  }
  
  .dashboard-container {
    @apply px-0;
  }
}
</style>
