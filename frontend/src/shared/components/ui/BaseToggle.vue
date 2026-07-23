<script setup lang="ts">
defineProps<{ modelValue: boolean; label?: string }>()
defineEmits<{ (e: 'update:modelValue', value: boolean): void }>()
</script>

<template>
    <label class="inline-flex items-center gap-2 cursor-pointer select-none">
        <span v-if="label" class="text-sm font-medium text-text/70">{{ label }}</span>
        <button type="button" role="switch" :aria-checked="modelValue" @click="$emit('update:modelValue', !modelValue)"
            class="relative w-10 h-6 rounded-full transition-colors shrink-0"
            :class="modelValue ? 'bg-accent' : 'bg-border'">
            <span class="absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-white shadow-sm transition-transform"
                :class="modelValue ? 'translate-x-4' : 'translate-x-0'" />
        </button>
    </label>
</template>
