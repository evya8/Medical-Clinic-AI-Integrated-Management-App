import { api, API_ENDPOINTS, handleApiError } from './api'
import type { User } from '@/types/api.types'

// User service interfaces
export interface UserCreateData {
  username: string
  email: string
  password: string
  role: 'admin' | 'doctor'
  firstName: string
  lastName: string
  phone?: string
  // Doctor-specific fields (when role is 'doctor')
  specialty?: string
  licenseNumber?: string
  workingDays?: string[]
  workingHours?: {
    start: string
    end: string
  }
}

export interface UserUpdateData {
  username?: string
  email?: string
  password?: string
  role?: 'admin' | 'doctor'
  firstName?: string
  lastName?: string
  phone?: string
  isActive?: boolean
  // Doctor-specific fields
  specialty?: string
  licenseNumber?: string
  workingDays?: string[]
  workingHours?: {
    start: string
    end: string
  }
}

export interface UserListResponse {
  data: User[]
  total: number
  page?: number
  limit?: number
}

export class UsersService {
  // Get all users (admin only)
  async getUsers(): Promise<User[]> {
    try {
      const response = await api.get<UserListResponse>(API_ENDPOINTS.USERS.LIST)
      
      if (response.success && response.data) {
        return Array.isArray(response.data) ? response.data : response.data.data
      } else {
        throw new Error(response.message || 'Failed to fetch users')
      }
    } catch (error: any) {
      throw new Error(handleApiError(error))
    }
  }

  // Get user by ID (admin only)
  async getUserById(id: number): Promise<User> {
    try {
      const response = await api.get<User>(API_ENDPOINTS.USERS.GET(id))
      
      if (response.success && response.data) {
        return response.data
      } else {
        throw new Error(response.message || 'Failed to fetch user')
      }
    } catch (error: any) {
      throw new Error(handleApiError(error))
    }
  }

  // Create new user (admin only)
  async createUser(userData: UserCreateData): Promise<User> {
    try {
      const response = await api.post<User>(API_ENDPOINTS.USERS.CREATE, userData)
      
      if (response.success && response.data) {
        return response.data
      } else {
        throw new Error(response.message || 'Failed to create user')
      }
    } catch (error: any) {
      throw new Error(handleApiError(error))
    }
  }

  // Update existing user (admin only)
  async updateUser(id: number, userData: UserUpdateData): Promise<User> {
    try {
      const response = await api.put<User>(API_ENDPOINTS.USERS.UPDATE(id), userData)
      
      if (response.success && response.data) {
        return response.data
      } else {
        throw new Error(response.message || 'Failed to update user')
      }
    } catch (error: any) {
      throw new Error(handleApiError(error))
    }
  }

  // Deactivate user (admin only) - soft delete
  async deactivateUser(id: number): Promise<boolean> {
    try {
      const response = await api.delete(API_ENDPOINTS.USERS.DELETE(id))
      
      if (response.success) {
        return true
      } else {
        throw new Error(response.message || 'Failed to deactivate user')
      }
    } catch (error: any) {
      throw new Error(handleApiError(error))
    }
  }

  // Reactivate user (admin only)
  async reactivateUser(id: number): Promise<boolean> {
    try {
      const response = await api.post(API_ENDPOINTS.USERS.ACTIVATE(id))
      
      if (response.success) {
        return true
      } else {
        throw new Error(response.message || 'Failed to reactivate user')
      }
    } catch (error: any) {
      throw new Error(handleApiError(error))
    }
  }

  // Get users by role
  async getUsersByRole(role: 'admin' | 'doctor'): Promise<User[]> {
    try {
      const users = await this.getUsers()
      return users.filter(user => user.role === role)
    } catch (error: any) {
      throw new Error(handleApiError(error))
    }
  }

  // Get active users only
  async getActiveUsers(): Promise<User[]> {
    try {
      const users = await this.getUsers()
      return users.filter(user => user.isActive !== false)
    } catch (error: any) {
      throw new Error(handleApiError(error))
    }
  }

  // Get doctors only (for dropdowns, etc.)
  async getDoctorUsers(): Promise<User[]> {
    try {
      return await this.getUsersByRole('doctor')
    } catch (error: any) {
      throw new Error(handleApiError(error))
    }
  }

  // Search users by term
  searchUsers(users: User[], searchTerm: string): User[] {
    if (!searchTerm.trim()) {
      return users
    }

    const term = searchTerm.toLowerCase()
    return users.filter(user => 
      user.username.toLowerCase().includes(term) ||
      user.email.toLowerCase().includes(term) ||
      user.firstName?.toLowerCase().includes(term) ||
      user.lastName?.toLowerCase().includes(term)
    )
  }

  // Validate user data
  validateUserData(userData: UserCreateData | UserUpdateData): string[] {
    const errors: string[] = []

    // Required fields for creation
    if ('username' in userData && !userData.username?.trim()) {
      errors.push('Username is required')
    }
    
    if ('email' in userData && !userData.email?.trim()) {
      errors.push('Email is required')
    }
    
    if ('password' in userData && !userData.password?.trim()) {
      errors.push('Password is required')
    }
    
    if ('firstName' in userData && !userData.firstName?.trim()) {
      errors.push('First name is required')
    }
    
    if ('lastName' in userData && !userData.lastName?.trim()) {
      errors.push('Last name is required')
    }

    // Email validation
    if (userData.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(userData.email)) {
      errors.push('Please enter a valid email address')
    }

    // Password validation (for new passwords)
    if (userData.password && userData.password.length < 6) {
      errors.push('Password must be at least 6 characters long')
    }

    // Doctor-specific validation
    if (userData.role === 'doctor') {
      if (userData.specialty && !userData.specialty.trim()) {
        errors.push('Specialty is required for doctors')
      }
      if (userData.licenseNumber && !userData.licenseNumber.trim()) {
        errors.push('License number is required for doctors')
      }
    }

    return errors
  }
}

// Create singleton instance
export const usersService = new UsersService()

// Export for easier importing
export default usersService
