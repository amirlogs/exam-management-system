<script setup lang="ts">
defineProps<{
    modelValue: string
    label: string
    type?: string
    placeholder?: string
    error?: string
}>()
defineEmits(['update:modelValue'])
</script>

<template>
    <div>
        <label class="block text-xs font-bold tracking-wide uppercase text-text/70 mb-2">
            {{ label }}
        </label>
        <input :type="type || 'text'" :value="modelValue" :placeholder="placeholder"
            @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)" class="w-full px-4 py-3 rounded-md border bg-bg text-text placeholder:text-text/40
             focus:outline-none focus:border-accent transition-colors"
            :class="error ? 'border-red-400' : 'border-border'" />
    </div>
</template>
