<script setup lang="ts">
import { onMounted, onUnmounted, ref, computed } from 'vue'
import { Archive, Layers, Edit, MoreVertical, Plus, RefreshCw, RotateCcw, Search, X } from 'lucide-vue-next'

import BaseDialog from '@/shared/components/ui/BaseDialog.vue'
import AppPagination from '@/shared/components/AppPagination.vue'
import BaseButton from '@/shared/components/ui/BaseButton.vue'
import BaseInput from '@/shared/components/ui/BaseInput.vue'
import BaseSelect from '@/shared/components/ui/BaseSelect.vue'
import BaseBadge from '@/shared/components/ui/BaseBadge.vue'

import { useCrudResource } from '@/shared/composables/useCrudResource'
import { useResourceForm } from '@/shared/composables/useResourceForm'
import { departmentSchema } from '../schemas/department.schema'
import { getDepartments, getArchivedDepartments, createDepartment, updateDepartment, deleteDepartment, restoreDepartment } from '../api/departments'
import { getColleges } from '@/modules/admin/colleges/api/colleges'

import type { Department } from '../types/department'
import type { College } from '@/modules/admin/colleges/types/college'
import { useUiStore } from '@/stores/ui'
import ConfirmModal from '@/shared/components/ConfirmModal.vue'

const uiStore = useUiStore()
const crud = useCrudResource<Department>(
    { list: getDepartments, listArchived: getArchivedDepartments, remove: deleteDepartment, restore: restoreDepartment },
    'name',
)

const search = ref('')
const openMenuId = ref<number | null>(null)
const menuPosition = ref({ top: 0, left: 0 })
const showFormModal = ref(false)
const showArchiveModal = ref(false)
const showRestoreModal = ref(false)
const selected = ref<Department | null>(null)
const saving = ref(false)
const archiving = ref(false)
const restoring = ref(false)

const colleges = ref<College[]>([])
const collegeOptions = computed(() => colleges.value.map((c) => ({ value: String(c.id), label: c.name })))
const typeOptions = [
    { value: 'service_only', label: 'Service only' },
    { value: 'degree_granting', label: 'Degree granting' },
]

const { form, errors, validate, reset, applyServerErrors } = useResourceForm(departmentSchema, {
    college_id: 0,
    name: '',
    type: 'service_only' as const,
})

async function loadCollegeOptions() {
    if (colleges.value.length) return
    try {
        const res = await getColleges(1, 100)
        colleges.value = res.data
    } catch (err) {
        console.error('Failed to load colleges for dropdown:', err)
        uiStore.showToast('Failed to load colleges.', 'error')
    }
}

function toggleMenu(id: number, event: MouseEvent) {
    if (openMenuId.value === id) { openMenuId.value = null; return }
    const rect = (event.currentTarget as HTMLElement).getBoundingClientRect()
    const menuHeight = 140
    const openUpward = window.innerHeight - rect.bottom < menuHeight
    menuPosition.value = { top: openUpward ? rect.top - menuHeight - 4 : rect.bottom + 4, left: rect.right - 160 }
    openMenuId.value = id
}
function closeMenus() { openMenuId.value = null }
function handleClickOutside(e: MouseEvent) {
    const target = e.target as HTMLElement
    if (!target.closest('[data-action-menu]') && !target.closest('[data-action-trigger]')) closeMenus()
}

onMounted(() => {
    crud.load(1)
    document.addEventListener('click', handleClickOutside)
    window.addEventListener('scroll', closeMenus, true)
})
onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
    window.removeEventListener('scroll', closeMenus, true)
})

function openCreate() {
    selected.value = null
    reset({ college_id: 0, name: '', type: 'service_only' })
    loadCollegeOptions()
    showFormModal.value = true
}
function openEdit(dept: Department) {
    selected.value = dept
    reset({ college_id: dept.college_id, name: dept.name, type: dept.type })
    loadCollegeOptions()
    showFormModal.value = true
    openMenuId.value = null
}
function closeFormModal() { showFormModal.value = false; selected.value = null }

async function submitForm() {
    if (!validate()) return
    saving.value = true
    try {
        if (selected.value) await updateDepartment(selected.value.id, form)
        else await createDepartment(form)
        closeFormModal()
        saving.value = false
        uiStore.showToast(selected.value ? 'Department updated.' : 'Department created.', 'success')
        await crud.load(crud.currentPagination().current_page)
    } catch (err: any) {
        saving.value = false
        if (err?.response?.status === 422) {
            applyServerErrors(err.response.data?.errors)
            uiStore.showToast('Please fix the errors below.', 'error')
        } else {
            uiStore.showToast('Failed to save department.', 'error')
        }
    }
}

const emptyStateText = computed(() => {
    if (search.value) return 'No departments found'
    return crud.activeTab.value === 'archived' ? 'No archived departments' : 'No departments yet'
})
function openArchive(dept: Department) { selected.value = dept; showArchiveModal.value = true; openMenuId.value = null }
function openRestore(dept: Department) { selected.value = dept; showRestoreModal.value = true; openMenuId.value = null }

async function confirmArchive() {
    if (!selected.value) return
    archiving.value = true
    try { await crud.archive(selected.value); showArchiveModal.value = false; selected.value = null }
    catch { uiStore.showToast('Failed to archive department.', 'error') }
    finally { archiving.value = false }
}
async function confirmRestore() {
    if (!selected.value) return
    restoring.value = true
    try { await crud.restore(selected.value); showRestoreModal.value = false; selected.value = null }
    catch { uiStore.showToast('Failed to restore department.', 'error') }
    finally { restoring.value = false }
}



const filteredList = computed(() =>
    search.value ? crud.currentList().filter((d) => d.name.toLowerCase().includes(search.value.toLowerCase())) : crud.currentList(),
)
function retry() { crud.load(crud.currentPagination().current_page) }
function typeVariant(type: string) { return type === 'degree_granting' ? 'success' : 'neutral' }
function typeLabel(type: string) { return type === 'degree_granting' ? 'Degree granting' : 'Service only' }

</script>

<template>
    <div class="mx-auto w-full max-w-360 space-y-6 px-6 py-6">
        <section class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-text">Departments</h1>
                <p class="mt-1 text-sm text-text/60">Manage departments under each college.</p>
            </div>
            <BaseButton @click="openCreate"><template #icon>
                    <Plus class="h-4 w-4" />
                </template>Add department
            </BaseButton>
        </section>

        <div class="border-b border-border">
            <div class="flex gap-6">
                <button type="button"
                    :class="['relative flex h-10 items-center gap-2 text-sm font-medium transition-colors', crud.activeTab.value === 'active' ? 'text-accent' : 'text-text/60 hover:text-text']"
                    @click="crud.changeTab('active')">
                    <Layers class="h-4 w-4" /> Active
                    <span class="rounded-full bg-accent/10 px-2 py-0.5 text-xs tabular-nums">{{
                        crud.activePagination.value.total }}</span>
                    <span v-if="crud.activeTab.value === 'active'"
                        class="absolute inset-x-0 bottom-0 h-0.5 bg-accent" />
                </button>
                <button type="button"
                    :class="['relative flex h-10 items-center gap-2 text-sm font-medium transition-colors', crud.activeTab.value === 'archived' ? 'text-accent' : 'text-text/60 hover:text-text']"
                    @click="crud.changeTab('archived')">
                    <Archive class="h-4 w-4" /> Archived
                    <span class="rounded-full bg-text/5 px-2 py-0.5 text-xs tabular-nums">{{
                        crud.archivedPagination.value.total
                        }}</span>
                    <span v-if="crud.activeTab.value === 'archived'"
                        class="absolute inset-x-0 bottom-0 h-0.5 bg-accent" />
                </button>
            </div>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="relative w-full max-w-md">
                <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-text/40" />
                <input v-model="search" type="search" placeholder="Search departments..."
                    class="h-9 w-full rounded-md border border-border bg-surface pl-9 pr-9 text-sm text-text outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20" />
                <button v-if="search" type="button"
                    class="absolute right-2 top-1/2 flex h-6 w-6 -translate-y-1/2 items-center justify-center rounded text-text/50 hover:bg-text/5 hover:text-text"
                    @click="search = ''">
                    <X class="h-3.5 w-3.5" />
                </button>
            </div>
            <BaseButton variant="secondary" :disabled="crud.loading.value" @click="retry">
                <template #icon>
                    <RefreshCw :class="['h-4 w-4', crud.loading.value && 'animate-spin']" />
                </template>Refresh
            </BaseButton>
        </div>

        <div v-if="crud.error.value" class="flex items-start gap-3 rounded-md border border-error/30 bg-error/5 p-4">
            <p class="flex-1 text-sm font-medium text-error">{{ crud.error.value }}</p>
            <button type="button" class="text-sm font-medium text-error hover:underline" @click="retry">Retry</button>
        </div>

        <div class="rounded-md border border-border bg-surface">
            <div class="overflow-x-auto">
                <table class="w-full min-w-190 border-collapse">
                    <thead>
                        <tr class="border-b border-border bg-text/2.5">
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">
                                Department</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">
                                College
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">
                                Type</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">
                                Created
                            </th>
                            <th
                                class="w-16 px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-text/50">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody v-if="crud.loading.value" class="divide-y divide-border">
                        <tr v-for="row in 6" :key="row">
                            <td class="px-4 py-4">
                                <div class="h-3.5 w-40 animate-pulse rounded bg-text/5" />
                            </td>
                            <td class="px-4 py-4">
                                <div class="h-3.5 w-32 animate-pulse rounded bg-text/5" />
                            </td>
                            <td class="px-4 py-4">
                                <div class="h-6 w-24 animate-pulse rounded bg-text/5" />
                            </td>
                            <td class="px-4 py-4">
                                <div class="h-3.5 w-24 animate-pulse rounded bg-text/5" />
                            </td>
                            <td />
                        </tr>
                    </tbody>
                    <tbody v-else-if="filteredList.length" class="divide-y divide-border">
                        <tr v-for="dept in filteredList" :key="dept.id" class="group transition-colors hover:bg-text/2">
                            <td class="px-4 py-3.5">
                                <p class="text-sm font-medium text-text">{{ dept.name }}</p>
                            </td>
                            <td class="px-4 py-3.5"><span class="text-sm text-text/60">{{ dept.college?.name || '—'
                                    }}</span>
                            </td>
                            <td class="px-4 py-3.5">
                                <BaseBadge :variant="typeVariant(dept.type)">{{ typeLabel(dept.type) }}</BaseBadge>
                            </td>
                            <td class="px-4 py-3.5"><span class="font-mono text-xs tabular-nums text-text/60">{{
                                dept.created_at
                                    }}</span></td>
                            <td class="px-4 py-3.5 text-right">
                                <button type="button" data-action-trigger
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-md text-text/50 transition hover:bg-text/5 hover:text-text"
                                    @click.stop="toggleMenu(dept.id, $event)">
                                    <MoreVertical class="h-4 w-4" />
                                </button>
                                <Teleport to="body">
                                    <div v-if="openMenuId === dept.id" data-action-menu
                                        class="fixed z-[100] w-40 rounded-md border border-border bg-surface py-1 shadow-lg"
                                        :style="{ top: menuPosition.top + 'px', left: menuPosition.left + 'px' }"
                                        @click.stop>
                                        <button v-if="crud.activeTab.value === 'active'" type="button"
                                            class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-text hover:bg-text/5"
                                            @click="openEdit(dept)">
                                            <Edit class="h-4 w-4 text-text/50" /> Edit
                                        </button>
                                        <div v-if="crud.activeTab.value === 'active'"
                                            class="my-1 border-t border-border" />
                                        <button v-if="crud.activeTab.value === 'active'" type="button"
                                            class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-error hover:bg-error/5"
                                            @click="openArchive(dept)">
                                            <Archive class="h-4 w-4" /> Archive
                                        </button>
                                        <button v-else type="button"
                                            class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-accent hover:bg-accent/5"
                                            @click="openRestore(dept)">
                                            <RotateCcw class="h-4 w-4" /> Restore
                                        </button>
                                    </div>
                                </Teleport>
                            </td>
                        </tr>
                    </tbody>
                    <tbody v-else>
                        <tr>
                            <td colspan="5" class="px-6 py-16 text-center text-sm text-text/55">{{ emptyStateText }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <AppPagination :pagination="crud.currentPagination()" @change-page="crud.load" />
        </div>
    </div>

    <BaseDialog :model-value="showFormModal" :title="selected ? 'Edit department' : 'Add department'"
        @update:model-value="closeFormModal">
        <div class="space-y-4">
            <BaseSelect v-model="form.college_id as any" label="College" :options="collegeOptions"
                placeholder="Select college" :error="errors.college_id" />
            <BaseInput v-model="form.name" label="Department name" placeholder="e.g. Computer Science"
                :error="errors.name" />
            <BaseSelect v-model="form.type" label="Type" :options="typeOptions" :error="errors.type" />
        </div>
        <template #footer>
            <div class="flex justify-end gap-2">
                <BaseButton variant="secondary" @click="closeFormModal">Cancel</BaseButton>
                <BaseButton :loading="saving" @click="submitForm">{{ selected ? 'Save changes' : 'Create department' }}
                </BaseButton>
            </div>
        </template>
    </BaseDialog>

    <ConfirmModal :show="showArchiveModal" title="Archive department?"
        :description="`This will remove ${selected?.name} from active department views.`"
        confirm-text="Archive department" variant="danger" :icon="Archive" :loading="archiving"
        @close="showArchiveModal = false" @confirm="confirmArchive" />
    <ConfirmModal :show="showRestoreModal" title="Restore department?"
        :description="`This will return ${selected?.name} to the active department list.`"
        confirm-text="Restore department" variant="accent" :icon="RotateCcw" :loading="restoring"
        @close="showRestoreModal = false" @confirm="confirmRestore" />
</template>
