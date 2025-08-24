<template>
  <AppLayout>
    <div class="summaries-container">
      <div class="page-header mb-8">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 flex items-center">
            <DocumentTextIcon class="w-8 h-8 mr-3 text-blue-500" />
            AI Summaries
          </h1>
          <p class="mt-2 text-sm text-gray-700">
            Automated clinical documentation and appointment summaries
          </p>
        </div>
      </div>

      <ComingSoonCard
        title="AI-Generated Clinical Summaries"
        description="Automatically generate SOAP notes, billing summaries, and patient-friendly reports from appointment conversations and clinical data."
        :features="summaryFeatures"
        phase="Phase 3"
      />
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { DocumentTextIcon } from '@heroicons/vue/24/outline'
import AppLayout from '@/components/common/AppLayout.vue'
import ComingSoonCard from '@/components/common/ComingSoonCard.vue'

const summaryFeatures = [
  'SOAP note generation',
  'Billing code suggestions',
  'Patient-friendly summaries',
  'Multi-language support',
  'Template customization',
  'Voice-to-text integration'
]
</script>

<style scoped>
.summaries-container {
  @apply max-w-6xl mx-auto;
}
</style>
