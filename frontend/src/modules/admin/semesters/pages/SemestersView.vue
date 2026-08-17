<script setup lang="ts">
import { onMounted, onUnmounted, ref, computed } from 'vue'
import { Archive, Edit, Lock, LockOpen, MoreVertical, Plus, RotateCcw } from 'lucide-vue-next'

import BaseDialog from '@/shared/components/ui/BaseDialog.vue'
import AppPagination from '@/shared/components/AppPagination.vue'
import BaseButton from '@/shared/components/ui/BaseButton.vue'
import BaseInput from '@/shared/components/ui/BaseInput.vue'
import BaseSelect from '@/shared/components/ui/BaseSelect.vue'
import BaseBadge from '@/shared/components/ui/BaseBadge.vue'

import { useResourceForm } from '@/shared/composables/useResourceForm'
import { semesterSchema } from '../schemas/semester.schema'
import { getSemesters, getArchivedSemesters, createSemester, updateSemester, openSemester, closeSemester, archiveSemester, restoreSemester } from '../api/semesters'
import { handleApiError } from '@/shared/utils/apiError'

import type { Semester, SemesterStatus } from '../types/semester'
import type { Pagination } from '@/shared/composables/useCrudResource'
import { useUiStore } from '@/stores/ui'
import ConfirmModal from '@/shared/components/ConfirmModal.vue'

const uiStore = useUiStore()

const semesters = ref<Semester[]>([])
const showArchived = ref(false)
const loading = ref(false)
const emptyPagination = (): Pagination => ({ current_page: 1, last_page: 1, per_page: 12, total: 0, from: null, to: null })
const pagination = ref<Pagination>(emptyPagination())

const openSemesterItem = computed(() => semesters.value.find((s) => s.status === 'active') ?? null)
const otherSemesters = computed(() => semesters.value.filter((s) => s.id !== openSemesterItem.value?.id))

const openMenuId = ref<number | null>(null)
const menuPosition = ref({ top: 0, left: 0 })

const showFormModal = ref(false)
const showArchiveModal = ref(false)
const showToggleModal = ref(false)
const selected = ref<Semester | null>(null)
const semesterToToggle = ref<Semester | null>(null)
const saving = ref(false)
const archiving = ref(false)
const restoring = ref(false)
const toggling = ref(false)

const { form, errors, validate, reset, applyServerErrors } = useResourceForm(semesterSchema, {
    academic_year: new Date().getFullYear(), name: 1, start_date: '', end_date: '',
})
const nameOptions = [{ value: '1', label: 'Semester 1' }, { value: '2', label: 'Semester 2' }]

function semesterTitle(s: Semester) { return `Semester ${s.name} · ${s.academic_year}` }
function statusVariant(status: SemesterStatus) {
    if (status === 'active') return 'success'
    if (status === 'completed') return 'neutral'
    return 'info' // upcoming
}
function statusLabel(status: SemesterStatus) { return status.charAt(0).toUpperCase() + status.slice(1) }

function toggleMenu(id: number, event: MouseEvent) {
    if (openMenuId.value === id) { openMenuId.value = null; return }
    const rect = (event.currentTarget as HTMLElement).getBoundingClientRect()
    const menuHeight = 160
    const openUpward = window.innerHeight - rect.bottom < menuHeight
    menuPosition.value = { top: openUpward ? rect.top - menuHeight - 4 : rect.bottom + 4, left: rect.right - 180 }
    openMenuId.value = id
}
function closeMenus() { openMenuId.value = null }
function handleClickOutside(e: MouseEvent) {
    const t = e.target as HTMLElement
    if (!t.closest('[data-action-menu]') && !t.closest('[data-action-trigger]')) closeMenus()
}

onMounted(() => {
    load(1)
    document.addEventListener('click', handleClickOutside)
    window.addEventListener('scroll', closeMenus, true)
})
onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
    window.removeEventListener('scroll', closeMenus, true)
})

async function load(page = 1) {
    loading.value = true
    try {
        const res = showArchived.value ? await getArchivedSemesters(page) : await getSemesters(page)
        semesters.value = res.data ?? []
        pagination.value = res.pagination ?? emptyPagination()
    } catch (err) {
        handleApiError(err, uiStore, undefined, 'Failed to load semesters.')
    } finally {
        loading.value = false
    }
}
function toggleArchivedView() {
    showArchived.value = !showArchived.value
    load(1)
}

function openCreate() {
    selected.value = null
    reset({ academic_year: new Date().getFullYear(), name: 1, start_date: '', end_date: '' })
    showFormModal.value = true
}
function openEdit(s: Semester) {
    selected.value = s
    reset({ academic_year: Number(s.academic_year), name: Number(s.name), start_date: s.start_date.slice(0, 10), end_date: s.end_date.slice(0, 10) })
    showFormModal.value = true
    openMenuId.value = null
}
function closeFormModal() { showFormModal.value = false; selected.value = null }

async function submitForm() {
    if (!validate()) return
    saving.value = true
    const isEditing = !!selected.value
    try {
        if (selected.value) await updateSemester(selected.value.id, form)
        else await createSemester(form)
        closeFormModal()
        saving.value = false
        uiStore.showToast(isEditing ? 'Semester updated.' : 'Semester created.', 'success')
        await load(pagination.value.current_page)
    } catch (err) {
        saving.value = false
        handleApiError(err, uiStore, applyServerErrors, isEditing ? 'Failed to update semester.' : 'Failed to save semester.')
    }
}

function askToggleOpen(s: Semester) {
    semesterToToggle.value = s
    showToggleModal.value = true
    openMenuId.value = null
}
async function confirmToggleOpen() {
    if (!semesterToToggle.value) return
    const s = semesterToToggle.value
    toggling.value = true
    try {
        if (s.status === 'active') await closeSemester(s.id)
        else await openSemester(s.id)
        showToggleModal.value = false
        semesterToToggle.value = null
        uiStore.showToast(s.status === 'active' ? 'Semester closed.' : 'Semester opened.', 'success')
        await load(pagination.value.current_page)
    } catch (err) {
        handleApiError(err, uiStore, undefined, 'Failed to update semester status.')
    } finally {
        toggling.value = false
    }
}

function openArchive(s: Semester) { selected.value = s; showArchiveModal.value = true; openMenuId.value = null }
async function confirmArchive() {
    if (!selected.value) return
    archiving.value = true
    try {
        await archiveSemester(selected.value.id)
        showArchiveModal.value = false
        selected.value = null
        archiving.value = false
        uiStore.showToast('Semester archived.', 'success')
        await load(pagination.value.current_page)
    } catch (err) {
        archiving.value = false
        handleApiError(err, uiStore, undefined, 'Failed to archive semester.')
    }
}

async function restoreOne(s: Semester) {
    restoring.value = true
    try {
        await restoreSemester(s.id)
        uiStore.showToast('Semester restored.', 'success')
        await load(pagination.value.current_page)
    } catch (err) {
        handleApiError(err, uiStore, undefined, 'Failed to restore semester.')
    } finally {
        restoring.value = false
        openMenuId.value = null
    }
}
</script>

<template>
    <div class="mx-auto w-full max-w-260 space-y-8 px-6 py-6">
        <section class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-text">Semesters</h1>
                <p class="mt-1 text-sm text-text/60">Manage academic terms and the currently active semester.</p>
            </div>
            <BaseButton variant="secondary" @click="toggleArchivedView">
                {{ showArchived ? 'Show active' : 'Show archived' }}
            </BaseButton>
        </section>

        <div v-if="!showArchived && openSemesterItem"
            class="relative overflow-hidden rounded-xl border border-border bg-surface p-8">
            <div class="flex flex-col justify-between gap-6 md:flex-row md:items-start">
                <div class="flex-1">
                    <div class="mb-3 flex items-center gap-3">
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full border border-success/30 bg-success/10 px-3 py-1">
                            <span class="h-2 w-2 rounded-full bg-success" />
                            <span class="text-xs font-bold uppercase tracking-wide text-success">Active</span>
                        </span>
                        <span class="text-xs uppercase tracking-wide text-text/50">Academic year {{
                            openSemesterItem.academic_year }}</span>
                    </div>
                    <h2 class="font-display text-3xl text-text">{{ semesterTitle(openSemesterItem) }}</h2>
                    <div class="mt-6 flex gap-8">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-text/50 mb-1">Start date</p>
                            <p class="font-mono text-sm text-text">{{ openSemesterItem.start_date.slice(0, 10) }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-text/50 mb-1">End date</p>
                            <p class="font-mono text-sm text-text">{{ openSemesterItem.end_date.slice(0, 10) }}</p>
                        </div>
                    </div>
                </div>
                <BaseButton :loading="toggling" variant="secondary" @click="askToggleOpen(openSemesterItem)">
                    <template #icon>
                        <Lock class="h-4 w-4" />
                    </template>
                    Close semester
                </BaseButton>
            </div>
        </div>
        <div v-else-if="!showArchived"
            class="rounded-md border border-dashed border-border p-6 text-center text-sm text-text/55">
            No semester is currently active.
        </div>

        <section>
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-text">{{ showArchived ? 'Archived semesters' : 'Other semesters'
                }}</h2>
                <BaseButton v-if="!showArchived" variant="secondary" @click="openCreate"><template #icon>
                        <Plus class="h-4 w-4" />
                    </template>New semester</BaseButton>
            </div>

            <div class="overflow-hidden rounded-md border border-border bg-surface">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="border-b border-border bg-text/2.5">
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">
                                Semester
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">
                                Status
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">
                                Duration
                            </th>
                            <th
                                class="w-16 px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-text/50">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody v-if="loading" class="divide-y divide-border">
                        <tr v-for="row in 4" :key="row">
                            <td class="px-4 py-4" colspan="4">
                                <div class="h-3.5 w-full max-w-xs animate-pulse rounded bg-text/5" />
                            </td>
                        </tr>
                    </tbody>
                    <tbody v-else-if="(showArchived ? semesters : otherSemesters).length"
                        class="divide-y divide-border">
                        <tr v-for="s in showArchived ? semesters : otherSemesters" :key="s.id" class="hover:bg-text/2">
                            <td class="px-4 py-3 text-sm font-medium text-text">{{ semesterTitle(s) }}</td>
                            <td class="px-4 py-3">
                                <BaseBadge :variant="statusVariant(s.status)">{{ statusLabel(s.status) }}</BaseBadge>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-text/60">{{ s.start_date.slice(0, 10) }} – {{
                                s.end_date.slice(0, 10) }}</td>
                            <td class="px-4 py-3 text-right">
                                <button type="button" data-action-trigger
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-md text-text/50 transition hover:bg-text/5 hover:text-text"
                                    @click.stop="toggleMenu(s.id, $event)">
                                    <MoreVertical class="h-4 w-4" />
                                </button>
                                <Teleport to="body">
                                    <div v-if="openMenuId === s.id" data-action-menu
                                        class="fixed z-[100] w-44 rounded-md border border-border bg-surface py-1 shadow-lg"
                                        :style="{ top: menuPosition.top + 'px', left: menuPosition.left + 'px' }"
                                        @click.stop>
                                        <template v-if="!showArchived">
                                            <button type="button"
                                                class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-text hover:bg-text/5"
                                                @click="openEdit(s)">
                                                <Edit class="h-4 w-4 text-text/50" /> Edit
                                            </button>
                                            <button v-if="s.status !== 'completed'" type="button"
                                                class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-text hover:bg-text/5"
                                                @click="askToggleOpen(s)">
                                                <component :is="s.status === 'active' ? Lock : LockOpen"
                                                    class="h-4 w-4 text-text/50" />
                                                {{ s.status === 'active' ? 'Close' : 'Open' }}
                                            </button>
                                            <div class="my-1 border-t border-border" />
                                            <button type="button"
                                                class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-error hover:bg-error/5"
                                                @click="openArchive(s)">
                                                <Archive class="h-4 w-4" /> Archive
                                            </button>
                                        </template>
                                        <button v-else type="button"
                                            class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-accent hover:bg-accent/5"
                                            @click="restoreOne(s)">
                                            <RotateCcw class="h-4 w-4" /> Restore
                                        </button>
                                    </div>
                                </Teleport>
                            </td>
                        </tr>
                    </tbody>
                    <tbody v-else>
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-sm text-text/55">
                                {{ showArchived ? 'No archived semesters' : 'No other semesters yet' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <AppPagination :pagination="pagination" @change-page="load" />
        </section>
    </div>

    <BaseDialog :model-value="showFormModal" :title="selected ? 'Edit semester' : 'New semester'"
        @update:model-value="closeFormModal">
        <div class="space-y-4">
            <BaseInput v-model.number="form.academic_year as any" type="number" label="Academic year" placeholder="2026"
                :error="errors.academic_year" />
            <BaseSelect v-model="form.name as any" label="Semester" :options="nameOptions" :error="errors.name" />
            <BaseInput v-model="form.start_date" type="date" label="Start date" :error="errors.start_date" />
            <BaseInput v-model="form.end_date" type="date" label="End date" :error="errors.end_date" />
        </div>
        <template #footer>
            <div class="flex justify-end gap-2">
                <BaseButton variant="secondary" @click="closeFormModal">Cancel</BaseButton>
                <BaseButton :loading="saving" @click="submitForm">{{ selected ? 'Save changes' : 'Create semester' }}
                </BaseButton>
            </div>
        </template>
    </BaseDialog>

    <ConfirmModal :show="showToggleModal"
        :title="semesterToToggle?.status === 'active' ? 'Close this semester?' : 'Open this semester?'" :description="semesterToToggle?.status === 'active'
            ? 'This will mark the semester as completed. This cannot be undone from here.'
            : 'This becomes the active semester system-wide. Only one semester can be active at a time.'"
        :confirm-text="semesterToToggle?.status === 'active' ? 'Close semester' : 'Open semester'" variant="danger"
        :icon="semesterToToggle?.status === 'active' ? Lock : LockOpen" :loading="toggling"
        @close="showToggleModal = false" @confirm="confirmToggleOpen" />

    <ConfirmModal :show="showArchiveModal" title="Archive semester?"
        :description="`This will archive ${selected ? semesterTitle(selected) : ''}.`" confirm-text="Archive semester"
        variant="danger" :icon="Archive" :loading="archiving" @close="showArchiveModal = false"
        @confirm="confirmArchive" />
</template>
