<template>
  <div class="medical-card p-12 text-center">
    <div class="ai-icon-container w-16 h-16 bg-gradient-to-br from-purple-100 to-indigo-100 rounded-full flex items-center justify-center mx-auto mb-6">
      <CpuChipIcon class="w-8 h-8 text-purple-600" />
    </div>
    
    <h3 class="text-xl font-semibold text-gray-900 mb-2">
      {{ title }}
    </h3>
    <p class="text-gray-600 mb-8 max-w-2xl mx-auto">
      {{ description }}
    </p>
    
    <!-- Features List -->
    <div v-if="features.length > 0" class="bg-gray-50 rounded-lg p-6 mb-8">
      <h4 class="text-lg font-medium text-gray-900 mb-4">Planned Features</h4>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div 
          v-for="feature in features" 
          :key="feature"
          class="flex items-center space-x-3 text-left"
        >
          <div class="w-2 h-2 bg-purple-400 rounded-full flex-shrink-0"></div>
          <span class="text-sm text-gray-700">{{ feature }}</span>
        </div>
      </div>
    </div>

    <!-- Phase Indicator -->
    <div class="mb-6">
      <span class="ai-indicator">
        Coming in {{ phase }}
      </span>
    </div>

    <!-- Back Button -->
    <RouterLink to="/dashboard" class="medical-button-secondary">
      <ArrowLeftIcon class="w-4 h-4 mr-2" />
      Back to Dashboard
    </RouterLink>
  </div>
</template>

<script setup lang="ts">
import {
  CpuChipIcon,
  ArrowLeftIcon,
} from '@heroicons/vue/24/outline'

interface Props {
  title: string
  description: string
  features: string[]
  phase: string
}

defineProps<Props>()
</script>

<style scoped>
.ai-icon-container {
  animation: pulse-glow 3s ease-in-out infinite;
}

@keyframes pulse-glow {
  0%, 100% {
    box-shadow: 0 0 15px rgba(147, 51, 234, 0.2);
  }
  50% {
    box-shadow: 0 0 25px rgba(147, 51, 234, 0.4);
  }
}

.ai-indicator {
  @apply inline-flex items-center px-3 py-1 rounded-full bg-purple-100 text-purple-700 font-medium text-sm;
}
</style>
