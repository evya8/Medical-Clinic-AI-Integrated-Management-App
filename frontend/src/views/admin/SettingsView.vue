<template>
  <AppLayout>
    <div class="settings-container">
      <div class="page-header mb-8">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 flex items-center">
            <CogIcon class="w-8 h-8 mr-3 text-gray-600" />
            System Settings
          </h1>
          <p class="mt-2 text-sm text-gray-700">
            Configure clinic settings and system preferences
          </p>
        </div>
      </div>

      <ComingSoonCard
        title="Advanced System Configuration"
        description="Comprehensive settings panel for clinic configuration, system preferences, integrations, and customization options."
        :features="settingsFeatures"
        phase="Phase 4"
      />
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { CogIcon } from '@heroicons/vue/24/outline'
import AppLayout from '@/components/common/AppLayout.vue'
import ComingSoonCard from '@/components/common/ComingSoonCard.vue'

const settingsFeatures = [
  'Clinic profile management',
  'Email & SMS configuration',
  'Third-party integrations',
  'Backup & restore settings',
  'Security configurations',
  'Custom branding options'
]
</script>

<style lang="postcss" scoped>
.settings-container {
  @apply max-w-6xl mx-auto;
}
</style>
