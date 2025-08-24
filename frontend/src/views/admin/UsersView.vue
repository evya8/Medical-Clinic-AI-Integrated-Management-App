<template>
  <AppLayout>
    <div class="users-container">
      <div class="page-header mb-8">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 flex items-center">
            <UserGroupIcon class="w-8 h-8 mr-3 text-indigo-500" />
            User Management
          </h1>
          <p class="mt-2 text-sm text-gray-700">
            Manage clinic staff, doctors, and system users
          </p>
        </div>
      </div>

      <ComingSoonCard
        title="Comprehensive User Management"
        description="Complete user administration system with role-based access control, permissions management, and staff scheduling integration."
        :features="userFeatures"
        phase="Phase 4"
      />
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { UserGroupIcon } from '@heroicons/vue/24/outline'
import AppLayout from '@/components/common/AppLayout.vue'
import ComingSoonCard from '@/components/common/ComingSoonCard.vue'

const userFeatures = [
  'Role-based access control',
  'Staff scheduling integration',
  'Permission management',
  'User audit trails',
  'Single sign-on support',
  'Multi-factor authentication'
]
</script>

<style scoped>
.users-container {
  @apply max-w-6xl mx-auto;
}
</style>
