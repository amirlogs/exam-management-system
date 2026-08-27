<script setup lang="ts">
defineProps<{
  icon?: string; // pass an icon component via slot instead if preferred
  label: string;
  value: string | number;
  tag?: string;
  tagVariant?: 'neutral' | 'info' | 'danger' | 'warning' | 'success' | 'dark';
  subtext?: string;
  progress?: number; // 0-100, optional progress bar
}>();
</script>

<template>
  <BaseCard>
    <div class="flex items-center justify-between mb-4">
      <slot name="icon" />
      <BaseBadge v-if="tag" :variant="tagVariant">{{ tag }}</BaseBadge>
    </div>

    <div class="text-xs font-bold tracking-wide uppercase text-text/60 mb-1">{{ label }}</div>
    <div class="text-4xl font-bold text-text mb-1">{{ value }}</div>
    <div v-if="subtext" class="text-sm text-text/60">{{ subtext }}</div>

    <div v-if="progress !== undefined" class="mt-3 h-1.5 rounded-full bg-border overflow-hidden">
      <div class="h-full bg-accent rounded-full" :style="{ width: `${progress}%` }" />
    </div>
  </BaseCard>
</template>

<script lang="ts">
import BaseCard from './BaseCard.vue';
import BaseBadge from './BaseBadge.vue';
export default { components: { BaseCard, BaseBadge } };
</script>
