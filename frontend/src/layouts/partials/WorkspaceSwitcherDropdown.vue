<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { ChevronDown, Check } from 'lucide-vue-next';
import { useRoute, useRouter } from 'vue-router';
import { usePermissionsStore } from '@/stores/permission';
import type { WorkspaceState } from '@/modules/instructor/pages/question-bank/types';

const route = useRoute();
const router = useRouter();
const permissionsStore = usePermissionsStore();

const isOpen = ref(false);
const el = ref<HTMLElement | null>(null);

const labels: Record<keyof WorkspaceState, string> = {
  admin: 'Admin',
  instructor: 'Instructor',
  student: 'Student',
};

const currentWorkspace = computed(() => {
  return route.meta.workspace as keyof WorkspaceState | undefined;
});

const currentLabel = computed(() => {
  return currentWorkspace.value ? labels[currentWorkspace.value] : '';
});

const visibleWorkspaces = computed(() => {
  if (!permissionsStore.workspaces) return [];

  return (Object.keys(permissionsStore.workspaces) as (keyof WorkspaceState)[]).filter((workspace) => permissionsStore.workspaces![workspace]);
});

function selectWorkspace(workspace: keyof WorkspaceState) {
  isOpen.value = false;
  usePermissionsStore().setActiveWorkspace(workspace);
  router.push(`/${workspace}/dashboard`);
}

function handleClickOutside(e: MouseEvent) {
  if (el.value && !el.value.contains(e.target as Node)) {
    isOpen.value = false;
  }
}

onMounted(() => document.addEventListener('click', handleClickOutside));
onUnmounted(() => document.removeEventListener('click', handleClickOutside));
</script>

<template>
  <!-- Single workspace pill -->
  <div
    v-if="visibleWorkspaces.length <= 1"
    class="h-9 px-3 rounded-xl border border-border/60 bg-bg/50 flex items-center text-xs font-mono text-text/60 select-none">
    <span>{{ currentLabel }}</span>
  </div>

  <!-- Switchable multiple workspaces -->
  <div v-else ref="el" class="relative">
    <button
      type="button"
      @click="isOpen = !isOpen"
      class="h-9 px-3 rounded-xl border border-border bg-surface hover:bg-bg hover:border-accent/40 shadow-2xs flex items-center gap-1.5 text-xs font-mono font-medium text-text transition-all cursor-pointer group active:scale-98"
      :class="{ 'border-accent/50 bg-bg': isOpen }">
      <span class="tracking-wide">{{ currentLabel }}</span>

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
        class="absolute right-0 mt-2 w-52 bg-surface border border-border rounded-2xl shadow-xl p-1.5 z-30">
        <div class="px-2.5 py-1.5 text-[10px] font-mono font-semibold uppercase tracking-wider text-text/40">
          Switch Workspace
        </div>
        <div class="space-y-0.5">
          <button
            v-for="workspace in visibleWorkspaces"
            :key="workspace"
            type="button"
            @click="selectWorkspace(workspace)"
            class="w-full flex items-center justify-between px-2.5 py-2 rounded-xl text-xs font-medium transition-all text-left"
            :class="workspace === currentWorkspace
              ? 'text-accent font-semibold bg-accent/10'
              : 'text-text hover:bg-bg hover:text-text'">
            <span class="font-mono">{{ labels[workspace] }}</span>
            <Check v-if="workspace === currentWorkspace" class="w-3.5 h-3.5 text-accent" />
          </button>
        </div>
      </div>
    </Transition>
  </div>
</template>
