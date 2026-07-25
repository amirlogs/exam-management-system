<script setup lang="ts">
import { Megaphone } from 'lucide-vue-next'

defineProps<{
    items: { dot: string; title: string; detail: string; time: string }[]
}>()
</script>

<template>
    <div class="bg-surface border border-border rounded-lg p-6">
        <div class="flex items-center justify-between mb-5">
            <h2 class="text-xl font-bold text-text">Integrity Feed</h2>
            <div class="w-9 h-9 rounded-full bg-accent/10 flex items-center justify-center">
                <Megaphone class="w-4 h-4 text-accent" />
            </div>
        </div>

        <div class="space-y-5">
            <div v-for="(item, i) in items" :key="i" class="flex gap-3">
                <span class="w-2 h-2 rounded-full mt-1.5 shrink-0" :class="item.dot" />
                <div>
                    <div class="text-sm font-semibold text-text">{{ item.title }}</div>
                    <p class="text-sm text-text/60 mt-0.5 leading-relaxed">{{ item.detail }}</p>
                    <div class="text-xs text-text/40 mt-1.5">{{ item.time }}</div>
                </div>
            </div>
        </div>
    </div>
</template>
