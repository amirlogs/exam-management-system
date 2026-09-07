<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { Bell, Menu, Maximize, Minimize } from 'lucide-vue-next';
import ThemeSwitcherDropdown from '@/shared/components/ui/ThemeSwitcherDropdown.vue';
import UserMenuDropdown from '@/shared/components/ui/UserMenuDropdown.vue';
import { useSidebar } from '@/shared/composables/useSidebar';

defineProps<{
  userName?: string;
  userAvatar?: string;
}>();

const { toggleMobile } = useSidebar();

const isFullscreen = ref(false);

async function toggleFullscreen() {
  try {
    if (!document.fullscreenElement) {
      await document.documentElement.requestFullscreen();
    } else if (document.exitFullscreen) {
      await document.exitFullscreen();
    }
  } catch (error) {
    console.error('Fullscreen toggle error:', error);
  }
}

function handleFullscreenChange() {
  isFullscreen.value = !!document.fullscreenElement;
}

onMounted(() => {
  document.addEventListener('fullscreenchange', handleFullscreenChange);
});

onUnmounted(() => {
  document.removeEventListener('fullscreenchange', handleFullscreenChange);
});
</script>

<template>
  <header class="h-17 px-3 sm:px-4 md:pl-6 md:pr-4 flex items-center justify-between border-b border-border bg-surface/95 backdrop-blur-sm sticky top-0 z-20 shrink-0">
    <div class="flex items-center gap-2 sm:gap-3 shrink-0">
      <button
        type="button"
        class="md:hidden w-8.5 h-8.5 sm:w-9 sm:h-9 rounded-xl border border-border bg-surface hover:bg-bg hover:border-accent/40 shadow-2xs flex items-center justify-center text-text/70 hover:text-text transition-all cursor-pointer active:scale-95 shrink-0"
        @click="toggleMobile"
        aria-label="Toggle navigation menu">
        <Menu class="w-4.5 h-4.5" />
      </button>
      <slot name="left" />
    </div>

    <div class="flex items-center gap-1.5 sm:gap-2.5 shrink-0">
      <ThemeSwitcherDropdown class="shrink-0" />

      <!-- Fullscreen Toggle Button -->
      <button
        type="button"
        @click="toggleFullscreen"
        class="w-8.5 h-8.5 sm:w-9 sm:h-9 rounded-xl border border-border bg-surface hover:bg-bg hover:border-accent/40 shadow-2xs flex items-center justify-center text-text/70 hover:text-text transition-all cursor-pointer active:scale-95 shrink-0"
        :title="isFullscreen ? 'Exit full screen' : 'Full screen'"
        :aria-label="isFullscreen ? 'Exit full screen' : 'Full screen'">
        <Minimize v-if="isFullscreen" class="w-4 h-4 text-text/60 hover:text-text transition-colors" />
        <Maximize v-else class="w-4 h-4 text-text/60 hover:text-text transition-colors" />
      </button>

      <!-- Notifications Button -->
      <button
        type="button"
        class="relative w-8.5 h-8.5 sm:w-9 sm:h-9 rounded-xl border border-border bg-surface hover:bg-bg hover:border-accent/40 shadow-2xs flex items-center justify-center text-text/70 hover:text-text transition-all cursor-pointer active:scale-95 shrink-0"
        title="Notifications">
        <Bell class="w-4 h-4 text-text/60 hover:text-text transition-colors" />
        <span class="absolute top-2 right-2 w-1.5 h-1.5 bg-accent rounded-full" />
      </button>

      <slot name="workspace-switcher" />

      <div class="w-px h-5 bg-border/80 mx-0.5 shrink-0" />

      <UserMenuDropdown :user-name="userName" :user-avatar="userAvatar" class="shrink-0" />
    </div>
  </header>
</template>
