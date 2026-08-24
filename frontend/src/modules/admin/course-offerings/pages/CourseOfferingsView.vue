<script setup lang="ts">
import { onMounted, onBeforeUnmount, ref, computed, watch } from 'vue'
import { GraduationCap, Sparkles, RotateCcw, Archive as ArchiveIcon, Eye } from 'lucide-vue-next'
import ResourceToolbar from '@/shared/components/ResourceToolbar.vue'
import TableRowActions from '@/shared/components/TableRowActions.vue'
import BaseButton from '@/shared/components/ui/BaseButton.vue'
import BaseSelect from '@/shared/components/ui/BaseSelect.vue'
import BaseBadge from '@/shared/components/ui/BaseBadge.vue'
import AppPagination from '@/shared/components/AppPagination.vue'
import ConfirmModal from '@/shared/components/ConfirmModal.vue'
import SuggestionsPanel from '../components/SuggestionsPanel.vue'
import OfferingDetailModal from '../components/OfferingDetailModal.vue'
import {
    listOfferings, getArchivedOfferings, generateSuggestions, createOffering,
    archiveOffering, restoreOffering,
} from '../api/courseOfferings'
import { getSemesters } from '@/modules/admin/semesters/api/semesters'
import type { CourseOffering, OfferingSuggestion, OfferingStatus } from '../types/courseOffering'
import type { Semester } from '@/modules/admin/semesters/types/semester'
import type { Pagination } from '@/shared/composables/useCrudResource'
import { useUiStore } from '@/stores/ui'

const uiStore = useUiStore()

type OfferingColumn = 'course' | 'semester' | 'status' | 'sections' | 'instructors' | 'updated_at'

const columns: { key: OfferingColumn; label: string; required?: boolean }[] = [
    { key: 'course', label: 'Course', required: true },
    { key: 'status', label: 'Status', required: true },
    { key: 'sections', label: 'Sections' },
    { key: 'instructors', label: 'Instructors' },
    { key: 'semester', label: 'Semester' },
    { key: 'updated_at', label: 'Updated' },
]
const visibleColumns = ref<OfferingColumn[]>(['course', 'status', 'sections', 'instructors', 'updated_at'])
const isColumnVisible = (c: OfferingColumn) => visibleColumns.value.includes(c)
function toggleColumn(c: OfferingColumn) {
    const cfg = columns.find((x) => x.key === c)
    if (cfg?.required) return
    isColumnVisible(c)
        ? (visibleColumns.value = visibleColumns.value.filter((x) => x !== c))
        : (visibleColumns.value = [...visibleColumns.value, c])
}
function resetColumns() {
    visibleColumns.value = ['course', 'status', 'sections', 'instructors', 'updated_at']
}
const showColumns = ref(false)
const totalTableColumns = computed(() => visibleColumns.value.length + 1)

const semesters = ref<Semester[]>([])
const selectedSemesterId = ref<number | null>(null)
const semesterOptions = computed(() =>
    semesters.value.map((s) => ({ value: String(s.id), label: `Semester ${s.name} · ${s.academic_year}` })),
)

const activeTab = ref<'active' | 'archived'>('active')
const search = ref('')
const statusFilter = ref<OfferingStatus | null>(null)
const statusFilterOptions = [
    { value: null, label: 'All statuses' },
    { value: 'draft', label: 'Draft' },
    { value: 'approved', label: 'Approved' },
    { value: 'rejected', label: 'Rejected' },
    { value: 'cancelled', label: 'Cancelled' },
]
const hasActiveFilters = computed(() => statusFilter.value !== null)
function clearFilters() {
    search.value = ''
    statusFilter.value = null
    load(1)
}

const list = ref<CourseOffering[]>([])
const pagination = ref<Pagination>({ current_page: 1, last_page: 1, per_page: 10, total: 0, from: 0, to: 0 })
const loading = ref(false)
const error = ref('')

const showSuggestions = ref(false)
const suggestions = ref<OfferingSuggestion[]>([])
const loadingSuggestions = ref(false)
const creatingKey = ref<string | null>(null)

const detailOffering = ref<CourseOffering | null>(null)
const showDetail = ref(false)

const showArchiveModal = ref(false)
const showRestoreModal = ref(false)
const selected = ref<CourseOffering | null>(null)
const archiving = ref(false)
const restoring = ref(false)

async function load(page = 1) {
    if (!selectedSemesterId.value) return
    loading.value = true
    error.value = ''
    try {
        const fetcher = activeTab.value === 'active' ? listOfferings : getArchivedOfferings
        const res = await fetcher(selectedSemesterId.value, page, 10, statusFilter.value ?? undefined)
        list.value = res.data
        pagination.value = res.pagination
    } catch {
        error.value = 'Failed to load course offerings.'
    } finally {
        loading.value = false
    }
}

const filteredList = computed(() =>
    list.value.filter((o) => {
        if (!search.value) return true
        const q = search.value.toLowerCase()
        const hay = `${o.course?.code ?? ''} ${o.course?.name ?? ''}`.toLowerCase()
        return hay.includes(q)
    }),
)

watch(statusFilter, () => load(1))
watch(activeTab, () => load(1))

function onSemesterChange(id: string) {
    selectedSemesterId.value = Number(id)
    showSuggestions.value = false
    load(1)
}
function changeTab(tab: 'active' | 'archived') {
    activeTab.value = tab
}
function retry() {
    load(pagination.value.current_page)
}

async function openSuggestions() {
    if (!selectedSemesterId.value) return
    loadingSuggestions.value = true
    showSuggestions.value = true
    try {
        suggestions.value = await generateSuggestions(selectedSemesterId.value)
    } catch {
        uiStore.showToast('Failed to generate suggestions.', 'error')
    } finally {
        loadingSuggestions.value = false
    }
}
async function createFromSuggestion(s: OfferingSuggestion) {
    if (!selectedSemesterId.value) return
    const key = `${s.course_id}-${s.program_id}`
    creatingKey.value = key
    try {
        await createOffering(s.course_id, selectedSemesterId.value)
        suggestions.value = suggestions.value.filter((x) => !(x.course_id === s.course_id && x.program_id === s.program_id))
        uiStore.showToast(`${s.course_code} offering created.`, 'success')
        await load(1)
    } catch (err: any) {
        uiStore.showToast(err?.response?.data?.message || 'Failed to create offering.', 'error')
    } finally {
        creatingKey.value = null
    }
}

function openDetail(offering: CourseOffering) {
    detailOffering.value = offering
    showDetail.value = true
}
function onUpdated(updated: CourseOffering) {
    const idx = list.value.findIndex((o) => o.id === updated.id)
    if (idx !== -1) list.value[idx] = updated
}
function onArchived(id: number) {
    list.value = list.value.filter((o) => o.id !== id)
    pagination.value.total = Math.max(0, pagination.value.total - 1)
}

function openArchive(o: CourseOffering) {
    selected.value = o
    showArchiveModal.value = true
}
async function confirmArchive() {
    if (!selected.value) return
    archiving.value = true
    try {
        await archiveOffering(selected.value.id)
        uiStore.showToast('Offering archived.', 'success')
        showArchiveModal.value = false
        selected.value = null
        await load(pagination.value.current_page)
    } catch (err: any) {
        uiStore.showToast(err?.response?.data?.message || 'Failed to archive offering.', 'error')
    } finally {
        archiving.value = false
    }
}

function openRestore(o: CourseOffering) {
    selected.value = o
    showRestoreModal.value = true
}
async function confirmRestore() {
    if (!selected.value) return
    restoring.value = true
    try {
        await restoreOffering(selected.value.id)
        uiStore.showToast('Offering restored.', 'success')
        showRestoreModal.value = false
        selected.value = null
        await load(pagination.value.current_page)
    } catch {
        uiStore.showToast('Failed to restore offering.', 'error')
    } finally {
        restoring.value = false
    }
}

function handleRowAction(key: string, o: CourseOffering) {
    if (key === 'view') openDetail(o)
}

function statusVariant(status: OfferingStatus) {
    return status === 'approved' ? 'success' : status === 'rejected' ? 'danger' : status === 'cancelled' ? 'neutral' : 'info'
}

const emptyStateText = computed(() => {
    if (search.value || hasActiveFilters.value) return 'No matching offerings found'
    return activeTab.value === 'archived' ? 'No archived offerings' : 'No offerings yet for this semester'
})

function handleDocumentClick(event: MouseEvent) {
    const target = event.target as HTMLElement
    if (!target.closest('[data-columns-container]')) {
        showColumns.value = false
    }
}

onMounted(async () => {
    document.addEventListener('click', handleDocumentClick)
    const res = await getSemesters(1, 100)
    semesters.value = res.data
    selectedSemesterId.value = semesters.value.find((s) => s.status === 'active')?.id ?? semesters.value[0]?.id ?? null
    if (selectedSemesterId.value) await load(1)
})

onBeforeUnmount(() => {
    document.removeEventListener('click', handleDocumentClick)
})
</script>

<template>
    <div class="mx-auto w-full max-w-360 space-y-6 px-6 py-6">

        <div data-columns-container class="relative">
            <ResourceToolbar title="Course offerings" description="Staff and approve course sections for the semester."
                search-placeholder="Search by course code or name..." :show-search="true" :show-filter="true"
                :show-refresh="true" :show-columns="true" :show-fullscreen="true" :show-tabs="true"
                :active-tab="activeTab" :active-count="activeTab === 'active' ? pagination.total : undefined"
                :archived-count="activeTab === 'archived' ? pagination.total : undefined"
                :has-active-filters="hasActiveFilters" :refreshing="loading" @clear-filters="clearFilters"
                @change-tab="changeTab" @update:search="search = $event" @refresh="retry"
                @columns="showColumns = !showColumns">
                <template #actions>
                    <BaseSelect :model-value="String(selectedSemesterId ?? '')" :options="semesterOptions"
                        placeholder="Select semester" @update:model-value="onSemesterChange" />
                    <BaseButton v-if="activeTab === 'active'" v-can="'course_offering.create'" @click="openSuggestions">
                        <template #icon>
                            <Sparkles class="h-4 w-4" />
                        </template>Generate suggestions
                    </BaseButton>
                </template>

                <template #filters>
                    <BaseSelect v-model="statusFilter" label="Status" :options="statusFilterOptions" />
                </template>
            </ResourceToolbar>

            <div v-if="showColumns"
                class="absolute right-0 top-full z-40 mt-2 w-64 rounded-lg border border-border bg-surface p-2 shadow-xl"
                @click.stop>
                <div class="flex items-center justify-between px-2 py-2">
                    <div>
                        <p class="text-sm font-semibold text-text">Columns</p>
                        <p class="mt-0.5 text-xs text-text/45">Choose what appears in the table</p>
                    </div>
                    <button type="button"
                        class="rounded-md px-2 py-1 text-xs font-medium text-text/50 transition-colors hover:bg-text/5 hover:text-accent"
                        @click="resetColumns">Reset</button>
                </div>
                <div class="my-1 border-t border-border" />
                <div class="space-y-0.5">
                    <button v-for="column in columns" :key="column.key" type="button"
                        class="flex w-full items-center gap-3 rounded-md px-2 py-2 text-left transition-colors hover:bg-text/5"
                        @click="toggleColumn(column.key)">
                        <span class="flex h-4 w-4 shrink-0 items-center justify-center rounded border transition-colors"
                            :class="isColumnVisible(column.key) ? 'border-accent bg-accent text-white' : 'border-border bg-surface'">
                            <svg v-if="isColumnVisible(column.key)" viewBox="0 0 12 12" class="h-3 w-3" fill="none"
                                stroke="currentColor" stroke-width="2">
                                <path d="M2 6l2.5 2.5L10 3" />
                            </svg>
                        </span>
                        <span class="flex-1 text-sm text-text/80">{{ column.label }}</span>
                        <span v-if="column.required" class="text-[10px] font-medium text-text/35">Always</span>
                    </button>
                </div>
            </div>
        </div>

        <SuggestionsPanel v-if="showSuggestions && activeTab === 'active'" :suggestions="suggestions"
            :creating-key="creatingKey" @create="createFromSuggestion" @close="showSuggestions = false" />

        <div v-if="error" class="flex items-start gap-3 rounded-md border border-error/30 bg-error/5 p-4">
            <p class="flex-1 text-sm font-medium text-error">{{ error }}</p>
            <button type="button" class="text-sm font-medium text-error hover:underline" @click="retry">Retry</button>
        </div>

        <div v-can="'course_offering.view'" class="overflow-hidden rounded-md border border-border bg-surface">
            <div class="overflow-x-auto">
                <table class="w-full min-w-190 border-collapse">
                    <thead>
                        <tr class="border-b border-border bg-text/2.5">
                            <th v-if="isColumnVisible('course')" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Course</th>
                            <th v-if="isColumnVisible('status')" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Status</th>
                            <th v-if="isColumnVisible('sections')" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Sections</th>
                            <th v-if="isColumnVisible('instructors')" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Instructors</th>
                            <th v-if="isColumnVisible('semester')" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Semester</th>
                            <th v-if="isColumnVisible('updated_at')" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Updated</th>
                            <th class="w-20 px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-text/50">Actions</th>
                        </tr>
                    </thead>

                    <tbody v-if="loading" class="divide-y divide-border">
                        <tr v-for="row in 6" :key="row">
                            <td v-for="column in visibleColumns" :key="column" class="px-4 py-4">
                                <div class="h-3.5 animate-pulse rounded bg-text/5"
                                    :class="column === 'course' ? 'w-44' : 'w-20'" />
                            </td>
                            <td class="px-4 py-4"><div class="ml-auto h-8 w-8 animate-pulse rounded-md bg-text/5" /></td>
                        </tr>
                    </tbody>

                    <tbody v-else-if="filteredList.length" class="divide-y divide-border">
                        <tr v-for="o in filteredList" :key="o.id" class="group cursor-pointer transition-colors hover:bg-text/2"
                            @click="openDetail(o)">
                            <td v-if="isColumnVisible('course')" class="px-4 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md border border-border bg-bg text-text/50">
                                        <GraduationCap class="h-4 w-4" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-text">{{ o.course?.name }}</p>
                                        <p class="font-mono text-xs text-text/50">{{ o.course?.code }}</p>
                                    </div>
                                </div>
                            </td>
                            <td v-if="isColumnVisible('status')" class="px-4 py-3.5">
                                <BaseBadge :variant="statusVariant(o.status)">{{ o.status }}</BaseBadge>
                            </td>
                            <td v-if="isColumnVisible('sections')" class="px-4 py-3.5">
                                <span class="text-sm" :class="(o.sections?.length ?? 0) > 0 ? 'text-text/70' : 'text-text/35'">
                                    {{ o.sections?.length ?? 0 }}
                                </span>
                            </td>
                            <td v-if="isColumnVisible('instructors')" class="px-4 py-3.5">
                                <span class="text-sm" :class="(o.instructors?.length ?? 0) > 0 ? 'text-text/70' : 'text-text/35'">
                                    {{ o.instructors?.length ?? 0 }}
                                </span>
                            </td>
                            <td v-if="isColumnVisible('semester')" class="px-4 py-3.5">
                                <span class="text-sm text-text/60">{{ o.semester?.name }} · {{ o.semester?.academic_year }}</span>
                            </td>
                            <td v-if="isColumnVisible('updated_at')" class="px-4 py-3.5">
                                <span class="font-mono text-xs tabular-nums text-text/60">{{ o.updated_at || '—' }}</span>
                            </td>
                            <td class="px-4 py-3.5 text-right" @click.stop>
                                <TableRowActions :active-tab="activeTab" archive-permission="course_offering.archive"
                                    restore-permission="course_offering.restore"
                                    :extra-actions="[{ key: 'view', label: 'View', icon: Eye, showOn: 'both' }]"
                                    @archive="openArchive(o)" @restore="openRestore(o)"
                                    @action="(key) => handleRowAction(key, o)" />
                            </td>
                        </tr>
                    </tbody>

                    <tbody v-else>
                        <tr>
                            <td :colspan="totalTableColumns" class="px-6 py-16 text-center">
                                <div class="mx-auto flex max-w-sm flex-col items-center">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-text/5 text-text/40">
                                        <GraduationCap class="h-5 w-5" />
                                    </div>
                                    <p class="mt-3 text-sm font-medium text-text">{{ emptyStateText }}</p>
                                    <p v-if="search || hasActiveFilters" class="mt-1 text-xs text-text/45">
                                        Try adjusting your search or filters.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <AppPagination :pagination="pagination" @change-page="load" />
        </div>
    </div>

    <OfferingDetailModal v-model="showDetail" :offering="detailOffering" @updated="onUpdated" @archived="onArchived" />

    <ConfirmModal :show="showArchiveModal" title="Archive this offering?"
        :description="`This will remove the ${selected?.course?.code} offering from the active list.`"
        confirm-text="Archive offering" variant="danger" :icon="ArchiveIcon" :loading="archiving"
        @close="showArchiveModal = false" @confirm="confirmArchive" />

    <ConfirmModal :show="showRestoreModal" title="Restore this offering?"
        :description="`This will return the ${selected?.course?.code} offering to the active list.`"
        confirm-text="Restore offering" variant="accent" :icon="RotateCcw" :loading="restoring"
        @close="showRestoreModal = false" @confirm="confirmRestore" />
</template>
