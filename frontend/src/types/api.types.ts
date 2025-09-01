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
  phone?: string
  isActive?: boolean
  createdAt: string
  updatedAt: string
}

// Simplified role system for admin/doctor only
export type UserRole = 'admin' | 'doctor'

export interface LoginCredentials {
  email: string
  password: string
}

export interface LoginResponse {
  user: User
  token: string
  refreshToken?: string
  expiresIn: number
}

export interface RefreshTokenResponse {
  token: string
  refreshToken?: string
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
  gender?: 'male' | 'female' | 'other' | 'prefer_not_to_say'
  address?: string
  emergencyContactName?: string
  emergencyContactPhone?: string
  bloodType?: string
  allergies?: string
  medicalNotes?: string
  insuranceProvider?: string
  insurancePolicyNumber?: string
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
  appointmentDate: string
  startTime: string
  endTime: string
  appointmentType: string
  priority: 'low' | 'normal' | 'high' | 'urgent'
  status: AppointmentStatus
  notes?: string
  diagnosis?: string
  treatmentNotes?: string
  followUpRequired: boolean
  followUpDate?: string
  createdAt: string
  updatedAt: string
  patient?: Patient
  doctor?: string // Doctor name for display
}

export type AppointmentStatus = 'scheduled' | 'confirmed' | 'completed' | 'cancelled' | 'no-show'

export interface AppointmentFormData {
  patientId: number
  doctorId: number
  scheduledAt: string
  reason?: string
  notes?: string
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
export interface RouteMeta extends Record<PropertyKey, unknown> {
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

// Medical Records Types
export interface MedicalHistoryEntry {
  id: number
  patientId: number
  date: string
  type: 'visit' | 'diagnosis' | 'treatment' | 'surgery' | 'emergency' | 'test'
  title: string
  description?: string
  diagnosis?: string
  treatment?: string
  medications?: string[]
  doctor?: string
  status?: 'active' | 'resolved'
  followUpDate?: string
  attachments?: {
    id: number
    name: string
    url: string
    size: string
  }[]
  createdAt: string
  updatedAt: string
}

export interface MedicalRecord {
  id: number
  patientId: number
  filename: string
  type: 'lab-result' | 'imaging' | 'prescription' | 'report' | 'referral' | 'insurance' | 'other'
  description?: string
  url: string
  size: number
  uploadedBy?: string
  tags?: string[]
  metadata?: Record<string, any>
  createdAt: string
  updatedAt: string
}


// Medical Alert Types  
export interface MedicalAlert {
  id: number
  patientId?: number
  type: 'patient_safety' | 'operational' | 'quality' | 'revenue' | 'inventory'
  priority: number // 1-5 scale
  title: string
  message: string
  actionRequired?: string
  timeline?: string
  source: 'ai' | 'system' | 'manual'
  status: 'active' | 'acknowledged' | 'dismissed' | 'resolved'
  isActive: boolean
  createdAt: string
  updatedAt: string
}

// Doctor Types (updated)
export interface Doctor {
  id: number
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
  createdAt: string
  updatedAt: string
}

// Enhanced AI Types for Frontend Components
export interface TriagePatient {
  id: number
  name: string
  age: number
  gender: string
  chiefComplaint: string
  priority: 'critical' | 'urgent' | 'standard' | 'low'
  urgencyScore: number // 1-5
  confidence: number // 0-1
  arrivalTime: string
  estimatedWait: number // minutes
  redFlags: string[]
  recommendedSpecialty?: string
  suggestedTests: string[]
  aiReasoning: string
}

export interface TriageFormData {
  patientId: string
  chiefComplaint: string
  symptoms: string
  vitalSigns: {
    bloodPressure: string
    heartRate: number | null
    temperature: number | null
    oxygenSaturation: number | null
  }
  medicalHistory: string
  painLevel: string
}

export interface AppointmentSummary {
  id: number
  type: 'soap' | 'patient' | 'custom'
  title: string
  preview: string
  content: string
  patientName: string
  createdAt: string
  wordCount: number
  rating: number
}

export interface SummaryGenerateForm {
  type: string
  patientId: string
  appointmentId: string
  language: string
  clinicalNotes: string
  instructions: string
  options: {
    includeDiagnosticCodes: boolean
    includeProcedureCodes: boolean
    includeFollowUp: boolean
  }
}

export interface AIAlertExtended {
  id: number
  type: 'drug_interaction' | 'vital_signs' | 'patient_safety' | 'operational' | 'system'
  severity: 'critical' | 'high' | 'medium' | 'low'
  status: 'active' | 'acknowledged' | 'resolved'
  title: string
  message: string
  source: string
  createdAt: string
  patient?: {
    name: string
    age: number
    gender: string
    room?: string
  }
  actionRequired?: string
  timeline?: string
  aiAnalysis?: string
  relatedData?: string[]
}

export interface SearchResult {
  id: number
  type: 'patient' | 'appointment' | 'doctor' | 'medical_record'
  title: string
  description: string
  date: string
  status?: string
}

export interface SearchFilters {
  dateFrom: string
  dateTo: string
  status: string[]
  ageMin: number | null
  ageMax: number | null
  gender: string[]
  insuranceProvider: string
  appointmentType: string
  priority: string[]
  doctorId: string
}


export interface TimeSlot {
  time: string
  available: boolean
  doctorId?: number
  duration?: number
}

export interface DailyBriefing {
  overview: string
  insights: string[]
  recommendations: string[]
  generatedAt: string
}

export interface PriorityTask {
  id: number
  title: string
  description: string
  category: 'medical' | 'safety' | 'planning' | 'analytics'
  priority: 'urgent' | 'high' | 'medium' | 'low'
  estimatedTime: string
  dueDate: string
  assignedTo?: string
  status?: 'pending' | 'in_progress' | 'completed'
}

export interface AIMetrics {
  triageProcessed: number
  summariesGenerated: number
  activeAlerts: number
  accuracy: number
}

// Enhanced types for better type safety
export type AppointmentPriority = 'low' | 'normal' | 'high' | 'urgent'

// Common Component Types
export interface NotificationData {
  id: string
  type: 'success' | 'error' | 'warning' | 'info'
  title: string
  message: string
  duration?: number
  actions?: NotificationAction[]
}

export interface NotificationAction {
  label: string
  action: () => void
  style?: 'primary' | 'secondary'
}

export interface SidebarItem {
  id: string
  label: string
  icon?: any
  href?: string
  onClick?: () => void
  active?: boolean
  badge?: string | number
  children?: SidebarItem[]
}

export interface SidebarSection {
  id: string
  title: string
  items: SidebarItem[]
  collapsible?: boolean
  collapsed?: boolean
}

// Dashboard Types
export interface MetricCardData {
  title: string
  value: string | number
  change?: {
    value: number
    type: 'increase' | 'decrease'
    period: string
  }
  icon?: any
  color?: 'blue' | 'green' | 'yellow' | 'red' | 'purple' | 'gray'
  trend?: number[]
}

export interface ActivityItemData {
  id: string
  type: 'appointment' | 'patient' | 'alert' | 'system'
  title: string
  description: string
  timestamp: string
  user?: {
    name: string
    avatar?: string
  }
  metadata?: Record<string, any>
}

export interface QuickAction {
  id: string
  label: string
  description: string
  icon: any
  color: 'blue' | 'green' | 'yellow' | 'red' | 'purple'
  onClick: () => void
  disabled?: boolean
}

// Enhanced Patient Type
export interface PatientWithRelations extends Patient {
  upcomingAppointments?: Appointment[]
  recentAppointments?: Appointment[]
  alerts?: AIAlertExtended[]
  lastVisit?: string
  nextAppointment?: string
}

// Enhanced Appointment Type 
export interface AppointmentWithRelations extends Omit<Appointment, 'doctor'> {
  patient?: Patient
  doctor?: Doctor
  conflicts?: string[]
  canReschedule?: boolean
  canCancel?: boolean
}

export interface AIAnalytics {
  totalRequests: number
  usagePercentage: number
  avgResponseTime: number
  accuracyScore: number
}
