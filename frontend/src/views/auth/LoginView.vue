<template>
  <div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
      <div class="flex justify-center">
        <!-- Medical Icon/Logo -->
        <div class="flex items-center justify-center w-16 h-16 bg-primary-500 rounded-full">
          <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
          </svg>
        </div>
      </div>
      <h2 class="mt-6 text-center text-3xl font-bold tracking-tight text-gray-900">
        MediCore Clinic
      </h2>
      <p class="mt-2 text-center text-sm text-gray-600">
        Sign in to your medical management system
      </p>
    </div>

    <!-- Login Form -->
    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
      <div class="medical-card py-8 px-4 shadow sm:rounded-lg sm:px-10">
        <form class="space-y-6" @submit.prevent="handleSubmit">
          <!-- Email Field -->
          <div>
            <label for="email" class="medical-form-label">
              Email address
            </label>
            <div class="mt-1">
              <input
                id="email"
                v-model="form.email"
                name="email"
                type="email"
                autocomplete="email"
                required
                class="medical-input"
                :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': errors.email }"
                placeholder="doctor@example.com"
              />
              <p v-if="errors.email" class="mt-1 text-sm text-red-600">
                {{ errors.email }}
              </p>
            </div>
          </div>

          <!-- Password Field -->
          <div>
            <label for="password" class="medical-form-label">
              Password
            </label>
            <div class="mt-1 relative">
              <input
                id="password"
                v-model="form.password"
                name="password"
                :type="showPassword ? 'text' : 'password'"
                autocomplete="current-password"
                required
                class="medical-input pr-10"
                :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': errors.password }"
                placeholder="Enter your password"
              />
              <button
                type="button"
                class="absolute inset-y-0 right-0 pr-3 flex items-center"
                @click="showPassword = !showPassword"
              >
                <EyeIcon v-if="!showPassword" class="h-5 w-5 text-gray-400 hover:text-gray-600" />
                <EyeSlashIcon v-else class="h-5 w-5 text-gray-400 hover:text-gray-600" />
              </button>
              <p v-if="errors.password" class="mt-1 text-sm text-red-600">
                {{ errors.password }}
              </p>
            </div>
          </div>

          <!-- Remember Me -->
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <input
                id="remember-me"
                v-model="form.rememberMe"
                name="remember-me"
                type="checkbox"
                class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
              />
              <label for="remember-me" class="ml-2 block text-sm text-gray-900">
                Remember me
              </label>
            </div>

            <div class="text-sm">
              <a href="#" class="font-medium text-primary-600 hover:text-primary-500">
                Forgot your password?
              </a>
            </div>
          </div>

          <!-- Error Message -->
          <div v-if="generalError" class="rounded-md bg-red-50 p-4">
            <div class="flex">
              <ExclamationTriangleIcon class="h-5 w-5 text-red-400" />
              <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800">
                  Login Failed
                </h3>
                <p class="mt-1 text-sm text-red-700">
                  {{ generalError }}
                </p>
              </div>
            </div>
          </div>

          <!-- Submit Button -->
          <div>
            <button
              type="submit"
              :disabled="isLoading"
              class="medical-button-primary w-full flex justify-center py-3 px-4"
            >
              <svg
                v-if="isLoading"
                class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
                fill="none"
                viewBox="0 0 24 24"
              >
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
              </svg>
              {{ isLoading ? 'Signing in...' : 'Sign in' }}
            </button>
          </div>
        </form>

        <!-- Demo Credentials (Development Only) -->
        <div v-if="isDevelopment" class="mt-6 pt-6 border-t border-gray-200">
          <p class="text-xs text-gray-500 mb-2">Demo Credentials:</p>
          <div class="text-xs space-y-1">
            <button
              type="button"
              class="block text-left text-primary-600 hover:text-primary-800"
              @click="fillDemoCredentials('admin')"
            >
              Admin: admin@clinic.com / admin123
            </button>
            <button
              type="button"
              class="block text-left text-primary-600 hover:text-primary-800"
              @click="fillDemoCredentials('doctor')"
            >
              Doctor: smith@clinic.com / doctor123
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import { EyeIcon, EyeSlashIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline'
import { useAuthStore } from '@/stores/auth'
import type { LoginCredentials } from '@/types/api.types'

// Router
const router = useRouter()

// Store
const authStore = useAuthStore()

// Reactive state
const form = reactive<LoginCredentials & { rememberMe: boolean }>({
  email: '',
  password: '',
  rememberMe: false,
})

const showPassword = ref(false)
const errors = ref<Record<string, string>>({})
const generalError = ref<string | null>(null)

// Computed
const isLoading = computed(() => authStore.isLoading)
const isDevelopment = computed(() => import.meta.env.DEV)

// Methods
const validateForm = (): boolean => {
  errors.value = {}
  
  if (!form.email) {
    errors.value.email = 'Email is required'
  } else if (!/\S+@\S+\.\S+/.test(form.email)) {
    errors.value.email = 'Email is invalid'
  }
  
  if (!form.password) {
    errors.value.password = 'Password is required'
  } else if (form.password.length < 6) {
    errors.value.password = 'Password must be at least 6 characters'
  }
  
  return Object.keys(errors.value).length === 0
}

const handleSubmit = async () => {
  generalError.value = null
  
  if (!validateForm()) {
    return
  }

  try {
    const success = await authStore.login({
      email: form.email,
      password: form.password,
    })

    if (success) {
      // Get redirect path from query params or default to dashboard
      const redirect = router.currentRoute.value.query.redirect as string || '/dashboard'
      router.push(redirect)
    }
  } catch (error: any) {
    generalError.value = error.message || 'Login failed. Please try again.'
  }
}

const fillDemoCredentials = (role: 'admin' | 'doctor') => {
  if (role === 'admin') {
    form.email = 'admin@clinic.com'
    form.password = 'admin123'
  } else if (role === 'doctor') {
    form.email = 'smith@clinic.com'
    form.password = 'doctor123'
  }
}

// Clear errors when user starts typing
const clearError = (field: string) => {
  if (errors.value[field]) {
    delete errors.value[field]
  }
  if (generalError.value) {
    generalError.value = null
  }
}

// Watch form changes to clear errors (proper Vue way)
watch(() => form.email, () => {
  if (errors.value.email) {
    clearError('email')
  }
})

watch(() => form.password, () => {
  if (errors.value.password) {
    clearError('password')
  }
})

watch(() => generalError.value, (newError) => {
  if (newError) {
    // Clear general error after 5 seconds
    setTimeout(() => {
      // Only clear if the error hasn't changed
      if (generalError.value === newError) {
        generalError.value = null
      }
    }, 5000)
  }
})
</script>

<style scoped>
/* Additional component-specific styles */
.login-container {
  background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
}

@media (max-width: 640px) {
  .medical-card {
    margin: 0 1rem;
  }
}
</style>
