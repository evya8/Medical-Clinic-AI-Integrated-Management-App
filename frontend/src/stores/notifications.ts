import { defineStore } from 'pinia'
import { ref } from 'vue'
import type { Notification } from '@/types/api.types'

export const useNotifications = defineStore('notifications', () => {
  // State
  const notifications = ref<Notification[]>([])

  // Actions
  const addNotification = (notification: Omit<Notification, 'id'>): string => {
    const id = Date.now().toString() + Math.random().toString(36).substr(2, 9)
    const newNotification: Notification = {
      id,
      duration: 5000, // Default 5 seconds
      persistent: false,
      ...notification,
    }

    notifications.value.push(newNotification)

    // Auto remove non-persistent notifications
    if (!newNotification.persistent) {
      setTimeout(() => {
        removeNotification(id)
      }, newNotification.duration)
    }

    return id
  }

  const removeNotification = (id: string): void => {
    const index = notifications.value.findIndex(n => n.id === id)
    if (index > -1) {
      notifications.value.splice(index, 1)
    }
  }

  const clearAll = (): void => {
    notifications.value = []
  }

  // Convenience methods for common notification types
  const success = (title: string, message?: string): string => {
    return addNotification({ type: 'success', title, message })
  }

  const error = (title: string, message?: string): string => {
    return addNotification({ 
      type: 'error', 
      title, 
      message,
      duration: 8000, // Error messages stay longer
    })
  }

  const warning = (title: string, message?: string): string => {
    return addNotification({ type: 'warning', title, message })
  }

  const info = (title: string, message?: string): string => {
    return addNotification({ type: 'info', title, message })
  }

  return {
    // State
    notifications: readonly(notifications),

    // Actions
    addNotification,
    removeNotification,
    clearAll,
    success,
    error,
    warning,
    info,
  }
})

// Export for easier importing
export default useNotifications
