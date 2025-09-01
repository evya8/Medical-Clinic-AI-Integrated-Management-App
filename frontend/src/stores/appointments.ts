import { defineStore } from 'pinia'
import { ref, computed, readonly } from 'vue'
import type { Appointment, AppointmentStatus, Patient, Doctor } from '@/types/api.types'
import { appointmentsService, type AppointmentCreateData, type AppointmentUpdateData, type AppointmentWithDetails, type TimeSlot } from '@/services/appointments.service'
import { useNotifications } from './notifications'

// AppointmentCreateData, AppointmentUpdateData, AppointmentWithDetails, and TimeSlot are imported from appointments.service.ts

export interface AppointmentFilters {
  dateFrom?: string
  dateTo?: string
  doctorId?: number | ''
  patientId?: number | ''
  status?: AppointmentStatus | ''
  priority?: 'low' | 'normal' | 'high' | 'urgent' | ''
  appointmentType?: string
  search?: string
}



export interface AppointmentStats {
  total: number
  byStatus: Record<AppointmentStatus, number>
  byPriority: Record<string, number>
  todayTotal: number
  upcomingTotal: number
  completionRate: number
}



export const useAppointmentsStore = defineStore('appointments', () => {
  // State
  const appointments = ref<AppointmentWithDetails[]>([])
  const currentAppointment = ref<AppointmentWithDetails | null>(null)
  const isLoading = ref(false)
  const isCreating = ref(false)
  const isUpdating = ref(false)
  const isDeleting = ref(false)
  const error = ref<string | null>(null)
  const filters = ref<AppointmentFilters>({
    dateFrom: '',
    dateTo: '',
    doctorId: '',
    patientId: '',
    status: '',
    priority: '',
    appointmentType: '',
    search: ''
  })
  const availableSlots = ref<TimeSlot[]>([])
  const isLoadingSlots = ref(false)

  // Get notifications store
  const { addNotification } = useNotifications()

  // Helper functions
  const isToday = (dateString: string): boolean => {
    const today = new Date().toISOString().split('T')[0]
    return dateString === today
  }

  const isFuture = (dateString: string, timeString: string): boolean => {
    const appointmentDateTime = new Date(`${dateString}T${timeString}`)
    return appointmentDateTime > new Date()
  }

  const isPast = (dateString: string, timeString: string): boolean => {
    const appointmentDateTime = new Date(`${dateString}T${timeString}`)
    return appointmentDateTime < new Date()
  }

  // Getters
  const filteredAppointments = computed(() => {
    let filtered = [...appointments.value]

    // Filter by date range
    if (filters.value.dateFrom) {
      filtered = filtered.filter(apt => apt.appointmentDate >= filters.value.dateFrom!)
    }
    if (filters.value.dateTo) {
      filtered = filtered.filter(apt => apt.appointmentDate <= filters.value.dateTo!)
    }

    // Filter by doctor
    if (filters.value.doctorId && filters.value.doctorId !== '') {
      filtered = filtered.filter(apt => apt.doctorId === filters.value.doctorId)
    }

    // Filter by patient
    if (filters.value.patientId && filters.value.patientId !== '') {
      filtered = filtered.filter(apt => apt.patientId === filters.value.patientId)
    }

    // Filter by status
    if (filters.value.status && filters.value.status !== '') {
      filtered = filtered.filter(apt => apt.status === filters.value.status)
    }

    // Filter by priority
    if (filters.value.priority && filters.value.priority !== '') {
      filtered = filtered.filter(apt => apt.priority === filters.value.priority)
    }

    // Filter by appointment type
    if (filters.value.appointmentType) {
      filtered = filtered.filter(apt => 
        apt.appointmentType.toLowerCase().includes(filters.value.appointmentType!.toLowerCase())
      )
    }

    // Filter by search term
    if (filters.value.search) {
      const searchTerm = filters.value.search.toLowerCase()
      filtered = filtered.filter(apt =>
        apt.patientName.toLowerCase().includes(searchTerm) ||
        apt.doctorName.toLowerCase().includes(searchTerm) ||
        apt.appointmentType.toLowerCase().includes(searchTerm) ||
        apt.notes?.toLowerCase().includes(searchTerm) ||
        apt.diagnosis?.toLowerCase().includes(searchTerm)
      )
    }

    // Sort by date and time
    filtered.sort((a, b) => {
      const dateTimeA = new Date(`${a.appointmentDate}T${a.startTime}`)
      const dateTimeB = new Date(`${b.appointmentDate}T${b.startTime}`)
      return dateTimeA.getTime() - dateTimeB.getTime()
    })

    return filtered
  })

  const appointmentStats = computed((): AppointmentStats => {
    const total = appointments.value.length
    const today = new Date().toISOString().split('T')[0]

    const byStatus = appointments.value.reduce((acc, apt) => {
      acc[apt.status] = (acc[apt.status] || 0) + 1
      return acc
    }, {} as Record<AppointmentStatus, number>)

    const byPriority = appointments.value.reduce((acc, apt) => {
      acc[apt.priority] = (acc[apt.priority] || 0) + 1
      return acc
    }, {} as Record<string, number>)

    const todayTotal = appointments.value.filter(apt => isToday(apt.appointmentDate)).length
    
    const upcomingTotal = appointments.value.filter(apt => 
      isFuture(apt.appointmentDate, apt.startTime)
    ).length

    const completedCount = byStatus.completed || 0
    const completionRate = total > 0 ? Math.round((completedCount / total) * 100) : 0

    return {
      total,
      byStatus,
      byPriority,
      todayTotal,
      upcomingTotal,
      completionRate
    }
  })

  const todayAppointments = computed(() => 
    appointments.value.filter(apt => isToday(apt.appointmentDate))
  )

  const upcomingAppointments = computed(() => 
    appointments.value.filter(apt => isFuture(apt.appointmentDate, apt.startTime))
  )

  const pastAppointments = computed(() => 
    appointments.value.filter(apt => isPast(apt.appointmentDate, apt.startTime))
  )

  const urgentAppointments = computed(() => 
    appointments.value.filter(apt => apt.priority === 'urgent' || apt.priority === 'high')
  )

  // Actions
  const fetchAppointments = async (params?: { 
    dateFrom?: string
    dateTo?: string
    doctorId?: number
    patientId?: number 
  }): Promise<void> => {
    isLoading.value = true
    error.value = null

    try {
      const filters = params ? {
        dateFrom: params.dateFrom,
        dateTo: params.dateTo,
        doctorId: params.doctorId,
        patientId: params.patientId
      } : undefined

      appointments.value = await appointmentsService.getAppointments(filters)
    } catch (err: any) {
      error.value = err.message || 'Failed to fetch appointments'
      addNotification({
        type: 'error',
        title: 'Error Loading Appointments',
        message: error.value
      })
    } finally {
      isLoading.value = false
    }
  }

  const fetchAppointmentById = async (id: number): Promise<AppointmentWithDetails | null> => {
    isLoading.value = true
    error.value = null

    try {
      const appointment = await appointmentsService.getAppointmentById(id)
      
      // Update current appointment for details view
      currentAppointment.value = appointment
      
      // Update in appointments list if exists
      const existingIndex = appointments.value.findIndex(a => a.id === id)
      if (existingIndex !== -1) {
        appointments.value[existingIndex] = appointment
      }

      return appointment
    } catch (err: any) {
      error.value = err.message || 'Failed to fetch appointment'
      addNotification({
        type: 'error',
        title: 'Error Loading Appointment',
        message: error.value
      })
      return null
    } finally {
      isLoading.value = false
    }
  }

  const createAppointment = async (appointmentData: AppointmentCreateData): Promise<AppointmentWithDetails | null> => {
    isCreating.value = true
    error.value = null

    try {
      const newAppointment = await appointmentsService.createAppointment(appointmentData)

      // Add to appointments list
      appointments.value.unshift(newAppointment)

      addNotification({
        type: 'success',
        title: 'Appointment Created',
        message: `Appointment scheduled for ${newAppointment.patientName}`
      })

      return newAppointment
    } catch (err: any) {
      error.value = err.message || 'Failed to create appointment'
      addNotification({
        type: 'error',
        title: 'Error Creating Appointment',
        message: error.value
      })
      return null
    } finally {
      isCreating.value = false
    }
  }

  const updateAppointment = async (id: number, appointmentData: AppointmentUpdateData): Promise<AppointmentWithDetails | null> => {
    isUpdating.value = true
    error.value = null

    try {
      const updatedAppointment = await appointmentsService.updateAppointment(id, appointmentData)

      // Update in appointments list
      const existingIndex = appointments.value.findIndex(a => a.id === id)
      if (existingIndex !== -1) {
        appointments.value[existingIndex] = updatedAppointment
      }

      // Update current appointment if it's the same
      if (currentAppointment.value && currentAppointment.value.id === id) {
        currentAppointment.value = updatedAppointment
      }

      addNotification({
        type: 'success',
        title: 'Appointment Updated',
        message: `Appointment updated successfully`
      })

      return updatedAppointment
    } catch (err: any) {
      error.value = err.message || 'Failed to update appointment'
      addNotification({
        type: 'error',
        title: 'Error Updating Appointment',
        message: error.value
      })
      return null
    } finally {
      isUpdating.value = false
    }
  }

  const cancelAppointment = async (id: number, reason?: string): Promise<boolean> => {
    isUpdating.value = true
    error.value = null

    try {
      const updatedAppointment = await appointmentsService.cancelAppointment(id, reason)
      
      // Update in appointments list
      const existingIndex = appointments.value.findIndex(a => a.id === id)
      if (existingIndex !== -1) {
        appointments.value[existingIndex] = updatedAppointment
      }

      // Update current appointment if it's the same
      if (currentAppointment.value && currentAppointment.value.id === id) {
        currentAppointment.value = updatedAppointment
      }

      return true
    } catch (err: any) {
      error.value = err.message || 'Failed to cancel appointment'
      addNotification({
        type: 'error',
        title: 'Error Cancelling Appointment',
        message: error.value
      })
      return false
    } finally {
      isUpdating.value = false
    }
  }

  const completeAppointment = async (id: number, medicalData: {
    diagnosis?: string
    treatmentNotes?: string
    followUpRequired?: boolean
    followUpDate?: string
  }): Promise<boolean> => {
    isUpdating.value = true
    error.value = null

    try {
      const updatedAppointment = await appointmentsService.completeAppointment(id, medicalData)
      
      // Update in appointments list
      const existingIndex = appointments.value.findIndex(a => a.id === id)
      if (existingIndex !== -1) {
        appointments.value[existingIndex] = updatedAppointment
      }

      // Update current appointment if it's the same
      if (currentAppointment.value && currentAppointment.value.id === id) {
        currentAppointment.value = updatedAppointment
      }

      return true
    } catch (err: any) {
      error.value = err.message || 'Failed to complete appointment'
      addNotification({
        type: 'error',
        title: 'Error Completing Appointment',
        message: error.value
      })
      return false
    } finally {
      isUpdating.value = false
    }
  }

  const fetchAvailableSlots = async (doctorId: number, date: string): Promise<void> => {
    isLoadingSlots.value = true
    error.value = null

    try {
      const slots = await appointmentsService.getAvailableSlots(doctorId, date)
      availableSlots.value = slots
    } catch (err: any) {
      error.value = err.message || 'Failed to fetch available slots'
      addNotification({
        type: 'error',
        title: 'Error Loading Available Slots',
        message: error.value
      })
    } finally {
      isLoadingSlots.value = false
    }
  }

  const setFilters = (newFilters: Partial<AppointmentFilters>): void => {
    filters.value = { ...filters.value, ...newFilters }
  }

  const clearFilters = (): void => {
    filters.value = {
      dateFrom: '',
      dateTo: '',
      doctorId: '',
      patientId: '',
      status: '',
      priority: '',
      appointmentType: '',
      search: ''
    }
  }

  const clearError = (): void => {
    error.value = null
  }

  const clearCurrentAppointment = (): void => {
    currentAppointment.value = null
  }

  const clearAvailableSlots = (): void => {
    availableSlots.value = []
  }

  // Helper methods
  const getAppointmentsByPatient = (patientId: number): AppointmentWithDetails[] => {
    return appointments.value.filter(apt => apt.patientId === patientId)
  }

  const getAppointmentsByDoctor = (doctorId: number): AppointmentWithDetails[] => {
    return appointments.value.filter(apt => apt.doctorId === doctorId)
  }

  const getAppointmentsByDate = (date: string): AppointmentWithDetails[] => {
    return appointments.value.filter(apt => apt.appointmentDate === date)
  }

  return {
    // State
    appointments: readonly(appointments),
    currentAppointment: readonly(currentAppointment),
    isLoading: readonly(isLoading),
    isCreating: readonly(isCreating),
    isUpdating: readonly(isUpdating),
    isDeleting: readonly(isDeleting),
    error: readonly(error),
    filters: readonly(filters),
    availableSlots: readonly(availableSlots),
    isLoadingSlots: readonly(isLoadingSlots),

    // Getters
    filteredAppointments,
    appointmentStats,
    todayAppointments,
    upcomingAppointments,
    pastAppointments,
    urgentAppointments,

    // Actions
    fetchAppointments,
    fetchAppointmentById,
    createAppointment,
    updateAppointment,
    cancelAppointment,
    completeAppointment,
    fetchAvailableSlots,
    setFilters,
    clearFilters,
    clearError,
    clearCurrentAppointment,
    clearAvailableSlots,

    // Helpers
    getAppointmentsByPatient,
    getAppointmentsByDoctor,
    getAppointmentsByDate,
  }
})

// Export for easier importing
export default useAppointmentsStore
