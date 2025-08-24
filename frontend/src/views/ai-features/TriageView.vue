<template>
  <AppLayout>
    <div class="triage-container">
      <!-- Page Header -->
      <div class="page-header mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900 flex items-center">
              <HeartIcon class="w-8 h-8 mr-3 text-red-500" />
              AI Triage Assistant
            </h1>
            <p class="mt-2 text-sm text-gray-700">
              Intelligent patient prioritization and risk assessment
            </p>
          </div>
        </div>
      </div>

      <!-- Coming Soon -->
      <ComingSoonCard
        title="AI Triage System"
        description="Advanced AI algorithms will analyze patient symptoms, vital signs, and medical history to automatically prioritize care based on urgency and risk factors."
        :features="triageFeatures"
        phase="Phase 3"
      />
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { HeartIcon } from '@heroicons/vue/24/outline'
import AppLayout from '@/components/common/AppLayout.vue'
import ComingSoonCard from '@/components/common/ComingSoonCard.vue'

const triageFeatures = [
  'Automated symptom analysis',
  'Risk factor assessment',
  'Priority scoring (1-5 scale)',
  'Specialist recommendations',
  'Red flag detection',
  'Real-time patient monitoring'
]
</script>

<style scoped>
.triage-container {
  @apply max-w-6xl mx-auto;
}
</style>
