<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Edit, MoreVertical, Archive, RotateCcw, type LucideIcon } from 'lucide-vue-next'
import { usePermissionsStore } from '@/stores/permission'

interface ExtraAction {
    key: string
    label: string
    icon: LucideIcon
    permission?: string
    variant?: 'default' | 'danger' | 'accent'
    /** Show only in 'active' tab, only 'archived', or both (default). */
    showOn?: 'active' | 'archived' | 'both'
}

const props = defineProps<{
    activeTab: 'active' | 'archived'
    editPermission?: string
    archivePermission?: string
    restorePermission?: string
    /** Extra menu items beyond edit/archive/restore, e.g. View, Reopen. */
    extraActions?: ExtraAction[]
}>()

const emit = defineEmits<{
    edit: []
    archive: []
    restore: []
    action: [key: string]
}>()

const permissionsStore = usePermissionsStore()

const isOpen = ref(false)
const menuPosition = ref({ top: 0, left: 0 })

const visibleExtras = computed(() =>
    (props.extraActions ?? []).filter((a) => {
        const permOk = !a.permission || permissionsStore.hasPermission(a.permission)
        return permOk
    }),
)

const activeExtras = computed(() =>
    visibleExtras.value.filter((a) => !a.showOn || a.showOn === 'both' || a.showOn === 'active'),
)
const archivedExtras = computed(() =>
    visibleExtras.value.filter((a) => !a.showOn || a.showOn === 'both' || a.showOn === 'archived'),
)

const canShowActiveMenu = computed(() => {
    const activePerms = [props.editPermission, props.archivePermission].filter(Boolean) as string[]
    const baseAllowed = activePerms.some((p) => permissionsStore.hasPermission(p))
    return baseAllowed || activeExtras.value.length > 0
})

const canShowArchivedMenu = computed(() => {
    const restoreAllowed = !!props.restorePermission && permissionsStore.hasPermission(props.restorePermission)
    return restoreAllowed || archivedExtras.value.length > 0
})

const canShowTrigger = computed(() => {
    return props.activeTab === 'active' ? canShowActiveMenu.value : canShowArchivedMenu.value
})

function toggleMenu(event: MouseEvent) {
    if (isOpen.value) {
        isOpen.value = false
        return
    }
    const rect = (event.currentTarget as HTMLElement).getBoundingClientRect()
    const itemCount =
        (props.activeTab === 'active'
            ? (props.editPermission ? 1 : 0) + (props.archivePermission ? 1 : 0) + activeExtras.value.length
            : (props.restorePermission ? 1 : 0) + archivedExtras.value.length) || 1
    const menuHeight = Math.max(44, itemCount * 36 + 8)
    const openUpward = window.innerHeight - rect.bottom < menuHeight
    menuPosition.value = {
        top: openUpward ? rect.top - menuHeight - 4 : rect.bottom + 4,
        left: rect.right - 160,
    }
    isOpen.value = true
}

function closeMenu() {
    isOpen.value = false
}

function handleClickOutside(e: MouseEvent) {
    const target = e.target as HTMLElement
    if (!target.closest('[data-action-menu]') && !target.closest('[data-action-trigger]')) {
        closeMenu()
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside)
    window.addEventListener('scroll', closeMenu, true)
})

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
    window.removeEventListener('scroll', closeMenu, true)
})
</script>

<template>
    <div v-if="canShowTrigger">
        <button type="button" data-action-trigger
            class="inline-flex h-8 w-8 items-center justify-center rounded-md text-text/50 transition hover:bg-text/5 hover:text-text"
            @click.stop="toggleMenu">
            <MoreVertical class="h-4 w-4" />
        </button>

        <Teleport to="body">
            <div v-if="isOpen" data-action-menu
                class="fixed z-[100] w-40 rounded-md border border-border bg-surface py-1 shadow-lg"
                :style="{ top: menuPosition.top + 'px', left: menuPosition.left + 'px' }" @click.stop>

                <template v-if="activeTab === 'active'">
                    <button v-if="editPermission" v-can="editPermission" type="button"
                        class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-text hover:bg-text/5"
                        @click="closeMenu(); emit('edit')">
                        <Edit class="h-4 w-4 text-text/50" />
                        Edit
                    </button>

                    <button v-for="a in activeExtras" :key="a.key" type="button"
                        class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm hover:bg-text/5"
                        :class="a.variant === 'danger' ? 'text-error hover:bg-error/5' : a.variant === 'accent' ? 'text-accent hover:bg-accent/5' : 'text-text'"
                        @click="closeMenu(); emit('action', a.key)">
                        <component :is="a.icon" class="h-4 w-4" :class="a.variant ? '' : 'text-text/50'" />
                        {{ a.label }}
                    </button>

                    <div v-if="(editPermission || activeExtras.length) && archivePermission"
                        class="my-1 border-t border-border" />

                    <button v-if="archivePermission" v-can="archivePermission" type="button"
                        class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-error hover:bg-error/5"
                        @click="closeMenu(); emit('archive')">
                        <Archive class="h-4 w-4" />
                        Archive
                    </button>
                </template>

                <template v-else>
                    <button v-if="restorePermission" v-can="restorePermission" type="button"
                        class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-accent hover:bg-accent/5"
                        @click="closeMenu(); emit('restore')">
                        <RotateCcw class="h-4 w-4" />
                        Restore
                    </button>

                    <button v-for="a in archivedExtras" :key="a.key" type="button"
                        class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm hover:bg-text/5"
                        :class="a.variant === 'danger' ? 'text-error hover:bg-error/5' : a.variant === 'accent' ? 'text-accent hover:bg-accent/5' : 'text-text'"
                        @click="closeMenu(); emit('action', a.key)">
                        <component :is="a.icon" class="h-4 w-4" :class="a.variant ? '' : 'text-text/50'" />
                        {{ a.label }}
                    </button>
                </template>

            </div>
        </Teleport>
    </div>
</template>
