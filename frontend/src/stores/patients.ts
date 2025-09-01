import { defineStore } from 'pinia'
import { ref, computed, readonly } from 'vue'
import type { Patient, PatientFormData } from '@/types/api.types'
import { patientsService, type PatientCreateData, type PatientUpdateData } from '@/services/patients.service'
import { useNotifications } from './notifications'

// PatientCreateData and PatientUpdateData are now imported from patients.service.ts

export interface PatientSearchFilters {
  search?: string
  gender?: string
  ageMin?: number | null
  ageMax?: number | null
  hasAllergies?: boolean | null
  sortBy?: 'firstName' | 'lastName' | 'dateOfBirth' | 'createdAt'
  sortDirection?: 'asc' | 'desc'
}

export interface PatientStats {
  total: number
  byGender: {
    male: number
    female: number
    other: number
  }
  averageAge: number
  withAllergies: number
}

export const usePatientsStore = defineStore('patients', () => {
  // State
  const patients = ref<Patient[]>([])
  const currentPatient = ref<Patient | null>(null)
  const isLoading = ref(false)
  const isCreating = ref(false)
  const isUpdating = ref(false)
  const isDeleting = ref(false)
  const error = ref<string | null>(null)
  const searchFilters = ref<PatientSearchFilters>({
    search: '',
    gender: '',
    ageMin: null,
    ageMax: null,
    hasAllergies: null,
    sortBy: 'lastName',
    sortDirection: 'asc'
  })

  // Get notifications store
  const { addNotification } = useNotifications()

  // Helper function to calculate age (delegate to service)
  const calculateAge = (dateOfBirth: string): number => {
    return patientsService.getPatientAge({ dateOfBirth } as Patient)
  }

  // Getters
  const filteredPatients = computed(() => {
    let filtered = [...patients.value]

    // Filter by search term
    if (searchFilters.value.search) {
      const searchTerm = searchFilters.value.search.toLowerCase()
      filtered = filtered.filter(patient => 
        patient.firstName.toLowerCase().includes(searchTerm) ||
        patient.lastName.toLowerCase().includes(searchTerm) ||
        patient.email?.toLowerCase().includes(searchTerm) ||
        patient.phone?.includes(searchTerm) ||
        patient.medicalNotes?.toLowerCase().includes(searchTerm)
      )
    }

    // Filter by gender
    if (searchFilters.value.gender && searchFilters.value.gender !== '') {
      filtered = filtered.filter(patient => patient.gender === searchFilters.value.gender)
    }

    // Filter by age range
    if (searchFilters.value.ageMin !== null || searchFilters.value.ageMax !== null) {
      filtered = filtered.filter(patient => {
        const age = calculateAge(patient.dateOfBirth)
        const minAge = searchFilters.value.ageMin
        const maxAge = searchFilters.value.ageMax
        
        if (minAge !== null && age < minAge) return false
        if (maxAge !== null && age > maxAge) return false
        
        return true
      })
    }

    // Filter by allergies
    if (searchFilters.value.hasAllergies !== null) {
      filtered = filtered.filter(patient => {
        const hasAllergies = !!(patient.allergies && patient.allergies.trim().length > 0)
        return hasAllergies === searchFilters.value.hasAllergies
      })
    }

    // Sort
    const sortBy = searchFilters.value.sortBy || 'lastName'
    const sortDirection = searchFilters.value.sortDirection || 'asc'
    
    filtered.sort((a, b) => {
      let valueA: any = a[sortBy as keyof Patient]
      let valueB: any = b[sortBy as keyof Patient]
      
      // Handle special cases
      if (sortBy === 'dateOfBirth') {
        valueA = new Date(valueA)
        valueB = new Date(valueB)
      }
      
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

  const patientStats = computed((): PatientStats => {
    const total = patients.value.length
    const byGender = {
      male: patients.value.filter(p => p.gender === 'male').length,
      female: patients.value.filter(p => p.gender === 'female').length,
      other: patients.value.filter(p => p.gender === 'other').length
    }
    
    const totalAge = patients.value.reduce((sum, patient) => {
      return sum + calculateAge(patient.dateOfBirth)
    }, 0)
    const averageAge = total > 0 ? Math.round(totalAge / total) : 0
    
    const withAllergies = patients.value.filter(p => p.allergies && p.allergies.trim().length > 0).length

    return {
      total,
      byGender,
      averageAge,
      withAllergies
    }
  })

  const totalPatients = computed(() => patients.value.length)

  // Actions
  const fetchPatients = async (): Promise<void> => {
    isLoading.value = true
    error.value = null

    try {
      const fetchedPatients = await patientsService.getPatients()
      patients.value = fetchedPatients
    } catch (err: any) {
      error.value = err.message || 'Failed to fetch patients'
      addNotification({
        type: 'error',
        title: 'Error Loading Patients',
        message: error.value
      })
    } finally {
      isLoading.value = false
    }
  }

  const fetchPatientById = async (id: number): Promise<Patient | null> => {
    isLoading.value = true
    error.value = null

    try {
      const patient = await patientsService.getPatientById(id)
      
      // Update current patient for details view
      currentPatient.value = patient
      
      // Update in patients list if exists
      const existingIndex = patients.value.findIndex(p => p.id === id)
      if (existingIndex !== -1) {
        patients.value[existingIndex] = patient
      }

      return patient
    } catch (err: any) {
      error.value = err.message || 'Failed to fetch patient'
      addNotification({
        type: 'error',
        title: 'Error Loading Patient',
        message: error.value
      })
      return null
    } finally {
      isLoading.value = false
    }
  }

  const createPatient = async (patientData: PatientCreateData): Promise<Patient | null> => {
    isCreating.value = true
    error.value = null

    try {
      const newPatient = await patientsService.createPatient(patientData)

      // Add to patients list
      patients.value.unshift(newPatient)

      addNotification({
        type: 'success',
        title: 'Patient Created',
        message: `${newPatient.firstName} ${newPatient.lastName} has been added successfully`
      })

      return newPatient
    } catch (err: any) {
      error.value = err.message || 'Failed to create patient'
      addNotification({
        type: 'error',
        title: 'Error Creating Patient',
        message: error.value
      })
      return null
    } finally {
      isCreating.value = false
    }
  }

  const updatePatient = async (id: number, patientData: PatientUpdateData): Promise<Patient | null> => {
    isUpdating.value = true
    error.value = null

    try {
      const updatedPatient = await patientsService.updatePatient(id, patientData)

      // Update in patients list
      const existingIndex = patients.value.findIndex(p => p.id === id)
      if (existingIndex !== -1) {
        patients.value[existingIndex] = updatedPatient
      }

      // Update current patient if it's the same
      if (currentPatient.value && currentPatient.value.id === id) {
        currentPatient.value = updatedPatient
      }

      addNotification({
        type: 'success',
        title: 'Patient Updated',
        message: `${updatedPatient.firstName} ${updatedPatient.lastName} has been updated successfully`
      })

      return updatedPatient
    } catch (err: any) {
      error.value = err.message || 'Failed to update patient'
      addNotification({
        type: 'error',
        title: 'Error Updating Patient',
        message: error.value
      })
      return null
    } finally {
      isUpdating.value = false
    }
  }

  const deletePatient = async (id: number): Promise<boolean> => {
    isDeleting.value = true
    error.value = null

    try {
      const success = await patientsService.deletePatient(id)

      if (success) {
        // Remove from patients list
        const existingIndex = patients.value.findIndex(p => p.id === id)
        if (existingIndex !== -1) {
          patients.value.splice(existingIndex, 1)
        }

        // Clear current patient if it's the same
        if (currentPatient.value && currentPatient.value.id === id) {
          currentPatient.value = null
        }

        addNotification({
          type: 'info',
          title: 'Patient Deleted',
          message: 'Patient has been deleted successfully'
        })
      }

      return success
    } catch (err: any) {
      error.value = err.message || 'Failed to delete patient'
      addNotification({
        type: 'error',
        title: 'Error Deleting Patient',
        message: error.value
      })
      return false
    } finally {
      isDeleting.value = false
    }
  }

  const searchPatients = async (searchTerm: string): Promise<Patient[]> => {
    if (!searchTerm.trim()) {
      return patients.value
    }

    try {
      return await patientsService.searchPatients(searchTerm)
    } catch (err: any) {
      console.error('Search error:', err)
      // Fallback to client-side search
      const searchTermLower = searchTerm.toLowerCase()
      return patients.value.filter(patient => 
        patient.firstName.toLowerCase().includes(searchTermLower) ||
        patient.lastName.toLowerCase().includes(searchTermLower) ||
        patient.email?.toLowerCase().includes(searchTermLower) ||
        patient.phone?.includes(searchTerm)
      )
    }
  }

  const setSearchFilters = (newFilters: Partial<PatientSearchFilters>): void => {
    searchFilters.value = { ...searchFilters.value, ...newFilters }
  }

  const clearSearchFilters = (): void => {
    searchFilters.value = {
      search: '',
      gender: '',
      ageMin: null,
      ageMax: null,
      hasAllergies: null,
      sortBy: 'lastName',
      sortDirection: 'asc'
    }
  }

  const clearError = (): void => {
    error.value = null
  }

  const clearCurrentPatient = (): void => {
    currentPatient.value = null
  }

  // Helper to get patient age
  const getPatientAge = (patient: Patient): number => {
    return patientsService.getPatientAge(patient)
  }

  // Helper to get patient full name
  const getPatientFullName = (patient: Patient): string => {
    return patientsService.getPatientFullName(patient)
  }

  return {
    // State
    patients: readonly(patients),
    currentPatient: readonly(currentPatient),
    isLoading: readonly(isLoading),
    isCreating: readonly(isCreating),
    isUpdating: readonly(isUpdating),
    isDeleting: readonly(isDeleting),
    error: readonly(error),
    searchFilters: readonly(searchFilters),

    // Getters
    filteredPatients,
    patientStats,
    totalPatients,

    // Actions
    fetchPatients,
    fetchPatientById,
    createPatient,
    updatePatient,
    deletePatient,
    searchPatients,
    setSearchFilters,
    clearSearchFilters,
    clearError,
    clearCurrentPatient,
    
    // Helpers
    getPatientAge,
    getPatientFullName,
  }
})

// Export for easier importing
export default usePatientsStore
