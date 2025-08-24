<template>
  <AppLayout>
    <div class="appointments-container">
      <!-- Page Header -->
      <div class="page-header mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">Appointments</h1>
            <p class="mt-2 text-sm text-gray-700">
              Manage patient appointments and scheduling
            </p>
          </div>
          <button class="medical-button-primary">
            <CalendarPlusIcon class="w-4 h-4 mr-2" />
            New Appointment
          </button>
        </div>
      </div>

      <!-- Coming Soon Card -->
      <div class="medical-card p-12 text-center">
        <CalendarIcon class="mx-auto h-16 w-16 text-gray-400 mb-6" />
        <h3 class="text-lg font-medium text-gray-900 mb-2">
          Appointments Management
        </h3>
        <p class="text-gray-600 mb-8 max-w-md mx-auto">
          The appointments system will be available in Phase 2. This will include scheduling, 
          calendar integration, and appointment management features.
        </p>
        
        <!-- Features Preview -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-8">
          <div class="feature-preview p-4 bg-gray-50 rounded-lg">
            <CalendarDaysIcon class="w-8 h-8 text-primary-500 mx-auto mb-3" />
            <h4 class="font-medium text-gray-900 mb-2">Smart Scheduling</h4>
            <p class="text-sm text-gray-600">
              Intelligent appointment scheduling with conflict detection
            </p>
          </div>
          
          <div class="feature-preview p-4 bg-gray-50 rounded-lg">
            <ClockIcon class="w-8 h-8 text-primary-500 mx-auto mb-3" />
            <h4 class="font-medium text-gray-900 mb-2">Time Management</h4>
            <p class="text-sm text-gray-600">
              Optimize appointment slots and reduce wait times
            </p>
          </div>
          
          <div class="feature-preview p-4 bg-gray-50 rounded-lg">
            <BellAlertIcon class="w-8 h-8 text-primary-500 mx-auto mb-3" />
            <h4 class="font-medium text-gray-900 mb-2">Reminders</h4>
            <p class="text-sm text-gray-600">
              Automated SMS and email appointment reminders
            </p>
          </div>
        </div>

        <!-- Back to Dashboard -->
        <div class="mt-8">
          <RouterLink to="/dashboard" class="medical-button-secondary">
            <ArrowLeftIcon class="w-4 h-4 mr-2" />
            Back to Dashboard
          </RouterLink>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import {
  CalendarIcon,
  CalendarPlusIcon,
  CalendarDaysIcon,
  ClockIcon,
  BellAlertIcon,
  ArrowLeftIcon,
} from '@heroicons/vue/24/outline'
import AppLayout from '@/components/common/AppLayout.vue'
</script>

<style scoped>
.appointments-container {
  @apply max-w-6xl mx-auto;
}

.feature-preview {
  @apply transition-all duration-200 hover:shadow-md hover:scale-105;
}

/* Mobile responsive */
@media (max-width: 640px) {
  .page-header {
    @apply flex-col space-y-4;
  }
  
  .grid-cols-3 {
    @apply grid-cols-1;
  }
}
</style>
