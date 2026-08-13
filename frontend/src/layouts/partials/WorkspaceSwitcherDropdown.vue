<script setup lang="ts">
import { computed, ref } from 'vue'
import { ChevronDown, Check } from 'lucide-vue-next'
import { useRoute, useRouter } from 'vue-router'
import { usePermissionsStore } from '@/stores/permission'
import type { WorkspaceState } from '@/modules/workspace/types'

const route = useRoute()
const router = useRouter()
const permissionsStore = usePermissionsStore()

const isOpen = ref(false)

const labels: Record<keyof WorkspaceState, string> = {
  admin: 'Admin',
  instructor: 'Instructor',
  student: 'Student',
}

const currentWorkspace = computed(() => {
  return route.meta.workspace as keyof WorkspaceState | undefined
})

const currentLabel = computed(() => {
  return currentWorkspace.value
    ? labels[currentWorkspace.value]
    : ''
})

const visibleWorkspaces = computed(() => {
  if (!permissionsStore.workspaces) return []

  return (Object.keys(permissionsStore.workspaces) as (keyof WorkspaceState)[])
    .filter((workspace) => permissionsStore.workspaces![workspace])
})

function selectWorkspace(workspace: keyof WorkspaceState) {
  isOpen.value = false
  router.push(`/${workspace}/dashboard`)
}
</script>

<template>
  <!-- One workspace -->
  <span
    v-if="visibleWorkspaces.length <= 1"
    class="text-sm font-medium text-text/70 px-2"
  >
    {{ currentLabel }}
  </span>

  <!-- Multiple workspaces -->
  <div v-else class="relative">
    <button
      type="button"
      @click="isOpen = !isOpen"
      class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium text-text hover:bg-bg"
    >
      {{ currentLabel }}

      <ChevronDown
        class="w-4 h-4 text-text/50 transition-transform"
        :class="{ 'rotate-180': isOpen }"
      />
    </button>

    <div
      v-if="isOpen"
      class="absolute right-0 mt-2 w-52 bg-surface border border-border rounded-lg shadow-lg py-1 z-20"
    >
      <button
        v-for="workspace in visibleWorkspaces"
        :key="workspace"
        type="button"
        @click="selectWorkspace(workspace)"
        class="w-full flex items-center justify-between px-3 py-2.5 text-sm text-left hover:bg-bg"
        :class="
          workspace === currentWorkspace
            ? 'text-accent font-semibold bg-accent/5'
            : 'text-text'
        "
      >
        {{ labels[workspace] }}

        <Check
          v-if="workspace === currentWorkspace"
          class="w-4 h-4"
        />
      </button>
    </div>
  </div>
</template>