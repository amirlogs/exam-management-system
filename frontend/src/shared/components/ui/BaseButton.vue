<script setup lang="ts">
withDefaults(defineProps<{
    variant?: 'primary' | 'secondary' | 'ghost'
    block?: boolean
}>(), {
    variant: 'primary',
    block: false
})
</script>

<template>
    <button class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-md text-sm font-semibold
           transition-colors disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer " :class="[
            block ? 'w-full' : '',
            variant === 'primary' && 'bg-accent text-white hover:bg-accent-hover',
            variant === 'secondary' && 'bg-surface border border-border text-text hover:border-accent',
            variant === 'ghost' && 'text-accent hover:bg-accent/5'
        ]">
        <slot name="icon" />
        <slot />
    </button>
</template>
