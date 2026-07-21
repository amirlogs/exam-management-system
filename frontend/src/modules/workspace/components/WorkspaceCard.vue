<script setup lang="ts">
import { ArrowRight } from 'lucide-vue-next'
import BaseButton from '@/shared/components/ui/BaseButton.vue'

defineProps<{
    title: string
    description: string
    isDefault: boolean
}>()
defineEmits<{
    (e: 'update:isDefault', value: boolean): void
    (e: 'enter'): void
}>()
</script>

<template>
    <div class="w-full max-w-90 bg-surface border border-border rounded-xl p-6 flex flex-col">
        <div class="w-11 h-11 rounded-lg bg-accent/10 flex items-center justify-center mb-5 text-accent">
            <slot name="icon" />
        </div>

        <h3 class="text-xl font-bold text-text mb-2">{{ title }}</h3>
        <p class="text-text/60 text-sm flex-1">{{ description }}</p>

        <hr class="border-border my-5" />

        <label class="flex items-center gap-2 mb-4 cursor-pointer select-none">
            <input type="checkbox" :checked="isDefault"
                @change="$emit('update:isDefault', ($event.target as HTMLInputElement).checked)"
                class="w-4 h-4 rounded border-border text-accent focus:ring-accent" />
            <span class="text-sm text-text/70">Set as default workspace</span>
        </label>

        <BaseButton variant="primary" block @click="$emit('enter')">
            Enter Workspace
            <template #icon>
                <ArrowRight class="w-4 h-4" />
            </template>
        </BaseButton>
    </div>
</template>
