<script setup lang="ts">
import BaseDropdown from './BaseDropdown.vue'

defineProps<{
    modelValue: string
    options: { value: string; label: string }[]
    label?: string
    placeholder?: string
    error?: string
}>()
defineEmits<{ (e: 'update:modelValue', value: string): void }>()
</script>

<template>
    <div>
        <label v-if="label" class="block text-xs font-bold tracking-wide uppercase text-text/70 mb-2">{{ label
            }}</label>
        <BaseDropdown :model-value="modelValue" :options="options" :placeholder="placeholder"
            @update:model-value="$emit('update:modelValue', $event)" />
        <p v-if="error" class="mt-1 text-xs text-error">{{ error }}</p>
    </div>
</template>
