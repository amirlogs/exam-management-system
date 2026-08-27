<script setup lang="ts">
withDefaults(
  defineProps<{
    variant?: 'error' | 'success' | 'info';
  }>(),
  { variant: 'info' },
);
</script>

<template>
  <div
    class="flex items-center gap-2 px-4 py-3 rounded-md text-sm"
    :class="{
      'bg-red-50 text-red-600': variant === 'error',
      'bg-emerald-50 text-emerald-700': variant === 'success',
      'bg-blue-50 text-blue-700': variant === 'info',
    }">
    <slot name="icon" />
    <span>
      <slot />
    </span>
  </div>
</template>
