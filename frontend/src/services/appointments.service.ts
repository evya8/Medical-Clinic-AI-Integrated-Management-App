import { api, API_ENDPOINTS, handleApiError } from './api'
import type { Appointment, AppointmentStatus } from '@/types/api.types'

// Appointment service interfaces
export interface AppointmentCreateData {
  patientId: number
  doctorId: number
  appointmentDate: string
  startTime: string
  endTime: string
  appointmentType: string
  priority?: 'low' | 'normal' | 'high' | 'urgent'
  notes?: string
}

export interface AppointmentUpdateData {
  patientId?: number
  doctorId?: number
  appointmentDate?: string
  startTime?: string
  endTime?: string
  appointmentType?: string
  priority?: 'low' | 'normal' | 'high' | 'urgent'
  status?: AppointmentStatus
  notes?: string
  // Enhanced medical fields from backend
  diagnosis?: string
  treatmentNotes?: string
  followUpRequired?: boolean
  followUpDate?: string
}

export interface AppointmentWithDetails extends Appointment {
  patientName: string
  doctorName: string
  patientEmail?: string
  patientPhone?: string
  doctorSpecialty?: string
}

export interface AppointmentListResponse {
  data: AppointmentWithDetails[]
  total: number
  page?: number
  limit?: number
}

export interface TimeSlot {
  time: string
  available: boolean
  doctorId?: number
  duration?: number
}

export interface AvailableSlots {
  date: string
  doctorId: number
  slots: TimeSlot[]
}

export interface AppointmentFilters {
  dateFrom?: string
  dateTo?: string
  doctorId?: number
  patientId?: number
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
  averageDuration: number
  noShowRate: number
}

export class AppointmentsService {
  // Get all appointments with optional filters
  async getAppointments(filters?: AppointmentFilters): Promise<AppointmentWithDetails[]> {
    try {
      const response = await api.get<AppointmentListResponse>(API_ENDPOINTS.APPOINTMENTS.LIST, filters)
      
      if (response.success && response.data) {
        return Array.isArray(response.data) ? response.data : response.data.data
      } else {
        throw new Error(response.message || 'Failed to fetch appointments')
      }
    } catch (error: any) {
      throw new Error(handleApiError(error))
    }
  }

  // Get appointment by ID
  async getAppointmentById(id: number): Promise<AppointmentWithDetails> {
    try {
      const response = await api.get<AppointmentWithDetails>(API_ENDPOINTS.APPOINTMENTS.GET(id))
      
      if (response.success && response.data) {
        return response.data
      } else {
        throw new Error(response.message || 'Failed to fetch appointment')
      }
    } catch (error: any) {
      throw new Error(handleApiError(error))
    }
  }

  // Create new appointment
  async createAppointment(appointmentData: AppointmentCreateData): Promise<AppointmentWithDetails> {
    try {
      // Validate data before sending
      const validationErrors = this.validateAppointmentData(appointmentData)
      if (validationErrors.length > 0) {
        throw new Error(validationErrors.join(', '))
      }

      const response = await api.post<AppointmentWithDetails>(API_ENDPOINTS.APPOINTMENTS.CREATE, appointmentData)
      
      if (response.success && response.data) {
        return response.data
      } else {
        throw new Error(response.message || 'Failed to create appointment')
      }
    } catch (error: any) {
      throw new Error(handleApiError(error))
    }
  }

  // Update existing appointment
  async updateAppointment(id: number, appointmentData: AppointmentUpdateData): Promise<AppointmentWithDetails> {
    try {
      const response = await api.put<AppointmentWithDetails>(API_ENDPOINTS.APPOINTMENTS.UPDATE(id), appointmentData)
      
      if (response.success && response.data) {
        return response.data
      } else {
        throw new Error(response.message || 'Failed to update appointment')
      }
    } catch (error: any) {
      throw new Error(handleApiError(error))
    }
  }

  // Cancel appointment
  async cancelAppointment(id: number, reason?: string): Promise<AppointmentWithDetails> {
    try {
      const cancelData: AppointmentUpdateData = {
        status: 'cancelled',
        notes: reason ? `Cancelled: ${reason}` : 'Cancelled'
      }

      return await this.updateAppointment(id, cancelData)
    } catch (error: any) {
      throw new Error(handleApiError(error))
    }
  }

  // Complete appointment with medical data
  async completeAppointment(id: number, medicalData: {
    diagnosis?: string
    treatmentNotes?: string
    followUpRequired?: boolean
    followUpDate?: string
    notes?: string
  }): Promise<AppointmentWithDetails> {
    try {
      const completeData: AppointmentUpdateData = {
        status: 'completed',
        ...medicalData
      }

      return await this.updateAppointment(id, completeData)
    } catch (error: any) {
      throw new Error(handleApiError(error))
    }
  }

  // Reschedule appointment
  async rescheduleAppointment(id: number, newData: {
    appointmentDate: string
    startTime: string
    endTime: string
    doctorId?: number
    reason?: string
  }): Promise<AppointmentWithDetails> {
    try {
      const rescheduleData: AppointmentUpdateData = {
        appointmentDate: newData.appointmentDate,
        startTime: newData.startTime,
        endTime: newData.endTime,
        doctorId: newData.doctorId,
        notes: newData.reason ? `Rescheduled: ${newData.reason}` : 'Rescheduled'
      }

      return await this.updateAppointment(id, rescheduleData)
    } catch (error: any) {
      throw new Error(handleApiError(error))
    }
  }

  // Get available time slots for a doctor on a specific date
  async getAvailableSlots(doctorId: number, date: string): Promise<TimeSlot[]> {
    try {
      const response = await api.get<AvailableSlots>(API_ENDPOINTS.APPOINTMENTS.AVAILABLE_SLOTS, {
        doctor_id: doctorId,
        date: date
      })
      
      if (response.success && response.data) {
        return response.data.slots || []
      } else {
        throw new Error(response.message || 'Failed to fetch available slots')
      }
    } catch (error: any) {
      throw new Error(handleApiError(error))
    }
  }

  // Get appointments by date
  async getAppointmentsByDate(date: string): Promise<AppointmentWithDetails[]> {
    try {
      const response = await api.get<AppointmentListResponse>(API_ENDPOINTS.APPOINTMENTS.BY_DATE, {
        date: date
      })
      
      if (response.success && response.data) {
        return Array.isArray(response.data) ? response.data : response.data.data
      } else {
        throw new Error(response.message || 'Failed to fetch appointments by date')
      }
    } catch (error: any) {
      throw new Error(handleApiError(error))
    }
  }

  // Get appointments by patient
  async getAppointmentsByPatient(patientId: number): Promise<AppointmentWithDetails[]> {
    try {
      const response = await api.get<AppointmentListResponse>(API_ENDPOINTS.APPOINTMENTS.BY_PATIENT(patientId))
      
      if (response.success && response.data) {
        return Array.isArray(response.data) ? response.data : response.data.data
      } else {
        throw new Error(response.message || 'Failed to fetch patient appointments')
      }
    } catch (error: any) {
      throw new Error(handleApiError(error))
    }
  }

  // Get appointments by doctor
  async getAppointmentsByDoctor(doctorId: number): Promise<AppointmentWithDetails[]> {
    try {
      const response = await api.get<AppointmentListResponse>(API_ENDPOINTS.APPOINTMENTS.BY_DOCTOR(doctorId))
      
      if (response.success && response.data) {
        return Array.isArray(response.data) ? response.data : response.data.data
      } else {
        throw new Error(response.message || 'Failed to fetch doctor appointments')
      }
    } catch (error: any) {
      throw new Error(handleApiError(error))
    }
  }

  // Get appointment statistics
  async getAppointmentStats(dateFrom?: string, dateTo?: string): Promise<AppointmentStats> {
    try {
      // This would ideally be a dedicated API endpoint, but we'll calculate from appointments
      const appointments = await this.getAppointments({
        dateFrom,
        dateTo
      })
      
      const total = appointments.length
      const today = new Date().toISOString().split('T')[0]

      const byStatus = appointments.reduce((acc, apt) => {
        acc[apt.status] = (acc[apt.status] || 0) + 1
        return acc
      }, {} as Record<AppointmentStatus, number>)

      const byPriority = appointments.reduce((acc, apt) => {
        const priority = apt.priority || 'normal'
        acc[priority] = (acc[priority] || 0) + 1
        return acc
      }, {} as Record<string, number>)

      const todayTotal = appointments.filter(apt => apt.appointmentDate === today).length
      
      const upcomingTotal = appointments.filter(apt => 
        this.isFutureAppointment(apt.appointmentDate, apt.startTime)
      ).length

      const completedCount = byStatus.completed || 0
      const completionRate = total > 0 ? Math.round((completedCount / total) * 100) : 0

      // Calculate average duration
      const totalDuration = appointments.reduce((sum, apt) => {
        const duration = this.getAppointmentDuration(apt.startTime, apt.endTime)
        return sum + duration
      }, 0)
      const averageDuration = total > 0 ? Math.round(totalDuration / total) : 0

      // Calculate no-show rate
      const noShowCount = byStatus.no_show || 0
      const noShowRate = total > 0 ? Math.round((noShowCount / total) * 100) : 0

      return {
        total,
        byStatus,
        byPriority,
        todayTotal,
        upcomingTotal,
        completionRate,
        averageDuration,
        noShowRate
      }
    } catch (error: any) {
      throw new Error(handleApiError(error))
    }
  }

  // Helper: Check if appointment is in the future
  private isFutureAppointment(date: string, time: string): boolean {
    const appointmentDateTime = new Date(`${date}T${time}`)
    return appointmentDateTime > new Date()
  }

  // Helper: Check if appointment is today
  isAppointmentToday(date: string): boolean {
    const today = new Date().toISOString().split('T')[0]
    return date === today
  }

  // Helper: Check if appointment is past
  isAppointmentPast(date: string, time: string): boolean {
    const appointmentDateTime = new Date(`${date}T${time}`)
    return appointmentDateTime < new Date()
  }

  // Helper: Get appointment duration in minutes
  private getAppointmentDuration(startTime: string, endTime: string): number {
    const start = new Date(`2000-01-01T${startTime}`)
    const end = new Date(`2000-01-01T${endTime}`)
    return (end.getTime() - start.getTime()) / (1000 * 60)
  }

  // Helper: Format appointment time range
  formatAppointmentTime(startTime: string, endTime: string): string {
    const start = new Date(`2000-01-01T${startTime}`).toLocaleTimeString('en-US', {
      hour: 'numeric',
      minute: '2-digit',
      hour12: true
    })
    const end = new Date(`2000-01-01T${endTime}`).toLocaleTimeString('en-US', {
      hour: 'numeric',
      minute: '2-digit',
      hour12: true
    })
    return `${start} - ${end}`
  }

  // Helper: Get appointment status color
  getStatusColor(status: AppointmentStatus): string {
    const colors = {
      scheduled: 'blue',
      confirmed: 'green',
      in_progress: 'yellow',
      completed: 'emerald',
      cancelled: 'red',
      no_show: 'orange',
      rescheduled: 'purple'
    }
    return colors[status] || 'gray'
  }

  // Helper: Get priority color
  getPriorityColor(priority?: string): string {
    const colors = {
      low: 'gray',
      normal: 'blue',
      high: 'yellow',
      urgent: 'red'
    }
    return colors[priority || 'normal'] || 'blue'
  }

  // Helper: Check if appointment can be cancelled
  canBeCancelled(appointment: AppointmentWithDetails): boolean {
    return !['completed', 'cancelled', 'no_show'].includes(appointment.status) &&
           this.isFutureAppointment(appointment.appointmentDate, appointment.startTime)
  }

  // Helper: Check if appointment can be rescheduled
  canBeRescheduled(appointment: AppointmentWithDetails): boolean {
    return !['completed', 'cancelled', 'no_show'].includes(appointment.status)
  }

  // Helper: Check if appointment can be completed
  canBeCompleted(appointment: AppointmentWithDetails): boolean {
    return ['scheduled', 'confirmed', 'in_progress'].includes(appointment.status)
  }

  // Validate appointment data
  validateAppointmentData(appointmentData: AppointmentCreateData | AppointmentUpdateData): string[] {
    const errors: string[] = []

    // Required fields for creation
    if ('patientId' in appointmentData && !appointmentData.patientId) {
      errors.push('Patient is required')
    }
    
    if ('doctorId' in appointmentData && !appointmentData.doctorId) {
      errors.push('Doctor is required')
    }
    
    if ('appointmentDate' in appointmentData && !appointmentData.appointmentDate?.trim()) {
      errors.push('Appointment date is required')
    }
    
    if ('startTime' in appointmentData && !appointmentData.startTime?.trim()) {
      errors.push('Start time is required')
    }
    
    if ('endTime' in appointmentData && !appointmentData.endTime?.trim()) {
      errors.push('End time is required')
    }
    
    if ('appointmentType' in appointmentData && !appointmentData.appointmentType?.trim()) {
      errors.push('Appointment type is required')
    }

    // Date validation
    if (appointmentData.appointmentDate) {
      const appointmentDate = new Date(appointmentData.appointmentDate)
      if (isNaN(appointmentDate.getTime())) {
        errors.push('Please enter a valid appointment date')
      }
    }

    // Time validation
    if (appointmentData.startTime && appointmentData.endTime) {
      const startTime = new Date(`2000-01-01T${appointmentData.startTime}`)
      const endTime = new Date(`2000-01-01T${appointmentData.endTime}`)
      
      if (endTime <= startTime) {
        errors.push('End time must be after start time')
      }
      
      const duration = (endTime.getTime() - startTime.getTime()) / (1000 * 60)
      if (duration < 15) {
        errors.push('Appointment must be at least 15 minutes long')
      }
      
      if (duration > 480) { // 8 hours
        errors.push('Appointment cannot exceed 8 hours')
      }
    }

    // Priority validation
    if (appointmentData.priority && 
        !['low', 'normal', 'high', 'urgent'].includes(appointmentData.priority)) {
      errors.push('Please select a valid priority level')
    }

    // Status validation
    if (appointmentData.status && 
        !['scheduled', 'confirmed', 'in_progress', 'completed', 'cancelled', 'no_show', 'rescheduled'].includes(appointmentData.status)) {
      errors.push('Please select a valid status')
    }

    // Follow-up validation
    if (appointmentData.followUpRequired && !appointmentData.followUpDate) {
      errors.push('Follow-up date is required when follow-up is marked as required')
    }

    if (appointmentData.followUpDate) {
      const followUpDate = new Date(appointmentData.followUpDate)
      if (isNaN(followUpDate.getTime())) {
        errors.push('Please enter a valid follow-up date')
      }
    }

    return errors
  }

  // Helper: Generate time slots for a day
  generateTimeSlots(startTime: string, endTime: string, duration: number = 30, breakStart?: string, breakEnd?: string): string[] {
    const slots: string[] = []
    const start = new Date(`2000-01-01T${startTime}:00`)
    const end = new Date(`2000-01-01T${endTime}:00`)
    
    let current = new Date(start)
    while (current < end) {
      const timeString = current.toTimeString().substring(0, 5)
      
      // Skip break time if specified
      if (breakStart && breakEnd) {
        const breakStartTime = new Date(`2000-01-01T${breakStart}:00`)
        const breakEndTime = new Date(`2000-01-01T${breakEnd}:00`)
        
        if (current >= breakStartTime && current < breakEndTime) {
          current.setMinutes(current.getMinutes() + duration)
          continue
        }
      }
      
      slots.push(timeString)
      current.setMinutes(current.getMinutes() + duration)
    }
    
    return slots
  }
}

// Create singleton instance
export const appointmentsService = new AppointmentsService()

// Export for easier importing
export default appointmentsService
