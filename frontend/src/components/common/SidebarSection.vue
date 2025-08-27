<template>
  <div v-if="!collapsed" class="sidebar-section">
    <div class="px-3 pb-2">
      <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">
        {{ title }}
      </h3>
    </div>
    <div class="space-y-1">
      <slot></slot>
    </div>
  </div>
  <div v-else class="space-y-1">
    <slot></slot>
  </div>
</template>

<script setup lang="ts">
interface Props {
  title: string
  collapsed?: boolean
}

defineProps<Props>()
</script>

<style lang="postcss" scoped>
.sidebar-section {
  @apply mb-6;
}
</style>
