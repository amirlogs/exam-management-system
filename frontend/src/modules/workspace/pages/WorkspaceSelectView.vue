<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Landmark, ShieldUser, BookOpen, ClipboardList, ArrowRight } from 'lucide-vue-next';
import BaseButton from '@/shared/components/ui/BaseButton.vue';
import { usePermissionsStore } from '@/stores/permission';
import { useAuthStore } from '@/stores/auth';
import { useRouter } from 'vue-router';
import type { WorkspaceState } from '@/modules/instructor/pages/question-bank/types';

const permissionsStore = usePermissionsStore();
const authStore = useAuthStore();
const router = useRouter();

const year = new Date().getFullYear();
const isEntering = ref<string | null>(null);

const currentWorkspace = computed<WorkspaceState>(() => {
  const state = window.history.state;
  if (state && state.workspace) {
    return state.workspace;
  }
  return permissionsStore.workspaces || { admin: false, instructor: false, student: false };
});

const roles: (keyof WorkspaceState)[] = ['admin', 'instructor', 'student'];

const availableRoles = computed(() => {
  return roles.filter((role) => currentWorkspace.value[role]);
});

onMounted(async () => {
  if (!permissionsStore.workspaces) {
    await authStore.initializeAuth(router);
  }
});

const workspaces = {
  admin: {
    id: 'admin',
    title: 'Administration',
    subtitle: 'System & Institutional Management',
    description: 'System oversight, academic structure, curricula management, and institutional audit logs.',
    icon: ShieldUser,
    iconBg: 'bg-accent/10 border border-accent/20',
    iconColor: 'text-accent',
  },
  instructor: {
    id: 'instructor',
    title: 'Teaching',
    subtitle: 'Courses & Examination Control',
    description: 'Course offerings, question bank authoring, exam composition, and submission grading.',
    icon: BookOpen,
    iconBg: 'bg-blue-500/10 border border-blue-500/20',
    iconColor: 'text-blue-600 dark:text-blue-400',
  },
  student: {
    id: 'student',
    title: 'My Studies',
    subtitle: 'Assessments & Academic Records',
    description: 'Scheduled exam access, real-time exam taking sessions, and published academic results.',
    icon: ClipboardList,
    iconBg: 'bg-emerald-500/10 border border-emerald-500/20',
    iconColor: 'text-emerald-600 dark:text-emerald-400',
  },
};

async function enterWorkspace(role: keyof WorkspaceState) {
  if (isEntering.value) return;
  isEntering.value = role;
  try {
    await permissionsStore.setActiveWorkspace(role);
    router.push(`/${role}/dashboard`);
  } finally {
    isEntering.value = null;
  }
}
</script>

<template>
  <div class="w-full max-w-6xl mx-auto flex flex-col items-center justify-between min-h-[calc(100vh-4rem)] py-6 sm:py-10 px-2 sm:px-4">
    <!-- Header Section -->
    <div class="text-center mb-8 sm:mb-12 max-w-2xl mx-auto">
      <div class="w-14 h-14 sm:w-16 sm:h-16 mx-auto mb-4 rounded-2xl bg-accent/10 border border-accent/20 flex items-center justify-center shadow-xs">
        <Landmark class="w-7 h-7 sm:w-8 sm:h-8 text-accent" />
      </div>
      <h1 class="text-2xl sm:text-3xl font-display font-bold text-text tracking-tight">
        Select Workspace
      </h1>
      <p class="text-xs sm:text-sm text-text/60 mt-2.5 max-w-lg mx-auto leading-relaxed">
        Choose your workspace to begin. The system will remember your choice and automatically resume it for future sessions, and you can switch anytime from the top navigation bar.
      </p>
    </div>

    <!-- Cards Side-by-Side Responsive Grid -->
    <div
      class="w-full grid gap-6"
      :class="[
        availableRoles.length === 1 ? 'max-w-md mx-auto grid-cols-1' :
        availableRoles.length === 2 ? 'max-w-3xl mx-auto grid-cols-1 md:grid-cols-2' :
        'max-w-5xl mx-auto grid-cols-1 md:grid-cols-3'
      ]"
    >
      <div
        v-for="role in availableRoles"
        :key="role"
        @click="enterWorkspace(role)"
        class="group relative bg-surface border border-border hover:border-accent/40 rounded-2xl p-6 sm:p-7 flex flex-col justify-between shadow-xs hover:shadow-lg hover:-translate-y-1 transition-all duration-200 cursor-pointer select-none"
      >
        <!-- Top Row & Content -->
        <div>
          <!-- Icon -->
          <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-5 transition-transform group-hover:scale-105 duration-200" :class="workspaces[role].iconBg">
            <component :is="workspaces[role].icon" class="w-6 h-6" :class="workspaces[role].iconColor" />
          </div>

          <!-- Title & Subtitle -->
          <h3 class="text-lg sm:text-xl font-display font-bold text-text group-hover:text-accent transition-colors">
            {{ workspaces[role].title }}
          </h3>
          <p class="text-[11px] font-mono text-text/45 mt-0.5 mb-2.5">
            {{ workspaces[role].subtitle }}
          </p>

          <!-- Description -->
          <p class="text-xs sm:text-sm text-text/60 leading-relaxed mb-6">
            {{ workspaces[role].description }}
          </p>
        </div>

        <!-- Bottom Button -->
        <div class="pt-4 border-t border-border/60">
          <BaseButton
            variant="primary"
            block
            :loading="isEntering === role"
            @click.stop="enterWorkspace(role)"
            class="group-hover:shadow-sm"
          >
            <span>Enter Workspace</span>
            <template #icon>
              <ArrowRight class="w-4 h-4 transition-transform group-hover:translate-x-1" />
            </template>
          </BaseButton>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <footer class="w-full max-w-md mx-auto mt-12 sm:mt-16 pt-6 border-t border-border/60 text-center text-xs text-text/45">
      <p>University Exam Management System © {{ year }}</p>
      <div class="flex items-center justify-center gap-2 mt-1.5 font-mono text-[11px] text-text/40">
        <span>Institutional Access</span>
        <span class="w-1 h-1 rounded-full bg-border" />
        <span>Multi-Workspace Portal</span>
      </div>
    </footer>
  </div>
</template>
