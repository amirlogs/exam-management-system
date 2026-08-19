<!-- PermissionEditor.vue -->
<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { ChevronDown, ChevronUp, Save } from 'lucide-vue-next'
import BaseButton from '@/shared/components/ui/BaseButton.vue'
import { getPermissions } from '../api/permissions'
import { assignPermissions, removePermissions } from '../api/roles'
import type { Role } from '../types/role'
import type { Permission } from '../types/permission'
import { useUiStore } from '@/stores/ui'

const props = defineProps<{ role: Role }>()
const emit = defineEmits<{ updated: [Role] }>()

const uiStore = useUiStore()
const allPermissions = ref<Permission[]>([])
const selectedIds = ref<Set<number>>(new Set())
const openCategories = ref<Set<string>>(new Set())
const saving = ref(false)

const grouped = computed(() => {
    const map = new Map<string, Permission[]>()
    for (const p of allPermissions.value) {
        if (!map.has(p.category)) map.set(p.category, [])
        map.get(p.category)!.push(p)
    }
    return map
})

onMounted(async () => {
    allPermissions.value = await getPermissions()
    if (allPermissions.value.length) openCategories.value.add(allPermissions.value[0].category)
})

watch(
    () => props.role,
    (role) => {
        selectedIds.value = new Set(role.permissions.map((p) => p.id))
    },
    { immediate: true },
)

const hasChanges = computed(() => {
    const original = new Set(props.role.permissions.map((p) => p.id))
    if (original.size !== selectedIds.value.size) return true
    for (const id of selectedIds.value) if (!original.has(id)) return true
    return false
})

function toggleCategory(cat: string) {
    openCategories.value.has(cat) ? openCategories.value.delete(cat) : openCategories.value.add(cat)
}
function toggle(id: number) {
    selectedIds.value.has(id) ? selectedIds.value.delete(id) : selectedIds.value.add(id)
}
function toggleAllInCategory(cat: string, perms: Permission[]) {
    const allSelected = perms.every((p) => selectedIds.value.has(p.id))
    for (const p of perms) allSelected ? selectedIds.value.delete(p.id) : selectedIds.value.add(p.id)
}
function isAllSelected(perms: Permission[]) { return perms.every((p) => selectedIds.value.has(p.id)) }

async function save() {
    const original = new Set(props.role.permissions.map((p) => p.id))
    const toAdd = [...selectedIds.value].filter((id) => !original.has(id))
    const toRemove = [...original].filter((id) => !selectedIds.value.has(id))
    if (!toAdd.length && !toRemove.length) return

    saving.value = true
    try {
        let updated = props.role
        if (toAdd.length) updated = await assignPermissions(props.role.id, toAdd)
        if (toRemove.length) updated = await removePermissions(props.role.id, toRemove)
        emit('updated', updated)
        uiStore.showToast('Permissions saved.', 'success')
    } catch (err: any) {
        uiStore.showToast(err?.response?.data?.message || 'Failed to save permissions.', 'error')
    } finally {
        saving.value = false
    }
}
function discard() { selectedIds.value = new Set(props.role.permissions.map((p) => p.id)) }
</script>

<template>
    <div class="flex max-h-[calc(100vh-2rem)] xl:max-h-[calc(100vh-4rem)] flex-col rounded-md border border-border bg-surface">
        <div class="shrink-0 border-b border-border bg-bg/50 p-4 sm:p-5">
            <h3 class="font-display text-lg text-text">{{ role.name }}</h3>
            <p class="mt-1 text-xs text-text/55">{{ role.description }}</p>
        </div>

        <div class="flex-1 space-y-3 overflow-y-auto p-3 sm:p-4 min-h-0">
            <div v-for="[category, perms] in grouped" :key="category" class="overflow-hidden rounded-md border border-border">
                <div class="flex cursor-pointer items-center justify-between bg-bg px-3 py-2.5" @click="toggleCategory(category)">
                    <span class="text-xs font-bold uppercase tracking-wide text-text/70">{{ category }}</span>
                    <div class="flex items-center gap-3">
                        <label class="flex items-center gap-1.5 text-xs text-text/60" @click.stop>
                            <input type="checkbox" :checked="isAllSelected(perms)" class="h-3.5 w-3.5 rounded accent-accent" @change="toggleAllInCategory(category, perms)" />
                            All
                        </label>
                        <component :is="openCategories.has(category) ? ChevronUp : ChevronDown" class="h-4 w-4 text-text/40" />
                    </div>
                </div>
                <div v-if="openCategories.has(category)" class="divide-y divide-border">
                    <label v-for="p in perms" :key="p.id" class="flex cursor-pointer items-center justify-between px-3 py-2.5 hover:bg-text/2">
                        <div class="pr-2">
                            <p class="text-sm text-text">{{ p.name }}</p>
                            <p v-if="p.description" class="text-xs text-text/50">{{ p.description }}</p>
                        </div>
                        <input type="checkbox" :checked="selectedIds.has(p.id)" class="h-4 w-4 shrink-0 rounded accent-accent" @change="toggle(p.id)" />
                    </label>
                </div>
            </div>
        </div>

        <div v-if="hasChanges" class="shrink-0 flex justify-end gap-2 border-t border-border bg-bg/50 p-4">
            <BaseButton variant="secondary" @click="discard">Discard</BaseButton>
            <BaseButton :loading="saving" @click="save"><template #icon><Save class="h-4 w-4" /></template>Save permissions</BaseButton>
        </div>
    </div>
</template>