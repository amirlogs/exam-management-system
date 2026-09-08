<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { Palette, Moon, Sun, Check } from 'lucide-vue-next';
import { useThemeStore } from '@/stores/theme';

const themeStore = useThemeStore();
const isOpen = ref(false);
const el = ref<HTMLElement | null>(null);

const themes = [
  { id: 'oxford', label: 'Oxford', swatch: '#7A2731' },
  { id: 'forest', label: 'Forest', swatch: '#1B3A2B' },
  { id: 'slate', label: 'Slate', swatch: '#C17817' },
];

function handleClickOutside(e: MouseEvent) {
  if (el.value && !el.value.contains(e.target as Node)) isOpen.value = false;
}
onMounted(() => document.addEventListener('click', handleClickOutside));
onUnmounted(() => document.removeEventListener('click', handleClickOutside));
</script>

<template>
  <div ref="el" class="relative">
    <button
      type="button"
      @click="isOpen = !isOpen"
      class="w-9 h-9 rounded-xl border border-border bg-surface hover:bg-bg hover:border-accent/40 shadow-2xs flex items-center justify-center transition-all cursor-pointer group active:scale-95"
      :class="{ 'border-accent/50 bg-bg': isOpen }"
      title="Theme & Appearance">
      <Palette class="w-4 h-4 text-text/60 group-hover:text-text transition-colors" />
    </button>

    <Transition
      enter-active-class="transition duration-150 ease-out"
      enter-from-class="transform scale-95 opacity-0 -translate-y-1"
      enter-to-class="transform scale-100 opacity-100 translate-y-0"
      leave-active-class="transition duration-100 ease-in"
      leave-from-class="transform scale-100 opacity-100 translate-y-0"
      leave-to-class="transform scale-95 opacity-0 -translate-y-1">
      <div v-if="isOpen" class="absolute right-0 mt-2 w-52 bg-surface border border-border rounded-2xl shadow-xl p-1.5 z-30">
        <div class="px-2.5 py-1.5 text-[10px] font-mono font-semibold uppercase tracking-wider text-text/40">Color Theme</div>
        <div class="space-y-0.5">
          <button
            v-for="t in themes"
            :key="t.id"
            type="button"
            @click="themeStore.setTheme(t.id)"
            class="w-full flex items-center gap-2.5 px-2.5 py-2 rounded-xl text-xs font-medium text-left hover:bg-bg transition-all cursor-pointer"
            :class="themeStore.theme === t.id ? 'bg-accent/10 font-semibold text-accent' : 'text-text'">
            <span class="w-3 h-3 rounded-md border border-border/50 shrink-0" :style="{ backgroundColor: t.swatch }" />
            <span class="flex-1">{{ t.label }}</span>
            <Check v-if="themeStore.theme === t.id" class="w-3.5 h-3.5 text-accent" />
          </button>
        </div>

        <div class="border-t border-border/50 my-1 pt-1">
          <button
            type="button"
            @click="
              themeStore.toggleDark();
              isOpen = false;
            "
            class="w-full flex items-center gap-2.5 px-2.5 py-2 rounded-xl text-xs font-medium text-left hover:bg-bg transition-all cursor-pointer text-text/80 hover:text-text">
            <Sun v-if="themeStore.isDark" class="w-3.5 h-3.5 text-amber-500" />
            <Moon v-else class="w-3.5 h-3.5 text-indigo-400" />
            <span>{{ themeStore.isDark ? 'Switch to light mode' : 'Switch to dark mode' }}</span>
          </button>
        </div>
      </div>
    </Transition>
  </div>
</template>
