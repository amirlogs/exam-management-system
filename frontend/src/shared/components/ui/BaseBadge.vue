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
        'bg-blue-50 text-blue-700': variant === 'info',
        'bg-red-50 text-red-600': variant === 'danger',
        'bg-amber-50 text-amber-700': variant === 'warning',
        'bg-emerald-50 text-emerald-700': variant === 'success',
        'bg-gray-900 text-white': variant === 'dark',
    }">
        <slot />
    </span>
</template>
