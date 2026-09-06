<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { GraduationCap, Bell, Menu, X, LayoutDashboard, FileText, Award, Maximize, Minimize } from 'lucide-vue-next';
import ThemeSwitcherDropdown from '@/shared/components/ui/ThemeSwitcherDropdown.vue';
import UserMenuDropdown from '@/shared/components/ui/UserMenuDropdown.vue';
import WorkspaceSwitcherDropdown from './partials/WorkspaceSwitcherDropdown.vue';
import { useAuthStore } from '@/stores/auth';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const isMobileMenuOpen = ref(false);

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

const studentNumber = computed(() => {
  return authStore.user?.student?.student_number || `STU-${authStore.user?.id || '0000'}`;
});

const studentName = computed(() => {
  if (!authStore.user) return 'Student';
  return `${authStore.user.first_name || ''} ${authStore.user.last_name || ''}`.trim() || authStore.user.username || 'Student';
});

const isExamTakingPage = computed(() => {
  return route.name === 'student.exams.take';
});

const isExamsSection = computed(() => route.name?.toString().startsWith('student.exams'));

function closeMobileMenu() {
  isMobileMenuOpen.value = false;
}
</script>

<template>
  <!-- Full-screen exam sitting mode — no chrome whatsoever -->
  <div v-if="isExamTakingPage" class="min-h-screen bg-bg text-text antialiased">
    <router-view :key="$route.fullPath" />
  </div>

  <!-- Standard Student Portal with Top Navbar Layout -->
  <div v-else class="min-h-screen flex flex-col bg-bg text-text antialiased font-sans">
    <!-- Sticky Top Header (h-17 matching Admin and Instructor topbars) -->
    <header class="h-17 px-4 sm:px-6 lg:px-8 flex items-center justify-between border-b border-border bg-surface sticky top-0 z-30 shadow-xs shrink-0">
      <div class="flex items-center gap-4">
        <!-- Mobile Menu Toggle Button -->
        <button
          type="button"
          class="md:hidden w-9 h-9 rounded-xl border border-border bg-surface hover:bg-bg hover:border-accent/40 shadow-2xs flex items-center justify-center text-text/70 hover:text-text transition-all cursor-pointer active:scale-95"
          @click="isMobileMenuOpen = !isMobileMenuOpen"
          :aria-label="isMobileMenuOpen ? 'Close menu' : 'Open menu'">
          <X v-if="isMobileMenuOpen" class="w-4.5 h-4.5" />
          <Menu v-else class="w-4.5 h-4.5" />
        </button>

        <!-- Brand / Logo -->
        <div class="flex items-center gap-3 shrink-0 cursor-pointer select-none" @click="router.push({ name: 'student.dashboard' })">
          <div class="w-9 h-9 rounded-xl bg-accent/10 flex items-center justify-center shrink-0">
            <GraduationCap class="w-5 h-5 text-accent" />
          </div>
          <div class="hidden sm:block overflow-hidden leading-tight">
            <h1 class="text-base font-bold text-accent truncate">Academia</h1>
            <p class="text-[10px] font-bold tracking-wider uppercase text-text/40 truncate">Student Assessment</p>
          </div>
        </div>

        <!-- Desktop Navigation Tabs -->
        <nav class="hidden md:flex items-center gap-1 lg:gap-2 ml-4 h-17">
          <router-link
            :to="{ name: 'student.dashboard' }"
            class="h-full flex items-center px-3 text-sm font-medium transition-colors border-b-2"
            :class="route.name === 'student.dashboard' ? 'text-accent font-semibold border-accent' : 'text-text/60 hover:text-text hover:border-border border-transparent'">
            Dashboard
          </router-link>

          <router-link
            :to="{ name: 'student.exams.list' }"
            class="h-full flex items-center px-3 text-sm font-medium transition-colors border-b-2"
            :class="isExamsSection ? 'text-accent font-semibold border-accent' : 'text-text/60 hover:text-text hover:border-border border-transparent'">
            My Exams
          </router-link>

          <router-link
            :to="{ name: 'student.results.list' }"
            class="h-full flex items-center px-3 text-sm font-medium transition-colors border-b-2"
            :class="route.name === 'student.results.list' ? 'text-accent font-semibold border-accent' : 'text-text/60 hover:text-text hover:border-border border-transparent'">
            My Results
          </router-link>
        </nav>
      </div>

      <!-- Right Side Controls & Profile -->
      <div class="flex items-center gap-1.5 sm:gap-2.5 shrink-0">
        <!-- Student Number Badge (Pill) - avoids duplicate name with UserMenuDropdown -->
        <div class="hidden lg:flex items-center gap-1.5 h-9 bg-surface border border-border px-3 rounded-xl shadow-2xs shrink-0">
          <span class="w-1.5 h-1.5 rounded-full bg-accent inline-block shrink-0" />
          <span class="text-xs font-mono font-medium text-text/70">{{ studentNumber }}</span>
        </div>

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

        <WorkspaceSwitcherDropdown />

        <div class="w-px h-5 bg-border/80 mx-0.5 shrink-0" />

        <UserMenuDropdown :user-name="studentName" :user-avatar="authStore.user?.avatar" class="shrink-0" />
      </div>
    </header>

    <!-- Mobile Navigation Drawer -->
    <div v-if="isMobileMenuOpen" class="md:hidden border-b border-border bg-surface px-4 py-3 space-y-1 shadow-md animate-in slide-in-from-top-2 duration-150">
      <div class="pb-2 mb-2 border-b border-border/60 flex items-center justify-between text-xs text-text/60">
        <span class="font-medium">{{ studentName }}</span>
        <span class="font-mono bg-bg px-2 py-0.5 rounded border border-border">{{ studentNumber }}</span>
      </div>

      <router-link
        :to="{ name: 'student.dashboard' }"
        class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
        :class="route.name === 'student.dashboard' ? 'bg-accent/10 text-accent font-semibold' : 'text-text/70 hover:bg-bg hover:text-text'"
        @click="closeMobileMenu">
        <LayoutDashboard class="w-4 h-4" />
        <span>Dashboard</span>
      </router-link>

      <router-link
        :to="{ name: 'student.exams.list' }"
        class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
        :class="isExamsSection ? 'bg-accent/10 text-accent font-semibold' : 'text-text/70 hover:bg-bg hover:text-text'"
        @click="closeMobileMenu">
        <FileText class="w-4 h-4" />
        <span>My Exams</span>
      </router-link>

      <router-link
        :to="{ name: 'student.results.list' }"
        class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
        :class="route.name === 'student.results.list' ? 'bg-accent/10 text-accent font-semibold' : 'text-text/70 hover:bg-bg hover:text-text'"
        @click="closeMobileMenu">
        <Award class="w-4 h-4" />
        <span>My Results</span>
      </router-link>
    </div>

    <!-- Main Portal Content -->
    <main class="flex-1 w-full">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <router-view :key="$route.fullPath" />
      </div>
    </main>

    <!-- Portal Footer -->
    <footer class="w-full bg-surface border-t border-border py-5 mt-auto">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-text/60">
        <span>© {{ new Date().getFullYear() }} Academia Assessment Systems.</span>
        <div class="flex items-center gap-4">
          <span class="inline-flex items-center gap-1.5 text-success font-medium">
            <span class="w-1.5 h-1.5 rounded-full bg-success inline-block" />
            Session Secure
          </span>
          <span class="hidden sm:inline text-border">•</span>
          <span>Academic Honesty Code Active</span>
        </div>
      </div>
    </footer>
  </div>
</template>
