<script setup lang="ts">
import { onMounted, onUnmounted, ref, computed } from 'vue'
import { Archive, Building, Edit, MoreVertical, Plus, RotateCcw } from 'lucide-vue-next'
import ResourceToolbar from '@/shared/components/ResourceToolbar.vue'
import BaseDialog from '@/shared/components/ui/BaseDialog.vue'
import AppPagination from '@/shared/components/AppPagination.vue'
import BaseButton from '@/shared/components/ui/BaseButton.vue'
import BaseInput from '@/shared/components/ui/BaseInput.vue'

import { useCrudResource } from '@/shared/composables/useCrudResource'
import { useResourceForm } from '@/shared/composables/useResourceForm'
import { collegeSchema } from '../schemas/college.schema'
import { getColleges, getArchivedColleges, createCollege, updateCollege, deleteCollege, restoreCollege } from '../api/colleges'

import type { College } from '../types/college'
import { useUiStore } from '@/stores/ui'
import ConfirmModal from '@/shared/components/ConfirmModal.vue'

const uiStore = useUiStore()
const crud = useCrudResource<College>(
    { list: getColleges, listArchived: getArchivedColleges, remove: deleteCollege, restore: restoreCollege },
    'name',
)

const search = ref('')
const openMenuId = ref<number | null>(null)
const menuPosition = ref({ top: 0, left: 0 })
const showFormModal = ref(false)
const showArchiveModal = ref(false)
const showRestoreModal = ref(false)
const selected = ref<College | null>(null)
const saving = ref(false)
const archiving = ref(false)
const restoring = ref(false)

const { form, errors, validate, reset, applyServerErrors } = useResourceForm(collegeSchema, { name: '' })

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
    reset({ name: '' })
    showFormModal.value = true
}
function openEdit(college: College) {
    selected.value = college
    reset({ name: college.name })
    showFormModal.value = true
    openMenuId.value = null
}
function closeFormModal() { showFormModal.value = false; selected.value = null }

async function submitForm() {
    if (!validate()) return
    saving.value = true
    const isEditing = !!selected.value   // captured BEFORE closeFormModal nulls it out
    try {
        if (selected.value) await updateCollege(selected.value.id, form)
        else await createCollege(form)
        closeFormModal()
        saving.value = false
        uiStore.showToast(isEditing ? 'College updated.' : 'College created.', 'success')
        await crud.load(crud.currentPagination().current_page)
    } catch (err: any) {
        saving.value = false
        if (err?.response?.status === 422) {
            applyServerErrors(err.response.data?.errors)
            uiStore.showToast('Please fix the errors below.', 'error')
        } else {
            uiStore.showToast(isEditing ? 'Failed to update college.' : 'Failed to save college.', 'error')
        }
    }
}

function openArchive(college: College) { selected.value = college; showArchiveModal.value = true; openMenuId.value = null }
function openRestore(college: College) { selected.value = college; showRestoreModal.value = true; openMenuId.value = null }

async function confirmArchive() {
    if (!selected.value) return
    archiving.value = true
    try { await crud.archive(selected.value); showArchiveModal.value = false; selected.value = null }
    catch { uiStore.showToast('Failed to archive college.', 'error') }
    finally { archiving.value = false }
}
async function confirmRestore() {
    if (!selected.value) return
    restoring.value = true
    try { await crud.restore(selected.value); showRestoreModal.value = false; selected.value = null }
    catch { uiStore.showToast('Failed to restore college.', 'error') }
    finally { restoring.value = false }
}

const filteredList = computed(() =>
    search.value ? crud.currentList().filter((c) => c.name.toLowerCase().includes(search.value.toLowerCase())) : crud.currentList(),
)
const emptyStateText = computed(() => {
    if (search.value) return 'No colleges found'
    return crud.activeTab.value === 'archived' ? 'No archived colleges' : 'No colleges yet'
})
function retry() { crud.load(crud.currentPagination().current_page) }
</script>

<template>
    <div class="mx-auto w-full max-w-360 space-y-6 px-6 py-6">
        <ResourceToolbar title="Colleges" description="Manage the colleges in your institution."
            search-placeholder="Search colleges..." :show-search="true" :show-refresh="true"
            :refreshing="crud.loading.value" :show-tabs="true" :active-tab="crud.activeTab.value"
            :active-count="crud.activePagination.value.total" :archived-count="crud.archivedPagination.value.total"
            @update:search="search = $event" @refresh="retry" @change-tab="crud.changeTab" :showFullscreen="true">
            <template #actions>
                <BaseButton v-can="['college.create']" @click="openCreate">
                    <template #icon>
                        <Plus class="h-4 w-4" />
                    </template>

                    Add college
                </BaseButton>
            </template>
        </ResourceToolbar>

        <div v-if="crud.error.value" class="flex items-start gap-3 rounded-md border border-error/30 bg-error/5 p-4">
            <p class="flex-1 text-sm font-medium text-error">{{ crud.error.value }}</p>
            <button type="button" class="text-sm font-medium text-error hover:underline" @click="retry">Retry</button>
        </div>

        <div class="rounded-md border border-border bg-surface">
            <div class="overflow-x-auto">
                <table class="w-full min-w-140 border-collapse">
                    <thead>
                        <tr class="border-b border-border bg-text/2.5">
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">
                                College
                            </th>
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
                                <div class="flex items-center gap-3">
                                    <div class="h-9 w-9 animate-pulse rounded-md bg-text/5" />
                                    <div class="h-3.5 w-44 animate-pulse rounded bg-text/5" />
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="h-3.5 w-24 animate-pulse rounded bg-text/5" />
                            </td>
                            <td />
                        </tr>
                    </tbody>

                    <tbody v-else-if="filteredList.length" class="divide-y divide-border">
                        <tr v-for="college in filteredList" :key="college.id"
                            class="group transition-colors hover:bg-text/2">
                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md border border-border bg-bg text-text/50">
                                        <Building class="h-4 w-4" />
                                    </div>
                                    <p class="text-sm font-medium text-text">{{ college.name }}</p>
                                </div>
                            </td>
                            <td class="px-4 py-3.5"><span class="font-mono text-xs tabular-nums text-text/60">{{
                                college.created_at }}</span></td>
                            <td class="px-4 py-3.5 text-right">
                                <button type="button" data-action-trigger
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-md text-text/50 transition hover:bg-text/5 hover:text-text"
                                    @click.stop="toggleMenu(college.id, $event)">
                                    <MoreVertical class="h-4 w-4" />
                                </button>
                                <Teleport to="body">
                                    <div v-if="openMenuId === college.id" data-action-menu
                                        class="fixed z-[100] w-40 rounded-md border border-border bg-surface py-1 shadow-lg"
                                        :style="{ top: menuPosition.top + 'px', left: menuPosition.left + 'px' }"
                                        @click.stop>
                                        <button v-if="crud.activeTab.value === 'active'" type="button"
                                            class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-text hover:bg-text/5"
                                            @click="openEdit(college)">
                                            <Edit class="h-4 w-4 text-text/50" /> Edit
                                        </button>
                                        <div v-if="crud.activeTab.value === 'active'"
                                            class="my-1 border-t border-border" />
                                        <button v-if="crud.activeTab.value === 'active'" type="button"
                                            class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-error hover:bg-error/5"
                                            @click="openArchive(college)">
                                            <Archive class="h-4 w-4" /> Archive
                                        </button>
                                        <button v-else type="button"
                                            class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-accent hover:bg-accent/5"
                                            @click="openRestore(college)">
                                            <RotateCcw class="h-4 w-4" /> Restore
                                        </button>
                                    </div>
                                </Teleport>
                            </td>
                        </tr>
                    </tbody>

                    <tbody v-else>
                        <tr>
                            <td colspan="3" class="px-6 py-16 text-center text-sm text-text/55">{{ emptyStateText }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <AppPagination :pagination="crud.currentPagination()" @change-page="crud.load" />
        </div>
    </div>

    <BaseDialog :model-value="showFormModal" :title="selected ? 'Edit college' : 'Add college'"
        @update:model-value="closeFormModal">
        <BaseInput v-model="form.name" label="College name" placeholder="e.g. College of Engineering"
            :error="errors.name" />
        <template #footer>
            <div class="flex justify-end gap-2">
                <BaseButton variant="secondary" @click="closeFormModal">Cancel</BaseButton>
                <BaseButton :loading="saving" @click="submitForm">{{ selected ? 'Save changes' : 'Create college' }}
                </BaseButton>
            </div>
        </template>
    </BaseDialog>

    <ConfirmModal :show="showArchiveModal" title="Archive college?"
        :description="`This will remove ${selected?.name} from active college views. It can be restored later.`"
        confirm-text="Archive college" variant="danger" :icon="Archive" :loading="archiving"
        @close="showArchiveModal = false" @confirm="confirmArchive" />
    <ConfirmModal :show="showRestoreModal" title="Restore college?"
        :description="`This will return ${selected?.name} to the active college list.`" confirm-text="Restore college"
        variant="accent" :icon="RotateCcw" :loading="restoring" @close="showRestoreModal = false"
        @confirm="confirmRestore" />
</template>
