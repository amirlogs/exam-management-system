<script setup lang="ts">
import { useRoute } from 'vue-router';
import AppSidebar from './partials/AppSidebar.vue';
import AppTopbar from './partials/AppTopbar.vue';
import WorkspaceSwitcherDropdown from './partials/WorkspaceSwitcherDropdown.vue';
import { useAuthStore } from '@/stores/auth';
import { useNavigationStore } from '@/stores/navigation';

const route = useRoute();
const authStore = useAuthStore();
const navgationStore = useNavigationStore();
</script>
<template>
  <div class="flex min-h-screen bg-bg">
    <AppSidebar brand-name="University Exam" brand-subtitle="Management Portal" :items="navgationStore.items" :active-to="route.path" />
    <div class="flex-1 flex flex-col min-w-0">
      <AppTopbar :user-name="authStore.user?.first_name" :user-avatar="authStore.user?.avatar">
        <template #workspace-switcher>
          <WorkspaceSwitcherDropdown />
        </template>
      </AppTopbar>
      <main class="p-8 flex-1">
        <router-view :key="$route.fullPath" />
      </main>
    </div>
  </div>
</template>
