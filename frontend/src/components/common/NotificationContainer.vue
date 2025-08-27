<template>
  <Teleport to="body">
    <div
      aria-live="assertive"
      class="notification-container fixed inset-0 z-[100] flex items-start justify-center px-4 py-6 pointer-events-none sm:p-6"
    >
      <div class="w-full flex flex-col items-center space-y-4">
        <!-- Notifications List -->
        <TransitionGroup
          name="notification"
          tag="div"
          class="flex flex-col space-y-4"
        >
          <div
            v-for="notification in notifications"
            :key="notification.id"
            class="notification-card max-w-sm w-full bg-white shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden"
            :class="getBorderClass(notification.type)"
            role="alert"
          >
            <div class="p-4">
              <div class="flex items-start">
                <!-- Icon -->
                <div class="flex-shrink-0">
                  <CheckCircleIcon
                    v-if="notification.type === 'success'"
                    class="h-6 w-6 text-green-400"
                  />
                  <ExclamationTriangleIcon
                    v-else-if="notification.type === 'warning'"
                    class="h-6 w-6 text-yellow-400"
                  />
                  <XCircleIcon
                    v-else-if="notification.type === 'error'"
                    class="h-6 w-6 text-red-400"
                  />
                  <InformationCircleIcon
                    v-else
                    class="h-6 w-6 text-blue-400"
                  />
                </div>

                <!-- Content -->
                <div class="ml-3 w-0 flex-1 pt-0.5">
                  <p class="text-sm font-medium text-gray-900">
                    {{ notification.title }}
                  </p>
                  <p
                    v-if="notification.message"
                    class="mt-1 text-sm text-gray-500"
                  >
                    {{ notification.message }}
                  </p>
                </div>

                <!-- Close Button -->
                <div class="ml-4 flex-shrink-0 flex">
                  <button
                    class="bg-white rounded-md inline-flex text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                    @click="removeNotification(notification.id)"
                  >
                    <span class="sr-only">Close</span>
                    <XMarkIcon class="h-5 w-5" />
                  </button>
                </div>
              </div>

              <!-- Progress Bar (for auto-dismiss notifications) -->
              <div
                v-if="!notification.persistent && notification.duration"
                class="mt-3"
              >
                <div class="w-full bg-gray-200 rounded-full h-1">
                  <div
                    class="progress-bar h-1 rounded-full transition-all ease-linear"
                    :class="progressBarColor(notification.type)"
                    :style="{ 
                      width: '100%',
                      animation: `shrink ${notification.duration}ms linear forwards`
                    }"
                  ></div>
                </div>
              </div>
            </div>
          </div>
        </TransitionGroup>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import {
  CheckCircleIcon,
  ExclamationTriangleIcon,
  XCircleIcon,
  InformationCircleIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'
import { useNotifications } from '@/stores/notifications'
import type { NotificationData } from '@/types/api.types'

// Store
const notificationsStore = useNotifications()

// Computed
const notifications = computed(() => notificationsStore.notifications)

// Methods
const removeNotification = (id: string) => {
  notificationsStore.removeNotification(id)
}

const progressBarColor = (type: NotificationData['type']): string => {
  const colors = {
    success: 'bg-green-400',
    warning: 'bg-yellow-400',
    error: 'bg-red-400',
    info: 'bg-blue-400',
  }
  return colors[type] || colors.info
}

const getBorderClass = (type: NotificationData['type']): string => {
  const borderClasses = {
    success: 'border-green',
    warning: 'border-yellow',
    error: 'border-red',
    info: 'border-blue',
  }
  return borderClasses[type] || borderClasses.info
}
</script>

<style lang="postcss" scoped>
.notification-container {
  /* Ensure notifications appear above everything else */
  z-index: 100;
}

/* Notification enter/leave transitions */
.notification-enter-active {
  transition: all 0.3s ease-out;
}

.notification-leave-active {
  transition: all 0.2s ease-in;
}

.notification-enter-from {
  transform: translateX(100%);
  opacity: 0;
}

.notification-leave-to {
  transform: translateX(100%);
  opacity: 0;
}

.notification-move {
  transition: transform 0.3s ease;
}

/* Progress bar animation */
@keyframes shrink {
  from {
    width: 100%;
  }
  to {
    width: 0%;
  }
}

.progress-bar {
  transform-origin: left center;
}

/* Mobile responsive adjustments */
@media (max-width: 640px) {
  .notification-container {
    @apply items-center justify-center;
  }

  .notification-card {
    @apply mx-4;
  }
}
/* Improve accessibility */
/* Ensure notifications are accessible to screen readers */
/* role attribute should be set in the template, not in CSS */


/* Custom shadow for better visual hierarchy */
.notification-card {
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

/* Subtle border based on notification type */
.notification-card {
  border-left: 4px solid transparent;
}

/* Add colored left border based on notification type - handled via template classes */
.notification-card.border-green {
  border-left-color: #10b981;
}

.notification-card.border-yellow {
  border-left-color: #f59e0b;
}

.notification-card.border-red {
  border-left-color: #ef4444;
}

.notification-card.border-blue {
  border-left-color: #3b82f6;
}
</style>
