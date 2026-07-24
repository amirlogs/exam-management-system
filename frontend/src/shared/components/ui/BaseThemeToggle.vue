<script setup lang="ts">
import { Moon, Sun } from 'lucide-vue-next'
import { useThemeStore } from '@/stores/theme'

const themeStore = useThemeStore()
</script>

<template>
    <button type="button" @click="themeStore.toggleDark"
        class="w-9 h-9 rounded-full flex items-center justify-center hover:bg-bg transition-colors"
        :title="themeStore.isDark ? 'Switch to light mode' : 'Switch to dark mode'">
        <Sun v-if="themeStore.isDark" class="w-[18px] h-[18px] text-text/60" />
        <Moon v-else class="w-[18px] h-[18px] text-text/60" />
    </button>
</template>
