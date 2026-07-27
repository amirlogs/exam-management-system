<script setup lang="ts">
import { Bell, Menu } from 'lucide-vue-next'
import ThemeSwitcherDropdown from '@/shared/components/ui/ThemeSwitcherDropdown.vue'
import UserMenuDropdown from '@/shared/components/ui/UserMenuDropdown.vue'
import { useSidebar } from '@/shared/composables/useSidebar'

defineProps<{
    userName?: string
    userRole?: string
    userAvatar?: string
}>()

const { toggleMobile } = useSidebar()
</script>

<template>
    <header
        class="h-17 px-4  md:pl-6 md:pr-3 flex items-center justify-between border-b border-border bg-surface sticky top-0 z-20">
            
        <div class="flex items-center gap-3">
            <button class="md:hidden w-9 h-9 rounded-lg flex items-center justify-center hover:bg-bg"
                @click="toggleMobile">
                <Menu class="w-5 h-5 text-text/70" />
            </button>
            <slot name="left" />
        </div>

        <div class="flex items-center gap-2">
            <!-- AI Grading toggle — disabled for now, will re-enable later -->
            <!--
      <div class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-bg border border-border">
        <Sparkles class="w-4 h-4 text-accent" />
        <span class="text-xs font-bold uppercase tracking-wide text-text/70">AI Grading</span>
        <BaseToggle v-model="aiGradingEnabled" />
      </div>
      -->

            <ThemeSwitcherDropdown />

            <button
                class="w-9 h-9 rounded-full flex items-center justify-center hover:bg-bg transition-colors relative">
                <Bell class="w-4.5 h-4.5 text-text/60" />
                <span class="absolute top-2 right-2 w-1.5 h-1.5 bg-accent rounded-full" />
            </button>

            <slot name="workspace-switcher" />

            <div class="w-px h-6 bg-border" />

            <UserMenuDropdown :user-name="userName" :user-role="userRole" :user-avatar="userAvatar" />
        </div>
    </header>
</template>
