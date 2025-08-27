<template>
  <div class="day-calendar-view">
    <!-- Day Header -->
    <div class="border-b border-gray-200 p-4">
      <h3 class="text-lg font-medium text-gray-900">
        {{ format(currentDate, 'EEEE, MMMM dd, yyyy') }}
      </h3>
    </div>

    <!-- Time Slots -->
    <div class="flex-1 overflow-y-auto">
      <div class="relative">
        <div 
          v-for="hour in hours" 
          :key="hour"
          class="flex border-b border-gray-200"
        >
          <!-- Time -->
          <div class="w-20 p-3 text-sm text-gray-500 border-r border-gray-200">
            {{ formatHour(hour) }}
          </div>

          <!-- Appointment slot -->
          <div 
            class="flex-1 min-h-16 p-2 hover:bg-gray-50 cursor-pointer relative"
            @click="handleTimeSlotClick(hour)"
          >
            <!-- Appointments for this hour -->
            <div 
              v-for="appointment in getAppointmentsForHour(hour)"
              :key="appointment.id"
              class="mb-2 p-2 bg-primary-100 border border-primary-200 rounded cursor-pointer hover:bg-primary-200"
              @click.stop="$emit('appointment-click', appointment)"
            >
              <div class="font-medium text-primary-900">
                {{ appointment.patient?.firstName }} {{ appointment.patient?.lastName }}
              </div>
              <div class="text-sm text-primary-700">{{ appointment.appointmentType }}</div>
              <div class="text-xs text-primary-600">
                {{ appointment.startTime }} - {{ appointment.endTime }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { format, isSameDay, parseISO } from 'date-fns'
import type { Appointment } from '@/types/api.types'

interface Props {
  currentDate: Date
  appointments: Appointment[]
}

interface Emits {
  (e: 'appointment-click', appointment: Appointment): void
  (e: 'time-slot-click', hour: number): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Computed
const hours = computed(() => {
  return Array.from({ length: 12 }, (_, i) => i + 8) // 8 AM to 7 PM
})

// Methods
const formatHour = (hour: number): string => {
  if (hour === 12) return '12:00 PM'
  if (hour > 12) return `${hour - 12}:00 PM`
  return `${hour}:00 AM`
}

const getAppointmentsForHour = (hour: number): Appointment[] => {
  return props.appointments.filter(appointment => {
    try {
      const appointmentDate = parseISO(appointment.appointmentDate)
      const appointmentHour = appointmentDate.getHours()
      return isSameDay(appointmentDate, props.currentDate) && appointmentHour === hour
    } catch {
      return false
    }
  })
}

const handleTimeSlotClick = (hour: number) => {
  emit('time-slot-click', hour)
}
</script>

<style lang="postcss" scoped>
.day-calendar-view {
  @apply h-full flex flex-col;
}
</style>
