<!-- src/layouts/partials/WorkspaceSwitcherDropdown.vue -->
<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { ChevronDown, Check } from 'lucide-vue-next'
import { usePermissionsStore } from '@/stores/permission'
import { useRouter } from 'vue-router'

const permissionsStore = usePermissionsStore()
const router = useRouter()
const isOpen = ref(false)
const el = ref<HTMLElement | null>(null)

const labels: Record<string, string> = {
  admin: 'Administration',
  instructor: 'Teaching',
  student: 'My Studies',
}

const visibleWorkspaces = computed(() => {
  const state = permissionsStore.getVisibleWorkspaces()
  return Object.keys(state).filter((id) => state[id as keyof typeof state])
})

const currentLabel = computed(() => labels[permissionsStore.activeWorkspace])

function select(id: string) {
  isOpen.value = false
  permissionsStore.activeWorkspace = id
  router.push(`/${id}/dashboard`)
}

function handleClickOutside(e: MouseEvent) {
  if (el.value && !el.value.contains(e.target as Node)) isOpen.value = false
}
onMounted(() => document.addEventListener('click', handleClickOutside))
onUnmounted(() => document.removeEventListener('click', handleClickOutside))
</script>

<template>
  <span v-if="visibleWorkspaces.length <= 1" class="text-sm font-medium text-text/70 px-2">
    {{ currentLabel }}
  </span>

  <div v-else ref="el" class="relative">
    <button type="button" @click="isOpen = !isOpen" class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium text-text hover:bg-bg transition-colors">
      {{ currentLabel }}
      <ChevronDown class="w-4 h-4 text-text/50 transition-transform" :class="isOpen ? 'rotate-180' : ''" />
    </button>

    <div v-if="isOpen" class="absolute right-0 mt-2 w-52 bg-surface border border-border rounded-lg shadow-lg py-1 z-20">
      <button
        v-for="id in visibleWorkspaces"
        :key="id"
        @click="select(id)"
        class="w-full flex items-center justify-between px-3 py-2.5 text-sm text-left transition-colors"
        :class="id === permissionsStore.activeWorkspace ? 'text-accent font-semibold bg-accent/5' : 'text-text hover:bg-bg'"
      >
        {{ labels[id] }}
        <Check v-if="id === permissionsStore.activeWorkspace" class="w-4 h-4" />
      </button>
    </div>
  </div>
</template>