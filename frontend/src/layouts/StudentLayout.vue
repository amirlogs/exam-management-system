<script setup lang="ts">
import { useRoute } from 'vue-router';
import { LayoutGrid, FileText, Award } from 'lucide-vue-next';
import AppSidebar from './partials/AppSidebar.vue';
import AppTopbar from './partials/AppTopbar.vue';
import WorkspaceSwitcherDropdown from './partials/WorkspaceSwitcherDropdown.vue';
import { useAuthStore } from '@/stores/auth';

const route = useRoute();
const authStore = useAuthStore();

const navItems = [
  { label: 'Dashboard', to: '/student/dashboard', icon: LayoutGrid },
  { label: 'My Exams', to: '/student/exams', icon: FileText },
  { label: 'My Results', to: '/student/results', icon: Award },
];
</script>

<template>
  <div class="flex min-h-screen bg-bg">
    <AppSidebar brand-name="University Exam" brand-subtitle="Student Portal" :items="navItems" :active-to="route.path" />
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
