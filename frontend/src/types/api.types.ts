// API Response Types
export interface APIResponse<T = any> {
  success: boolean
  data: T
  message?: string
  errors?: Record<string, string[]>
}

// User & Authentication Types
export interface User {
  id: number
  username: string
  email: string
  role: UserRole
  firstName?: string
  lastName?: string
  createdAt: string
  updatedAt: string
}

export type UserRole = 'admin' | 'doctor' | 'staff' | 'receptionist'

export interface LoginCredentials {
  email: string
  password: string
}

export interface LoginResponse {
  user: User
  token: string
  expiresIn: number
}

// Patient Types
export interface Patient {
  id: number
  firstName: string
  lastName: string
  email?: string
  phone?: string
  dateOfBirth: string
  gender?: 'male' | 'female' | 'other'
  address?: string
  emergencyContact?: string
  allergies?: string
  medications?: string
  medicalHistory?: string
  createdAt: string
  updatedAt: string
}

export interface PatientFormData {
  firstName: string
  lastName: string
  email?: string
  phone?: string
  dateOfBirth: string
  gender?: 'male' | 'female' | 'other'
  address?: string
  emergencyContact?: string
  allergies?: string
  medications?: string
  medicalHistory?: string
}

// Appointment Types
export interface Appointment {
  id: number
  patientId: number
  doctorId: number
  scheduledAt: string
  status: AppointmentStatus
  reason?: string
  notes?: string
  createdAt: string
  updatedAt: string
  patient?: Patient
  doctor?: Doctor
}

export type AppointmentStatus = 'scheduled' | 'completed' | 'cancelled' | 'no-show'

export interface AppointmentFormData {
  patientId: number
  doctorId: number
  scheduledAt: string
  reason?: string
  notes?: string
}

// Doctor Types
export interface Doctor {
  id: number
  userId: number
  specialization?: string
  licenseNumber?: string
  phone?: string
  user?: User
  createdAt: string
  updatedAt: string
}

// AI Feature Types
export interface AITriageResult {
  urgencyScore: number // 1-5 scale
  priority: 'low' | 'normal' | 'high' | 'urgent'
  recommendations: string[]
  redFlags?: string[]
  confidence: number // 0-1 confidence score
  reasoning?: string
}

export interface AIAppointmentSummary {
  id: number
  appointmentId: number
  summaryType: 'soap' | 'billing' | 'patient_friendly'
  content: string
  confidence: number
  createdAt: string
}

export interface AIAlert {
  id: number
  patientId?: number
  doctorId?: number
  type: AIAlertType
  severity: 'low' | 'medium' | 'high' | 'critical'
  title: string
  message: string
  data?: Record<string, any>
  isRead: boolean
  isResolved: boolean
  createdAt: string
  updatedAt: string
}

export type AIAlertType = 'drug_interaction' | 'vital_signs' | 'appointment_delay' | 'system_performance' | 'other'

// Form and UI Types
export interface FormFieldError {
  field: string
  message: string
}

export interface TableColumn {
  key: string
  label: string
  sortable?: boolean
  type?: 'text' | 'date' | 'number' | 'boolean' | 'status' | 'priority'
  format?: string
}

export interface TableSort {
  column: string
  direction: 'asc' | 'desc'
}

export interface PaginationData {
  currentPage: number
  totalPages: number
  totalItems: number
  itemsPerPage: number
}

// Notification Types
export interface Notification {
  id: string
  type: 'success' | 'error' | 'warning' | 'info'
  title: string
  message?: string
  duration?: number
  persistent?: boolean
}

// Route Meta Types
export interface RouteMeta {
  requiresAuth?: boolean
  roles?: UserRole[]
  title?: string
  icon?: string
  hideInNav?: boolean
}

// AI Processing States
export interface AIProcessingState {
  isProcessing: boolean
  currentStep?: string
  progress?: number
  message?: string
  error?: string
}

// Dashboard Data Types
export interface DashboardMetrics {
  totalPatients: number
  todayAppointments: number
  pendingAlerts: number
  systemStatus: 'healthy' | 'warning' | 'error'
}

export interface ClinicStats {
  patientSatisfaction: number
  averageWaitTime: number
  appointmentCompletionRate: number
  aiRecommendationAccuracy: number
}
