<script setup lang="ts">
defineProps<{
    label: string
    value: string | number
    tag?: string
    tagVariant?: 'neutral' | 'info' | 'danger' | 'warning' | 'success' | 'dark'
    subtext?: string
    progress?: number
    iconBg?: string
    iconColor?: string
}>()
</script>

<template>
    <div class="bg-surface border border-border rounded-xl p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="w-11 h-11 rounded-lg flex items-center justify-center" :class="iconBg || 'bg-accent/10'">
                <slot name="icon" />
            </div>
            <BaseBadge v-if="tag" :variant="tagVariant">{{ tag }}</BaseBadge>
        </div>

        <div class="text-xs font-bold tracking-wide uppercase text-text/50 mb-1">{{ label }}</div>
        <div class="text-3xl font-bold text-text mb-1">{{ value }}</div>
        <div v-if="subtext" class="text-sm text-text/60">{{ subtext }}</div>

        <div v-if="progress !== undefined" class="mt-3 h-1.5 rounded-full bg-bg overflow-hidden">
            <div class="h-full bg-accent rounded-full transition-all duration-500" :style="{ width: `${progress}%` }" />
        </div>
    </div>
</template>

<script lang="ts">
import BaseBadge from './BaseBadge.vue'
export default { components: { BaseBadge } }
</script>
