<script setup lang="ts">
import ToastContainer from '@/shared/components/ui/ToastContainer.vue';
import GlobalLoadingBar from '@/shared/components/ui/GlobalLoadingBar.vue';
import { useUiStore } from '@/stores/ui';
import { useAuthStore } from '@/stores/auth';

const uiStore = useUiStore();
const authStore = useAuthStore();
</script>

<template>
  <div id="app">
    <div v-if="authStore.isInitializing" class="min-h-screen flex items-center justify-center bg-bg">
      <span class="text-text/50 text-sm">Loading…</span>
    </div>

    <div v-else>
      <router-view />
      <ToastContainer />
      <GlobalLoadingBar v-if="uiStore.isLoading" />
    </div>
  </div>
</template>
