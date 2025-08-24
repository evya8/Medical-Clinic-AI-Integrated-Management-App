import axios, { AxiosInstance, AxiosResponse } from 'axios'
import type { APIResponse } from '@/types/api.types'

// Create axios instance with base configuration
const apiClient: AxiosInstance = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api',
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
})

// Request interceptor to add auth token
apiClient.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('auth_token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Response interceptor to handle common response patterns
apiClient.interceptors.response.use(
  (response: AxiosResponse) => {
    return response
  },
  (error) => {
    // Handle common HTTP errors
    if (error.response?.status === 401) {
      // Unauthorized - clear token and redirect to login
      localStorage.removeItem('auth_token')
      localStorage.removeItem('user')
      window.location.href = '/login'
    } else if (error.response?.status === 403) {
      // Forbidden - show access denied message
      console.error('Access denied:', error.response.data?.message)
    } else if (error.response?.status >= 500) {
      // Server errors
      console.error('Server error:', error.response.data?.message || 'Internal server error')
    }
    
    return Promise.reject(error)
  }
)

// Generic API methods
export const api = {
  // GET request
  async get<T>(url: string, params?: Record<string, any>): Promise<APIResponse<T>> {
    const response = await apiClient.get(url, { params })
    return response.data
  },

  // POST request
  async post<T>(url: string, data?: any): Promise<APIResponse<T>> {
    const response = await apiClient.post(url, data)
    return response.data
  },

  // PUT request
  async put<T>(url: string, data?: any): Promise<APIResponse<T>> {
    const response = await apiClient.put(url, data)
    return response.data
  },

  // PATCH request
  async patch<T>(url: string, data?: any): Promise<APIResponse<T>> {
    const response = await apiClient.patch(url, data)
    return response.data
  },

  // DELETE request
  async delete<T>(url: string): Promise<APIResponse<T>> {
    const response = await apiClient.delete(url)
    return response.data
  },

  // Upload file
  async upload<T>(url: string, file: File, onUploadProgress?: (progress: number) => void): Promise<APIResponse<T>> {
    const formData = new FormData()
    formData.append('file', file)

    const response = await apiClient.post(url, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
      onUploadProgress: (progressEvent) => {
        if (onUploadProgress && progressEvent.total) {
          const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total)
          onUploadProgress(percentCompleted)
        }
      },
    })

    return response.data
  },
}

// Export axios instance for advanced usage
export { apiClient }

// API endpoint constants
export const API_ENDPOINTS = {
  // Authentication
  AUTH: {
    LOGIN: '/auth/login',
    LOGOUT: '/auth/logout',
    ME: '/auth/me',
    REFRESH: '/auth/refresh',
  },
  
  // Users
  USERS: {
    LIST: '/users',
    CREATE: '/users',
    GET: (id: number) => `/users/${id}`,
    UPDATE: (id: number) => `/users/${id}`,
    DELETE: (id: number) => `/users/${id}`,
  },

  // Patients
  PATIENTS: {
    LIST: '/patients',
    CREATE: '/patients',
    GET: (id: number) => `/patients/${id}`,
    UPDATE: (id: number) => `/patients/${id}`,
    DELETE: (id: number) => `/patients/${id}`,
    SEARCH: '/patients/search',
  },

  // Appointments
  APPOINTMENTS: {
    LIST: '/appointments',
    CREATE: '/appointments',
    GET: (id: number) => `/appointments/${id}`,
    UPDATE: (id: number) => `/appointments/${id}`,
    DELETE: (id: number) => `/appointments/${id}`,
    BY_DATE: '/appointments/by-date',
    BY_PATIENT: (patientId: number) => `/appointments/patient/${patientId}`,
    BY_DOCTOR: (doctorId: number) => `/appointments/doctor/${doctorId}`,
  },

  // Doctors
  DOCTORS: {
    LIST: '/doctors',
    CREATE: '/doctors',
    GET: (id: number) => `/doctors/${id}`,
    UPDATE: (id: number) => `/doctors/${id}`,
    DELETE: (id: number) => `/doctors/${id}`,
    AVAILABILITY: (id: number) => `/doctors/${id}/availability`,
  },

  // AI Features
  AI: {
    TRIAGE: '/ai/triage',
    SUMMARY: '/ai/appointment-summary',
    ALERTS: '/ai/alerts',
    DASHBOARD: '/ai/dashboard',
    BULK_ALERTS: '/ai/alerts/bulk',
  },

  // Dashboard
  DASHBOARD: {
    METRICS: '/dashboard/metrics',
    STATS: '/dashboard/stats',
    RECENT_ACTIVITY: '/dashboard/recent-activity',
  },
} as const

// Error handler utility
export const handleApiError = (error: any): string => {
  if (error.response?.data?.message) {
    return error.response.data.message
  } else if (error.response?.data?.errors) {
    // Handle validation errors
    const errors = error.response.data.errors
    const firstError = Object.values(errors)[0] as string[]
    return firstError?.[0] || 'Validation error occurred'
  } else if (error.message) {
    return error.message
  } else {
    return 'An unexpected error occurred'
  }
}

// Request timeout utility
export const withTimeout = <T>(promise: Promise<T>, timeoutMs = 5000): Promise<T> => {
  const timeout = new Promise<never>((_, reject) => {
    setTimeout(() => reject(new Error(`Request timeout after ${timeoutMs}ms`)), timeoutMs)
  })

  return Promise.race([promise, timeout])
}
