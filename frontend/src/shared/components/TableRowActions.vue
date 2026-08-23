<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Edit, MoreVertical, Archive, RotateCcw } from 'lucide-vue-next'
import { usePermissionsStore } from '@/stores/permission'

const props = defineProps<{
    activeTab: 'active' | 'archived'
    editPermission?: string
    archivePermission?: string
    restorePermission?: string
}>()

const emit = defineEmits<{
    edit: []
    archive: []
    restore: []
}>()

const permissionsStore = usePermissionsStore()

const isOpen = ref(false)
const menuPosition = ref({ top: 0, left: 0 })

// Check if user has permission for active actions (Edit or Archive)
const canShowActiveMenu = computed(() => {
    const activePerms = [props.editPermission, props.archivePermission].filter(Boolean) as string[]
    return activePerms.some((p) => permissionsStore.hasPermission(p))
})

// Check if user has permission for archived actions (Restore)
const canShowArchivedMenu = computed(() => {
    if (!props.restorePermission) return false
    return permissionsStore.hasPermission(props.restorePermission)
})

// Determine overall trigger button visibility based on active tab
const canShowTrigger = computed(() => {
    return props.activeTab === 'active' ? canShowActiveMenu.value : canShowArchivedMenu.value
})

function toggleMenu(event: MouseEvent) {
    if (isOpen.value) {
        isOpen.value = false
        return
    }
    const rect = (event.currentTarget as HTMLElement).getBoundingClientRect()
    const menuHeight = 100
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
    <!-- Render button only if user has permission for current tab -->
    <div v-if="canShowTrigger">
        <button type="button" data-action-trigger
            class="inline-flex h-8 w-8 items-center justify-center rounded-md text-text/50 transition hover:bg-text/5 hover:text-text"
            @click.stop="toggleMenu">
            <MoreVertical class="h-4 w-4" />
        </button>

        <!-- Dropdown Menu -->
        <Teleport to="body">
            <div v-if="isOpen" data-action-menu
                class="fixed z-[100] w-40 rounded-md border border-border bg-surface py-1 shadow-lg"
                :style="{ top: menuPosition.top + 'px', left: menuPosition.left + 'px' }"
                @click.stop>

                <!-- Active Tab Options -->
                <template v-if="activeTab === 'active'">
                    <button v-if="editPermission" v-can="editPermission" type="button"
                        class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-text hover:bg-text/5"
                        @click="closeMenu(); emit('edit')">
                        <Edit class="h-4 w-4 text-text/50" />
                        Edit
                    </button>

                    <div v-if="editPermission && archivePermission"
                        v-can:all="[editPermission, archivePermission]"
                        class="my-1 border-t border-border" />

                    <button v-if="archivePermission" v-can="archivePermission" type="button"
                        class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-error hover:bg-error/5"
                        @click="closeMenu(); emit('archive')">
                        <Archive class="h-4 w-4" />
                        Archive
                    </button>
                </template>

                <!-- Archived Tab Options -->
                <template v-else>
                    <button v-if="restorePermission" v-can="restorePermission" type="button"
                        class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-accent hover:bg-accent/5"
                        @click="closeMenu(); emit('restore')">
                        <RotateCcw class="h-4 w-4" />
                        Restore
                    </button>
                </template>

            </div>
        </Teleport>
    </div>
</template>
