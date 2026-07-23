<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { ChevronDown, Check } from 'lucide-vue-next'
import { usePermissionsStore } from '@/stores/permission'

const props = defineProps<{ activeWorkspace: string }>()
const emit = defineEmits<{ (e: 'change', id: string): void }>()

const permissionStore = usePermissionsStore()
const isOpen = ref(false)
const el = ref<HTMLElement | null>(null)

const allWorkspaces = [
    { id: 'admin', label: 'Administration' },
    { id: 'teaching', label: 'Teaching' },
    { id: 'student', label: 'My Studies' },
]
const visibleIds = permissionStore.getVisibleWorkspaces()
const workspaces = allWorkspaces.filter(ws => visibleIds.includes(ws.id))
const currentLabel = allWorkspaces.find(ws => ws.id === props.activeWorkspace)?.label

function select(id: string) {
    isOpen.value = false
    emit('change', id)
}

function handleClickOutside(e: MouseEvent) {
    if (el.value && !el.value.contains(e.target as Node)) isOpen.value = false
}
onMounted(() => document.addEventListener('click', handleClickOutside))
onUnmounted(() => document.removeEventListener('click', handleClickOutside))
</script>

<template>
    <!-- Only one workspace: plain label, no dropdown -->
    <span v-if="workspaces.length <= 1" class="text-sm font-medium text-text/70 px-2">
        {{ currentLabel }}
    </span>

    <!-- More than one: real dropdown, current role shown as the button label -->
    <div v-else ref="el" class="relative">
        <button type="button" @click="isOpen = !isOpen"
            class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium text-text hover:bg-bg transition-colors">
            {{ currentLabel }}
            <ChevronDown class="w-4 h-4 text-text/50 transition-transform" :class="isOpen ? 'rotate-180' : ''" />
        </button>

        <div v-if="isOpen"
            class="absolute right-0 mt-2 w-52 bg-surface border border-border rounded-lg shadow-lg py-1 z-20">
            <button v-for="ws in workspaces" :key="ws.id" @click="select(ws.id)"
                class="w-full flex items-center justify-between px-3 py-2.5 text-sm text-left transition-colors"
                :class="ws.id === activeWorkspace ? 'text-accent font-semibold bg-accent/5' : 'text-text hover:bg-bg'">
                {{ ws.label }}
                <Check v-if="ws.id === activeWorkspace" class="w-4 h-4" />
            </button>
        </div>
    </div>
</template>
