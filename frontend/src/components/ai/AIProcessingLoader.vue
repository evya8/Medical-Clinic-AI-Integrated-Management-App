<template>
  <div class="ai-processing-loader">
    <!-- Overlay for modal loading -->
    <div v-if="overlay" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-8 max-w-md mx-4 text-center">
        <!-- AI Icon with spinning animation -->
        <div class="relative mx-auto mb-6 flex items-center justify-center">
          <div :class="sizeClasses[size].spinner + ' relative'">
            <!-- Spinning outer ring -->
            <div class="absolute inset-0 border-4 border-purple-200 rounded-full"></div>
            <div class="absolute inset-0 border-4 border-transparent border-t-purple-600 rounded-full animate-spin"></div>
            <!-- AI chip icon in center -->
            <CpuChipIcon :class="'absolute inset-0 m-auto ' + sizeClasses[size].icon + ' text-purple-600'" />
          </div>
        </div>
        
        <!-- Main message -->
        <h3 :class="sizeClasses[size].text + ' font-semibold text-gray-900 mb-2'">
          {{ message }}
        </h3>
        
        <!-- Submessage or current step -->
        <p class="text-sm text-gray-600 mb-4">
          {{ displayMessage }}
        </p>
        
        <!-- Progress bar (if enabled) -->
        <div v-if="showProgress" class="w-full mb-4">
          <div class="flex justify-between text-xs text-gray-600 mb-2">
            <span>Progress</span>
            <span>{{ progress || 0 }}%</span>
          </div>
          <div class="w-full bg-gray-200 rounded-full h-2">
            <div 
              class="bg-purple-600 h-2 rounded-full transition-all duration-300 ease-out"
              :style="{ width: (progress || 0) + '%' }"
            ></div>
          </div>
        </div>
        
        <!-- Processing steps (if provided) -->
        <div v-if="processingSteps && processingSteps.length > 0" class="text-left mt-4">
          <h4 class="text-xs font-medium text-gray-700 mb-2">Processing Steps:</h4>
          <ol class="text-xs text-gray-600 space-y-1">
            <li 
              v-for="(step, index) in processingSteps"
              :key="index"
              :class="'flex items-center ' + getStepClass(index)"
            >
              <span class="w-4 h-4 mr-2 flex items-center justify-center">
                {{ getStepIcon(index) }}
              </span>
              {{ step }}
            </li>
          </ol>
        </div>
        
        <!-- Pulsing dots animation -->
        <div class="flex justify-center space-x-1 mt-4">
          <div class="w-2 h-2 bg-purple-600 rounded-full animate-bounce"></div>
          <div class="w-2 h-2 bg-purple-600 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
          <div class="w-2 h-2 bg-purple-600 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
        </div>
      </div>
    </div>
    
    <!-- Inline loading (same content) -->
    <div v-else class="text-center">
      <!-- AI Icon with spinning animation -->
      <div class="relative mx-auto mb-6 flex items-center justify-center">
        <div :class="sizeClasses[size].spinner + ' relative'">
          <!-- Spinning outer ring -->
          <div class="absolute inset-0 border-4 border-purple-200 rounded-full"></div>
          <div class="absolute inset-0 border-4 border-transparent border-t-purple-600 rounded-full animate-spin"></div>
          <!-- AI chip icon in center -->
          <CpuChipIcon :class="'absolute inset-0 m-auto ' + sizeClasses[size].icon + ' text-purple-600'" />
        </div>
      </div>
      
      <!-- Main message -->
      <h3 :class="sizeClasses[size].text + ' font-semibold text-gray-900 mb-2'">
        {{ message }}
      </h3>
      
      <!-- Submessage or current step -->
      <p class="text-sm text-gray-600 mb-4">
        {{ displayMessage }}
      </p>
      
      <!-- Progress bar (if enabled) -->
      <div v-if="showProgress" class="w-full mb-4">
        <div class="flex justify-between text-xs text-gray-600 mb-2">
          <span>Progress</span>
          <span>{{ progress || 0 }}%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2">
          <div 
            class="bg-purple-600 h-2 rounded-full transition-all duration-300 ease-out"
            :style="{ width: (progress || 0) + '%' }"
          ></div>
        </div>
      </div>
      
      <!-- Processing steps (if provided) -->
      <div v-if="processingSteps && processingSteps.length > 0" class="text-left mt-4">
        <h4 class="text-xs font-medium text-gray-700 mb-2">Processing Steps:</h4>
        <ol class="text-xs text-gray-600 space-y-1">
          <li 
            v-for="(step, index) in processingSteps"
            :key="index"
            :class="'flex items-center ' + getStepClass(index)"
          >
            <span class="w-4 h-4 mr-2 flex items-center justify-center">
              {{ getStepIcon(index) }}
            </span>
            {{ step }}
          </li>
        </ol>
      </div>
      
      <!-- Pulsing dots animation -->
      <div class="flex justify-center space-x-1 mt-4">
        <div class="w-2 h-2 bg-purple-600 rounded-full animate-bounce"></div>
        <div class="w-2 h-2 bg-purple-600 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
        <div class="w-2 h-2 bg-purple-600 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { CpuChipIcon } from '@heroicons/vue/24/outline'

interface Props {
  message?: string
  submessage?: string
  progress?: number // 0-100
  showProgress?: boolean
  size?: 'sm' | 'md' | 'lg'
  overlay?: boolean
  processingSteps?: string[]
  currentStep?: number
}

const props = withDefaults(defineProps<Props>(), {
  message: 'AI is processing...',
  showProgress: false,
  size: 'md',
  overlay: false,
  currentStep: 0,
})

const currentStepMessage = ref('')
const animationIndex = ref(0)

// Animation steps for the loader
const animationSteps = [
  'Initializing AI model...',
  'Analyzing data...',
  'Processing patterns...',
  'Generating insights...',
  'Finalizing results...',
]

const sizeClasses = {
  sm: { spinner: 'w-8 h-8', text: 'text-sm', icon: 'w-5 h-5' },
  md: { spinner: 'w-12 h-12', text: 'text-base', icon: 'w-6 h-6' },
  lg: { spinner: 'w-16 h-16', text: 'text-lg', icon: 'w-8 h-8' }
}

let animationInterval: number | null = null

const displayMessage = computed(() => {
  return props.submessage || currentStepMessage.value
})

const getStepClass = (index: number) => {
  if (index < props.currentStep) return 'text-green-600'
  if (index === props.currentStep) return 'text-purple-600 font-medium'
  return 'text-gray-400'
}

const getStepIcon = (index: number) => {
  if (index < props.currentStep) return '✓'
  if (index === props.currentStep) return '●'
  return '○'
}

onMounted(() => {
  if (!props.processingSteps || props.processingSteps.length === 0) {
    animationInterval = window.setInterval(() => {
      animationIndex.value = (animationIndex.value + 1) % animationSteps.length
      currentStepMessage.value = animationSteps[animationIndex.value]
    }, 2000)
    currentStepMessage.value = animationSteps[0]
  }
})

onUnmounted(() => {
  if (animationInterval) {
    clearInterval(animationInterval)
  }
})
</script>

<style scoped>
@keyframes bounce {
  0%, 100% {
    transform: translateY(-25%);
    animation-timing-function: cubic-bezier(0.8, 0, 1, 1);
  }
  50% {
    transform: none;
    animation-timing-function: cubic-bezier(0, 0, 0.2, 1);
  }
}

.animate-bounce {
  animation: bounce 1s infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}
</style>
