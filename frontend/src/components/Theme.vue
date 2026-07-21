<script setup>
import { useThemeStore } from '@/stores/theme'
const themeStore = useThemeStore()
</script>

<template>
    <button @click="themeStore.setTheme('forest')">Forest theme</button>
    <button @click="themeStore.toggleDark">
        {{ themeStore.isDark ? '☀️' : '🌙' }}
    </button>
</template>
