<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { ChevronDown, User, LogOut, Settings } from 'lucide-vue-next';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

defineProps<{
  userName?: string;
  userAvatar?: string;
}>();

const router = useRouter();
const authStore = useAuthStore();
const isOpen = ref(false);
const el = ref<HTMLElement | null>(null);

function initials(name?: string) {
  if (!name) return '?';
  return name
    .split(' ')
    .map((n) => n[0])
    .join('')
    .slice(0, 2)
    .toUpperCase();
}

async function handleLogout() {
  isOpen.value = false;
  await authStore.logout();
  router.push('/login');
}

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
      class="h-9 pl-2 pr-2.5 rounded-xl border border-border bg-surface hover:bg-bg hover:border-accent/40 shadow-2xs flex items-center gap-2.5 transition-all cursor-pointer group active:scale-98"
      :class="{ 'border-accent/50 bg-bg': isOpen }">
      <!-- Avatar -->
      <img
        v-if="userAvatar"
        :src="userAvatar"
        class="w-6.5 h-6.5 rounded-lg object-cover border border-border"
        alt="Avatar" />
      <div
        v-else
        class="w-6.5 h-6.5 rounded-lg bg-accent/10 border border-accent/20 flex items-center justify-center text-accent text-[11px] font-mono font-bold">
        {{ initials(userName) }}
      </div>

      <!-- User name -->
      <span class="text-xs font-semibold text-text max-w-32 truncate hidden sm:inline-block">
        {{ userName || 'User' }}
      </span>

      <!-- Chevron -->
      <ChevronDown
        class="w-3.5 h-3.5 text-text/40 group-hover:text-text transition-transform duration-200"
        :class="{ 'rotate-180 text-accent': isOpen }" />
    </button>

    <Transition
      enter-active-class="transition duration-150 ease-out"
      enter-from-class="transform scale-95 opacity-0 -translate-y-1"
      enter-to-class="transform scale-100 opacity-100 translate-y-0"
      leave-active-class="transition duration-100 ease-in"
      leave-from-class="transform scale-100 opacity-100 translate-y-0"
      leave-to-class="transform scale-95 opacity-0 -translate-y-1">
      <div
        v-if="isOpen"
        class="absolute right-0 mt-2 w-54 bg-surface border border-border rounded-2xl shadow-xl p-1.5 z-30">
        <!-- Header -->
        <div class="px-2.5 py-2 border-b border-border/50 mb-1">
          <div class="text-xs font-semibold text-text truncate">{{ userName || 'User' }}</div>
          <div class="text-[10px] font-mono text-text/50">Signed in</div>
        </div>

        <div class="space-y-0.5">
          <router-link
            to="/profile"
            @click="isOpen = false"
            class="flex items-center gap-2 px-2.5 py-2 rounded-xl text-xs font-medium text-text/80 hover:text-text hover:bg-bg transition-all">
            <User class="w-3.5 h-3.5 text-text/50" />
            <span>Profile</span>
          </router-link>

          <router-link
            to="/settings"
            @click="isOpen = false"
            class="flex items-center gap-2 px-2.5 py-2 rounded-xl text-xs font-medium text-text/80 hover:text-text hover:bg-bg transition-all">
            <Settings class="w-3.5 h-3.5 text-text/50" />
            <span>Settings</span>
          </router-link>
        </div>

        <div class="border-t border-border/50 mt-1 pt-1">
          <button
            type="button"
            @click="handleLogout"
            class="w-full flex items-center gap-2 px-2.5 py-2 rounded-xl text-xs font-medium text-error hover:bg-error/10 transition-all text-left cursor-pointer">
            <LogOut class="w-3.5 h-3.5" />
            <span>Log out</span>
          </button>
        </div>
      </div>
    </Transition>
  </div>
</template>
