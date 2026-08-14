<script setup lang="ts">
withDefaults(defineProps<{
    variant?: 'neutral' | 'info' | 'danger' | 'warning' | 'success' | 'dark'
}>(), {
    variant: 'neutral'
})
</script>

<template>
    <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-semibold tracking-wide uppercase" :class="{
        'bg-gray-100 text-gray-600': variant === 'neutral',
        'bg-accent/10 text-accent': variant === 'info',
        'bg-error/10 text-error': variant === 'danger',
        'bg-warning/10 text-warning': variant === 'warning',
        'bg-success/10 text-success': variant === 'success',
        'bg-gray-900 text-white': variant === 'dark',
    }">
        <slot />
    </span>
</template>
