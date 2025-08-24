import { api, API_ENDPOINTS, handleApiError } from './api'
import type { User, LoginCredentials, LoginResponse, APIResponse } from '@/types/api.types'

export class AuthService {
  // Login user
  async login(credentials: LoginCredentials): Promise<LoginResponse> {
    try {
      const response = await api.post<LoginResponse>(API_ENDPOINTS.AUTH.LOGIN, credentials)
      
      if (response.success && response.data) {
        // Store token and user data
        localStorage.setItem('auth_token', response.data.token)
        localStorage.setItem('user', JSON.stringify(response.data.user))
        
        return response.data
      } else {
        throw new Error(response.message || 'Login failed')
      }
    } catch (error: any) {
      throw new Error(handleApiError(error))
    }
  }

  // Logout user
  async logout(): Promise<void> {
    try {
      await api.post(API_ENDPOINTS.AUTH.LOGOUT)
    } catch (error: any) {
      // Continue with logout even if API call fails
      console.warn('Logout API call failed:', handleApiError(error))
    } finally {
      // Always clear local storage
      localStorage.removeItem('auth_token')
      localStorage.removeItem('user')
    }
  }

  // Get current user from API
  async getCurrentUser(): Promise<User> {
    try {
      const response = await api.get<User>(API_ENDPOINTS.AUTH.ME)
      
      if (response.success && response.data) {
        // Update stored user data
        localStorage.setItem('user', JSON.stringify(response.data))
        return response.data
      } else {
        throw new Error(response.message || 'Failed to get user data')
      }
    } catch (error: any) {
      throw new Error(handleApiError(error))
    }
  }

  // Refresh authentication token
  async refreshToken(): Promise<string> {
    try {
      const response = await api.post<{ token: string }>(API_ENDPOINTS.AUTH.REFRESH)
      
      if (response.success && response.data) {
        localStorage.setItem('auth_token', response.data.token)
        return response.data.token
      } else {
        throw new Error(response.message || 'Token refresh failed')
      }
    } catch (error: any) {
      // If refresh fails, user needs to login again
      this.clearAuthData()
      throw new Error(handleApiError(error))
    }
  }

  // Check if user is authenticated
  isAuthenticated(): boolean {
    const token = localStorage.getItem('auth_token')
    const user = localStorage.getItem('user')
    return !!(token && user)
  }

  // Get stored user data
  getStoredUser(): User | null {
    const userData = localStorage.getItem('user')
    if (userData) {
      try {
        return JSON.parse(userData)
      } catch (error) {
        console.error('Error parsing stored user data:', error)
        this.clearAuthData()
        return null
      }
    }
    return null
  }

  // Get stored auth token
  getStoredToken(): string | null {
    return localStorage.getItem('auth_token')
  }

  // Clear authentication data
  clearAuthData(): void {
    localStorage.removeItem('auth_token')
    localStorage.removeItem('user')
  }

  // Check if token is expired (basic check)
  isTokenExpired(): boolean {
    const token = this.getStoredToken()
    if (!token) return true

    try {
      // Basic JWT decode to check expiration
      const payload = JSON.parse(atob(token.split('.')[1]))
      const currentTime = Math.floor(Date.now() / 1000)
      
      return payload.exp < currentTime
    } catch (error) {
      console.error('Error checking token expiration:', error)
      return true
    }
  }

  // Initialize authentication state
  async initializeAuth(): Promise<User | null> {
    if (!this.isAuthenticated()) {
      return null
    }

    if (this.isTokenExpired()) {
      try {
        // Try to refresh token
        await this.refreshToken()
        // Get updated user data
        return await this.getCurrentUser()
      } catch (error) {
        // If refresh fails, clear auth data
        this.clearAuthData()
        return null
      }
    }

    // Token is valid, return stored user
    return this.getStoredUser()
  }

  // Check if user has specific role
  hasRole(requiredRole: string, user?: User): boolean {
    const currentUser = user || this.getStoredUser()
    return currentUser?.role === requiredRole
  }

  // Check if user has any of the specified roles
  hasAnyRole(requiredRoles: string[], user?: User): boolean {
    const currentUser = user || this.getStoredUser()
    return requiredRoles.includes(currentUser?.role || '')
  }

  // Get user's display name
  getUserDisplayName(user?: User): string {
    const currentUser = user || this.getStoredUser()
    if (!currentUser) return 'User'
    
    if (currentUser.firstName || currentUser.lastName) {
      return `${currentUser.firstName || ''} ${currentUser.lastName || ''}`.trim()
    }
    
    return currentUser.username || currentUser.email || 'User'
  }
}

// Create singleton instance
export const authService = new AuthService()

// Export for easier importing
export default authService
