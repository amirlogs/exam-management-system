<script setup lang="ts">
import { ref } from 'vue'
import BaseDropdown from '@/shared/components/ui/BaseDropdown.vue'

defineProps<{
    distribution: { grade: string; pct: number }[]
}>()

const selectedCourse = ref('cs401')
const courseOptions = [
    { value: 'cs401', label: 'CS401 - Data Structures' },
    { value: 'math202', label: 'MATH202 - Calculus II' },
    { value: 'hist101', label: 'HIST101 - World History' },
]
</script>

<template>
    <div class="bg-surface border border-border rounded-lg p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-text">Course Performance Distribution</h2>
            <BaseDropdown v-model="selectedCourse" :options="courseOptions" />
        </div>

        <div class="flex items-end gap-6 h-40">
            <div v-for="bar in distribution" :key="bar.grade"
                class="flex-1 flex flex-col items-center gap-2 h-full justify-end">
                <span class="text-xs font-semibold text-text/50">{{ bar.pct }}%</span>
                <div class="w-full bg-bg rounded-t-md flex items-end overflow-hidden" style="height: 100%">
                    <div class="w-full bg-accent rounded-t-md transition-all duration-500"
                        :style="{ height: `${bar.pct}%` }" />
                </div>
                <span class="text-sm font-semibold text-text/70">{{ bar.grade }}</span>
            </div>
        </div>
    </div>
</template>
