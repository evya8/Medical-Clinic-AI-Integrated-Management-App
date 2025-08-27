<template>
  <AppLayout>
    <div class="appointments-container">
      <!-- Header -->
      <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Appointments</h1>
          <p class="text-gray-600 mt-1">Manage clinic appointments and schedules</p>
        </div>
        <div class="flex items-center space-x-4 mt-4 lg:mt-0">
          <button
            @click="showScheduleModal = true"
            class="medical-button-primary flex items-center"
          >
            <PlusIcon class="w-4 h-4 mr-2" />
            Schedule Appointment
          </button>
          <button
            @click="toggleView"
            class="medical-button-secondary flex items-center"
          >
            <component :is="viewToggleIcon" class="w-4 h-4 mr-2" />
            {{ currentView === 'calendar' ? 'List View' : 'Calendar View' }}
          </button>
        </div>
      </div>

      <!-- Quick Stats -->
      <div class="stats-grid grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="stat-card medical-card p-4 text-center">
          <div class="text-2xl font-bold text-blue-600">{{ stats.today }}</div>
          <div class="text-sm text-gray-600">Today's Appointments</div>
        </div>
        
        <div class="stat-card medical-card p-4 text-center">
          <div class="text-2xl font-bold text-green-600">{{ stats.upcoming }}</div>
          <div class="text-sm text-gray-600">Upcoming</div>
        </div>
        
        <div class="stat-card medical-card p-4 text-center">
          <div class="text-2xl font-bold text-yellow-600">{{ stats.pending }}</div>
          <div class="text-sm text-gray-600">Pending Confirmation</div>
        </div>
        
        <div class="stat-card medical-card p-4 text-center">
          <div class="text-2xl font-bold text-red-600">{{ stats.overdue }}</div>
          <div class="text-sm text-gray-600">Overdue</div>
        </div>
      </div>

      <!-- Filters -->
      <div class="filters-section medical-card p-4 mb-6">
        <div class="flex flex-col lg:flex-row gap-4">
          <div class="flex-1">
            <label class="medical-form-label">Search</label>
            <div class="relative">
              <MagnifyingGlassIcon class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 transform -translate-y-1/2" />
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Search by patient name, doctor, or appointment type..."
                class="medical-input pl-10"
              />
            </div>
          </div>
          
          <div class="flex-1 lg:flex-initial lg:w-48">
            <label class="medical-form-label">Doctor</label>
            <select v-model="selectedDoctor" class="medical-input">
              <option value="">All Doctors</option>
              <option v-for="doctor in doctors" :key="doctor.id" :value="doctor.id">
                Dr. {{ doctor.firstName }} {{ doctor.lastName }}
              </option>
            </select>
          </div>
          
          <div class="flex-1 lg:flex-initial lg:w-48">
            <label class="medical-form-label">Status</label>
            <select v-model="selectedStatus" class="medical-input">
              <option value="">All Statuses</option>
              <option value="scheduled">Scheduled</option>
              <option value="confirmed">Confirmed</option>
              <option value="completed">Completed</option>
              <option value="cancelled">Cancelled</option>
              <option value="no-show">No Show</option>
            </select>
          </div>
          
          <div class="flex-1 lg:flex-initial lg:w-48">
            <label class="medical-form-label">Date Range</label>
            <select v-model="selectedDateRange" class="medical-input">
              <option value="today">Today</option>
              <option value="tomorrow">Tomorrow</option>
              <option value="week">This Week</option>
              <option value="month">This Month</option>
              <option value="custom">Custom Range</option>
            </select>
          </div>
        </div>

        <!-- Custom Date Range -->
        <div v-if="selectedDateRange === 'custom'" class="flex flex-col sm:flex-row gap-4 mt-4">
          <div class="flex-1">
            <label class="medical-form-label">From Date</label>
            <input v-model="customDateRange.start" type="date" class="medical-input" />
          </div>
          <div class="flex-1">
            <label class="medical-form-label">To Date</label>
            <input v-model="customDateRange.end" type="date" class="medical-input" />
          </div>
        </div>
      </div>

      <!-- Calendar/List View -->
      <div class="appointments-view">
        <!-- Calendar View -->
        <div v-if="currentView === 'calendar'" class="calendar-view medical-card">
          <AppointmentCalendar
            :appointments="filteredAppointments"
            :loading="isLoading"
            @appointment-click="handleAppointmentClick"
            @date-click="handleDateClick"
            @appointment-drop="handleAppointmentDrop"
          />
        </div>

        <!-- List View -->
        <div v-else class="list-view">
          <AppointmentList
            :appointments="filteredAppointments"
            :loading="isLoading"
            :group-by-date="true"
            @appointment-click="handleAppointmentClick"
            @appointment-edit="handleAppointmentEdit"
            @appointment-cancel="handleAppointmentCancel"
            @appointment-complete="handleAppointmentComplete"
          />
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="!isLoading && filteredAppointments.length === 0" class="empty-state medical-card p-12 text-center">
        <CalendarIcon class="w-16 h-16 text-gray-300 mx-auto mb-4" />
        <h3 class="text-lg font-medium text-gray-900 mb-2">No Appointments Found</h3>
        <p class="text-gray-600 mb-6">
          {{ appointments.length === 0 
            ? 'No appointments have been scheduled yet.' 
            : 'No appointments match your current filters.'
          }}
        </p>
        <button
          @click="showScheduleModal = true"
          class="medical-button-primary flex items-center mx-auto"
        >
          <PlusIcon class="w-4 h-4 mr-2" />
          Schedule First Appointment
        </button>
      </div>
    </div>

    <!-- Modals -->
    <QuickScheduleModal
      v-if="showScheduleModal"
      :initial-date="selectedDate"
      @close="showScheduleModal = false"
      @scheduled="handleAppointmentScheduled"
    />

    <AppointmentDetailModal
      v-if="showDetailModal && selectedAppointment"
      :appointment="selectedAppointment"
      @close="showDetailModal = false"
      @updated="handleAppointmentUpdated"
      @cancelled="handleAppointmentCancelled"
      @completed="handleAppointmentCompleted"
    />

    <AppointmentEditModal
      v-if="showEditModal && selectedAppointment"
      :is-open="showEditModal"
      :appointment="selectedAppointment"
      @close="showEditModal = false"
      @updated="handleAppointmentUpdated"
    />
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { 
  startOfDay, 
  endOfDay, 
  startOfWeek, 
  endOfWeek, 
  startOfMonth, 
  endOfMonth,
  addDays,
  isWithinInterval,
  isSameDay,
} from 'date-fns'
import {
  PlusIcon,
  CalendarIcon,
  MagnifyingGlassIcon,
  ListBulletIcon,
  ViewColumnsIcon,
} from '@heroicons/vue/24/outline'

import AppLayout from '@/components/common/AppLayout.vue'
import AppointmentCalendar from '@/components/appointments/AppointmentCalendar.vue'
import AppointmentList from '@/components/appointments/AppointmentList.vue'
import QuickScheduleModal from '@/components/appointments/QuickScheduleModal.vue'
import AppointmentDetailModal from '@/components/appointments/AppointmentDetailModal.vue'
import AppointmentEditModal from '@/components/appointments/AppointmentEditModal.vue'

import type { Appointment, Doctor } from '@/types/api.types'
import { api, API_ENDPOINTS } from '@/services/api'
import { useNotifications } from '@/stores/notifications'

const { addNotification } = useNotifications()

// State
const appointments = ref<Appointment[]>([])
const doctors = ref<Doctor[]>([])
const isLoading = ref(true)
const currentView = ref<'calendar' | 'list'>('calendar')
const selectedDate = ref<string | undefined>(undefined)
const selectedAppointment = ref<Appointment | null>(null)

// Filters
const searchQuery = ref('')
const selectedDoctor = ref<string>('')
const selectedStatus = ref<string>('')
const selectedDateRange = ref('week')
const customDateRange = ref({
  start: '',
  end: ''
})

// Modals
const showScheduleModal = ref(false)
const showDetailModal = ref(false)
const showEditModal = ref(false)

// Computed
const stats = computed(() => {
  const today = new Date()
  const todayAppointments = appointments.value.filter(app => 
    isSameDay(new Date(app.appointmentDate), today)
  )
  
  const upcoming = appointments.value.filter(app => 
    ['scheduled', 'confirmed'].includes(app.status) && 
    new Date(app.appointmentDate) > today
  )
  
  const pending = appointments.value.filter(app => 
    app.status === 'scheduled'
  )
  
  const overdue = appointments.value.filter(app => 
    app.status === 'scheduled' && 
    new Date(app.appointmentDate) < today
  )

  return {
    today: todayAppointments.length,
    upcoming: upcoming.length,
    pending: pending.length,
    overdue: overdue.length
  }
})

const viewToggleIcon = computed(() => {
  return currentView.value === 'calendar' ? ListBulletIcon : ViewColumnsIcon
})

const filteredAppointments = computed(() => {
  let filtered = [...appointments.value]

  // Filter by search query
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(app => 
      (app.patient?.firstName?.toLowerCase().includes(query)) ||
      (app.patient?.lastName?.toLowerCase().includes(query)) ||
      app.doctor?.toLowerCase().includes(query) ||
      app.appointmentType?.toLowerCase().includes(query) ||
      app.notes?.toLowerCase().includes(query)
    )
  }

  // Filter by doctor
  if (selectedDoctor.value) {
    filtered = filtered.filter(app => app.doctorId === parseInt(selectedDoctor.value))
  }

  // Filter by status
  if (selectedStatus.value) {
    filtered = filtered.filter(app => app.status === selectedStatus.value)
  }

  // Filter by date range
  if (selectedDateRange.value !== 'custom') {
    const now = new Date()
    let dateFilter: (date: Date) => boolean

    switch (selectedDateRange.value) {
      case 'today':
        dateFilter = (date: Date) => isSameDay(date, now)
        break
      case 'tomorrow':
        dateFilter = (date: Date) => isSameDay(date, addDays(now, 1))
        break
      case 'week':
        const weekStart = startOfWeek(now)
        const weekEnd = endOfWeek(now)
        dateFilter = (date: Date) => isWithinInterval(date, { start: weekStart, end: weekEnd })
        break
      case 'month':
        const monthStart = startOfMonth(now)
        const monthEnd = endOfMonth(now)
        dateFilter = (date: Date) => isWithinInterval(date, { start: monthStart, end: monthEnd })
        break
      default:
        dateFilter = () => true
    }

    filtered = filtered.filter(app => dateFilter(new Date(app.appointmentDate)))
  } else if (customDateRange.value.start && customDateRange.value.end) {
    const start = startOfDay(new Date(customDateRange.value.start))
    const end = endOfDay(new Date(customDateRange.value.end))
    filtered = filtered.filter(app => 
      isWithinInterval(new Date(app.appointmentDate), { start, end })
    )
  }

  return filtered.sort((a, b) => {
    const dateA = new Date(a.appointmentDate + ' ' + a.startTime)
    const dateB = new Date(b.appointmentDate + ' ' + b.startTime)
    return dateA.getTime() - dateB.getTime()
  })
})

// Methods
const loadAppointments = async () => {
  try {
    isLoading.value = true
    const response = await api.get<Appointment[]>(API_ENDPOINTS.APPOINTMENTS.LIST)
    
    if (response.success && response.data) {
      appointments.value = response.data
    } else {
      // Load mock data for demo
      appointments.value = generateMockAppointments()
    }
  } catch (error) {
    console.error('Error loading appointments:', error)
    // Fallback to mock data
    appointments.value = generateMockAppointments()
  } finally {
    isLoading.value = false
  }
}

const loadDoctors = async () => {
  try {
    const response = await api.get<Doctor[]>(API_ENDPOINTS.DOCTORS.LIST)
    
    if (response.success && response.data) {
      doctors.value = response.data
    } else {
      // Mock doctors for demo
      doctors.value = generateMockDoctors()
    }
  } catch (error) {
    console.error('Error loading doctors:', error)
    doctors.value = generateMockDoctors()
  }
}

const generateMockAppointments = (): Appointment[] => {
  const mockAppointments = []
  const today = new Date()
  
  for (let i = 0; i < 20; i++) {
    const appointmentDate = new Date(today)
    appointmentDate.setDate(today.getDate() + (i - 5)) // Some past, some future
    
    const hours = 9 + Math.floor(Math.random() * 8) // 9 AM to 5 PM
    const minutes = Math.random() < 0.5 ? 0 : 30 // :00 or :30
    
    mockAppointments.push({
      id: i + 1,
      patientId: i + 1,
      doctorId: Math.floor(Math.random() * 3) + 1,
      appointmentDate: appointmentDate.toISOString().split('T')[0],
      startTime: `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}`,
      endTime: `${hours.toString().padStart(2, '0')}:${(minutes + 30).toString().padStart(2, '0')}`,
      appointmentType: ['consultation', 'follow-up', 'checkup', 'urgent'][Math.floor(Math.random() * 4)],
      priority: ['low', 'normal', 'high', 'urgent'][Math.floor(Math.random() * 4)] as any,
      status: ['scheduled', 'confirmed', 'completed', 'cancelled'][Math.floor(Math.random() * 4)] as any,
      notes: 'Sample appointment notes',
      followUpRequired: Math.random() < 0.3,
      createdAt: today.toISOString(),
      updatedAt: today.toISOString(),
      patient: {
        id: i + 1,
        firstName: ['John', 'Jane', 'Bob', 'Alice', 'Charlie'][Math.floor(Math.random() * 5)],
        lastName: ['Doe', 'Smith', 'Johnson', 'Williams', 'Brown'][Math.floor(Math.random() * 5)],
        email: `patient${i + 1}@example.com`,
        phone: `(555) ${Math.floor(Math.random() * 900) + 100}-${Math.floor(Math.random() * 9000) + 1000}`,
        dateOfBirth: '1990-01-01',
        createdAt: today.toISOString(),
        updatedAt: today.toISOString(),
      },
      doctor: ['Dr. Smith', 'Dr. Johnson', 'Dr. Williams'][Math.floor(Math.random() * 3)]
    })
  }
  
  return mockAppointments
}

const generateMockDoctors = (): Doctor[] => {
  return [
    {
      id: 1,
      userId: 101,
      firstName: 'Sarah',
      lastName: 'Smith',
      email: 'dr.smith@clinic.com',
      specialization: 'Family Medicine',
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString(),
    },
    {
      id: 2,
      userId: 102,
      firstName: 'Michael',
      lastName: 'Johnson',
      email: 'dr.johnson@clinic.com',
      specialization: 'Cardiology',
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString(),
    },
    {
      id: 3,
      userId: 103,
      firstName: 'Emily',
      lastName: 'Williams',
      email: 'dr.williams@clinic.com',
      specialization: 'Pediatrics',
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString(),
    }
  ]
}

const toggleView = () => {
  currentView.value = currentView.value === 'calendar' ? 'list' : 'calendar'
}

const handleAppointmentClick = (appointment: Appointment) => {
  selectedAppointment.value = appointment
  showDetailModal.value = true
}

const handleDateClick = (date: string) => {
  selectedDate.value = date
  showScheduleModal.value = true
}

const handleAppointmentDrop = async (appointment: Appointment, newDate: string, newTime: string) => {
  try {
    const updatedAppointment = {
      ...appointment,
      appointmentDate: newDate,
      startTime: newTime,
    }

    // In a real app, this would call the API
    const index = appointments.value.findIndex(app => app.id === appointment.id)
    if (index !== -1) {
      appointments.value[index] = updatedAppointment
    }

    addNotification({
      type: 'success',
      title: 'Appointment Rescheduled',
      message: 'The appointment has been moved successfully.',
    })
  } catch (error) {
    addNotification({
      type: 'error',
      title: 'Error',
      message: 'Failed to reschedule appointment.',
    })
  }
}

const handleAppointmentEdit = (appointment: Appointment) => {
  selectedAppointment.value = appointment
  showEditModal.value = true
}

const handleAppointmentCancel = async (appointment: Appointment) => {
  if (confirm('Are you sure you want to cancel this appointment?')) {
    try {
      const index = appointments.value.findIndex(app => app.id === appointment.id)
      if (index !== -1) {
        appointments.value[index] = { ...appointment, status: 'cancelled' }
      }

      addNotification({
        type: 'info',
        title: 'Appointment Cancelled',
        message: 'The appointment has been cancelled.',
      })
    } catch (error) {
      addNotification({
        type: 'error',
        title: 'Error',
        message: 'Failed to cancel appointment.',
      })
    }
  }
}

const handleAppointmentComplete = async (appointment: Appointment) => {
  try {
    const index = appointments.value.findIndex(app => app.id === appointment.id)
    if (index !== -1) {
      appointments.value[index] = { ...appointment, status: 'completed' }
    }

    addNotification({
      type: 'success',
      title: 'Appointment Completed',
      message: 'The appointment has been marked as completed.',
    })
  } catch (error) {
    addNotification({
      type: 'error',
      title: 'Error',
      message: 'Failed to complete appointment.',
    })
  }
}

const handleAppointmentScheduled = (appointment: Appointment) => {
  appointments.value.unshift(appointment)
  showScheduleModal.value = false
  
  addNotification({
    type: 'success',
    title: 'Appointment Scheduled',
    message: 'New appointment has been scheduled successfully.',
  })
}

const handleAppointmentUpdated = (updatedAppointment: Appointment) => {
  const index = appointments.value.findIndex(app => app.id === updatedAppointment.id)
  if (index !== -1) {
    appointments.value[index] = updatedAppointment
  }
  
  showDetailModal.value = false
  showEditModal.value = false
  
  addNotification({
    type: 'success',
    title: 'Appointment Updated',
    message: 'Appointment has been updated successfully.',
  })
}

const handleAppointmentCancelled = (appointment: Appointment) => {
  handleAppointmentCancel(appointment)
  showDetailModal.value = false
}

const handleAppointmentCompleted = (appointment: Appointment) => {
  handleAppointmentComplete(appointment)
  showDetailModal.value = false
}

// Initialize
onMounted(() => {
  loadAppointments()
  loadDoctors()
  
  // Set default custom date range
  const today = new Date()
  customDateRange.value = {
    start: today.toISOString().split('T')[0],
    end: addDays(today, 7).toISOString().split('T')[0]
  }
})
</script>

<style lang="postcss" scoped>
.appointments-container {
  @apply max-w-7xl mx-auto;
}

.stats-grid .stat-card {
  @apply transition-all duration-200 hover:shadow-md;
}

.stats-grid .stat-card:hover {
  transform: translateY(-2px);
}

.filters-section {
  @apply bg-gray-50 border-gray-200;
}

.calendar-view,
.list-view {
  @apply min-h-[600px];
}

.empty-state {
  @apply border-2 border-dashed border-gray-200 bg-gray-50;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .stats-grid {
    @apply grid-cols-2;
  }
  
  .appointments-container {
    @apply px-4;
  }
}

/* Print styles */
@media print {
  .filters-section,
  .appointments-container > div:first-child {
    @apply hidden;
  }
  
  .calendar-view,
  .list-view {
    @apply shadow-none border-none;
  }
}
</style>
