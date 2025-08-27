<template>
  <div class="ai-confidence-score">
    <div class="flex items-center justify-between mb-2">
      <span class="text-sm font-medium text-gray-700">
        {{ label }}
      </span>
      <span :class="['text-sm font-semibold', getConfidenceColor()]">
        {{ Math.round(confidence * 100) }}%
      </span>
    </div>
    
    <!-- Progress bar -->
    <div class="w-full bg-gray-200 rounded-full h-2 mb-2">
      <div 
        :class="[
          'h-2 rounded-full transition-all duration-700 ease-out',
          getConfidenceBarColor()
        ]"
        :style="{ width: (confidence * 100) + '%' }"
      ></div>
    </div>
    
    <!-- Confidence level description -->
    <div class="flex items-center justify-between">
      <span class="text-xs text-gray-500">
        {{ getConfidenceDescription() }}
      </span>
      <div v-if="showModelInfo" class="flex items-center text-xs text-gray-400">
        <CpuChipIcon class="w-3 h-3 mr-1" />
        <span>{{ modelName || 'AI Model' }}</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { CpuChipIcon } from '@heroicons/vue/24/outline'

interface Props {
  confidence: number // 0.0 to 1.0
  label?: string
  modelName?: string
  showModelInfo?: boolean
  size?: 'sm' | 'md' | 'lg'
}

const props = withDefaults(defineProps<Props>(), {
  label: 'AI Confidence',
  showModelInfo: false,
  size: 'md',
})

const getConfidenceColor = () => {
  if (props.confidence >= 0.9) return 'text-green-700'
  if (props.confidence >= 0.8) return 'text-blue-700'
  if (props.confidence >= 0.7) return 'text-yellow-700'
  return 'text-red-700'
}

const getConfidenceBarColor = () => {
  if (props.confidence >= 0.9) return 'bg-green-500'
  if (props.confidence >= 0.8) return 'bg-blue-500'
  if (props.confidence >= 0.7) return 'bg-yellow-500'
  return 'bg-red-500'
}

const getConfidenceDescription = () => {
  if (props.confidence >= 0.95) return 'Very High Confidence'
  if (props.confidence >= 0.85) return 'High Confidence'
  if (props.confidence >= 0.75) return 'Good Confidence'
  if (props.confidence >= 0.6) return 'Moderate Confidence'
  return 'Low Confidence'
}
</script>

<style lang="postcss" scoped>
/* Size variants */
.ai-confidence-score[data-size="sm"] .h-2 {
  @apply h-1;
}

.ai-confidence-score[data-size="lg"] .h-2 {
  @apply h-3;
}

/* Smooth animations */
.transition-all {
  transition-property: width, background-color;
}
</style>
