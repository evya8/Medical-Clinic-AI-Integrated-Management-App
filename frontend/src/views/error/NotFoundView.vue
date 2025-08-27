<template>
  <div class="min-h-screen bg-gray-50 flex flex-col justify-center items-center px-4">
    <div class="max-w-md w-full text-center">
      <!-- 404 Illustration -->
      <div class="mb-8">
        <div class="mx-auto w-24 h-24 bg-primary-100 rounded-full flex items-center justify-center mb-6">
          <ExclamationTriangleIcon class="w-12 h-12 text-primary-600" />
        </div>
        
        <!-- 404 Text -->
        <h1 class="text-6xl font-bold text-gray-900 mb-2">404</h1>
        <h2 class="text-2xl font-semibold text-gray-700 mb-4">Page Not Found</h2>
        <p class="text-gray-600 mb-8">
          Sorry, we couldn't find the page you're looking for. The page may have been moved, deleted, or you may have entered the wrong URL.
        </p>
      </div>

      <!-- Action Buttons -->
      <div class="space-y-4">
        <button
          class="medical-button-primary w-full"
          @click="goHome"
        >
          <HomeIcon class="w-5 h-5 mr-2" />
          Back to Dashboard
        </button>
        
        <button
          class="medical-button-secondary w-full"
          @click="goBack"
        >
          <ArrowLeftIcon class="w-5 h-5 mr-2" />
          Go Back
        </button>
      </div>

      <!-- Help Links -->
      <div class="mt-8 pt-6 border-t border-gray-200">
        <p class="text-sm text-gray-500 mb-4">
          Need help? Try one of these:
        </p>
        <div class="grid grid-cols-2 gap-4 text-sm">
          <RouterLink
            to="/patients"
            class="text-primary-600 hover:text-primary-800 flex items-center justify-center py-2 hover:bg-primary-50 rounded-md transition-colors duration-200"
          >
            <UsersIcon class="w-4 h-4 mr-1" />
            View Patients
          </RouterLink>
          <RouterLink
            to="/appointments"
            class="text-primary-600 hover:text-primary-800 flex items-center justify-center py-2 hover:bg-primary-50 rounded-md transition-colors duration-200"
          >
            <CalendarIcon class="w-4 h-4 mr-1" />
            Appointments
          </RouterLink>
        </div>
      </div>

      <!-- Contact Support -->
      <div class="mt-6">
        <p class="text-xs text-gray-400">
          Still having trouble? 
          <a href="#" class="text-primary-600 hover:text-primary-800">
            Contact Support
          </a>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'
import {
  ExclamationTriangleIcon,
  HomeIcon,
  ArrowLeftIcon,
  UsersIcon,
  CalendarIcon,
} from '@heroicons/vue/24/outline'

// Router
const router = useRouter()

// Methods
const goHome = () => {
  router.push('/')
}

const goBack = () => {
  // Try to go back, but fallback to dashboard if no history
  if (window.history.length > 1) {
    router.go(-1)
  } else {
    router.push('/')
  }
}
</script>

<style lang="postcss" scoped>
/* Add some subtle animations */
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.max-w-md {
  animation: fadeInUp 0.6s ease-out;
}

/* Floating animation for the icon */
@keyframes float {
  0%, 100% { transform: translateY(0px); }
  50% { transform: translateY(-10px); }
}

.bg-primary-100 {
  animation: float 3s ease-in-out infinite;
}

/* Hover effect for help links */
.grid a {
  transition: all 0.2s ease;
}

.grid a:hover {
  transform: translateY(-1px);
}

/* Mobile responsive adjustments */
@media (max-width: 640px) {
  .text-6xl {
    @apply text-5xl;
  }
  
  .text-2xl {
    @apply text-xl;
  }
  
  .w-24.h-24 {
    @apply w-20 h-20;
  }
  
  .w-12.h-12 {
    @apply w-10 h-10;
  }
  
  .grid-cols-2 {
    @apply grid-cols-1 gap-2;
  }
}

/* Focus states for accessibility */
button:focus,
a:focus {
  @apply outline-none ring-2 ring-primary-500 ring-offset-2;
}

/* Error page specific styling */
.min-h-screen {
  background-image: radial-gradient(circle at 25% 25%, rgba(14, 165, 233, 0.05) 0%, transparent 50%),
                    radial-gradient(circle at 75% 75%, rgba(14, 165, 233, 0.05) 0%, transparent 50%);
}

/* Add subtle grid pattern */
.min-h-screen::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-image: 
    linear-gradient(rgba(14, 165, 233, 0.02) 1px, transparent 1px),
    linear-gradient(90deg, rgba(14, 165, 233, 0.02) 1px, transparent 1px);
  background-size: 20px 20px;
  pointer-events: none;
}

/* Ensure content appears above the background pattern */
.max-w-md {
  position: relative;
  z-index: 1;
}
</style>
