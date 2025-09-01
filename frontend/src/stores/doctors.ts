import { defineStore } from 'pinia'
import { ref, computed, readonly } from 'vue'
import type { Doctor } from '@/types/api.types'
import { doctorsService, type DoctorCreateData, type DoctorUpdateData, type DoctorWithStats } from '@/services/doctors.service'
import { useNotifications } from './notifications'

// DoctorCreateData, DoctorUpdateData, and DoctorWithStats are imported from doctors.service.ts

export interface DoctorAvailability {
  doctorId: number
  date: string
  timeSlots: {
    time: string
    available: boolean
    duration?: number
  }[]
}

export interface WorkingHours {
  start: string
  end: string
  breakStart?: string
  breakEnd?: string
}

export interface DoctorSchedule {
  [day: string]: WorkingHours
}

export interface DoctorFilters {
  search?: string
  specialization?: string
  availability?: boolean | null
  sortBy?: 'firstName' | 'lastName' | 'specialization'
  sortDirection?: 'asc' | 'desc'
}

export interface DoctorStats {
  total: number
  bySpecialization: Record<string, number>
  averageAppointmentsPerDay: number
  availabilityRate: number
}



export const useDoctorsStore = defineStore('doctors', () => {
  // State
  const doctors = ref<DoctorWithStats[]>([])
  const currentDoctor = ref<DoctorWithStats | null>(null)
  const isLoading = ref(false)
  const isCreating = ref(false)
  const isUpdating = ref(false)
  const isDeleting = ref(false)
  const error = ref<string | null>(null)
  const filters = ref<DoctorFilters>({
    search: '',
    specialization: '',
    availability: null,
    sortBy: 'firstName',
    sortDirection: 'asc'
  })
  const doctorAvailability = ref<Record<number, DoctorAvailability>>({})
  const isLoadingAvailability = ref(false)

  // Get notifications store
  const { addNotification } = useNotifications()

  // Helper functions
  const isWorkingDay = (doctor: DoctorWithStats, date: string): boolean => {
    const dayOfWeek = new Date(date).toLocaleLowerCase()
    return doctor.availability ? Object.keys(doctor.availability).includes(dayOfWeek) : false
  }

  const getWorkingHours = (doctor: DoctorWithStats, day: string): string[] => {
    return doctor.availability?.[day.toLowerCase()] || []
  }

  // Getters
  const filteredDoctors = computed(() => {
    let filtered = [...doctors.value]

    // Filter by search term
    if (filters.value.search) {
      const searchTerm = filters.value.search.toLowerCase()
      filtered = filtered.filter(doctor => 
        doctor.firstName.toLowerCase().includes(searchTerm) ||
        doctor.lastName.toLowerCase().includes(searchTerm) ||
        doctor.email.toLowerCase().includes(searchTerm) ||
        doctor.specialization?.toLowerCase().includes(searchTerm) ||
        doctor.licenseNumber?.toLowerCase().includes(searchTerm)
      )
    }

    // Filter by specialization
    if (filters.value.specialization && filters.value.specialization !== '') {
      filtered = filtered.filter(doctor => 
        doctor.specialization?.toLowerCase() === filters.value.specialization!.toLowerCase()
      )
    }

    // Filter by availability
    if (filters.value.availability !== null) {
      const today = new Date().toISOString().split('T')[0]
      filtered = filtered.filter(doctor => 
        isWorkingDay(doctor, today) === filters.value.availability
      )
    }

    // Sort
    const sortBy = filters.value.sortBy || 'firstName'
    const sortDirection = filters.value.sortDirection || 'asc'
    
    filtered.sort((a, b) => {
      let valueA: any = a[sortBy as keyof DoctorWithStats]
      let valueB: any = b[sortBy as keyof DoctorWithStats]
      
      if (typeof valueA === 'string') {
        valueA = valueA.toLowerCase()
        valueB = valueB.toLowerCase()
      }
      
      if (valueA < valueB) return sortDirection === 'asc' ? -1 : 1
      if (valueA > valueB) return sortDirection === 'asc' ? 1 : -1
      return 0
    })

    return filtered
  })

  const doctorStats = computed((): DoctorStats => {
    const total = doctors.value.length
    
    const bySpecialization = doctors.value.reduce((acc, doctor) => {
      const spec = doctor.specialization || 'General'
      acc[spec] = (acc[spec] || 0) + 1
      return acc
    }, {} as Record<string, number>)
    
    const totalAppointments = doctors.value.reduce((sum, doctor) => 
      sum + (doctor.totalAppointments || 0), 0
    )
    const averageAppointmentsPerDay = total > 0 ? Math.round(totalAppointments / total / 30) : 0
    
    const today = new Date().toISOString().split('T')[0]
    const availableToday = doctors.value.filter(doctor => isWorkingDay(doctor, today)).length
    const availabilityRate = total > 0 ? Math.round((availableToday / total) * 100) : 0

    return {
      total,
      bySpecialization,
      averageAppointmentsPerDay,
      availabilityRate
    }
  })

  const availableDoctors = computed(() => {
    const today = new Date().toISOString().split('T')[0]
    return doctors.value.filter(doctor => isWorkingDay(doctor, today))
  })

  const specializations = computed(() => {
    const specs = new Set(doctors.value.map(doctor => doctor.specialization).filter(Boolean))
    return Array.from(specs)
  })

  const totalDoctors = computed(() => doctors.value.length)

  // Actions
  const fetchDoctors = async (): Promise<void> => {
    isLoading.value = true
    error.value = null

    try {
      const fetchedDoctors = await doctorsService.getDoctors()
      doctors.value = fetchedDoctors
    } catch (err: any) {
      error.value = err.message || 'Failed to fetch doctors'
      addNotification({
        type: 'error',
        title: 'Error Loading Doctors',
        message: error.value
      })
    } finally {
      isLoading.value = false
    }
  }

  const fetchDoctorById = async (id: number): Promise<DoctorWithStats | null> => {
    isLoading.value = true
    error.value = null

    try {
      const doctor = await doctorsService.getDoctorById(id)
      
      // Update current doctor for details view
      currentDoctor.value = doctor
      
      // Update in doctors list if exists
      const existingIndex = doctors.value.findIndex(d => d.id === id)
      if (existingIndex !== -1) {
        doctors.value[existingIndex] = doctor
      }

      return doctor
    } catch (err: any) {
      error.value = err.message || 'Failed to fetch doctor'
      addNotification({
        type: 'error',
        title: 'Error Loading Doctor',
        message: error.value
      })
      return null
    } finally {
      isLoading.value = false
    }
  }

  const createDoctor = async (doctorData: DoctorCreateData): Promise<DoctorWithStats | null> => {
    isCreating.value = true
    error.value = null

    try {
      const newDoctor = await doctorsService.createDoctor(doctorData)

      // Add to doctors list
      doctors.value.unshift(newDoctor)

      addNotification({
        type: 'success',
        title: 'Doctor Added',
        message: `Dr. ${newDoctor.firstName} ${newDoctor.lastName} has been added successfully`
      })

      return newDoctor
    } catch (err: any) {
      error.value = err.message || 'Failed to create doctor'
      addNotification({
        type: 'error',
        title: 'Error Adding Doctor',
        message: error.value
      })
      return null
    } finally {
      isCreating.value = false
    }
  }

  const updateDoctor = async (id: number, doctorData: DoctorUpdateData): Promise<DoctorWithStats | null> => {
    isUpdating.value = true
    error.value = null

    try {
      const updatedDoctor = await doctorsService.updateDoctor(id, doctorData)

      // Update in doctors list
      const existingIndex = doctors.value.findIndex(d => d.id === id)
      if (existingIndex !== -1) {
        doctors.value[existingIndex] = updatedDoctor
      }

      // Update current doctor if it's the same
      if (currentDoctor.value && currentDoctor.value.id === id) {
        currentDoctor.value = updatedDoctor
      }

      addNotification({
        type: 'success',
        title: 'Doctor Updated',
        message: `Dr. ${updatedDoctor.firstName} ${updatedDoctor.lastName} has been updated successfully`
      })

      return updatedDoctor
    } catch (err: any) {
      error.value = err.message || 'Failed to update doctor'
      addNotification({
        type: 'error',
        title: 'Error Updating Doctor',
        message: error.value
      })
      return null
    } finally {
      isUpdating.value = false
    }
  }

  const updateDoctorAvailability = async (id: number, availability: DoctorSchedule): Promise<boolean> => {
    isUpdating.value = true
    error.value = null

    try {
      const updatedDoctor = await doctorsService.updateDoctorAvailability(id, availability)
      
      // Update in doctors list
      const existingIndex = doctors.value.findIndex(d => d.id === id)
      if (existingIndex !== -1) {
        doctors.value[existingIndex] = updatedDoctor
      }

      // Update current doctor if it's the same
      if (currentDoctor.value && currentDoctor.value.id === id) {
        currentDoctor.value = updatedDoctor
      }

      return true
    } catch (err: any) {
      error.value = err.message || 'Failed to update doctor availability'
      addNotification({
        type: 'error',
        title: 'Error Updating Availability',
        message: error.value
      })
      return false
    } finally {
      isUpdating.value = false
    }
  }

  const fetchDoctorAvailability = async (doctorId: number, date: string): Promise<void> => {
    isLoadingAvailability.value = true
    error.value = null

    try {
      const availability = await doctorsService.getDoctorAvailability(doctorId, date)
      // Store availability data
      doctorAvailability.value[doctorId] = availability
    } catch (err: any) {
      error.value = err.message || 'Failed to fetch doctor availability'
      addNotification({
        type: 'error',
        title: 'Error Loading Availability',
        message: error.value
      })
    } finally {
      isLoadingAvailability.value = false
    }
  }

  const deleteDoctor = async (id: number): Promise<boolean> => {
    isDeleting.value = true
    error.value = null

    try {
      const success = await doctorsService.deleteDoctor(id)

      if (success) {
        // Remove from doctors list
        const existingIndex = doctors.value.findIndex(d => d.id === id)
        if (existingIndex !== -1) {
          doctors.value.splice(existingIndex, 1)
        }

        // Clear current doctor if it's the same
        if (currentDoctor.value && currentDoctor.value.id === id) {
          currentDoctor.value = null
        }

        addNotification({
          type: 'info',
          title: 'Doctor Removed',
          message: 'Doctor has been removed successfully'
        })
      }

      return success
    } catch (err: any) {
      error.value = err.message || 'Failed to delete doctor'
      addNotification({
        type: 'error',
        title: 'Error Removing Doctor',
        message: error.value
      })
      return false
    } finally {
      isDeleting.value = false
    }
  }

  const setFilters = (newFilters: Partial<DoctorFilters>): void => {
    filters.value = { ...filters.value, ...newFilters }
  }

  const clearFilters = (): void => {
    filters.value = {
      search: '',
      specialization: '',
      availability: null,
      sortBy: 'firstName',
      sortDirection: 'asc'
    }
  }

  const clearError = (): void => {
    error.value = null
  }

  const clearCurrentDoctor = (): void => {
    currentDoctor.value = null
  }

  const clearAvailabilityData = (): void => {
    doctorAvailability.value = {}
  }

  // Helper methods
  const getDoctorFullName = (doctor: DoctorWithStats): string => {
    return doctorsService.getDoctorFullName(doctor)
  }

  const getDoctorsBySpecialization = (specialization: string): DoctorWithStats[] => {
    return doctors.value.filter(doctor => doctor.specialization === specialization)
  }

  const getDoctorAvailabilityForDate = (doctorId: number, date: string): DoctorAvailability | null => {
    return doctorAvailability.value[doctorId] || null
  }

  const isDoctorAvailable = (doctor: DoctorWithStats, date: string, time: string): boolean => {
    return doctorsService.isWorkingDay(doctor, date) && 
           doctorsService.getWorkingHours(doctor, new Date(date).toLocaleDateString('en', { weekday: 'long' })).includes(time)
  }

  const generateTimeSlots = (startTime: string, endTime: string, duration: number = 30): string[] => {
    return doctorsService.generateTimeSlots(startTime, endTime, duration)
  }

  return {
    // State
    doctors: readonly(doctors),
    currentDoctor: readonly(currentDoctor),
    isLoading: readonly(isLoading),
    isCreating: readonly(isCreating),
    isUpdating: readonly(isUpdating),
    isDeleting: readonly(isDeleting),
    error: readonly(error),
    filters: readonly(filters),
    doctorAvailability: readonly(doctorAvailability),
    isLoadingAvailability: readonly(isLoadingAvailability),

    // Getters
    filteredDoctors,
    doctorStats,
    availableDoctors,
    specializations,
    totalDoctors,

    // Actions
    fetchDoctors,
    fetchDoctorById,
    createDoctor,
    updateDoctor,
    updateDoctorAvailability,
    fetchDoctorAvailability,
    deleteDoctor,
    setFilters,
    clearFilters,
    clearError,
    clearCurrentDoctor,
    clearAvailabilityData,

    // Helpers
    getDoctorFullName,
    getDoctorsBySpecialization,
    getDoctorAvailabilityForDate,
    isDoctorAvailable,
    generateTimeSlots,
  }
})

// Export for easier importing
export default useDoctorsStore
