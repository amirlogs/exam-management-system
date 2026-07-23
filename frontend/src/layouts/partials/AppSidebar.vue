<script setup lang="ts">
import { Settings, LifeBuoy } from 'lucide-vue-next'
import type { Component } from 'vue'

defineProps<{
    brandName: string
    brandSubtitle: string
    items: { label: string; to: string; icon: Component }[]
    activeTo: string
}>()
</script>

<template>
    <aside class="w-66 shrink-0 bg-surface border-r border-border min-h-screen md:max-h-screen sticky top-0  flex flex-col justify-between">
        <div class="p-5">
            <div class="mb-8 px-2">
                <h1 class="text-lg font-bold text-accent leading-tight">{{ brandName }}</h1>
                <p class="text-[11px] font-bold tracking-wider uppercase text-text/40 mt-0.5">{{ brandSubtitle }}</p>
            </div>

            <nav class="space-y-0.5">
                <router-link v-for="item in items" :key="item.to" :to="item.to"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors" :class="activeTo.startsWith(item.to)
                        ? 'bg-accent/10 text-accent'
                        : 'text-text/60 hover:bg-bg hover:text-text'">
                    <component :is="item.icon" class="w-4.5 h-4.5 shrink-0" />
                    {{ item.label }}
                </router-link>
            </nav>
        </div>

        <div class="p-5 pt-3 border-t border-border space-y-0.5">
            <router-link to="/settings"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-text/60 hover:bg-bg hover:text-text transition-colors">
                <Settings class="w-4.5 h-4.5" />
                Settings
            </router-link>
            <router-link to="/support"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-text/60 hover:bg-bg hover:text-text transition-colors">
                <LifeBuoy class="w-4.5 h-4.5" />
                Support
            </router-link>
        </div>
    </aside>
</template>
