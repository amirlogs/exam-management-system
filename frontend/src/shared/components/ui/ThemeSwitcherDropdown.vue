<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { Palette, Moon, Sun, Check } from 'lucide-vue-next'
import { useThemeStore } from '@/stores/theme'

const themeStore = useThemeStore()
const isOpen = ref(false)
const el = ref<HTMLElement | null>(null)

const themes = [
    { id: 'oxford', label: 'Oxford', swatch: '#7A2731' },
    { id: 'forest', label: 'Forest', swatch: '#1B3A2B' },
    { id: 'slate', label: 'Slate', swatch: '#C17817' },
]

function handleClickOutside(e: MouseEvent) {
    if (el.value && !el.value.contains(e.target as Node)) isOpen.value = false
}
onMounted(() => document.addEventListener('click', handleClickOutside))
onUnmounted(() => document.removeEventListener('click', handleClickOutside))
</script>

<template>
    <div ref="el" class="relative">
        <button type="button" @click="isOpen = !isOpen"
            class="w-9 h-9 rounded-full flex items-center justify-center hover:bg-bg transition-colors" title="Theme">
            <Palette class="w-[18px] h-[18px] text-text/60" />
        </button>

        <div v-if="isOpen"
            class="absolute right-0 mt-2 w-52 bg-surface border border-border rounded-lg shadow-lg py-1 z-20">
            <div class="px-3 pt-2 pb-1 text-[11px] font-bold uppercase tracking-wide text-text/40">Theme</div>
            <button v-for="t in themes" :key="t.id" @click="themeStore.setTheme(t.id)"
                class="w-full flex items-center gap-2.5 px-3 py-2 text-sm text-left hover:bg-bg transition-colors">
                <span class="w-3 h-3 rounded-full border border-border/50" :style="{ backgroundColor: t.swatch }" />
                <span class="flex-1" :class="themeStore.theme === t.id ? 'font-semibold text-text' : 'text-text/70'">{{
                    t.label }}</span>
                <Check v-if="themeStore.theme === t.id" class="w-3.5 h-3.5 text-accent" />
            </button>

            <div class="border-t border-border mt-1 pt-1">
                <button @click="themeStore.toggleDark(); isOpen = false"
                    class="w-full flex items-center gap-2.5 px-3 py-2 text-sm text-left hover:bg-bg transition-colors text-text/70">
                    <Sun v-if="themeStore.isDark" class="w-3.5 h-3.5" />
                    <Moon v-else class="w-3.5 h-3.5" />
                    {{ themeStore.isDark ? 'Switch to light' : 'Switch to dark' }}
                </button>
            </div>
        </div>
    </div>
</template>
