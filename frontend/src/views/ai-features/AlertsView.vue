<template>
  <AppLayout>
    <div class="alerts-container">
      <div class="page-header mb-8">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 flex items-center">
            <ExclamationTriangleIcon class="w-8 h-8 mr-3 text-yellow-500" />
            AI Alerts
          </h1>
          <p class="mt-2 text-sm text-gray-700">
            Proactive clinical alerts and safety notifications
          </p>
        </div>
      </div>

      <ComingSoonCard
        title="Intelligent Clinical Alerts"
        description="Advanced AI monitoring system that provides real-time alerts for drug interactions, critical vital signs, and potential health risks."
        :features="alertFeatures"
        phase="Phase 3"
      />
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline'
import AppLayout from '@/components/common/AppLayout.vue'
import ComingSoonCard from '@/components/common/ComingSoonCard.vue'

const alertFeatures = [
  'Drug interaction warnings',
  'Allergy conflict detection',
  'Critical vital sign alerts',
  'Appointment delay notifications',
  'Medication adherence tracking',
  'Custom alert rules'
]
</script>

<style scoped>
.alerts-container {
  @apply max-w-6xl mx-auto;
}
</style>
