<template>
  <AppLayout>
    <div class="analytics-container">
      <!-- Page Header -->
      <div class="page-header mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900 flex items-center">
              <ChartBarIcon class="w-8 h-8 mr-3 text-blue-600" />
              Analytics & Reports
            </h1>
            <p class="mt-2 text-sm text-gray-700">
              Comprehensive clinic performance analytics and insights
            </p>
          </div>
          
          <!-- Export Controls -->
          <div class="flex items-center space-x-4">
            <select 
              v-model="selectedTimeRange" 
              @change="handleTimeRangeChange"
              class="form-select rounded-md border-gray-300 text-sm"
            >
              <option value="7d">Last 7 days</option>
              <option value="30d">Last 30 days</option>
              <option value="90d">Last 90 days</option>
              <option value="1y">Last year</option>
            </select>
            
            <button 
              @click="exportReport"
              :disabled="isExporting"
              class="medical-button-primary flex items-center"
            >
              <DocumentArrowDownIcon class="w-4 h-4 mr-2" />
              {{ isExporting ? 'Exporting...' : 'Export Report' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Key Metrics Cards -->
      <div class="metrics-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div v-for="metric in keyMetrics" :key="metric.key" class="medical-card p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600 mb-1">{{ metric.label }}</p>
              <p class="text-2xl font-bold text-gray-900">{{ metric.value }}</p>
              <div class="flex items-center mt-2">
                <component 
                  :is="metric.trend === 'up' ? ArrowTrendingUpIcon : ArrowTrendingDownIcon"
                  :class="[
                    'w-4 h-4 mr-1',
                    metric.trend === 'up' ? 'text-green-500' : 'text-red-500'
                  ]"
                />
                <span 
                  :class="[
                    'text-sm font-medium',
                    metric.trend === 'up' ? 'text-green-500' : 'text-red-500'
                  ]"
                >
                  {{ metric.change }}%
                </span>
                <span class="text-xs text-gray-500 ml-2">vs {{ selectedTimeRange }}</span>
              </div>
            </div>
            <div :class="[
              'w-12 h-12 rounded-full flex items-center justify-center',
              metric.iconBg
            ]">
              <component :is="metric.icon" :class="['w-6 h-6', metric.iconColor]" />
            </div>
          </div>
        </div>
      </div>

      <!-- Charts Grid -->
      <div class="charts-grid grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Patient Flow Chart -->
        <div class="medical-card p-6">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-900">Patient Flow Trends</h3>
            <div class="flex items-center space-x-2">
              <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
              <span class="text-sm text-gray-600">New Patients</span>
              <div class="w-3 h-3 bg-green-500 rounded-full ml-4"></div>
              <span class="text-sm text-gray-600">Returning Patients</span>
            </div>
          </div>
          <div class="chart-container">
            <Line 
              :data="patientFlowChartData" 
              :options="chartOptions" 
            />
          </div>
        </div>

        <!-- Appointment Status Distribution -->
        <div class="medical-card p-6">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-900">Appointment Status</h3>
          </div>
          <div class="chart-container">
            <Doughnut 
              :data="appointmentStatusChartData" 
              :options="doughnutOptions"
            />
          </div>
        </div>

        <!-- Revenue Analysis -->
        <div class="medical-card p-6">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-900">Revenue Analysis</h3>
            <span class="text-sm text-gray-600">{{ selectedTimeRange.toUpperCase() }}</span>
          </div>
          <div class="chart-container">
            <Bar 
              :data="revenueChartData" 
              :options="barChartOptions"
            />
          </div>
        </div>

        <!-- Doctor Performance -->
        <div class="medical-card p-6">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-900">Doctor Performance</h3>
          </div>
          <div class="space-y-4">
            <div v-for="doctor in doctorPerformance" :key="doctor.id" class="flex items-center justify-between">
              <div class="flex items-center">
                <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center mr-3">
                  <span class="text-sm font-semibold text-gray-700">{{ doctor.initials }}</span>
                </div>
                <div>
                  <p class="font-medium text-gray-900">{{ doctor.name }}</p>
                  <p class="text-xs text-gray-500">{{ doctor.specialty }}</p>
                </div>
              </div>
              <div class="text-right">
                <p class="text-sm font-semibold text-gray-900">{{ doctor.rating }}/5.0</p>
                <p class="text-xs text-gray-500">{{ doctor.appointments }} appointments</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Detailed Analytics Tables -->
      <div class="analytics-tables grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Top Procedures -->
        <div class="medical-card">
          <div class="card-header p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
              <h3 class="text-lg font-semibold text-gray-900">Top Procedures</h3>
              <button 
                @click="exportProcedures"
                class="text-sm text-blue-600 hover:text-blue-800"
              >
                Export CSV
              </button>
            </div>
          </div>
          <div class="p-6">
            <div class="space-y-4">
              <div 
                v-for="procedure in topProcedures" 
                :key="procedure.code"
                class="flex items-center justify-between py-2 border-b border-gray-100 last:border-b-0"
              >
                <div>
                  <p class="font-medium text-gray-900">{{ procedure.name }}</p>
                  <p class="text-xs text-gray-500">Code: {{ procedure.code }}</p>
                </div>
                <div class="text-right">
                  <p class="font-semibold text-gray-900">{{ procedure.count }}</p>
                  <p class="text-xs text-gray-500">${{ procedure.revenue.toLocaleString() }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Patient Demographics -->
        <div class="medical-card">
          <div class="card-header p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
              <h3 class="text-lg font-semibold text-gray-900">Patient Demographics</h3>
              <button 
                @click="exportDemographics"
                class="text-sm text-blue-600 hover:text-blue-800"
              >
                Export CSV
              </button>
            </div>
          </div>
          <div class="p-6">
            <div class="space-y-6">
              <!-- Age Groups -->
              <div>
                <h4 class="text-sm font-medium text-gray-900 mb-3">Age Distribution</h4>
                <div class="space-y-2">
                  <div v-for="age in ageDistribution" :key="age.range" class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">{{ age.range }}</span>
                    <div class="flex items-center">
                      <div class="w-24 bg-gray-200 rounded-full h-2 mr-3">
                        <div 
                          class="bg-blue-500 h-2 rounded-full transition-all duration-500"
                          :style="{ width: age.percentage + '%' }"
                        ></div>
                      </div>
                      <span class="text-sm font-medium text-gray-900 w-12">{{ age.count }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Gender Distribution -->
              <div>
                <h4 class="text-sm font-medium text-gray-900 mb-3">Gender Distribution</h4>
                <div class="grid grid-cols-2 gap-4">
                  <div class="text-center">
                    <p class="text-2xl font-bold text-blue-600">{{ genderDistribution.male }}%</p>
                    <p class="text-xs text-gray-500">Male</p>
                  </div>
                  <div class="text-center">
                    <p class="text-2xl font-bold text-pink-600">{{ genderDistribution.female }}%</p>
                    <p class="text-xs text-gray-500">Female</p>
                  </div>
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
  ChartBarIcon,
  DocumentArrowDownIcon,
  ArrowTrendingUpIcon,
  ArrowTrendingDownIcon,
  UsersIcon,
  CalendarIcon,
  CurrencyDollarIcon,
  HeartIcon,
} from '@heroicons/vue/24/outline'
import AppLayout from '@/components/common/AppLayout.vue'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Legend,
  ArcElement,
} from 'chart.js'
import { Line, Doughnut, Bar } from 'vue-chartjs'
import { useNotifications } from '@/stores/notifications'

// Types
interface MetricData {
  key: string
  label: string
  value: string
  change: number
  trend: 'up' | 'down'
  icon: any
  iconColor: string
  iconBg: string
}

interface DoctorPerformance {
  id: number
  name: string
  initials: string
  specialty: string
  rating: number
  appointments: number
}

interface ProcedureData {
  code: string
  name: string
  count: number
  revenue: number
}

interface AgeDistribution {
  range: string
  count: number
  percentage: number
}

// Register Chart.js components
ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Legend,
  ArcElement
)

const { success } = useNotifications()

// State
const selectedTimeRange = ref('30d')
const isExporting = ref(false)

// Mock data - would come from API in real implementation
const keyMetrics = ref<MetricData[]>([
  {
    key: 'totalPatients',
    label: 'Total Patients',
    value: '1,247',
    change: 12.5,
    trend: 'up',
    icon: UsersIcon,
    iconColor: 'text-blue-600',
    iconBg: 'bg-blue-100',
  },
  {
    key: 'appointments',
    label: 'Appointments',
    value: '856',
    change: 8.2,
    trend: 'up',
    icon: CalendarIcon,
    iconColor: 'text-green-600',
    iconBg: 'bg-green-100',
  },
  {
    key: 'revenue',
    label: 'Revenue',
    value: '$124,580',
    change: 15.3,
    trend: 'up',
    icon: CurrencyDollarIcon,
    iconColor: 'text-yellow-600',
    iconBg: 'bg-yellow-100',
  },
  {
    key: 'satisfaction',
    label: 'Satisfaction',
    value: '4.8/5.0',
    change: 2.1,
    trend: 'up',
    icon: HeartIcon,
    iconColor: 'text-red-600',
    iconBg: 'bg-red-100',
  },
])

const doctorPerformance = ref<DoctorPerformance[]>([
  { id: 1, name: 'Dr. Sarah Johnson', initials: 'SJ', specialty: 'Cardiology', rating: 4.9, appointments: 156 },
  { id: 2, name: 'Dr. Michael Chen', initials: 'MC', specialty: 'Internal Medicine', rating: 4.8, appointments: 142 },
  { id: 3, name: 'Dr. Emily Rodriguez', initials: 'ER', specialty: 'Pediatrics', rating: 4.7, appointments: 128 },
  { id: 4, name: 'Dr. James Wilson', initials: 'JW', specialty: 'Orthopedics', rating: 4.6, appointments: 98 },
])

const topProcedures = ref<ProcedureData[]>([
  { code: '99213', name: 'Office Visit - Established Patient', count: 156, revenue: 18720 },
  { code: '99214', name: 'Office Visit - Complex', count: 89, revenue: 12460 },
  { code: '93000', name: 'Electrocardiogram', count: 67, revenue: 8040 },
  { code: '36415', name: 'Blood Draw', count: 134, revenue: 4020 },
  { code: '85025', name: 'Complete Blood Count', count: 112, revenue: 3360 },
])

const ageDistribution = ref<AgeDistribution[]>([
  { range: '0-18', count: 89, percentage: 18 },
  { range: '19-35', count: 156, percentage: 31 },
  { range: '36-50', count: 178, percentage: 36 },
  { range: '51-65', count: 98, percentage: 20 },
  { range: '65+', count: 67, percentage: 13 },
])

const genderDistribution = ref({
  male: 48,
  female: 52,
})

// Chart data
const patientFlowChartData = computed(() => ({
  labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
  datasets: [
    {
      label: 'New Patients',
      data: [65, 59, 80, 81, 56, 55, 78],
      borderColor: 'rgb(59, 130, 246)',
      backgroundColor: 'rgba(59, 130, 246, 0.1)',
      tension: 0.4,
    },
    {
      label: 'Returning Patients',
      data: [28, 48, 40, 19, 86, 27, 45],
      borderColor: 'rgb(34, 197, 94)',
      backgroundColor: 'rgba(34, 197, 94, 0.1)',
      tension: 0.4,
    },
  ],
}))

const appointmentStatusChartData = computed(() => ({
  labels: ['Completed', 'Scheduled', 'Cancelled', 'No Show'],
  datasets: [
    {
      data: [75, 15, 7, 3],
      backgroundColor: [
        'rgb(34, 197, 94)',   // Green
        'rgb(59, 130, 246)',  // Blue
        'rgb(239, 68, 68)',   // Red
        'rgb(156, 163, 175)', // Gray
      ],
      borderWidth: 2,
      borderColor: '#ffffff',
    },
  ],
}))

const revenueChartData = computed(() => ({
  labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
  datasets: [
    {
      label: 'Revenue ($)',
      data: [12400, 19300, 15200, 17800, 16700, 18900, 21200],
      backgroundColor: 'rgba(34, 197, 94, 0.8)',
      borderColor: 'rgb(34, 197, 94)',
      borderWidth: 1,
    },
  ],
}))

// Chart options
const chartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false,
    },
  },
  scales: {
    y: {
      beginAtZero: true,
      grid: {
        color: 'rgba(0, 0, 0, 0.05)',
      },
    },
    x: {
      grid: {
        display: false,
      },
    },
  },
}))

const doughnutOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom' as const,
      labels: {
        usePointStyle: true,
        padding: 20,
      },
    },
  },
}))

const barChartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false,
    },
  },
  scales: {
    y: {
      beginAtZero: true,
      grid: {
        color: 'rgba(0, 0, 0, 0.05)',
      },
    },
    x: {
      grid: {
        display: false,
      },
    },
  },
}))

// Methods
const handleTimeRangeChange = () => {
  // Simulate data refresh
  success('Analytics updated', `Data refreshed for ${selectedTimeRange.value}`)
}

const exportReport = async () => {
  isExporting.value = true
  
  try {
    // Simulate export process
    await new Promise(resolve => setTimeout(resolve, 2000))
    
    // In real implementation, this would call the backend API
    const reportData = {
      metrics: keyMetrics.value,
      timeRange: selectedTimeRange.value,
      generatedAt: new Date().toISOString(),
    }
    
    // Create and download CSV
    const csv = generateCSV(reportData)
    downloadCSV(csv, `clinic-analytics-${selectedTimeRange.value}-${new Date().toISOString().split('T')[0]}.csv`)
    
    success('Export complete', 'Analytics report has been downloaded')
  } catch (error) {
    console.error('Export failed:', error)
  } finally {
    isExporting.value = false
  }
}

const exportProcedures = () => {
  const csv = generateProceduresCSV()
  downloadCSV(csv, `top-procedures-${new Date().toISOString().split('T')[0]}.csv`)
  success('Export complete', 'Procedures data has been downloaded')
}

const exportDemographics = () => {
  const csv = generateDemographicsCSV()
  downloadCSV(csv, `patient-demographics-${new Date().toISOString().split('T')[0]}.csv`)
  success('Export complete', 'Demographics data has been downloaded')
}

const generateCSV = (data: any) => {
  const headers = ['Metric', 'Value', 'Change', 'Trend']
  const rows = data.metrics.map((metric: any) => [
    metric.label,
    metric.value,
    `${metric.change}%`,
    metric.trend
  ])
  
  return [headers, ...rows]
    .map(row => row.map(cell => `"${cell}"`).join(','))
    .join('\n')
}

const generateProceduresCSV = () => {
  const headers = ['Code', 'Procedure Name', 'Count', 'Revenue']
  const rows = topProcedures.value.map(proc => [
    proc.code,
    proc.name,
    proc.count,
    `$${proc.revenue}`
  ])
  
  return [headers, ...rows]
    .map(row => row.map(cell => `"${cell}"`).join(','))
    .join('\n')
}

const generateDemographicsCSV = () => {
  const headers = ['Age Range', 'Count', 'Percentage']
  const rows = ageDistribution.value.map(age => [
    age.range,
    age.count,
    `${age.percentage}%`
  ])
  
  return [headers, ...rows]
    .map(row => row.map(cell => `"${cell}"`).join(','))
    .join('\n')
}

const downloadCSV = (csv: string, filename: string) => {
  const blob = new Blob([csv], { type: 'text/csv' })
  const url = window.URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = filename
  link.click()
  window.URL.revokeObjectURL(url)
}

// Lifecycle
onMounted(() => {
  // Load analytics data
  console.log('Analytics dashboard loaded')
})
</script>

<style lang="postcss" scoped>
.analytics-container {
  @apply max-w-7xl mx-auto;
}

.chart-container {
  @apply relative;
  height: 200px;
}

.card-header {
  @apply bg-gray-50;
}

/* Responsive adjustments */
@media (max-width: 1024px) {
  .charts-grid {
    @apply grid-cols-1;
  }
  
  .analytics-tables {
    @apply grid-cols-1;
  }
}

@media (max-width: 768px) {
  .metrics-grid {
    @apply grid-cols-1 sm:grid-cols-2;
  }
  
  .page-header {
    @apply block;
  }
  
  .page-header > div {
    @apply flex-col space-y-4 items-start;
  }
}

/* Animation for progress bars */
.bg-blue-500 {
  transition: width 0.5s ease-in-out;
}
</style>
