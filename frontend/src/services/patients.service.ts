import { api, API_ENDPOINTS, handleApiError } from './api'
import type { Patient } from '@/types/api.types'

// Patient service interfaces
export interface PatientCreateData {
  firstName: string
  lastName: string
  email?: string
  phone?: string
  dateOfBirth: string
  gender?: 'male' | 'female' | 'other' | 'prefer_not_to_say'
  address?: string
  emergencyContactName?: string
  emergencyContactPhone?: string
  medicalNotes?: string
  allergies?: string
}

export interface PatientUpdateData extends Partial<PatientCreateData> {}

export interface PatientListResponse {
  data: Patient[]
  total: number
  page?: number
  limit?: number
}

export interface PatientSearchResult {
  patients: Patient[]
  total: number
  searchTerm: string
}

export class PatientsService {
  // Get all patients
  async getPatients(): Promise<Patient[]> {
    try {
      const response = await api.get<PatientListResponse>(API_ENDPOINTS.PATIENTS.LIST)
      
      if (response.success && response.data) {
        return Array.isArray(response.data) ? response.data : response.data.data
      } else {
        throw new Error(response.message || 'Failed to fetch patients')
      }
    } catch (error: any) {
      throw new Error(handleApiError(error))
    }
  }

  // Get patient by ID
  async getPatientById(id: number): Promise<Patient> {
    try {
      const response = await api.get<Patient>(API_ENDPOINTS.PATIENTS.GET(id))
      
      if (response.success && response.data) {
        return response.data
      } else {
        throw new Error(response.message || 'Failed to fetch patient')
      }
    } catch (error: any) {
      throw new Error(handleApiError(error))
    }
  }

  // Create new patient
  async createPatient(patientData: PatientCreateData): Promise<Patient> {
    try {
      // Validate data before sending
      const validationErrors = this.validatePatientData(patientData)
      if (validationErrors.length > 0) {
        throw new Error(validationErrors.join(', '))
      }

      const response = await api.post<Patient>(API_ENDPOINTS.PATIENTS.CREATE, patientData)
      
      if (response.success && response.data) {
        return response.data
      } else {
        throw new Error(response.message || 'Failed to create patient')
      }
    } catch (error: any) {
      throw new Error(handleApiError(error))
    }
  }

  // Update existing patient
  async updatePatient(id: number, patientData: PatientUpdateData): Promise<Patient> {
    try {
      // Validate data before sending
      const validationErrors = this.validatePatientData(patientData)
      if (validationErrors.length > 0) {
        throw new Error(validationErrors.join(', '))
      }

      const response = await api.put<Patient>(API_ENDPOINTS.PATIENTS.UPDATE(id), patientData)
      
      if (response.success && response.data) {
        return response.data
      } else {
        throw new Error(response.message || 'Failed to update patient')
      }
    } catch (error: any) {
      throw new Error(handleApiError(error))
    }
  }

  // Delete patient (soft delete)
  async deletePatient(id: number): Promise<boolean> {
    try {
      const response = await api.delete(API_ENDPOINTS.PATIENTS.DELETE(id))
      
      if (response.success) {
        return true
      } else {
        throw new Error(response.message || 'Failed to delete patient')
      }
    } catch (error: any) {
      throw new Error(handleApiError(error))
    }
  }

  // Search patients
  async searchPatients(searchTerm: string): Promise<Patient[]> {
    try {
      if (!searchTerm.trim()) {
        return []
      }

      const response = await api.get<PatientListResponse>(API_ENDPOINTS.PATIENTS.SEARCH, {
        q: searchTerm,
        limit: 50 // Reasonable limit for search results
      })
      
      if (response.success && response.data) {
        return Array.isArray(response.data) ? response.data : response.data.data
      } else {
        throw new Error(response.message || 'Search failed')
      }
    } catch (error: any) {
      throw new Error(handleApiError(error))
    }
  }

  // Get patients with advanced filtering
  async getPatientsWithFilters(filters: {
    search?: string
    gender?: string
    ageMin?: number
    ageMax?: number
    hasAllergies?: boolean
    sortBy?: string
    sortDirection?: 'asc' | 'desc'
    page?: number
    limit?: number
  }): Promise<PatientListResponse> {
    try {
      const response = await api.get<PatientListResponse>(API_ENDPOINTS.PATIENTS.LIST, filters)
      
      if (response.success && response.data) {
        return response.data
      } else {
        throw new Error(response.message || 'Failed to fetch filtered patients')
      }
    } catch (error: any) {
      throw new Error(handleApiError(error))
    }
  }

  // Get patient statistics
  async getPatientStats(): Promise<{
    total: number
    byGender: Record<string, number>
    averageAge: number
    withAllergies: number
    recentRegistrations: number
  }> {
    try {
      // This would ideally be a dedicated API endpoint, but we'll calculate from all patients
      const patients = await this.getPatients()
      
      const total = patients.length
      const byGender = patients.reduce((acc, patient) => {
        const gender = patient.gender || 'unknown'
        acc[gender] = (acc[gender] || 0) + 1
        return acc
      }, {} as Record<string, number>)
      
      const totalAge = patients.reduce((sum, patient) => {
        const age = this.calculateAge(patient.dateOfBirth)
        return sum + age
      }, 0)
      const averageAge = total > 0 ? Math.round(totalAge / total) : 0
      
      const withAllergies = patients.filter(p => p.allergies && p.allergies.trim().length > 0).length
      
      // Recent registrations (last 30 days)
      const thirtyDaysAgo = new Date()
      thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30)
      const recentRegistrations = patients.filter(p => {
        const createdAt = new Date(p.createdAt || '')
        return createdAt >= thirtyDaysAgo
      }).length

      return {
        total,
        byGender,
        averageAge,
        withAllergies,
        recentRegistrations
      }
    } catch (error: any) {
      throw new Error(handleApiError(error))
    }
  }

  // Helper: Calculate age from date of birth
  private calculateAge(dateOfBirth: string): number {
    const today = new Date()
    const birthDate = new Date(dateOfBirth)
    let age = today.getFullYear() - birthDate.getFullYear()
    const monthDiff = today.getMonth() - birthDate.getMonth()
    
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
      age--
    }
    
    return age
  }

  // Helper: Get patient full name
  getPatientFullName(patient: Patient): string {
    return `${patient.firstName} ${patient.lastName}`.trim()
  }

  // Helper: Get patient age
  getPatientAge(patient: Patient): number {
    return this.calculateAge(patient.dateOfBirth)
  }

  // Helper: Format patient contact info
  formatPatientContact(patient: Patient): string {
    const contact = []
    if (patient.phone) contact.push(patient.phone)
    if (patient.email) contact.push(patient.email)
    return contact.join(' • ')
  }

  // Validate patient data
  validatePatientData(patientData: PatientCreateData | PatientUpdateData): string[] {
    const errors: string[] = []

    // Required fields for creation
    if ('firstName' in patientData && !patientData.firstName?.trim()) {
      errors.push('First name is required')
    }
    
    if ('lastName' in patientData && !patientData.lastName?.trim()) {
      errors.push('Last name is required')
    }
    
    if ('dateOfBirth' in patientData && !patientData.dateOfBirth?.trim()) {
      errors.push('Date of birth is required')
    }

    // Email validation
    if (patientData.email && patientData.email.trim() && 
        !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(patientData.email)) {
      errors.push('Please enter a valid email address')
    }

    // Phone validation (basic)
    if (patientData.phone && patientData.phone.trim() && 
        !/^[\+]?[1-9][\d]{0,15}$/.test(patientData.phone.replace(/\D/g, ''))) {
      errors.push('Please enter a valid phone number')
    }

    // Date of birth validation
    if (patientData.dateOfBirth) {
      const birthDate = new Date(patientData.dateOfBirth)
      const today = new Date()
      
      if (isNaN(birthDate.getTime())) {
        errors.push('Please enter a valid date of birth')
      } else if (birthDate > today) {
        errors.push('Date of birth cannot be in the future')
      } else if (this.calculateAge(patientData.dateOfBirth) > 120) {
        errors.push('Please verify the date of birth')
      }
    }

    // Gender validation
    if (patientData.gender && 
        !['male', 'female', 'other', 'prefer_not_to_say'].includes(patientData.gender)) {
      errors.push('Please select a valid gender option')
    }

    // Emergency contact validation
    if (patientData.emergencyContactName && !patientData.emergencyContactPhone) {
      errors.push('Emergency contact phone is required when emergency contact name is provided')
    }
    
    if (patientData.emergencyContactPhone && !patientData.emergencyContactName) {
      errors.push('Emergency contact name is required when emergency contact phone is provided')
    }

    return errors
  }

  // Helper: Check if patient has complete contact info
  hasCompleteContact(patient: Patient): boolean {
    return !!(patient.phone || patient.email)
  }

  // Helper: Check if patient has emergency contact
  hasEmergencyContact(patient: Patient): boolean {
    return !!(patient.emergencyContactName && patient.emergencyContactPhone)
  }

  // Helper: Check if patient has medical info
  hasMedicalInfo(patient: Patient): boolean {
    return !!(patient.allergies || patient.medicalNotes)
  }
}

// Create singleton instance
export const patientsService = new PatientsService()

// Export for easier importing
export default patientsService
