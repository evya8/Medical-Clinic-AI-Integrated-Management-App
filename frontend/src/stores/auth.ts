import { defineStore } from 'pinia'
import { ref, computed, readonly } from 'vue'
import type { User, LoginCredentials } from '@/types/api.types'
import { authService } from '@/services/auth.service'
import { useNotifications } from './notifications'

export const useAuthStore = defineStore('auth', () => {
  // State
  const user = ref<User | null>(null)
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  const refreshToken = ref<string | null>(null)
  const tokenRefreshTimer = ref<NodeJS.Timeout | null>(null)

  // Get notifications store
  const { addNotification } = useNotifications()

  // Getters
  const isAuthenticated = computed(() => !!user.value && !!localStorage.getItem('auth_token'))
  const userRole = computed(() => user.value?.role || null)
  const userName = computed(() => authService.getUserDisplayName(user.value || undefined))
  const canAccessAdminPanel = computed(() => userRole.value === 'admin')
  const canAccessAI = computed(() => ['admin', 'doctor'].includes(userRole.value || ''))
  const canManageUsers = computed(() => userRole.value === 'admin')
  const isDoctor = computed(() => userRole.value === 'doctor')
  const isAdmin = computed(() => userRole.value === 'admin')

  // Actions
  const login = async (credentials: LoginCredentials): Promise<boolean> => {
    isLoading.value = true
    error.value = null

    try {
      const loginResponse = await authService.login(credentials)
      user.value = loginResponse.user
      
      // Store refresh token if provided
      if (loginResponse.refreshToken) {
        refreshToken.value = loginResponse.refreshToken
        localStorage.setItem('refresh_token', loginResponse.refreshToken)
      }
      
      // Set up automatic token refresh
      setupTokenRefresh()
      
      addNotification({
        type: 'success',
        title: 'Login Successful',
        message: `Welcome back, ${userName.value}!`,
      })

      return true
    } catch (err: any) {
      error.value = err.message || 'Login failed'
      addNotification({
        type: 'error',
        title: 'Login Failed',
        message: error.value || undefined,
      })
      return false
    } finally {
      isLoading.value = false
    }
  }

  const logout = async (): Promise<void> => {
    isLoading.value = true

    try {
      await authService.logout()
    } catch (err: any) {
      console.error('Logout error:', err)
      // Continue with logout even if API call fails
    } finally {
      // Clear all auth state
      user.value = null
      error.value = null
      refreshToken.value = null
      
      // Clear stored tokens
      localStorage.removeItem('auth_token')
      localStorage.removeItem('refresh_token')
      localStorage.removeItem('user')
      
      // Clear refresh timer
      if (tokenRefreshTimer.value) {
        clearTimeout(tokenRefreshTimer.value)
        tokenRefreshTimer.value = null
      }
      
      addNotification({
        type: 'info',
        title: 'Logged Out',
        message: 'You have been successfully logged out.',
      })
      
      isLoading.value = false
    }
  }

  const refreshUser = async (): Promise<void> => {
    if (!isAuthenticated.value) return

    try {
      const updatedUser = await authService.getCurrentUser()
      user.value = updatedUser
    } catch (err: any) {
      console.error('Failed to refresh user data:', err)
      // If refresh fails, user might need to login again
      if (err.message.includes('401') || err.message.includes('token')) {
        await logout()
      }
    }
  }

  const initializeAuth = async (): Promise<void> => {
    isLoading.value = true

    try {
      const storedUser = await authService.initializeAuth()
      if (storedUser) {
        user.value = storedUser
      }
    } catch (err: any) {
      console.error('Auth initialization failed:', err)
      // Clear any invalid auth data
      user.value = null
      authService.clearAuthData()
    } finally {
      isLoading.value = false
    }
  }

  const clearError = (): void => {
    error.value = null
  }

  const hasRole = (role: string): boolean => {
    return authService.hasRole(role, user.value || undefined)
  }

  const hasAnyRole = (roles: string[]): boolean => {
    return authService.hasAnyRole(roles, user.value || undefined)
  }

  const updateUser = (updatedUser: Partial<User>): void => {
    if (user.value) {
      user.value = { ...user.value, ...updatedUser }
      // Update localStorage
      localStorage.setItem('user', JSON.stringify(user.value))
    }
  }

  // Token refresh functionality
  const refreshTokens = async (): Promise<boolean> => {
    try {
      const storedRefreshToken = refreshToken.value || localStorage.getItem('refresh_token')
      if (!storedRefreshToken) {
        throw new Error('No refresh token available')
      }

      const response = await authService.refreshToken()
      
      // Update tokens
      if (response.refreshToken) {
        refreshToken.value = response.refreshToken
        localStorage.setItem('refresh_token', response.refreshToken)
      }
      
      // Set up next refresh
      setupTokenRefresh()
      
      return true
    } catch (error: any) {
      console.error('Token refresh failed:', error)
      // If refresh fails, logout user
      await logout()
      return false
    }
  }

  // Set up automatic token refresh (13 minutes for 15-minute tokens)
  const setupTokenRefresh = (): void => {
    if (tokenRefreshTimer.value) {
      clearTimeout(tokenRefreshTimer.value)
    }
    
    // Refresh token every 13 minutes (780000ms) for 15-minute access tokens
    tokenRefreshTimer.value = setTimeout(() => {
      if (isAuthenticated.value) {
        refreshTokens()
      }
    }, 780000)
  }

  // Initialize auth with refresh token support
  const initializeAuthWithRefresh = async (): Promise<void> => {
    isLoading.value = true

    try {
      const storedUser = await authService.initializeAuth()
      if (storedUser) {
        user.value = storedUser
        refreshToken.value = localStorage.getItem('refresh_token')
        
        // Check if we need to refresh token on startup
        if (authService.isTokenExpired()) {
          const refreshSuccess = await refreshTokens()
          if (!refreshSuccess) {
            return // logout was called in refreshTokens
          }
        } else {
          // Set up refresh timer for valid token
          setupTokenRefresh()
        }
      }
    } catch (err: any) {
      console.error('Auth initialization failed:', err)
      user.value = null
      refreshToken.value = null
      authService.clearAuthData()
      localStorage.removeItem('refresh_token')
    } finally {
      isLoading.value = false
    }
  }

  // Reactive permissions (simplified for admin/doctor roles only)
  const permissions = computed(() => ({
    canManageUsers: hasRole('admin'),
    canManagePatients: hasAnyRole(['admin', 'doctor']),
    canManageAppointments: hasAnyRole(['admin', 'doctor']),
    canAccessAI: hasAnyRole(['admin', 'doctor']),
    canViewReports: hasAnyRole(['admin', 'doctor']),
    canAccessAdminPanel: hasRole('admin'),
    canCreateUsers: hasRole('admin'),
    canManageDoctors: hasRole('admin'),
  }))

  return {
    // State
    user: readonly(user),
    isLoading: readonly(isLoading),
    error: readonly(error),
    refreshToken: readonly(refreshToken),

    // Getters
    isAuthenticated,
    userRole,
    userName,
    canAccessAdminPanel,
    canAccessAI,
    canManageUsers,
    isDoctor,
    isAdmin,
    permissions,

    // Actions
    login,
    logout,
    refreshUser,
    initializeAuth,
    initializeAuthWithRefresh,
    refreshTokens,
    setupTokenRefresh,
    clearError,
    hasRole,
    hasAnyRole,
    updateUser,
  }
})

// Export for easier importing
export default useAuthStore
