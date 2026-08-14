<script setup lang="ts">
withDefaults(defineProps<{
    variant?: 'primary' | 'secondary' | 'danger' | 'ghost'
    block?: boolean
    loading?: boolean
    disabled?: boolean
    type?: 'button' | 'submit'
}>(), { variant: 'primary', block: false, loading: false, disabled: false, type: 'button' })
</script>

<template>
    <button
        :type="type"
        :disabled="disabled || loading"
        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-md text-sm font-semibold transition-colors disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
        :class="[
            block ? 'w-full' : '',
            variant === 'primary' && 'bg-accent text-white hover:bg-accent-hover',
            variant === 'secondary' && 'bg-surface border border-border text-text hover:border-accent',
            variant === 'danger' && 'bg-error text-white hover:bg-error-hover',
            variant === 'ghost' && 'text-accent hover:bg-accent/5',
        ]"
    >
        <span v-if="loading" class="h-3.5 w-3.5 animate-spin rounded-full border-2 border-current border-t-transparent" />
        <slot v-else name="icon" />
        <slot />
    </button>
</template>