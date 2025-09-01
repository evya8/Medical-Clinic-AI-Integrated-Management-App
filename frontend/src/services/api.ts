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
  // Authentication (3 endpoints)
  AUTH: {
    LOGIN: '/auth/login',
    LOGOUT: '/auth/logout',
    ME: '/auth/me',
    REFRESH: '/auth/refresh',
  },
  
  // User Management (6 endpoints)
  USERS: {
    LIST: '/users',
    CREATE: '/users',
    GET: (id: number) => `/users/${id}`,
    UPDATE: (id: number) => `/users/${id}`,
    DELETE: (id: number) => `/users/${id}`,
    ACTIVATE: (id: number) => `/users/activate/${id}`,
  },

  // Patients (5 endpoints)
  PATIENTS: {
    LIST: '/patients',
    CREATE: '/patients',
    GET: (id: number) => `/patients/${id}`,
    UPDATE: (id: number) => `/patients/${id}`,
    DELETE: (id: number) => `/patients/${id}`,
    SEARCH: '/patients/search',
  },

  // Appointments (6 endpoints)
  APPOINTMENTS: {
    LIST: '/appointments',
    CREATE: '/appointments',
    GET: (id: number) => `/appointments/${id}`,
    UPDATE: (id: number) => `/appointments/${id}`,
    DELETE: (id: number) => `/appointments/${id}`,
    AVAILABLE_SLOTS: '/appointments/available-slots',
    BY_DATE: '/appointments/by-date',
    BY_PATIENT: (patientId: number) => `/appointments/patient/${patientId}`,
    BY_DOCTOR: (doctorId: number) => `/appointments/doctor/${doctorId}`,
  },

  // Doctors (4 endpoints)
  DOCTORS: {
    LIST: '/doctors',
    CREATE: '/doctors',
    GET: (id: number) => `/doctors/${id}`,
    UPDATE: (id: number) => `/doctors/${id}`,
    DELETE: (id: number) => `/doctors/${id}`,
    AVAILABILITY: (id: number) => `/doctors/${id}/availability`,
  },

  // AI Features (33 endpoints total)
  AI: {
    // AI Dashboard (8 endpoints)
    DASHBOARD: {
      BRIEFING: '/ai-dashboard/briefing',
      STATUS: '/ai-dashboard/status', 
      TASKS: '/ai-dashboard/tasks',
      METRICS: '/ai-dashboard/metrics',
      SUMMARY: '/ai-dashboard/summary',
      TEST_AI: '/ai-dashboard/test-ai',
      ANALYZE: '/ai-dashboard/analyze',
      REFRESH: '/ai-dashboard/refresh',
    },

    // AI Triage (7 endpoints)
    TRIAGE: {
      STATS: '/ai-triage/stats',
      ANALYZE: '/ai-triage/analyze',
      BATCH_ANALYZE: '/ai-triage/batch-analyze',
      SYMPTOM_TRIAGE: '/ai-triage/symptom-triage',
      REFERRAL_RECOMMENDATIONS: '/ai-triage/referral-recommendations',
      QUICK_ASSESSMENT: '/ai-triage/quick-assessment',
      UPDATE_PRIORITY: '/ai-triage/update-priority',
    },

    // AI Summaries (8 endpoints)
    SUMMARIES: {
      STATS: '/ai-summaries/stats',
      GET: (id: number) => `/ai-summaries/${id}`,
      GENERATE: '/ai-summaries/generate',
      SOAP: '/ai-summaries/soap',
      BILLING: '/ai-summaries/billing',
      PATIENT: '/ai-summaries/patient',
      BATCH: '/ai-summaries/batch',
      UPDATE: '/ai-summaries/update',
    },

    // AI Alerts (10 endpoints)
    ALERTS: {
      DASHBOARD: '/ai-alerts/dashboard',
      ACTIVE: '/ai-alerts/active',
      PATIENT: '/ai-alerts/patient',
      ANALYTICS: '/ai-alerts/analytics',
      GENERATE: '/ai-alerts/generate',
      SAFETY: '/ai-alerts/safety',
      OPERATIONAL: '/ai-alerts/operational',
      QUALITY: '/ai-alerts/quality',
      ACKNOWLEDGE: '/ai-alerts/acknowledge',
      UPDATE: '/ai-alerts/update',
    },
  },

  // Dashboard (3 endpoints)
  DASHBOARD: {
    METRICS: '/dashboard/metrics',
    STATS: '/dashboard/stats',
    RECENT_ACTIVITY: '/dashboard/recent-activity',
  },

  // Background Services (2 endpoints)
  REMINDERS: {
    PROCESS: '/reminders/process',
  },
  
  HEALTH: '/health',
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
