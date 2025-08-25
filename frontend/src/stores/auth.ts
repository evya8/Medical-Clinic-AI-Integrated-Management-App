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

  // Get notifications store
  const { addNotification } = useNotifications()

  // Getters
  const isAuthenticated = computed(() => !!user.value)
  const userRole = computed(() => user.value?.role || null)
  const userName = computed(() => authService.getUserDisplayName(user.value || undefined))
  const canAccessAdminPanel = computed(() => userRole.value === 'admin')
  const canAccessAI = computed(() => ['admin', 'doctor'].includes(userRole.value || ''))

  // Actions
  const login = async (credentials: LoginCredentials): Promise<boolean> => {
    isLoading.value = true
    error.value = null

    try {
      const loginResponse = await authService.login(credentials)
      user.value = loginResponse.user
      
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
        message: error.value,
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
      user.value = null
      error.value = null
      
      addNotification({
        type: 'info',
        title: 'Logged Out',
        message: 'You have been successfully logged out.',
      })
    } catch (err: any) {
      console.error('Logout error:', err)
      // Even if API call fails, clear local state
      user.value = null
      error.value = null
    } finally {
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

  // Reactive permissions
  const permissions = computed(() => ({
    canManageUsers: hasRole('admin'),
    canManagePatients: hasAnyRole(['admin', 'doctor', 'staff']),
    canManageAppointments: hasAnyRole(['admin', 'doctor', 'staff', 'receptionist']),
    canAccessAI: hasAnyRole(['admin', 'doctor']),
    canViewReports: hasAnyRole(['admin', 'doctor']),
    canManageInventory: hasAnyRole(['admin', 'staff']),
    canManageBilling: hasAnyRole(['admin', 'staff']),
  }))

  return {
    // State
    user: readonly(user),
    isLoading: readonly(isLoading),
    error: readonly(error),

    // Getters
    isAuthenticated,
    userRole,
    userName,
    canAccessAdminPanel,
    canAccessAI,
    permissions,

    // Actions
    login,
    logout,
    refreshUser,
    initializeAuth,
    clearError,
    hasRole,
    hasAnyRole,
    updateUser,
  }
})

// Export for easier importing
export default useAuthStore
