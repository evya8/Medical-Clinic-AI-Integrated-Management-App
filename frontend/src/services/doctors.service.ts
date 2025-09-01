import { api, API_ENDPOINTS, handleApiError } from './api'
import type { Doctor } from '@/types/api.types'

// Doctor service interfaces
export interface DoctorCreateData {
  userId: number
  specialization?: string
  firstName: string
  lastName: string
  email: string
  phone?: string
  licenseNumber?: string
  qualifications?: string[]
  availability?: {
    [key: string]: string[] // day: time slots
  }
}

export interface DoctorUpdateData extends Partial<DoctorCreateData> {}

export interface DoctorWithStats extends Doctor {
  totalAppointments?: number
  todayAppointments?: number
  upcomingAppointments?: number
  completedAppointments?: number
  averageRating?: number
  isAvailableToday?: boolean
  nextAvailableSlot?: string
}

export interface DoctorListResponse {
  data: DoctorWithStats[]
  total: number
  page?: number
  limit?: number
}

export interface DoctorAvailability {
  doctorId: number
  date: string
  timeSlots: {
    time: string
    available: boolean
    duration?: number
    appointmentId?: number
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

export interface DoctorStats {
  total: number
  bySpecialization: Record<string, number>
  averageAppointmentsPerDay: number
  availabilityRate: number
  activeToday: number
}

export class DoctorsService {
  // Get all doctors
  async getDoctors(): Promise<DoctorWithStats[]> {
    try {
      const response = await api.get<DoctorListResponse>(API_ENDPOINTS.DOCTORS.LIST)
      
      if (response.success && response.data) {
        return Array.isArray(response.data) ? response.data : response.data.data
      } else {
        throw new Error(response.message || 'Failed to fetch doctors')
      }
    } catch (error: any) {
      throw new Error(handleApiError(error))
    }
  }

  // Get doctor by ID
  async getDoctorById(id: number): Promise<DoctorWithStats> {
    try {
      const response = await api.get<DoctorWithStats>(API_ENDPOINTS.DOCTORS.GET(id))
      
      if (response.success && response.data) {
        return response.data
      } else {
        throw new Error(response.message || 'Failed to fetch doctor')
      }
    } catch (error: any) {
      throw new Error(handleApiError(error))
    }
  }

  // Create new doctor (usually happens automatically when user with role 'doctor' is created)
  async createDoctor(doctorData: DoctorCreateData): Promise<DoctorWithStats> {
    try {
      // Validate data before sending
      const validationErrors = this.validateDoctorData(doctorData)
      if (validationErrors.length > 0) {
        throw new Error(validationErrors.join(', '))
      }

      const response = await api.post<DoctorWithStats>(API_ENDPOINTS.DOCTORS.CREATE, doctorData)
      
      if (response.success && response.data) {
        return response.data
      } else {
        throw new Error(response.message || 'Failed to create doctor')
      }
    } catch (error: any) {
      throw new Error(handleApiError(error))
    }
  }

  // Update existing doctor
  async updateDoctor(id: number, doctorData: DoctorUpdateData): Promise<DoctorWithStats> {
    try {
      // Validate data before sending
      const validationErrors = this.validateDoctorData(doctorData)
      if (validationErrors.length > 0) {
        throw new Error(validationErrors.join(', '))
      }

      const response = await api.put<DoctorWithStats>(API_ENDPOINTS.DOCTORS.UPDATE(id), doctorData)
      
      if (response.success && response.data) {
        return response.data
      } else {
        throw new Error(response.message || 'Failed to update doctor')
      }
    } catch (error: any) {
      throw new Error(handleApiError(error))
    }
  }

  // Update doctor availability/schedule
  async updateDoctorAvailability(id: number, availability: DoctorSchedule): Promise<DoctorWithStats> {
    try {
      return await this.updateDoctor(id, { availability })
    } catch (error: any) {
      throw new Error(handleApiError(error))
    }
  }

  // Delete doctor (soft delete - should also deactivate associated user)
  async deleteDoctor(id: number): Promise<boolean> {
    try {
      const response = await api.delete(API_ENDPOINTS.DOCTORS.DELETE(id))
      
      if (response.success) {
        return true
      } else {
        throw new Error(response.message || 'Failed to delete doctor')
      }
    } catch (error: any) {
      throw new Error(handleApiError(error))
    }
  }

  // Get doctor availability for a specific date
  async getDoctorAvailability(doctorId: number, date: string): Promise<DoctorAvailability> {
    try {
      const response = await api.get<DoctorAvailability>(API_ENDPOINTS.DOCTORS.AVAILABILITY(doctorId), {
        date: date
      })
      
      if (response.success && response.data) {
        return response.data
      } else {
        throw new Error(response.message || 'Failed to fetch doctor availability')
      }
    } catch (error: any) {
      throw new Error(handleApiError(error))
    }
  }

  // Get doctors by specialization
  async getDoctorsBySpecialization(specialization: string): Promise<DoctorWithStats[]> {
    try {
      const doctors = await this.getDoctors()
      return doctors.filter(doctor => 
        doctor.specialization?.toLowerCase() === specialization.toLowerCase()
      )
    } catch (error: any) {
      throw new Error(handleApiError(error))
    }
  }

  // Get available doctors for a specific date and time
  async getAvailableDoctors(date: string, time?: string): Promise<DoctorWithStats[]> {
    try {
      const doctors = await this.getDoctors()
      const availableDoctors: DoctorWithStats[] = []

      for (const doctor of doctors) {
        if (await this.isDoctorAvailable(doctor, date, time)) {
          availableDoctors.push(doctor)
        }
      }

      return availableDoctors
    } catch (error: any) {
      throw new Error(handleApiError(error))
    }
  }

  // Check if a doctor is available on a specific date and time
  async isDoctorAvailable(doctor: DoctorWithStats, date: string, time?: string): Promise<boolean> {
    try {
      if (!this.isWorkingDay(doctor, date)) {
        return false
      }

      if (!time) {
        return true // Just checking if working that day
      }

      const availability = await this.getDoctorAvailability(doctor.id, date)
      const timeSlot = availability.timeSlots.find(slot => slot.time === time)
      
      return timeSlot?.available ?? false
    } catch (error: any) {
      console.error('Error checking doctor availability:', error)
      return false
    }
  }

  // Get doctor statistics
  async getDoctorStats(): Promise<DoctorStats> {
    try {
      const doctors = await this.getDoctors()
      const total = doctors.length
      
      const bySpecialization = doctors.reduce((acc, doctor) => {
        const spec = doctor.specialization || 'General'
        acc[spec] = (acc[spec] || 0) + 1
        return acc
      }, {} as Record<string, number>)
      
      const totalAppointments = doctors.reduce((sum, doctor) => 
        sum + (doctor.totalAppointments || 0), 0
      )
      const averageAppointmentsPerDay = total > 0 ? Math.round(totalAppointments / total / 30) : 0
      
      const today = new Date().toISOString().split('T')[0]
      const activeToday = doctors.filter(doctor => 
        this.isWorkingDay(doctor, today)
      ).length
      const availabilityRate = total > 0 ? Math.round((activeToday / total) * 100) : 0

      return {
        total,
        bySpecialization,
        averageAppointmentsPerDay,
        availabilityRate,
        activeToday
      }
    } catch (error: any) {
      throw new Error(handleApiError(error))
    }
  }

  // Get all specializations
  getSpecializations(doctors: DoctorWithStats[]): string[] {
    const specs = new Set(doctors.map(doctor => doctor.specialization).filter(Boolean))
    return Array.from(specs)
  }

  // Helper: Check if doctor is working on a specific day
  isWorkingDay(doctor: DoctorWithStats, date: string): boolean {
    const dayOfWeek = new Date(date).toLocaleDateString('en-US', { weekday: 'long' }).toLowerCase()
    return doctor.availability ? Object.keys(doctor.availability).includes(dayOfWeek) : false
  }

  // Helper: Get working hours for a specific day
  getWorkingHours(doctor: DoctorWithStats, day: string): string[] {
    const dayLower = day.toLowerCase()
    return doctor.availability?.[dayLower] || []
  }

  // Helper: Get doctor full name with title
  getDoctorFullName(doctor: DoctorWithStats): string {
    return `Dr. ${doctor.firstName} ${doctor.lastName}`.trim()
  }

  // Helper: Get doctor display name with specialization
  getDoctorDisplayName(doctor: DoctorWithStats): string {
    const name = this.getDoctorFullName(doctor)
    return doctor.specialization ? `${name} (${doctor.specialization})` : name
  }

  // Helper: Format doctor contact information
  formatDoctorContact(doctor: DoctorWithStats): string {
    const contact = []
    if (doctor.phone) contact.push(doctor.phone)
    if (doctor.email) contact.push(doctor.email)
    return contact.join(' • ')
  }

  // Helper: Check if doctor has complete profile
  hasCompleteProfile(doctor: DoctorWithStats): boolean {
    return !!(
      doctor.firstName &&
      doctor.lastName &&
      doctor.email &&
      doctor.phone &&
      doctor.specialization &&
      doctor.licenseNumber
    )
  }

  // Helper: Generate time slots for a doctor's working day
  generateDoctorTimeSlots(
    doctor: DoctorWithStats, 
    date: string, 
    slotDuration: number = 30
  ): string[] {
    const dayOfWeek = new Date(date).toLocaleDateString('en-US', { weekday: 'long' }).toLowerCase()
    const workingHours = this.getWorkingHours(doctor, dayOfWeek)
    
    if (workingHours.length < 2) {
      return []
    }
    
    const [startTime, endTime] = workingHours
    return this.generateTimeSlots(startTime, endTime, slotDuration)
  }

  // Helper: Generate time slots between two times
  private generateTimeSlots(startTime: string, endTime: string, duration: number = 30): string[] {
    const slots: string[] = []
    const start = new Date(`2000-01-01T${startTime}:00`)
    const end = new Date(`2000-01-01T${endTime}:00`)
    
    let current = new Date(start)
    while (current < end) {
      const timeString = current.toTimeString().substring(0, 5)
      slots.push(timeString)
      current.setMinutes(current.getMinutes() + duration)
    }
    
    return slots
  }

  // Helper: Get next available appointment slot for a doctor
  async getNextAvailableSlot(doctorId: number, daysToCheck: number = 14): Promise<{
    date: string
    time: string
  } | null> {
    try {
      const today = new Date()
      
      for (let i = 0; i < daysToCheck; i++) {
        const checkDate = new Date(today)
        checkDate.setDate(today.getDate() + i)
        const dateString = checkDate.toISOString().split('T')[0]
        
        const availability = await this.getDoctorAvailability(doctorId, dateString)
        const availableSlot = availability.timeSlots.find(slot => slot.available)
        
        if (availableSlot) {
          return {
            date: dateString,
            time: availableSlot.time
          }
        }
      }
      
      return null
    } catch (error: any) {
      console.error('Error finding next available slot:', error)
      return null
    }
  }

  // Helper: Check if doctor is available now
  isAvailableNow(doctor: DoctorWithStats): boolean {
    const now = new Date()
    const today = now.toISOString().split('T')[0]
    const currentTime = now.toTimeString().substring(0, 5)
    
    if (!this.isWorkingDay(doctor, today)) {
      return false
    }
    
    const workingHours = this.getWorkingHours(doctor, new Date().toLocaleDateString('en-US', { weekday: 'long' }))
    if (workingHours.length < 2) {
      return false
    }
    
    const [startTime, endTime] = workingHours
    return currentTime >= startTime && currentTime <= endTime
  }

  // Validate doctor data
  validateDoctorData(doctorData: DoctorCreateData | DoctorUpdateData): string[] {
    const errors: string[] = []

    // Required fields for creation
    if ('firstName' in doctorData && !doctorData.firstName?.trim()) {
      errors.push('First name is required')
    }
    
    if ('lastName' in doctorData && !doctorData.lastName?.trim()) {
      errors.push('Last name is required')
    }
    
    if ('email' in doctorData && !doctorData.email?.trim()) {
      errors.push('Email is required')
    }

    // Email validation
    if (doctorData.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(doctorData.email)) {
      errors.push('Please enter a valid email address')
    }

    // Phone validation (basic)
    if (doctorData.phone && doctorData.phone.trim() && 
        !/^[\+]?[1-9][\d]{0,15}$/.test(doctorData.phone.replace(/\D/g, ''))) {
      errors.push('Please enter a valid phone number')
    }

    // License number validation
    if (doctorData.licenseNumber && !doctorData.licenseNumber.trim()) {
      errors.push('License number cannot be empty if provided')
    }

    // Specialization validation
    if (doctorData.specialization && !doctorData.specialization.trim()) {
      errors.push('Specialization cannot be empty if provided')
    }

    // Availability validation
    if (doctorData.availability) {
      Object.entries(doctorData.availability).forEach(([day, hours]) => {
        if (!Array.isArray(hours) || hours.length < 2) {
          errors.push(`Invalid working hours for ${day}`)
        } else {
          const [start, end] = hours
          const startTime = new Date(`2000-01-01T${start}:00`)
          const endTime = new Date(`2000-01-01T${end}:00`)
          
          if (endTime <= startTime) {
            errors.push(`End time must be after start time for ${day}`)
          }
        }
      })
    }

    // Qualifications validation
    if (doctorData.qualifications && !Array.isArray(doctorData.qualifications)) {
      errors.push('Qualifications must be provided as an array')
    }

    return errors
  }

  // Helper: Filter doctors by various criteria
  filterDoctors(doctors: DoctorWithStats[], filters: {
    search?: string
    specialization?: string
    availableToday?: boolean
    hasCompleteProfile?: boolean
  }): DoctorWithStats[] {
    let filtered = [...doctors]

    // Filter by search term
    if (filters.search) {
      const searchTerm = filters.search.toLowerCase()
      filtered = filtered.filter(doctor => 
        doctor.firstName.toLowerCase().includes(searchTerm) ||
        doctor.lastName.toLowerCase().includes(searchTerm) ||
        doctor.email.toLowerCase().includes(searchTerm) ||
        doctor.specialization?.toLowerCase().includes(searchTerm) ||
        doctor.licenseNumber?.toLowerCase().includes(searchTerm)
      )
    }

    // Filter by specialization
    if (filters.specialization) {
      filtered = filtered.filter(doctor => 
        doctor.specialization?.toLowerCase() === filters.specialization!.toLowerCase()
      )
    }

    // Filter by today availability
    if (filters.availableToday) {
      const today = new Date().toISOString().split('T')[0]
      filtered = filtered.filter(doctor => this.isWorkingDay(doctor, today))
    }

    // Filter by complete profile
    if (filters.hasCompleteProfile) {
      filtered = filtered.filter(doctor => this.hasCompleteProfile(doctor))
    }

    return filtered
  }

  // Helper: Sort doctors by various criteria
  sortDoctors(doctors: DoctorWithStats[], sortBy: 'name' | 'specialization' | 'appointments' | 'rating', direction: 'asc' | 'desc' = 'asc'): DoctorWithStats[] {
    const sorted = [...doctors]

    sorted.sort((a, b) => {
      let valueA: any
      let valueB: any

      switch (sortBy) {
        case 'name':
          valueA = `${a.firstName} ${a.lastName}`.toLowerCase()
          valueB = `${b.firstName} ${b.lastName}`.toLowerCase()
          break
        case 'specialization':
          valueA = a.specialization?.toLowerCase() || ''
          valueB = b.specialization?.toLowerCase() || ''
          break
        case 'appointments':
          valueA = a.totalAppointments || 0
          valueB = b.totalAppointments || 0
          break
        case 'rating':
          valueA = a.averageRating || 0
          valueB = b.averageRating || 0
          break
        default:
          valueA = a.firstName.toLowerCase()
          valueB = b.firstName.toLowerCase()
      }

      if (valueA < valueB) return direction === 'asc' ? -1 : 1
      if (valueA > valueB) return direction === 'asc' ? 1 : -1
      return 0
    })

    return sorted
  }
}

// Create singleton instance
export const doctorsService = new DoctorsService()

// Export for easier importing
export default doctorsService
