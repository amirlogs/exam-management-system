<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
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
  UserCog,
  ShieldCheck,
  BarChart3,
  HelpCircle,
  FileText,
  ClipboardCheck,
  Flag,
  LibraryBig,
  FileQuestion,
  ChevronRight,
  Check,
} from 'lucide-vue-next';

import type { Component } from 'vue';
import { useSidebar } from '@/shared/composables/useSidebar';

const icons: Record<string, Component> = {
  LayoutGrid,
  Landmark,
  UserCog,
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
  LibraryBig,
  FileQuestion,
};

interface ChildNavItem {
  label: string;
  to: string;
  icon?: string | Component;
}

interface NavItem {
  label: string;
  to?: string;
  icon?: string | Component;
  children?: ChildNavItem[];
}

interface NavSection {
  title?: string;
  items: NavItem[];
}

const props = defineProps<{
  brandName: string;
  brandSubtitle: string;
  items: { label: string; to: string; icon: any }[];
  activeTo: string;
}>();

const { isCollapsed, isMobileOpen, toggleCollapse, closeMobile } = useSidebar();

const openItems = ref<Record<string, boolean>>({});

function toggleOpen(label: string) {
  openItems.value[label] = !openItems.value[label];
}

function handleNavigate() {
  closeMobile();
}

function isGroupActive(item: NavItem): boolean {
  return item.children?.some((c) => props.activeTo.startsWith(c.to)) ?? false;
}

// 100% dynamic: groups items by `item.group` received directly from backend
const groupedSections = computed<NavSection[]>(() => {
  if (!props.items || props.items.length === 0) return [];

  const map = new Map<string, NavItem[]>();

  for (const rawItem of props.items) {
    const grp = (rawItem as any).group || '';
    if (!map.has(grp)) {
      map.set(grp, []);
    }
    map.get(grp)!.push(rawItem as NavItem);
  }

  const sections: NavSection[] = [];
  for (const [title, items] of map.entries()) {
    sections.push({
      title: title || undefined,
      items,
    });
  }

  return sections;
});

// Auto-expand any parent group that contains the active route
watch(
  () => [props.activeTo, groupedSections.value],
  () => {
    for (const sec of groupedSections.value) {
      for (const item of sec.items) {
        if (item.children && isGroupActive(item)) {
          openItems.value[item.label] = true;
        }
      }
    }
  },
  { immediate: true },
);

function handleKeyDown(e: KeyboardEvent) {
  if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'b') {
    const tag = (e.target as HTMLElement)?.tagName;
    if (tag === 'INPUT' || tag === 'TEXTAREA' || (e.target as HTMLElement)?.isContentEditable) {
      return;
    }
    e.preventDefault();
    toggleCollapse();
  }
}

onMounted(() => {
  window.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeyDown);
});
</script>

<template>
  <!-- Mobile Overlay -->
  <Transition name="fade">
    <div v-if="isMobileOpen" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-40 md:hidden" @click="closeMobile" />
  </Transition>

  <!-- Sidebar -->
  <aside
    class="fixed md:sticky top-0 h-screen shrink-0 bg-surface border-r border-border flex flex-col justify-between z-50 md:z-40 transition-all duration-300 ease-in-out shadow-2xl md:shadow-none"
    :class="[isCollapsed ? 'md:w-16' : 'md:w-66', isMobileOpen ? 'translate-x-0 w-70' : '-translate-x-full md:translate-x-0 w-70 md:w-auto']">
    <!-- Fixed Top bar -->
    <div
      class="h-17 px-3 flex items-center justify-between border-b border-border shrink-0"
      :class="isCollapsed ? 'md:justify-center md:px-0' : ''">
      <div class="flex items-center gap-3 leading-tight overflow-hidden">
        <button
          type="button"
          @click="isCollapsed && toggleCollapse()"
          class="w-9 h-9 rounded-xl bg-accent/10 flex items-center justify-center shrink-0 transition-all"
          :class="isCollapsed ? 'cursor-pointer hover:bg-accent/20 active:scale-95 group' : ''"
          :title="isCollapsed ? 'Expand sidebar (Ctrl+B)' : ''">
          <GraduationCap class="w-5 h-5 text-accent" :class="isCollapsed ? 'group-hover:hidden' : ''" />
          <PanelLeftOpen v-if="isCollapsed" class="w-4.5 h-4.5 text-accent hidden group-hover:block" />
        </button>

        <div
          class="overflow-hidden whitespace-nowrap transition-all duration-300"
          :class="isCollapsed ? 'md:w-0 md:opacity-0' : 'w-auto opacity-100'">
          <h1 class="text-base font-bold text-accent truncate">{{ brandName }}</h1>
          <p class="text-[10px] font-bold tracking-wider uppercase text-text/40 truncate">{{ brandSubtitle }}</p>
        </div>
      </div>

      <!-- Collapse button (Desktop) -->
      <button
        v-if="!isCollapsed"
        type="button"
        @click="toggleCollapse"
        class="hidden md:flex ml-2 w-8 h-8 rounded-lg items-center justify-center text-text/50 hover:bg-bg hover:text-text transition-colors shrink-0 cursor-pointer"
        title="Collapse sidebar (Ctrl+B)">
        <PanelLeftClose class="w-4.5 h-4.5" />
      </button>

      <!-- Close button (Mobile) -->
      <button
        type="button"
        class="md:hidden w-9 h-9 rounded-xl flex items-center justify-center bg-bg text-text/60 hover:text-text active:scale-95 transition-all cursor-pointer"
        @click="closeMobile"
        aria-label="Close sidebar">
        <X class="w-5 h-5" />
      </button>
    </div>

    <!-- Nav Sections (Scrollable without visible scrollbar) -->
    <nav
      class="flex-1 overflow-y-auto sidebar-scroll transition-all duration-300"
      :class="isCollapsed ? 'px-2 py-3 space-y-1' : 'px-3 py-3 space-y-3'">
      <div v-for="(section, sIdx) in groupedSections" :key="sIdx" class="space-y-1">
        <!-- Section Header / Divider -->
        <div v-if="section.title">
          <div
            v-if="!isCollapsed"
            class="px-2.5 pt-2 pb-1 text-[10px] font-mono font-semibold uppercase tracking-wider text-text/40 select-none">
            {{ section.title }}
          </div>
          <div v-else class="my-2 border-t border-border/50 mx-1.5" />
        </div>

        <!-- Section Items -->
        <template v-for="item in section.items" :key="item.label">
          <!-- 1. NESTED ITEM (Has Children) -->
          <div v-if="item.children && item.children.length > 0" class="relative group">
            <!-- Expanded: Accordion Header Trigger -->
            <button
              v-if="!isCollapsed"
              type="button"
              @click="toggleOpen(item.label)"
              class="w-full flex items-center justify-between px-2.5 py-2 rounded-lg text-sm font-medium transition-colors cursor-pointer group"
              :class="isGroupActive(item)
                ? 'text-text font-medium'
                : 'text-text/70 hover:text-text hover:bg-text/[0.04]'">
              <div class="flex items-center gap-2.5 min-w-0">
                <component
                  :is="icons[item.icon as string] || item.icon"
                  class="w-4.5 h-4.5 shrink-0 transition-colors"
                  :class="isGroupActive(item) ? 'text-text' : 'text-text/45 group-hover:text-text/75'" />
                <span class="truncate">{{ item.label }}</span>
              </div>
              <ChevronRight
                class="w-3.5 h-3.5 text-text/40 group-hover:text-text/70 transition-transform duration-200 ease-out shrink-0"
                :class="{ 'rotate-90 text-text/70': openItems[item.label] }" />
            </button>

            <!-- Expanded: Smooth Grid Accordion Slide -->
            <div
              v-if="!isCollapsed"
              class="grid transition-[grid-template-rows] duration-200 ease-out"
              :class="openItems[item.label] ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]'">
              <div class="overflow-hidden">
                <div class="ml-4 pl-3.5 border-l border-border/60 space-y-0.5 py-1">
                  <router-link
                    v-for="child in item.children"
                    :key="child.to"
                    :to="child.to"
                    @click="handleNavigate"
                    class="flex items-center justify-between px-2.5 py-1.5 rounded-lg text-sm transition-colors"
                    :class="activeTo.startsWith(child.to)
                      ? 'bg-text/[0.08] text-text font-medium'
                      : 'text-text/65 hover:text-text hover:bg-text/[0.04]'">
                    <span class="truncate">{{ child.label }}</span>
                    <span
                      v-if="activeTo.startsWith(child.to)"
                      class="w-1.5 h-1.5 rounded-full bg-accent shrink-0 ml-2" />
                  </router-link>
                </div>
              </div>
            </div>

            <!-- Collapsed: Rail Icon Trigger + Floating Flyout Popover -->
            <div v-if="isCollapsed" class="relative group/flyout">
              <button
                type="button"
                class="w-full h-10 rounded-lg flex items-center justify-center transition-colors cursor-pointer relative"
                :class="isGroupActive(item)
                  ? 'bg-text/[0.08] text-text'
                  : 'text-text/50 hover:bg-text/[0.04] hover:text-text'">
                <component
                  :is="icons[item.icon as string] || item.icon"
                  class="w-4.5 h-4.5 shrink-0"
                  :class="isGroupActive(item) ? 'text-text' : 'text-text/50 group-hover/flyout:text-text'" />
                <span
                  v-if="isGroupActive(item)"
                  class="absolute top-2 right-2 w-1.5 h-1.5 rounded-full bg-accent" />
              </button>

              <!-- Flyout Menu Card -->
              <div
                class="hidden md:block pointer-events-none group-hover/flyout:pointer-events-auto absolute left-full top-0 ml-2.5 w-52 bg-surface border border-border rounded-xl shadow-xl p-1.5 z-50 opacity-0 -translate-x-1 group-hover/flyout:opacity-100 group-hover/flyout:translate-x-0 transition-all duration-150">
                <div class="px-2.5 py-1.5 text-[10px] font-mono font-medium uppercase tracking-wider text-text/40 border-b border-border/50 mb-1">
                  {{ item.label }}
                </div>
                <div class="space-y-0.5">
                  <router-link
                    v-for="child in item.children"
                    :key="child.to"
                    :to="child.to"
                    @click="handleNavigate"
                    class="flex items-center justify-between px-2.5 py-1.5 rounded-lg text-sm transition-colors"
                    :class="activeTo.startsWith(child.to)
                      ? 'bg-text/[0.08] text-text font-medium'
                      : 'text-text/65 hover:bg-text/[0.04] hover:text-text'">
                    <span class="truncate">{{ child.label }}</span>
                    <span
                      v-if="activeTo.startsWith(child.to)"
                      class="w-1.5 h-1.5 rounded-full bg-accent shrink-0 ml-2" />
                  </router-link>
                </div>
              </div>
            </div>
          </div>

          <!-- 2. DIRECT ITEM (No Children) -->
          <router-link
            v-else
            :to="item.to!"
            @click="handleNavigate"
            class="group relative flex items-center rounded-lg text-sm transition-colors"
            :class="[
              activeTo.startsWith(item.to!)
                ? 'bg-text/[0.08] text-text font-medium'
                : 'text-text/70 hover:bg-text/[0.04] hover:text-text',
              isCollapsed ? 'md:justify-center md:h-10 md:w-full' : 'gap-2.5 px-2.5 py-2',
            ]">
            <component
              :is="icons[item.icon as string] || item.icon"
              class="w-5 h-5 md:w-4.5 md:h-4.5 shrink-0 transition-colors"
              :class="activeTo.startsWith(item.to!) ? 'text-text' : 'text-text/45 group-hover:text-text/75'" />

            <span
              class="overflow-hidden whitespace-nowrap transition-all duration-300 truncate"
              :class="isCollapsed ? 'md:w-0 md:opacity-0' : 'w-auto opacity-100'">
              {{ item.label }}
            </span>

            <!-- Minimalist Active Indicator Pip -->
            <span
              v-if="activeTo.startsWith(item.to!)"
              class="w-1.5 h-1.5 rounded-full bg-accent shrink-0 ml-auto"
              :class="isCollapsed ? 'hidden' : 'block'" />

            <!-- Collapsed Rail Pip -->
            <span
              v-if="isCollapsed && activeTo.startsWith(item.to!)"
              class="absolute top-2 right-2 w-1.5 h-1.5 rounded-full bg-accent" />

            <!-- Tooltip Pill (Collapsed Mode) -->
            <div
              v-if="isCollapsed"
              class="hidden md:block pointer-events-none absolute left-full ml-2.5 px-2.5 py-1 rounded-lg bg-surface border border-border text-xs font-medium text-text shadow-lg whitespace-nowrap z-50 opacity-0 -translate-x-1 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-150">
              {{ item.label }}
            </div>
          </router-link>
        </template>
      </div>
    </nav>

    <!-- Footer / Settings (Fixed at bottom) -->
    <div
      class="border-t border-border shrink-0 transition-all duration-300"
      :class="isCollapsed ? 'px-2 py-2.5' : 'px-3 py-2.5'">
      <router-link
        to="/settings"
        @click="handleNavigate"
        class="group relative flex items-center rounded-lg text-sm transition-colors"
        :class="[
          activeTo.startsWith('/settings')
            ? 'bg-text/[0.08] text-text font-medium'
            : 'text-text/70 hover:bg-text/[0.04] hover:text-text',
          isCollapsed ? 'md:justify-center md:h-10 md:w-full' : 'gap-2.5 px-2.5 py-2',
        ]">
        <Settings
          class="w-5 h-5 md:w-4.5 md:h-4.5 shrink-0 transition-colors"
          :class="activeTo.startsWith('/settings') ? 'text-text' : 'text-text/45 group-hover:text-text/75'" />

        <span
          class="overflow-hidden whitespace-nowrap transition-all duration-300 truncate"
          :class="isCollapsed ? 'md:w-0 md:opacity-0' : 'w-auto opacity-100'">
          Settings
        </span>

        <!-- Minimalist Active Indicator Pip -->
        <span
          v-if="activeTo.startsWith('/settings')"
          class="w-1.5 h-1.5 rounded-full bg-accent shrink-0 ml-auto"
          :class="isCollapsed ? 'hidden' : 'block'" />

        <!-- Collapsed Rail Pip -->
        <span
          v-if="isCollapsed && activeTo.startsWith('/settings')"
          class="absolute top-2 right-2 w-1.5 h-1.5 rounded-full bg-accent" />

        <!-- Floating Tooltip for Settings -->
        <div
          v-if="isCollapsed"
          class="hidden md:block pointer-events-none absolute left-full ml-2.5 px-2.5 py-1 rounded-lg bg-surface border border-border text-xs font-medium text-text shadow-lg whitespace-nowrap z-50 opacity-0 -translate-x-1 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-150">
          Settings
        </div>
      </router-link>
    </div>
  </aside>
</template>

<style scoped>
.sidebar-scroll {
  scrollbar-width: none; /* Firefox */
  -ms-overflow-style: none; /* IE/Edge */
}
.sidebar-scroll::-webkit-scrollbar {
  display: none; /* Chrome, Safari, Opera */
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
