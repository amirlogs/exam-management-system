<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { BookOpen, Check, Plus, X, Trash2, RefreshCcw } from 'lucide-vue-next'

import BaseDialog from '@/shared/components/ui/BaseDialog.vue'
import BaseButton from '@/shared/components/ui/BaseButton.vue'
import BaseInput from '@/shared/components/ui/BaseInput.vue'
import BaseSelect from '@/shared/components/ui/BaseSelect.vue'
import BaseBadge from '@/shared/components/ui/BaseBadge.vue'

import { useResourceForm } from '@/shared/composables/useResourceForm'
import { curriculumVersionSchema, curriculumCourseSchema } from '../schemas/curriculum.schema'
import { getCurriculums, createCurriculum, activateCurriculum, deactivateCurriculum, addCourseToCurriculum, removeCurriculumCourse } from '../api/curriculum'
import { getPrograms } from '@/modules/admin/programs/api/programs'
import { getCourses } from '@/modules/admin/courses/api/courses'
import { handleApiError } from '@/shared/utils/apiError'

import type { Curriculum, CurriculumCourse } from '../types/curriculum'
import type { Program } from '@/modules/admin/programs/types/program'
import type { Course } from '@/modules/admin/courses/types/course'
import { useUiStore } from '@/stores/ui'
import ConfirmModal from '@/shared/components/ConfirmModal.vue'

const uiStore = useUiStore()

const programs = ref<Program[]>([])
const selectedProgramId = ref<number | null>(null)
const selectedProgram = computed(() => programs.value.find((p) => p.id === selectedProgramId.value) ?? null)
const programOptions = computed(() => programs.value.map((p) => ({ value: String(p.id), label: p.name })))

const curricula = ref<Curriculum[]>([])
const selectedCurriculumId = ref<number | null>(null)
const selectedCurriculum = computed(() => curricula.value.find((c) => c.id === selectedCurriculumId.value) ?? null)

const loadingCurricula = ref(false)
const activating = ref(false)

const courses = ref<Course[]>([])
// const courseOptions = computed(() => {
//     const deptId = selectedProgram.value?.department?.id
//     const pool = deptId ? courses.value.filter((c) => c.department?.id === deptId) : courses.value
//     return pool.map((c) => ({ value: String(c.id), label: `${c.code} — ${c.name}` }))
// })
const courseOptions = computed(() => {
    courses.value.map((c) => {
        return { value: String(c.id), label: `${c.code} — ${c.name}` }
    })
})

async function loadPrograms() {
    const res = await getPrograms(1, 100)
    programs.value = res.data
    if (programs.value.length && !selectedProgramId.value) {
        selectedProgramId.value = programs.value[0].id
    }
}
async function loadCourses() {
    if (courses.value.length) return
    const res = await getCourses(1, 200)
    courses.value = res.data
}
async function loadCurricula() {
    if (!selectedProgramId.value) return
    loadingCurricula.value = true
    try {
        const res = await getCurriculums(selectedProgramId.value)
        curricula.value = res.data.filter((c) => c.program_id === selectedProgramId.value) // client-side safety net
        selectedCurriculumId.value = curricula.value.find((c) => c.is_active)?.id ?? curricula.value[0]?.id ?? null
    } catch (err) {
        handleApiError(err, uiStore, undefined, 'Failed to load curricula.')
    } finally {
        loadingCurricula.value = false
    }
}

function onProgramChange(id: string) {
    selectedProgramId.value = Number(id)
    loadCurricula()
}

onMounted(async () => {
    await loadPrograms()
    await loadCurricula()
})

// ---------------- New version ----------------
const showVersionModal = ref(false)
const savingVersion = ref(false)
const { form: versionForm, errors: versionErrors, validate: validateVersion, reset: resetVersionForm } = useResourceForm(curriculumVersionSchema, {
    program_id: 0, version: '',
})

function openVersionModal() {
    resetVersionForm({ program_id: selectedProgramId.value ?? 0, version: '' })
    showVersionModal.value = true
}
async function submitVersion() {
    if (!validateVersion()) return
    savingVersion.value = true
    try {
        await createCurriculum(versionForm)
        showVersionModal.value = false
        savingVersion.value = false
        uiStore.showToast('Curriculum version created.', 'success')
        await loadCurricula()
    } catch (err) {
        savingVersion.value = false
        handleApiError(err, uiStore, undefined, 'Failed to create curriculum version.')
    }
}

// ---------------- Activate ----------------
async function toggleActivate() {
    if (!selectedCurriculum.value) return
    activating.value = true
    try {
        if (selectedCurriculum.value.is_active) {
            await deactivateCurriculum(selectedCurriculum.value.id)
        } else {
            await activateCurriculum(selectedCurriculum.value.id)
        }
        uiStore.showToast(selectedCurriculum.value.is_active ? 'Curriculum deactivated.' : 'Curriculum activated.', 'success')
        await loadCurricula()
    } catch (err) {
        handleApiError(err, uiStore, undefined, 'Failed to update curriculum status.')
    } finally {
        activating.value = false
    }
}

// ---------------- Grid ----------------
const years = computed(() => {
    const n = selectedProgram.value?.duration_years ?? 4
    return Array.from({ length: n }, (_, i) => i + 1)
})
function coursesFor(year: number, semester: number): CurriculumCourse[] {
    return selectedCurriculum.value?.courses.filter((c) => c.year_level === year && c.semester_number === semester) ?? []
}
function creditsFor(year: number, semester: number): number {
    return coursesFor(year, semester).reduce((sum, c) => sum + (c.course?.credit_hours ?? 0), 0)
}
function creditsForYear(year: number): number {
    return creditsFor(year, 1) + creditsFor(year, 2)
}

// ---------------- Add course to cell ----------------
const showAddCourseModal = ref(false)
const savingCourse = ref(false)
const { form: courseForm, errors: courseErrors, validate: validateCourse, reset: resetCourseForm } = useResourceForm(curriculumCourseSchema, {
    course_id: 0, year_level: 1, semester_number: 1,
})

function openAddCourse(year: number, semester: number) {
    resetCourseForm({ course_id: 0, year_level: year, semester_number: semester })
    loadCourses()
    showAddCourseModal.value = true
}
async function submitAddCourse() {
    if (!validateCourse() || !selectedCurriculum.value) return
    savingCourse.value = true
    try {
        await addCourseToCurriculum(selectedCurriculum.value.id, courseForm)
        showAddCourseModal.value = false
        savingCourse.value = false
        uiStore.showToast('Course added to curriculum.', 'success')
        await loadCurricula()
    } catch (err) {
        savingCourse.value = false
        handleApiError(err, uiStore, undefined, 'Failed to add course.')
    }
}

// ---------------- Remove course from cell ----------------
const showRemoveModal = ref(false)
const removing = ref(false)
const courseToRemove = ref<CurriculumCourse | null>(null)

function openRemove(cc: CurriculumCourse) {
    courseToRemove.value = cc
    showRemoveModal.value = true
}
async function confirmRemove() {
    if (!courseToRemove.value) return
    removing.value = true
    try {
        await removeCurriculumCourse(courseToRemove.value.id)
        showRemoveModal.value = false
        removing.value = false
        courseToRemove.value = null
        uiStore.showToast('Course removed from curriculum.', 'success')
        await loadCurricula()
    } catch (err) {
        removing.value = false
        handleApiError(err, uiStore, undefined, 'Failed to remove course.')
    }
}
</script>

<template>
    <div class="mx-auto w-full max-w-360 space-y-6 px-6 py-6">
        <!-- Top control bar -->
        <div class="flex flex-col gap-4 border-b border-border pb-4 md:flex-row md:items-end md:justify-between">
            <div class="w-full max-w-xs">
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-text/60">Program</label>
                <!-- swap BaseSelect for: -->
                <BaseSearchableSelect v-model="courseForm.course_id as any" label="Course" :options="courseOptions"
                    placeholder="Search courses..." :error="courseErrors.course_id" />
            </div>
            <BaseButton variant="secondary" :disabled="loadingCurricula" @click="loadCurricula">
                <template #icon>
                    <RefreshCcw :class="['h-4 w-4', loadingCurricula && 'animate-spin']" />
                </template>
                Refresh
            </BaseButton>

            <div class="flex flex-col gap-1.5 md:items-end">
                <label class="text-xs font-bold uppercase tracking-wide text-text/60">Curriculum version</label>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="flex gap-1 rounded-md border border-border bg-bg p-1">
                        <button v-for="c in curricula" :key="c.id" type="button"
                            class="flex items-center gap-1.5 rounded px-3 py-1.5 text-sm font-medium transition-colors"
                            :class="c.id === selectedCurriculumId ? 'bg-accent/10 text-accent' : 'text-text/60 hover:bg-text/5 hover:text-text'"
                            @click="selectedCurriculumId = c.id">
                            {{ c.version }}
                            <Check v-if="c.is_active" class="h-3.5 w-3.5" />
                        </button>
                        <button type="button"
                            class="flex items-center gap-1 rounded px-3 py-1.5 text-sm text-text/50 hover:bg-text/5 hover:text-text"
                            @click="openVersionModal">
                            <Plus class="h-3.5 w-3.5" /> New
                        </button>
                    </div>

                    <BaseButton v-if="selectedCurriculum" :loading="activating"
                        :variant="selectedCurriculum.is_active ? 'secondary' : 'primary'" @click="toggleActivate">
                        <template #icon>
                            <RefreshCcw class="h-4 w-4" />
                        </template>
                        {{ selectedCurriculum.is_active ? 'Deactivate' : 'Activate this version' }}
                    </BaseButton>
                </div>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="loadingCurricula" class="py-16 text-center text-sm text-text/50">Loading curriculum...</div>

        <!-- No curricula for this program -->
        <div v-else-if="!curricula.length"
            class="flex flex-col items-center rounded-md border border-border bg-surface py-16 text-center">
            <BookOpen class="mb-3 h-8 w-8 text-text/30" />
            <h3 class="text-sm font-semibold text-text">No curriculum versions yet</h3>
            <p class="mt-1 text-sm text-text/55">Create the first version for this program.</p>
            <BaseButton class="mt-4" @click="openVersionModal"><template #icon>
                    <Plus class="h-4 w-4" />
                </template>New version
            </BaseButton>
        </div>

        <!-- Year x Semester grid -->
        <div v-else class="space-y-8">
            <div v-for="year in years" :key="year">
                <h2
                    class="font-display mb-4 flex items-center gap-3 border-l-4 border-border pl-3 py-1 text-2xl text-text">
                    Year {{ year }}
                    <span class="rounded bg-bg px-2 py-1 font-mono text-xs text-text/50">{{ creditsForYear(year) }}
                        credit
                        hrs</span>
                </h2>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div v-for="semester in [1, 2]" :key="semester"
                        class="flex h-full flex-col rounded-md border border-border bg-surface p-4">
                        <div class="mb-3 flex items-center justify-between border-b border-border pb-2">
                            <h3 class="font-display text-base text-text">Semester {{ semester }}</h3>
                            <span class="font-mono text-xs text-text/50">{{ creditsFor(year, semester) }} credit
                                hrs</span>
                        </div>

                        <div class="mb-3 flex-1 space-y-2">
                            <div v-for="cc in coursesFor(year, semester)" :key="cc.id"
                                class="group flex items-center justify-between rounded-md border border-border bg-bg px-3 py-2">
                                <div class="min-w-0">
                                    <p class="truncate font-mono text-xs font-semibold text-text">{{ cc.course.code }}
                                    </p>
                                    <p class="truncate text-xs text-text/55">{{ cc.course.name }}</p>
                                </div>
                                <div class="flex shrink-0 items-center gap-2">
                                    <span class="rounded bg-surface px-2 py-0.5 font-mono text-xs text-text/60">{{
                                        cc.course.credit_hours }} hrs</span>
                                    <button type="button"
                                        class="text-text/30 opacity-0 transition-opacity hover:text-error group-hover:opacity-100"
                                        @click="openRemove(cc)">
                                        <X class="h-3.5 w-3.5" />
                                    </button>
                                </div>
                            </div>

                            <p v-if="!coursesFor(year, semester).length" class="py-3 text-center text-xs text-text/40">
                                No
                                courses yet</p>
                        </div>

                        <button type="button"
                            class="flex h-8 w-full items-center justify-center gap-1.5 rounded-md border border-dashed border-border text-xs font-medium text-text/50 transition-colors hover:border-accent hover:text-accent"
                            @click="openAddCourse(year, semester)">
                            <Plus class="h-3.5 w-3.5" /> Add course
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- New version modal -->
    <BaseDialog :model-value="showVersionModal" title="New curriculum version"
        @update:model-value="showVersionModal = false">
        <BaseInput v-model="versionForm.version" label="Version" placeholder="e.g. 2027"
            :error="versionErrors.version" />
        <template #footer>
            <div class="flex justify-end gap-2">
                <BaseButton variant="secondary" @click="showVersionModal = false">Cancel</BaseButton>
                <BaseButton :loading="savingVersion" @click="submitVersion">Create version</BaseButton>
            </div>
        </template>
    </BaseDialog>

    <!-- Add course modal -->
    <BaseDialog :model-value="showAddCourseModal" title="Add course"
        :description="`Year ${courseForm.year_level}, Semester ${courseForm.semester_number}`"
        @update:model-value="showAddCourseModal = false">
        <BaseSelect v-model="courseForm.course_id as any" label="Course" :options="courseOptions"
            placeholder="Select course" :error="courseErrors.course_id" />
        <template #footer>
            <div class="flex justify-end gap-2">
                <BaseButton variant="secondary" @click="showAddCourseModal = false">Cancel</BaseButton>
                <BaseButton :loading="savingCourse" @click="submitAddCourse">Add course</BaseButton>
            </div>
        </template>
    </BaseDialog>

    <ConfirmModal :show="showRemoveModal" title="Remove course?"
        :description="`This will remove ${courseToRemove?.course.code} from this curriculum. This cannot be undone.`"
        confirm-text="Remove course" variant="danger" :icon="Trash2" :loading="removing"
        @close="showRemoveModal = false" @confirm="confirmRemove" />
</template>
