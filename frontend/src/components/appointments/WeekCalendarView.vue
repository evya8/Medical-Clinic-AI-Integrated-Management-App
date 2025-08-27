<template>
  <div class="week-calendar-view">
    <!-- Week Header -->
    <div class="grid grid-cols-8 border-b border-gray-200">
      <div class="p-2 text-xs font-medium text-gray-500 text-center">Time</div>
      <div 
        v-for="day in weekDays" 
        :key="day.date"
        class="p-2 text-xs font-medium text-gray-900 text-center border-l border-gray-200"
      >
        <div>{{ day.dayName }}</div>
        <div class="mt-1 font-normal text-gray-500">{{ day.dayNumber }}</div>
      </div>
    </div>

    <!-- Time Slots Grid -->
    <div class="relative flex-1 overflow-y-auto">
      <!-- Time column -->
      <div class="absolute left-0 top-0 w-16 z-10">
        <div 
          v-for="hour in hours" 
          :key="hour"
          class="h-16 border-b border-gray-200 bg-white"
        >
          <div class="p-2 text-xs text-gray-500">{{ formatHour(hour) }}</div>
        </div>
      </div>

      <!-- Days grid -->
      <div class="ml-16 grid grid-cols-7">
        <div 
          v-for="day in weekDays" 
          :key="day.date"
          class="border-l border-gray-200 relative"
        >
          <!-- Hour slots for each day -->
          <div 
            v-for="hour in hours" 
            :key="hour"
            class="h-16 border-b border-gray-200 relative hover:bg-gray-50 cursor-pointer"
            @click="handleTimeSlotClick(day.date, hour)"
          >
            <!-- Appointments for this time slot -->
            <div 
              v-for="appointment in getAppointmentsForSlot(day.date, hour)"
              :key="appointment.id"
              class="absolute inset-x-1 bg-primary-100 border border-primary-200 rounded px-2 py-1 text-xs cursor-pointer hover:bg-primary-200"
              :style="getAppointmentStyle(appointment)"
              @click.stop="$emit('appointment-click', appointment)"
            >
              <div class="font-medium text-primary-900">{{ appointment.patient?.firstName }} {{ appointment.patient?.lastName }}</div>
              <div class="text-primary-700">{{ appointment.appointmentType }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { format,  addDays,  parseISO } from 'date-fns'
import type { Appointment } from '@/types/api.types'

interface Props {
  currentWeek: { start: Date; end: Date }
  appointments: Appointment[]
}

interface Emits {
  (e: 'appointment-click', appointment: Appointment): void
  (e: 'date-click', date: string): void
  (e: 'appointment-drop', appointment: Appointment, newDate: string, newTime: string): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Computed
const weekDays = computed(() => {
  const start = props.currentWeek.start
  return Array.from({ length: 7 }, (_, i) => {
    const date = addDays(start, i)
    return {
      date: format(date, 'yyyy-MM-dd'),
      dayName: format(date, 'EEE'),
      dayNumber: format(date, 'dd')
    }
  })
})

const hours = computed(() => {
  return Array.from({ length: 12 }, (_, i) => i + 8) // 8 AM to 7 PM
})

// Methods
const formatHour = (hour: number): string => {
  if (hour === 12) return '12 PM'
  if (hour > 12) return `${hour - 12} PM`
  return `${hour} AM`
}

const getAppointmentsForSlot = (date: string, hour: number): Appointment[] => {
  return props.appointments.filter(appointment => {
    try {
      const appointmentDate = parseISO(appointment.appointmentDate)
      const appointmentHour = appointmentDate.getHours()
      return format(appointmentDate, 'yyyy-MM-dd') === date && appointmentHour === hour
    } catch {
      return false
    }
  })
}

const getAppointmentStyle = (appointment: Appointment) => {
  try {
    const startTime = parseISO(appointment.appointmentDate)
    const minutes = startTime.getMinutes()
    const top = `${(minutes / 60) * 100}%`
    return { top }
  } catch {
    return { top: '0%' }
  }
}

const handleTimeSlotClick = (date: string, hour: number) => {
  emit('date-click', date)
}
</script>

<style lang="postcss" scoped>
.week-calendar-view {
  @apply h-full flex flex-col;
}
</style>
