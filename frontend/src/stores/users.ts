import { defineStore } from 'pinia'
import { ref, computed, readonly } from 'vue'
import type { User } from '@/types/api.types'
import { usersService, type UserCreateData, type UserUpdateData } from '@/services/users.service'
import { useNotifications } from './notifications'

export interface UserListFilters {
  search?: string
  role?: 'admin' | 'doctor' | ''
  isActive?: boolean | null
}

export const useUsersStore = defineStore('users', () => {
  // State
  const users = ref<User[]>([])
  const currentUser = ref<User | null>(null)
  const isLoading = ref(false)
  const isCreating = ref(false)
  const isUpdating = ref(false)
  const isDeleting = ref(false)
  const error = ref<string | null>(null)
  const filters = ref<UserListFilters>({
    search: '',
    role: '',
    isActive: null
  })

  // Get notifications store
  const { addNotification } = useNotifications()

  // Getters
  const filteredUsers = computed(() => {
    let filtered = users.value

    // Filter by search term
    if (filters.value.search) {
      const searchTerm = filters.value.search.toLowerCase()
      filtered = filtered.filter(user => 
        user.username.toLowerCase().includes(searchTerm) ||
        user.email.toLowerCase().includes(searchTerm) ||
        user.firstName?.toLowerCase().includes(searchTerm) ||
        user.lastName?.toLowerCase().includes(searchTerm)
      )
    }

    // Filter by role
    if (filters.value.role && filters.value.role !== '') {
      filtered = filtered.filter(user => user.role === filters.value.role)
    }

    // Filter by active status
    if (filters.value.isActive !== null) {
      filtered = filtered.filter(user => user.isActive === filters.value.isActive)
    }

    return filtered
  })

  const totalUsers = computed(() => users.value.length)
  const activeUsers = computed(() => users.value.filter(user => user.isActive).length)
  const adminUsers = computed(() => users.value.filter(user => user.role === 'admin').length)
  const doctorUsers = computed(() => users.value.filter(user => user.role === 'doctor').length)

  // Actions
  const fetchUsers = async (): Promise<void> => {
    isLoading.value = true
    error.value = null

    try {
      const fetchedUsers = await usersService.getUsers()
      users.value = fetchedUsers
    } catch (err: any) {
      error.value = err.message || 'Failed to fetch users'
      addNotification({
        type: 'error',
        title: 'Error Loading Users',
        message: error.value
      })
    } finally {
      isLoading.value = false
    }
  }

  const fetchUserById = async (id: number): Promise<User | null> => {
    isLoading.value = true
    error.value = null

    try {
      const user = await usersService.getUserById(id)
      
      // Update current user for details view
      currentUser.value = user
      
      // Update in users list if exists
      const existingIndex = users.value.findIndex(u => u.id === id)
      if (existingIndex !== -1) {
        users.value[existingIndex] = user
      }

      return user
    } catch (err: any) {
      error.value = err.message || 'Failed to fetch user'
      addNotification({
        type: 'error',
        title: 'Error Loading User',
        message: error.value
      })
      return null
    } finally {
      isLoading.value = false
    }
  }

  const createUser = async (userData: UserCreateData): Promise<User | null> => {
    isCreating.value = true
    error.value = null

    try {
      // Validate user data first
      const validationErrors = usersService.validateUserData(userData)
      if (validationErrors.length > 0) {
        throw new Error(validationErrors.join(', '))
      }

      const newUser = await usersService.createUser(userData)

      // Add to users list
      users.value.unshift(newUser)

      addNotification({
        type: 'success',
        title: 'User Created',
        message: `${newUser.firstName} ${newUser.lastName} has been created successfully`
      })

      return newUser
    } catch (err: any) {
      error.value = err.message || 'Failed to create user'
      addNotification({
        type: 'error',
        title: 'Error Creating User',
        message: error.value
      })
      return null
    } finally {
      isCreating.value = false
    }
  }

  const updateUser = async (id: number, userData: UserUpdateData): Promise<User | null> => {
    isUpdating.value = true
    error.value = null

    try {
      // Validate user data first
      const validationErrors = usersService.validateUserData(userData)
      if (validationErrors.length > 0) {
        throw new Error(validationErrors.join(', '))
      }

      const updatedUser = await usersService.updateUser(id, userData)

      // Update in users list
      const existingIndex = users.value.findIndex(u => u.id === id)
      if (existingIndex !== -1) {
        users.value[existingIndex] = updatedUser
      }

      // Update current user if it's the same
      if (currentUser.value && currentUser.value.id === id) {
        currentUser.value = updatedUser
      }

      addNotification({
        type: 'success',
        title: 'User Updated',
        message: `${updatedUser.firstName} ${updatedUser.lastName} has been updated successfully`
      })

      return updatedUser
    } catch (err: any) {
      error.value = err.message || 'Failed to update user'
      addNotification({
        type: 'error',
        title: 'Error Updating User',
        message: error.value
      })
      return null
    } finally {
      isUpdating.value = false
    }
  }

  const deactivateUser = async (id: number): Promise<boolean> => {
    isDeleting.value = true
    error.value = null

    try {
      const success = await usersService.deactivateUser(id)
      
      if (success) {
        // Update user status in list (soft delete)
        const existingIndex = users.value.findIndex(u => u.id === id)
        if (existingIndex !== -1) {
          users.value[existingIndex] = {
            ...users.value[existingIndex],
            isActive: false
          }
        }

        addNotification({
          type: 'info',
          title: 'User Deactivated',
          message: 'User has been deactivated successfully'
        })
      }

      return success
    } catch (err: any) {
      error.value = err.message || 'Failed to deactivate user'
      addNotification({
        type: 'error',
        title: 'Error Deactivating User',
        message: error.value
      })
      return false
    } finally {
      isDeleting.value = false
    }
  }

  const reactivateUser = async (id: number): Promise<boolean> => {
    isUpdating.value = true
    error.value = null

    try {
      const success = await usersService.reactivateUser(id)
      
      if (success) {
        // Update user status in list
        const existingIndex = users.value.findIndex(u => u.id === id)
        if (existingIndex !== -1) {
          users.value[existingIndex] = {
            ...users.value[existingIndex],
            isActive: true
          }
        }

        addNotification({
          type: 'success',
          title: 'User Reactivated',
          message: 'User has been reactivated successfully'
        })
      }

      return success
    } catch (err: any) {
      error.value = err.message || 'Failed to reactivate user'
      addNotification({
        type: 'error',
        title: 'Error Reactivating User',
        message: error.value
      })
      return false
    } finally {
      isUpdating.value = false
    }
  }

  const setFilters = (newFilters: Partial<UserListFilters>): void => {
    filters.value = { ...filters.value, ...newFilters }
  }

  const clearFilters = (): void => {
    filters.value = {
      search: '',
      role: '',
      isActive: null
    }
  }

  const clearError = (): void => {
    error.value = null
  }

  const clearCurrentUser = (): void => {
    currentUser.value = null
  }

  return {
    // State
    users: readonly(users),
    currentUser: readonly(currentUser),
    isLoading: readonly(isLoading),
    isCreating: readonly(isCreating),
    isUpdating: readonly(isUpdating),
    isDeleting: readonly(isDeleting),
    error: readonly(error),
    filters: readonly(filters),

    // Getters
    filteredUsers,
    totalUsers,
    activeUsers,
    adminUsers,
    doctorUsers,

    // Actions
    fetchUsers,
    fetchUserById,
    createUser,
    updateUser,
    deactivateUser,
    reactivateUser,
    setFilters,
    clearFilters,
    clearError,
    clearCurrentUser,
  }
})

// Export for easier importing
export default useUsersStore
