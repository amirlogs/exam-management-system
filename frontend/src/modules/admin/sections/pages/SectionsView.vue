<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { Archive, Edit, FilterX, Plus, RefreshCw } from 'lucide-vue-next'

import BaseDialog from '@/shared/components/ui/BaseDialog.vue'
import AppPagination from '@/shared/components/AppPagination.vue'
import BaseButton from '@/shared/components/ui/BaseButton.vue'
import BaseInput from '@/shared/components/ui/BaseInput.vue'
import BaseSelect from '@/shared/components/ui/BaseSelect.vue'

import { useResourceForm } from '@/shared/composables/useResourceForm'
import { sectionSchema } from '../schemas/section.schema'
import { getSections, createSection, updateSection, archiveSection } from '../api/sections'
import { getSemesters } from '@/modules/admin/semesters/api/semesters'
import { getPrograms } from '@/modules/admin/programs/api/programs'
import { handleApiError } from '@/shared/utils/apiError'

import type { Section } from '../types/section'
import type { Semester } from '@/modules/admin/semesters/types/semester'
import type { Program } from '@/modules/admin/programs/types/program'
import type { Pagination } from '@/shared/composables/useCrudResource'
import { useUiStore } from '@/stores/ui'
import ConfirmModal from '@/shared/components/ConfirmModal.vue'

const uiStore = useUiStore()

const semesters = ref<Semester[]>([])
const programs = ref<Program[]>([])
const semesterOptions = computed(() => semesters.value.map((s) => ({ value: String(s.id), label: `Semester ${s.name} · ${s.academic_year}` })))
const programOptions = computed(() => programs.value.map((p) => ({ value: String(p.id), label: p.name })))

const filterSemesterId = ref<number | null>(null)
const filterProgramId = ref<number | null>(null)
const filtersReady = computed(() => !!filterSemesterId.value && !!filterProgramId.value)

const sections = ref<Section[]>([])
const loading = ref(false)
const emptyPagination = (): Pagination => ({ current_page: 1, last_page: 1, per_page: 12, total: 0, from: null, to: null })
const pagination = ref<Pagination>(emptyPagination())

const showFormModal = ref(false)
const showArchiveModal = ref(false)
const selected = ref<Section | null>(null)
const saving = ref(false)
const archiving = ref(false)

const { form, errors, validate, reset, applyServerErrors } = useResourceForm(sectionSchema, {
    semester_id: 0, program_id: 0, year_level: 1, name: 1,
})

onMounted(async () => {
    const [semRes, progRes] = await Promise.all([getSemesters(1, 100), getPrograms(1, 100)])
    semesters.value = semRes.data
    programs.value = progRes.data
})

async function load(page = 1) {
    if (!filtersReady.value) return
    loading.value = true
    try {
        // Backend now filters server-side (RequestFilters) — this returns exactly this semester+program's sections
        const res = await getSections(filterSemesterId.value!, filterProgramId.value!, page)
        sections.value = res.data ?? []
        pagination.value = res.pagination ?? emptyPagination()
    } catch (err) {
        handleApiError(err, uiStore, undefined, 'Failed to load sections.')
        sections.value = []
        pagination.value = emptyPagination()
    } finally {
        loading.value = false
    }
}
function onFilterChange() {
    if (filtersReady.value) load(1)
}
function clearFilters() {
    filterSemesterId.value = null
    filterProgramId.value = null
    sections.value = []
    pagination.value = emptyPagination()
}
function retry() { load(pagination.value.current_page) }

function openCreate() {
    selected.value = null
    reset({ semester_id: filterSemesterId.value ?? 0, program_id: filterProgramId.value ?? 0, year_level: 1, name: sections.value.length + 1 })
    showFormModal.value = true
}
function openEdit(s: Section) {
    selected.value = s
    reset({ semester_id: s.semester_id, program_id: s.program_id, year_level: s.year_level, name: Number(s.name) })
    showFormModal.value = true
}
function closeFormModal() { showFormModal.value = false; selected.value = null }

async function submitForm() {
    if (!validate()) return
    saving.value = true
    const isEditing = !!selected.value
    try {
        if (selected.value) await updateSection(selected.value.id, form)
        else await createSection(form)
        closeFormModal()
        saving.value = false
        uiStore.showToast(isEditing ? 'Section updated.' : 'Section created.', 'success')
        await load(pagination.value.current_page)
    } catch (err) {
        saving.value = false
        // This is the real fix — applyServerErrors was silently omitted before, so 422 field errors never rendered
        handleApiError(err, uiStore, applyServerErrors, isEditing ? 'Failed to update section.' : 'Failed to save section.')
    }
}

function openArchive(s: Section) { selected.value = s; showArchiveModal.value = true }
async function confirmArchive() {
    if (!selected.value) return
    archiving.value = true
    try {
        await archiveSection(selected.value.id)
        showArchiveModal.value = false
        selected.value = null
        archiving.value = false
        uiStore.showToast('Section archived.', 'success')
        await load(pagination.value.current_page)
    } catch (err) {
        archiving.value = false
        handleApiError(err, uiStore, undefined, 'Failed to archive section.')
    }
}
</script>

<template>
    <div class="mx-auto w-full max-w-260 space-y-6 px-6 py-6">
        <section class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-text">Sections</h1>
                <p class="mt-1 text-sm text-text/60">Choose a semester and program to view or manage sections.</p>
            </div>
            <BaseButton :disabled="!filtersReady" @click="openCreate"><template #icon>
                    <Plus class="h-4 w-4" />
                </template>Add
                section</BaseButton>
        </section>

        <div class="flex flex-wrap items-end gap-4 rounded-md border border-border bg-surface p-4">
            <div class="w-full max-w-xs">
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-text/60">Semester</label>
                <BaseSelect :model-value="String(filterSemesterId ?? '')" :options="semesterOptions"
                    placeholder="Select semester"
                    @update:model-value="(v) => { filterSemesterId = Number(v); onFilterChange() }" />
            </div>
            <div class="w-full max-w-xs">
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-text/60">Program</label>
                <BaseSelect :model-value="String(filterProgramId ?? '')" :options="programOptions"
                    placeholder="Select program"
                    @update:model-value="(v) => { filterProgramId = Number(v); onFilterChange() }" />
            </div>
            <BaseButton v-if="filtersReady" variant="secondary" :disabled="loading" @click="retry">
                <template #icon>
                    <RefreshCw :class="['h-4 w-4', loading && 'animate-spin']" />
                </template>
                Refresh
            </BaseButton>
            <BaseButton v-if="filtersReady" variant="ghost" @click="clearFilters"><template #icon>
                    <FilterX class="h-4 w-4" />
                </template>Clear</BaseButton>
        </div>

        <div v-if="!filtersReady"
            class="flex flex-col items-center rounded-md border border-dashed border-border py-16 text-center">
            <FilterX class="mb-3 h-8 w-8 text-text/30" />
            <h3 class="text-sm font-semibold text-text">No sections shown</h3>
            <p class="mt-1 text-sm text-text/55">Select a semester and program above to view available sections.</p>
        </div>

        <div v-else class="rounded-md border border-border bg-surface">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="border-b border-border bg-text/2.5">
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">
                                Section
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">
                                Year
                                level</th>
                            <th
                                class="w-24 px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-text/50">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody v-if="loading" class="divide-y divide-border">
                        <tr v-for="row in 4" :key="row">
                            <td class="px-4 py-4" colspan="3">
                                <div class="h-3.5 w-full max-w-xs animate-pulse rounded bg-text/5" />
                            </td>
                        </tr>
                    </tbody>
                    <tbody v-else-if="sections.length" class="divide-y divide-border">
                        <tr v-for="s in sections" :key="s.id" class="hover:bg-text/2">
                            <td class="px-4 py-3 text-sm font-medium text-text">Section {{ s.name }}</td>
                            <td class="px-4 py-3 text-sm text-text/60">Year {{ s.year_level }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-1">
                                    <button type="button"
                                        class="rounded p-1.5 text-text/50 hover:bg-text/5 hover:text-text" title="Edit"
                                        @click="openEdit(s)">
                                        <Edit class="h-4 w-4" />
                                    </button>
                                    <button type="button"
                                        class="rounded p-1.5 text-text/50 hover:bg-error/10 hover:text-error"
                                        title="Archive" @click="openArchive(s)">
                                        <Archive class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                    <tbody v-else>
                        <tr>
                            <td colspan="3" class="px-6 py-12 text-center text-sm text-text/55">No sections for this
                                semester
                                and program yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <AppPagination :pagination="pagination" @change-page="load" />
        </div>
    </div>

    <BaseDialog :model-value="showFormModal" :title="selected ? 'Edit section' : 'Add section'"
        @update:model-value="closeFormModal">
        <div class="space-y-4">
            <BaseInput v-model.number="form.year_level as any" type="number" label="Year level" placeholder="1"
                :error="errors.year_level" />
            <BaseInput v-model.number="form.name as any" type="number" label="Section number" placeholder="1"
                :error="errors.name" />
        </div>
        <template #footer>
            <div class="flex justify-end gap-2">
                <BaseButton variant="secondary" @click="closeFormModal">Cancel</BaseButton>
                <BaseButton :loading="saving" @click="submitForm">{{ selected ? 'Save changes' : 'Create section' }}
                </BaseButton>
            </div>
        </template>
    </BaseDialog>

    <ConfirmModal :show="showArchiveModal" title="Archive section?"
        :description="`This will archive Section ${selected?.name}.`" confirm-text="Archive section" variant="danger"
        :icon="Archive" :loading="archiving" @close="showArchiveModal = false" @confirm="confirmArchive" />
</template>
