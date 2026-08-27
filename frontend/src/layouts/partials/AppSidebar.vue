<script setup lang="ts">
import {
  PanelLeftClose,
  PanelLeftOpen,
  X,
  GraduationCap,
  Settings,
  LayoutGrid,
  Building2,
  Landmark,
  BookOpen,
  ListTree,
  CalendarDays,
  Users2,
  Upload,
  ClipboardList,
  ShieldCheck,
  BarChart3,
  HelpCircle,
  FileText,
  ClipboardCheck,
  Flag,
} from 'lucide-vue-next';

import type { Component } from 'vue';
import { useSidebar } from '@/shared/composables/useSidebar';
import {} from 'lucide-vue-next';

const icons = {
  LayoutGrid,
  Landmark,
  Building2,
  GraduationCap,
  BookOpen,
  ListTree,
  CalendarDays,
  Users2,
  Upload,
  ClipboardList,
  ShieldCheck,
  BarChart3,
  HelpCircle,
  FileText,
  ClipboardCheck,
  Flag,
};

defineProps<{
  brandName: string;
  brandSubtitle: string;
  items: { label: string; to: string; icon: Component }[];
  activeTo: string;
}>();

const { isCollapsed, isMobileOpen, toggleCollapse, closeMobile } = useSidebar();

function handleNavigate() {
  closeMobile();
}
</script>

<template>
  <!-- Mobile Overlay -->
  <Transition name="fade">
    <div v-if="isMobileOpen" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-40 md:hidden" @click="closeMobile" />
  </Transition>

  <!-- Sidebar -->
  <aside
    class="fixed md:sticky top-0 h-screen shrink-0 bg-surface border-r border-border flex flex-col justify-between z-50 md:z-40 transition-all duration-300 ease-in-out shadow-2xl md:shadow-none"
    :class="[isCollapsed ? 'md:w-19' : 'md:w-66', isMobileOpen ? 'translate-x-0 w-70' : '-translate-x-full md:translate-x-0 w-70 md:w-auto']">
    <div class="overflow-y-auto flex-1 flex flex-col">
      <!-- Top bar -->
      <div class="h-17 px-3 flex items-center justify-between border-b border-border shrink-0" :class="isCollapsed ? 'md:justify-center md:px-0' : ''">
        <div v-if="!isCollapsed" class="flex items-center gap-3 leading-tight overflow-hidden">
          <div class="w-9 h-9 rounded-xl bg-accent/10 flex items-center justify-center shrink-0">
            <GraduationCap class="w-5 h-5 text-accent" />
          </div>
          <div class="overflow-hidden">
            <h1 class="text-base font-bold text-accent truncate">{{ brandName }}</h1>
            <p class="text-[10px] font-bold tracking-wider uppercase text-text/40 truncate">{{ brandSubtitle }}</p>
          </div>
        </div>

        <!-- Collapsed icon toggle (Desktop) -->
        <button
          v-else-if="isCollapsed"
          @click="toggleCollapse"
          class="hidden md:flex group relative w-9 h-9 rounded-xl items-center justify-center text-text/50 hover:bg-bg hover:text-text transition-colors shrink-0"
          title="Expand sidebar">
          <GraduationCap class="w-5 h-5 text-accent group-hover:hidden" />
          <PanelLeftOpen class="w-4.5 h-4,5 hidden group-hover:block" />
        </button>

        <!-- Collapse button (Desktop) -->
        <button
          v-if="!isCollapsed"
          @click="toggleCollapse"
          class="hidden md:flex ml-4 w-8 h-8 rounded-lg items-center justify-center text-text/50 hover:bg-bg hover:text-text transition-colors shrink-0"
          title="Collapse sidebar">
          <PanelLeftClose class="w-4.5 h-4.5" />
        </button>

        <!-- Close button (Mobile) -->
        <button
          class="md:hidden w-9 h-9 rounded-4.5l flex items-center justify-center bg-bg text-text/60 hover:text-text active:scale-95 transition-all"
          @click="closeMobile"
          aria-label="Close sidebar">
          <X class="w-5 h-5" />
        </button>
      </div>

      <!-- Nav Items -->
      <nav class="p-4 space-y-1.5 flex-1">
        <router-link
          v-for="item in items"
          :key="item.to"
          :to="item.to"
          @click="handleNavigate"
          class="flex items-center gap-3.5 px-3.5 py-3 md:py-2.5 rounded-xl text-sm font-medium transition-all duration-150 active:scale-[0.98]"
          :class="[
            activeTo.startsWith(item.to) ? 'bg-accent/10 text-accent font-semibold' : 'text-text/60 hover:bg-bg hover:text-text',
            isCollapsed ? 'md:justify-center md:px-0' : '',
          ]"
          :title="isCollapsed ? item.label : ''">
          <component :is="icons[item.icon]" class="w-5 h-5 md:w-4.5 md:h-4.5 shrink-0" />
          <span :class="isCollapsed ? 'md:hidden' : ''">{{ item.label }}</span>
        </router-link>
      </nav>
    </div>

    <!-- Footer / Settings -->
    <div class="px-4 py-2 border-t border-border shrink-0">
      <router-link
        to="/settings"
        @click="handleNavigate"
        class="flex items-center gap-3.5 px-3.5 py-3 md:py-2.5 rounded-xl text-sm font-medium text-text/60 hover:bg-bg hover:text-text transition-all duration-150 active:scale-[0.98]"
        :class="isCollapsed ? 'md:justify-center md:px-0' : ''">
        <Settings class="w-5 h-5 md:w-4.5 md:h-4.5 shrink-0" />
        <span :class="isCollapsed ? 'md:hidden' : ''">Settings</span>
      </router-link>
    </div>
  </aside>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
