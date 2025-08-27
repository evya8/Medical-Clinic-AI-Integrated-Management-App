<template>
  <div class="appointment-calendar">
    <!-- Calendar Header -->
    <div class="calendar-header flex items-center justify-between p-4 border-b border-gray-200">
      <div class="flex items-center space-x-4">
        <h3 class="text-lg font-semibold text-gray-900">{{ currentMonthYear }}</h3>
        <div class="flex items-center space-x-2">
          <button
            @click="previousMonth"
            class="p-2 text-gray-400 hover:text-gray-600 rounded-full hover:bg-gray-100"
          >
            <ChevronLeftIcon class="w-5 h-5" />
          </button>
          <button
            @click="nextMonth"
            class="p-2 text-gray-400 hover:text-gray-600 rounded-full hover:bg-gray-100"
          >
            <ChevronRightIcon class="w-5 h-5" />
          </button>
        </div>
      </div>
      
      <div class="flex items-center space-x-3">
        <button
          @click="goToToday"
          class="medical-button-outline text-sm"
        >
          Today
        </button>
        
        <div class="flex items-center space-x-2">
          <button
            v-for="view in calendarViews"
            :key="view.key"
            @click="currentCalendarView = view.key"
            class="px-3 py-1 text-sm font-medium rounded transition-colors duration-200"
            :class="currentCalendarView === view.key 
              ? 'bg-primary-100 text-primary-700' 
              : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'"
          >
            {{ view.label }}
          </button>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
      <span class="ml-3 text-gray-600">Loading appointments...</span>
    </div>

    <!-- Calendar Grid -->
    <div v-else class="calendar-content">
      <!-- Month View -->
      <div v-if="currentCalendarView === 'month'" class="calendar-grid">
        <!-- Days of Week Header -->
        <div class="days-header grid grid-cols-7 border-b border-gray-200">
          <div
            v-for="day in daysOfWeek"
            :key="day"
            class="day-header p-3 text-center text-sm font-medium text-gray-600 bg-gray-50"
          >
            {{ day }}
          </div>
        </div>

        <!-- Calendar Days -->
        <div class="days-grid grid grid-cols-7">
          <div
            v-for="day in calendarDays"
            :key="`${day.date}-${day.isCurrentMonth}`"
            class="calendar-day border-r border-b border-gray-200 min-h-[120px] relative cursor-pointer transition-colors duration-200"
            :class="{
              'bg-gray-50': !day.isCurrentMonth,
              'bg-blue-50': day.isToday,
              'hover:bg-gray-50': day.isCurrentMonth && !day.isToday
            }"
            @click="handleDayClick(day.date)"
          >
            <!-- Day Number -->
            <div class="day-number p-2">
              <span 
                class="text-sm font-medium"
                :class="{
                  'text-gray-400': !day.isCurrentMonth,
                  'text-primary-600 bg-primary-100 rounded-full w-6 h-6 flex items-center justify-center': day.isToday,
                  'text-gray-900': day.isCurrentMonth && !day.isToday
                }"
              >
                {{ day.dayNumber }}
              </span>
            </div>

            <!-- Appointments for this day -->
            <div class="appointments-list px-1 pb-1 space-y-1 overflow-hidden">
              <div
                v-for="appointment in day.appointments.slice(0, 3)"
                :key="appointment.id"
                class="appointment-item px-2 py-1 rounded text-xs font-medium truncate cursor-pointer transition-colors duration-200"
                :class="getAppointmentClasses(appointment.status)"
                @click.stop="handleAppointmentClick(appointment)"
                @dragstart="handleDragStart(appointment, $event)"
                draggable="true"
              >
                {{ formatAppointmentTime(appointment.startTime) }} - 
                {{ appointment.patient?.firstName }} {{ appointment.patient?.lastName }}
              </div>
              
              <!-- Show more indicator -->
              <div 
                v-if="day.appointments.length > 3"
                class="text-xs text-gray-500 px-2 cursor-pointer hover:text-gray-700"
                @click.stop="showMoreAppointments(day.date, day.appointments)"
              >
                +{{ day.appointments.length - 3 }} more
              </div>
            </div>

            <!-- Drop zone indicator -->
            <div
              v-if="dragOverDay === day.date"
              class="absolute inset-0 bg-primary-100 border-2 border-primary-300 border-dashed rounded opacity-75"
            ></div>
          </div>
        </div>
      </div>

      <!-- Week View -->
      <div v-else-if="currentCalendarView === 'week'" class="week-view">
        <WeekCalendarView
          :appointments="appointments"
          :current-week="currentWeek"
          @appointment-click="handleAppointmentClick"
          @date-click="handleDayClick"
          @appointment-drop="handleAppointmentDrop"
        />
      </div>

      <!-- Day View -->
      <div v-else-if="currentCalendarView === 'day'" class="day-view">
        <DayCalendarView
          :appointments="dayAppointments"
          :selected-date="selectedDate"
          @appointment-click="handleAppointmentClick"
          @time-slot-click="handleTimeSlotClick"
          @appointment-drop="handleAppointmentDrop"
        />
      </div>
    </div>

    <!-- More Appointments Modal -->
    <MoreAppointmentsModal
      v-if="showMoreModal"
      :date="moreAppointmentsDate"
      :appointments="moreAppointmentsList"
      @close="showMoreModal = false"
      @appointment-click="handleAppointmentClick"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { 
  format, 
  startOfMonth, 
  endOfMonth, 
  startOfWeek, 
  endOfWeek, 
  eachDayOfInterval,
  isSameMonth,
  isToday,
  addMonths,
  subMonths,
  isSameDay,
} from 'date-fns'
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/outline'
import WeekCalendarView from './WeekCalendarView.vue'
import DayCalendarView from './DayCalendarView.vue'
import MoreAppointmentsModal from './MoreAppointmentsModal.vue'
import type { Appointment } from '@/types/api.types'

interface Props {
  appointments: Appointment[]
  loading?: boolean
}

interface Emits {
  (e: 'appointment-click', appointment: Appointment): void
  (e: 'date-click', date: string): void
  (e: 'appointment-drop', appointment: Appointment, newDate: string, newTime: string): void
}

const props = withDefaults(defineProps<Props>(), {
  loading: false
})

const emit = defineEmits<Emits>()

// State
const currentDate = ref(new Date())
const currentCalendarView = ref<'month' | 'week' | 'day'>('month')
const draggedAppointment = ref<Appointment | null>(null)
const dragOverDay = ref<string | null>(null)
const showMoreModal = ref(false)
const moreAppointmentsDate = ref('')
const moreAppointmentsList = ref<Appointment[]>([])

// Constants
const calendarViews = [
  { key: 'month', label: 'Month' },
  { key: 'week', label: 'Week' },
  { key: 'day', label: 'Day' },
]

const daysOfWeek = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']

// Computed
const currentMonthYear = computed(() => {
  return format(currentDate.value, 'MMMM yyyy')
})

const currentWeek = computed(() => {
  return {
    start: startOfWeek(currentDate.value),
    end: endOfWeek(currentDate.value)
  }
})

const selectedDate = computed(() => {
  return format(currentDate.value, 'yyyy-MM-dd')
})

const dayAppointments = computed(() => {
  return props.appointments.filter(appointment =>
    isSameDay(new Date(appointment.appointmentDate), currentDate.value)
  )
})

const calendarDays = computed(() => {
  const monthStart = startOfMonth(currentDate.value)
  const monthEnd = endOfMonth(currentDate.value)
  const calendarStart = startOfWeek(monthStart)
  const calendarEnd = endOfWeek(monthEnd)
  
  const days = eachDayOfInterval({ start: calendarStart, end: calendarEnd })
  
  return days.map(day => {
    const dayAppointments = props.appointments.filter(appointment =>
      isSameDay(new Date(appointment.appointmentDate), day)
    )
    
    return {
      date: format(day, 'yyyy-MM-dd'),
      dayNumber: format(day, 'd'),
      isCurrentMonth: isSameMonth(day, currentDate.value),
      isToday: isToday(day),
      appointments: dayAppointments.sort((a, b) => a.startTime.localeCompare(b.startTime))
    }
  })
})

// Methods
const previousMonth = () => {
  currentDate.value = subMonths(currentDate.value, 1)
}

const nextMonth = () => {
  currentDate.value = addMonths(currentDate.value, 1)
}

const goToToday = () => {
  currentDate.value = new Date()
}

const handleDayClick = (date: string) => {
  if (currentCalendarView.value === 'month') {
    currentDate.value = new Date(date)
    currentCalendarView.value = 'day'
  }
  emit('date-click', date)
}

const handleAppointmentClick = (appointment: Appointment) => {
  emit('appointment-click', appointment)
}

const handleTimeSlotClick = (date: string, time: string) => {
  emit('date-click', date)
}

const formatAppointmentTime = (time: string) => {
  try {
    const [hours, minutes] = time.split(':')
    const date = new Date()
    date.setHours(parseInt(hours), parseInt(minutes))
    return format(date, 'h:mm a')
  } catch {
    return time
  }
}

const getAppointmentClasses = (status: string) => {
  const baseClasses = 'border-l-2'
  const statusClasses: Record<string, string> = {
    'scheduled': 'bg-blue-100 text-blue-800 border-blue-400 hover:bg-blue-200',
    'confirmed': 'bg-green-100 text-green-800 border-green-400 hover:bg-green-200',
    'completed': 'bg-gray-100 text-gray-800 border-gray-400 hover:bg-gray-200',
    'cancelled': 'bg-red-100 text-red-800 border-red-400 hover:bg-red-200',
    'no-show': 'bg-yellow-100 text-yellow-800 border-yellow-400 hover:bg-yellow-200'
  }
  
  return `${baseClasses} ${statusClasses[status] ?? statusClasses['scheduled']}`
}

const showMoreAppointments = (date: string, appointments: Appointment[]) => {
  moreAppointmentsDate.value = date
  moreAppointmentsList.value = appointments
  showMoreModal.value = true
}

// Drag and Drop
const handleDragStart = (appointment: Appointment, event: DragEvent) => {
  draggedAppointment.value = appointment
  if (event.dataTransfer) {
    event.dataTransfer.effectAllowed = 'move'
    event.dataTransfer.setData('text/plain', appointment.id.toString())
  }
}

const handleDragOver = (event: DragEvent, date: string) => {
  event.preventDefault()
  event.dataTransfer!.dropEffect = 'move'
  dragOverDay.value = date
}

const handleDragLeave = () => {
  dragOverDay.value = null
}

const handleDrop = (event: DragEvent, date: string) => {
  event.preventDefault()
  dragOverDay.value = null
  
  if (draggedAppointment.value) {
    // For simplicity, keep the same time when dropping on a new date
    emit('appointment-drop', draggedAppointment.value, date, draggedAppointment.value.startTime)
    draggedAppointment.value = null
  }
}

const handleAppointmentDrop = (appointment: Appointment, newDate: string, newTime: string) => {
  emit('appointment-drop', appointment, newDate, newTime)
}

// Add drag and drop event listeners to calendar days
const addDragListeners = () => {
  const calendarDayElements = document.querySelectorAll('.calendar-day')
  
  calendarDayElements.forEach((element, index) => {
    const day = calendarDays.value[index]
    
    element.addEventListener('dragover', (e) => handleDragOver(e as DragEvent, day.date))
    element.addEventListener('dragleave', handleDragLeave)
    element.addEventListener('drop', (e) => handleDrop(e as DragEvent, day.date))
  })
}

// Watch for calendar changes to re-add drag listeners
watch([calendarDays, currentCalendarView], () => {
  // Use nextTick to ensure DOM is updated
  setTimeout(addDragListeners, 0)
})

onMounted(() => {
  addDragListeners()
})
</script>

<style lang="postcss" scoped>
.appointment-calendar {
  @apply bg-white rounded-lg shadow;
}

.calendar-header {
  @apply bg-gray-50;
}

.calendar-grid {
  @apply min-h-[600px];
}

.calendar-day {
  @apply relative;
}

.calendar-day:hover {
  @apply bg-gray-50;
}

.appointment-item {
  @apply select-none;
}

.appointment-item:hover {
  @apply shadow-sm;
}

/* Drag and drop styles */
.calendar-day.drag-over {
  @apply bg-primary-50 border-primary-200;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .calendar-header {
    @apply flex-col space-y-4;
  }
  
  .calendar-header  div {
    @apply justify-center;
  }
  
  .calendar-day {
    @apply min-h-[80px];
  }
  
  .appointment-item {
    @apply text-xs px-1 py-0.5;
  }
  
  .day-header {
    @apply p-2 text-xs;
  }
}

/* Print styles */
@media print {
  .calendar-header {
    @apply border-b-2 border-black;
  }
  
  .appointment-item {
    @apply bg-transparent border border-gray-400;
  }
}

/* Animation for drag and drop */
@keyframes dragPulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.7;
  }
}

.appointment-item[draggable="true"]:hover {
  animation: dragPulse 1s infinite;
  cursor: grab;
}

.appointment-item[draggable="true"]:active {
  cursor: grabbing;
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
