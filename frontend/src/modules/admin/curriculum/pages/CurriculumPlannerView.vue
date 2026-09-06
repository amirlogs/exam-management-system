<script setup lang="ts">
import { onMounted, ref, computed } from 'vue';

import { BookOpen, Check, Plus, Trash2, RefreshCw, Power, Pencil } from 'lucide-vue-next';

import BaseDialog from '@/shared/components/ui/BaseDialog.vue';
import BaseButton from '@/shared/components/ui/BaseButton.vue';
import BaseInput from '@/shared/components/ui/BaseInput.vue';
import BaseSelect from '@/shared/components/ui/BaseSelect.vue';
import BaseSearchableSelect from '@/shared/components/ui/BaseSearchableSelect.vue';
import ConfirmModal from '@/shared/components/ConfirmModal.vue';

import { useResourceForm } from '@/shared/composables/useResourceForm';
import { curriculumVersionSchema, curriculumCourseSchema } from '../schemas/curriculum.schema';

import { getCurriculums, createCurriculum, updateCurriculum, activateCurriculum, deactivateCurriculum, addCourseToCurriculum, removeCurriculumCourse } from '../api/curriculum';

import { getPrograms } from '@/modules/admin/programs/api/programs';
import { getCourses } from '@/modules/admin/courses/api/courses';
import { handleApiError } from '@/shared/utils/apiError';

import type { Curriculum, CurriculumCourse } from '../types/curriculum';

import type { Program } from '@/modules/admin/programs/types/program';
import type { Course } from '@/modules/admin/courses/types/course';

import { useUiStore } from '@/stores/ui';

const uiStore = useUiStore();

/*
|--------------------------------------------------------------------------
| Program
|--------------------------------------------------------------------------
*/

const programs = ref<Program[]>([]);
const selectedProgramId = ref<number | null>(null);

const selectedProgram = computed(() => programs.value.find((program) => program.id === selectedProgramId.value) ?? null);

const programOptions = computed(() =>
  programs.value.map((program) => ({
    value: String(program.id),
    label: program.name,
  })),
);

/*
|--------------------------------------------------------------------------
| Curriculum
|--------------------------------------------------------------------------
*/

const curricula = ref<Curriculum[]>([]);
const selectedCurriculumId = ref<number | null>(null);

const selectedCurriculum = computed(() => curricula.value.find((curriculum) => curriculum.id === selectedCurriculumId.value) ?? null);

const loadingCurricula = ref(false);
const activating = ref(false);

/*
|--------------------------------------------------------------------------
| Courses
|--------------------------------------------------------------------------
*/

const courses = ref<Course[]>([]);

const courseOptions = computed(() =>
  courses.value.map((course) => ({
    value: String(course.id),
    label: `${course.code} — ${course.name}`,
  })),
);

/*
|--------------------------------------------------------------------------
| Loading
|--------------------------------------------------------------------------
*/

async function loadPrograms() {
  try {
    const res = await getPrograms(1, 100);

    programs.value = res.data;

    if (programs.value.length && !selectedProgramId.value) {
      selectedProgramId.value = programs.value[0].id;
    }
  } catch (err) {
    handleApiError(err, uiStore, undefined, 'Failed to load programs.');
  }
}

async function loadCourses() {
  if (courses.value.length) return;

  try {
    const res = await getCourses(1, 200);

    courses.value = res.data;
  } catch (err) {
    handleApiError(err, uiStore, undefined, 'Failed to load courses.');
  }
}

async function loadCurricula() {
  if (!selectedProgramId.value) return;

  loadingCurricula.value = true;

  try {
    const res = await getCurriculums(selectedProgramId.value);

    curricula.value = res.data;

    selectedCurriculumId.value = curricula.value.find((curriculum) => curriculum.is_active)?.id ?? curricula.value[0]?.id ?? null;
  } catch (err) {
    handleApiError(err, uiStore, undefined, 'Failed to load curricula.');
  } finally {
    loadingCurricula.value = false;
  }
}

function onProgramChange(id: string) {
  selectedProgramId.value = Number(id);
  selectedCurriculumId.value = null;

  loadCurricula();
}

/*
|--------------------------------------------------------------------------
| Lifecycle
|--------------------------------------------------------------------------
*/

onMounted(async () => {
  await loadPrograms();
  await loadCurricula();
});

/*
|--------------------------------------------------------------------------
| Create Curriculum Version
|--------------------------------------------------------------------------
*/

const showVersionModal = ref(false);
const savingVersion = ref(false);

const {
  form: versionForm,
  errors: versionErrors,
  validate: validateVersion,
  reset: resetVersionForm,
  applyServerErrors: applyVersionErrors,
} = useResourceForm(curriculumVersionSchema, {
  program_id: 0,
  version: '',
});

function openVersionModal() {
  resetVersionForm({
    program_id: selectedProgramId.value ?? 0,
    version: '',
  });

  showVersionModal.value = true;
}

async function submitVersion() {
  if (!validateVersion()) return;

  savingVersion.value = true;

  try {
    await createCurriculum(versionForm);

    showVersionModal.value = false;

    uiStore.showToast('Curriculum version created.', 'success');

    await loadCurricula();
  } catch (err) {
    handleApiError(err, uiStore, applyVersionErrors, 'Failed to create curriculum version.');
  } finally {
    savingVersion.value = false;
  }
}

/*
|--------------------------------------------------------------------------
| Update Curriculum Version
|--------------------------------------------------------------------------
*/

const showEditVersionModal = ref(false);
const updatingVersion = ref(false);

const {
  form: editVersionForm,
  errors: editVersionErrors,
  validate: validateEditVersion,
  reset: resetEditVersionForm,
  applyServerErrors: applyEditVersionErrors,
} = useResourceForm(curriculumVersionSchema, {
  program_id: 0,
  version: '',
});

function openEditVersionModal() {
  if (!selectedCurriculum.value) return;

  resetEditVersionForm({
    program_id: selectedCurriculum.value.program_id,
    version: selectedCurriculum.value.version,
  });

  showEditVersionModal.value = true;
}

async function submitEditVersion() {
  if (!selectedCurriculum.value || !validateEditVersion()) {
    return;
  }

  updatingVersion.value = true;

  try {
    await updateCurriculum(selectedCurriculum.value.id, {
      version: editVersionForm.version,
    });

    showEditVersionModal.value = false;

    uiStore.showToast('Curriculum version updated.', 'success');

    await loadCurricula();
  } catch (err) {
    handleApiError(err, uiStore, applyEditVersionErrors, 'Failed to update curriculum version.');
  } finally {
    updatingVersion.value = false;
  }
}

/*
|--------------------------------------------------------------------------
| Activate / Deactivate
|--------------------------------------------------------------------------
*/

const showActivateModal = ref(false);

function askToggleActivate() {
  if (!selectedCurriculum.value) return;

  showActivateModal.value = true;
}

async function confirmToggleActivate() {
  if (!selectedCurriculum.value) return;

  const curriculum = selectedCurriculum.value;

  activating.value = true;

  try {
    if (curriculum.is_active) {
      await deactivateCurriculum(curriculum.id);

      uiStore.showToast('Curriculum deactivated.', 'success');
    } else {
      await activateCurriculum(curriculum.id);

      uiStore.showToast('Curriculum activated.', 'success');
    }

    showActivateModal.value = false;

    await loadCurricula();
  } catch (err) {
    handleApiError(err, uiStore, undefined, 'Failed to update curriculum status.');
  } finally {
    activating.value = false;
  }
}

/*
|--------------------------------------------------------------------------
| Curriculum Grid
|--------------------------------------------------------------------------
*/

const years = computed(() => {
  const duration = Number(selectedProgram.value?.duration_years) || 4;

  return Array.from({ length: duration }, (_, index) => index + 1);
});

function coursesFor(year: number, semester: number): CurriculumCourse[] {
  return selectedCurriculum.value?.courses.filter((course) => course.year_level === year && course.semester_number === semester) ?? [];
}

function creditsFor(year: number, semester: number): number {
  return coursesFor(year, semester).reduce((sum, course) => sum + (course.course?.credit_hours ?? 0), 0);
}

function creditsForYear(year: number): number {
  return creditsFor(year, 1) + creditsFor(year, 2);
}

/*
|--------------------------------------------------------------------------
| Add Course
|--------------------------------------------------------------------------
*/

const showAddCourseModal = ref(false);
const savingCourse = ref(false);

const {
  form: courseForm,
  errors: courseErrors,
  validate: validateCourse,
  reset: resetCourseForm,
  applyServerErrors: applyCourseErrors,
} = useResourceForm(curriculumCourseSchema, {
  course_id: 0,
  year_level: 1,
  semester_number: 1,
});

function openAddCourse(year: number, semester: number) {
  resetCourseForm({
    course_id: 0,
    year_level: year,
    semester_number: semester,
  });

  loadCourses();

  showAddCourseModal.value = true;
}

async function submitAddCourse() {
  if (!validateCourse() || !selectedCurriculum.value) {
    return;
  }

  savingCourse.value = true;

  try {
    await addCourseToCurriculum(selectedCurriculum.value.id, courseForm);

    showAddCourseModal.value = false;

    uiStore.showToast('Course added to curriculum.', 'success');

    await loadCurricula();
  } catch (err) {
    handleApiError(err, uiStore, applyCourseErrors, 'Failed to add course.');
  } finally {
    savingCourse.value = false;
  }
}

/*
|--------------------------------------------------------------------------
| Remove Course
|--------------------------------------------------------------------------
*/

const showRemoveModal = ref(false);
const removing = ref(false);

const courseToRemove = ref<CurriculumCourse | null>(null);

function openRemove(curriculumCourse: CurriculumCourse) {
  courseToRemove.value = curriculumCourse;

  showRemoveModal.value = true;
}

async function confirmRemove() {
  if (!courseToRemove.value) return;

  removing.value = true;

  try {
    await removeCurriculumCourse(courseToRemove.value.id);

    showRemoveModal.value = false;
    courseToRemove.value = null;

    uiStore.showToast('Course removed from curriculum.', 'success');

    await loadCurricula();
  } catch (err) {
    handleApiError(err, uiStore, undefined, 'Failed to remove course.');
  } finally {
    removing.value = false;
  }
}
</script>

<template>
  <div v-can="'curriculum.view'" class="mx-auto w-full max-w-360 space-y-6 px-6 py-6">
    <!-- ========================================================= -->
    <!-- PAGE HEADER                                               -->
    <!-- ========================================================= -->

    <div class="border-b border-border pb-5">
      <div class="flex flex-col gap-5 md:flex-row md:items-end md:justify-between">
        <!-- LEFT: Title, Description & Program Picker -->
        <div class="min-w-0">
          <h1 class="font-display text-2xl font-bold text-text">Curriculum</h1>

          <p class="mt-1 text-sm text-text/60">Manage curriculum versions and courses for each program.</p>

          <div class="mt-4 w-full max-w-xs">
            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-text/60"> Program </label>

            <BaseSelect
              :model-value="String(selectedProgramId ?? '')"
              :options="programOptions"
              placeholder="Select program"
              @update:model-value="onProgramChange" />
          </div>
        </div>

        <!-- RIGHT: Refresh Button -->
        <div class="flex shrink-0 items-center justify-end gap-2">
          <button
            type="button"
            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-border bg-surface text-text/60 transition-colors hover:border-accent/40 hover:text-accent disabled:pointer-events-none disabled:opacity-50 cursor-pointer"
            title="Refresh"
            :disabled="loadingCurricula"
            @click="loadCurricula">
            <RefreshCw
              class="h-4 w-4"
              :class="{
                'animate-spin': loadingCurricula,
              }" />
          </button>
        </div>
      </div>

      <!-- ===================================================== -->
      <!-- VERSION BAR                                           -->
      <!-- ===================================================== -->

      <div v-if="selectedProgramId" class="mt-5 flex flex-col gap-4 border-t border-border pt-4 lg:flex-row lg:items-center lg:justify-between">
        <!-- VERSION SELECTOR -->
        <div class="flex min-w-0 flex-wrap items-center gap-2">
          <span class="mr-1 text-xs font-bold uppercase tracking-wide text-text/50"> Version </span>

          <div class="flex flex-wrap items-center gap-1 rounded-lg border border-border bg-bg p-1">
            <button
              v-for="curriculum in curricula"
              :key="curriculum.id"
              type="button"
              class="flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-medium transition-colors cursor-pointer"
              :class="curriculum.id === selectedCurriculumId ? 'bg-accent/10 text-accent' : 'text-text/60 hover:bg-text/5 hover:text-text'"
              @click="selectedCurriculumId = curriculum.id">
              {{ curriculum.version }}

              <Check v-if="curriculum.is_active" class="h-3.5 w-3.5" />
            </button>

            <button
              v-can="'curriculum.create'"
              type="button"
              class="flex items-center gap-1 rounded-md px-3 py-1.5 text-sm text-text/50 transition-colors hover:bg-text/5 hover:text-text cursor-pointer"
              @click="openVersionModal">
              <Plus class="h-3.5 w-3.5" />

              New
            </button>
          </div>
        </div>

        <!-- STATUS + ACTIONS -->
        <div v-if="selectedCurriculum" class="flex shrink-0 items-center gap-2">
          <span
            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
            :class="selectedCurriculum.is_active ? 'bg-success/10 text-success' : 'bg-text/5 text-text/50'">
            <span class="h-1.5 w-1.5 rounded-full" :class="selectedCurriculum.is_active ? 'bg-success' : 'bg-text/30'" />

            {{ selectedCurriculum.is_active ? 'Active' : 'Inactive' }}
          </span>

          <!-- Edit -->
          <button
            v-can="'curriculum.update'"
            type="button"
            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-border bg-surface text-text/50 transition-colors hover:border-accent/40 hover:text-accent cursor-pointer"
            title="Edit version"
            @click="openEditVersionModal">
            <Pencil class="h-4 w-4" />
          </button>

          <!-- Activate / Deactivate -->
          <BaseButton
            v-can="selectedCurriculum.is_active ? 'curriculum.deactivate' : 'curriculum.activate'"
            :loading="activating"
            :variant="selectedCurriculum.is_active ? 'secondary' : 'primary'"
            @click="askToggleActivate">
            <template #icon>
              <Power class="h-4 w-4" />
            </template>

            {{ selectedCurriculum.is_active ? 'Deactivate' : 'Activate' }}
          </BaseButton>
        </div>
      </div>
    </div>

    <!-- ========================================================= -->
    <!-- LOADING                                                    -->
    <!-- ========================================================= -->

    <div v-if="loadingCurricula" class="rounded-md border border-border bg-surface py-16 text-center">
      <RefreshCw class="mx-auto h-6 w-6 animate-spin text-text/30" />

      <p class="mt-3 text-sm text-text/50">Loading curriculum...</p>
    </div>

    <!-- ========================================================= -->
    <!-- EMPTY STATE                                                -->
    <!-- ========================================================= -->

    <div v-else-if="!curricula.length" class="flex flex-col items-center rounded-md border border-border bg-surface py-16 text-center">
      <BookOpen class="mb-3 h-8 w-8 text-text/30" />

      <h3 class="text-sm font-semibold text-text">No curriculum versions yet</h3>

      <p class="mt-1 text-sm text-text/55">Create the first version for this program.</p>

      <BaseButton v-can="'curriculum.create'" class="mt-4" @click="openVersionModal">
        <template #icon>
          <Plus class="h-4 w-4" />
        </template>

        New version
      </BaseButton>
    </div>

    <!-- ========================================================= -->
    <!-- CURRICULUM GRID                                            -->
    <!-- ========================================================= -->

    <div v-else class="space-y-8">
      <div v-for="year in years" :key="year">
        <!-- YEAR -->
        <div class="mb-4 flex items-center justify-between border-l-4 border-border pl-3">
          <h2 class="font-display text-xl font-semibold text-text">Year {{ year }}</h2>

          <span class="rounded bg-bg px-2 py-1 font-mono text-xs text-text/50">
            {{ creditsForYear(year) }}
            credit hrs
          </span>
        </div>

        <!-- SEMESTERS -->
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div v-for="semester in [1, 2]" :key="semester" class="flex min-h-55 flex-col rounded-md border border-border bg-surface p-4">
            <div class="mb-3 flex items-center justify-between border-b border-border pb-3">
              <h3 class="font-display text-base font-semibold text-text">
                Semester
                {{ semester }}
              </h3>

              <span class="font-mono text-xs text-text/50">
                {{ creditsFor(year, semester) }}
                credit hrs
              </span>
            </div>

            <!-- COURSES -->
            <div class="flex-1 space-y-2">
              <div v-for="cc in coursesFor(year, semester)" :key="cc.id" class="flex items-center justify-between rounded-md border border-border bg-bg px-3 py-2.5">
                <div class="min-w-0">
                  <p class="truncate font-mono text-xs font-semibold text-text">
                    {{ cc.course?.code ?? 'Unknown course' }}
                  </p>

                  <p class="truncate text-xs text-text/55">
                    {{ cc.course?.name ?? 'Course unavailable' }}
                  </p>
                </div>

                <div class="flex shrink-0 items-center gap-2">
                  <span v-if="cc.course?.credit_hours != null" class="rounded bg-surface px-2 py-0.5 font-mono text-xs text-text/60">
                    {{ cc.course.credit_hours }}
                    hrs
                  </span>

                  <!-- ALWAYS VISIBLE -->
                  <button
                    v-can="'curriculum.course.remove'"
                    type="button"
                    class="inline-flex h-7 w-7 items-center justify-center rounded-md text-text/40 transition-colors hover:bg-error/10 hover:text-error"
                    title="Remove course"
                    @click="openRemove(cc)">
                    <Trash2 class="h-3.5 w-3.5" />
                  </button>
                </div>
              </div>

              <p
                v-if="!coursesFor(year, semester).length"
                class="flex min-h-20 items-center justify-center rounded-md border border-dashed border-border py-4 text-center text-xs text-text/40">
                No courses yet
              </p>
            </div>

            <!-- ADD COURSE -->
            <button
              v-can="'curriculum.course.add'"
              type="button"
              class="mt-3 flex h-9 w-full items-center justify-center gap-1.5 rounded-md border border-dashed border-border text-xs font-medium text-text/50 transition-colors hover:border-accent hover:bg-accent/5 hover:text-accent"
              @click="openAddCourse(year, semester)">
              <Plus class="h-3.5 w-3.5" />

              Add course
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ============================================================= -->
  <!-- CREATE VERSION                                                -->
  <!-- ============================================================= -->

  <BaseDialog :model-value="showVersionModal" title="New curriculum version" @update:model-value="showVersionModal = false">
    <BaseInput v-model="versionForm.version" label="Version" placeholder="e.g. 2027" :error="versionErrors.version" />

    <template #footer>
      <div class="flex justify-end gap-2">
        <BaseButton variant="secondary" @click="showVersionModal = false"> Cancel </BaseButton>

        <BaseButton v-can="'curriculum.create'" :loading="savingVersion" @click="submitVersion"> Create version </BaseButton>
      </div>
    </template>
  </BaseDialog>

  <!-- ============================================================= -->
  <!-- EDIT VERSION                                                  -->
  <!-- ============================================================= -->

  <BaseDialog
    :model-value="showEditVersionModal"
    title="Edit curriculum version"
    description="Update the curriculum version information."
    @update:model-value="showEditVersionModal = false">
    <BaseInput v-model="editVersionForm.version" label="Version" placeholder="e.g. 2027" :error="editVersionErrors.version" />

    <template #footer>
      <div class="flex justify-end gap-2">
        <BaseButton variant="secondary" @click="showEditVersionModal = false"> Cancel </BaseButton>

        <BaseButton v-can="'curriculum.update'" :loading="updatingVersion" @click="submitEditVersion"> Save changes </BaseButton>
      </div>
    </template>
  </BaseDialog>

  <!-- ============================================================= -->
  <!-- ADD COURSE                                                    -->
  <!-- ============================================================= -->

  <BaseDialog
    :model-value="showAddCourseModal"
    title="Add course"
    :description="`Year ${courseForm.year_level}, Semester ${courseForm.semester_number}`"
    @update:model-value="showAddCourseModal = false">
    <BaseSearchableSelect v-model="courseForm.course_id as any" label="Course" :options="courseOptions" placeholder="Search courses..." :error="courseErrors.course_id" />

    <template #footer>
      <div class="flex justify-end gap-2">
        <BaseButton variant="secondary" @click="showAddCourseModal = false"> Cancel </BaseButton>

        <BaseButton v-can="'curriculum.course.add'" :loading="savingCourse" @click="submitAddCourse"> Add course </BaseButton>
      </div>
    </template>
  </BaseDialog>

  <!-- ============================================================= -->
  <!-- ACTIVATE / DEACTIVATE                                         -->
  <!-- ============================================================= -->

  <ConfirmModal
    :show="showActivateModal"
    :title="selectedCurriculum?.is_active ? 'Deactivate this curriculum?' : 'Activate this curriculum?'"
    :description="
      selectedCurriculum?.is_active
        ? 'Students and instructors will no longer see this as the active curriculum.'
        : 'This becomes the live curriculum for this program. Any previously active version will be deactivated.'
    "
    :confirm-text="selectedCurriculum?.is_active ? 'Deactivate' : 'Activate'"
    variant="danger"
    :icon="Power"
    :loading="activating"
    @close="showActivateModal = false"
    @confirm="confirmToggleActivate" />

  <!-- ============================================================= -->
  <!-- REMOVE COURSE                                                 -->
  <!-- ============================================================= -->

  <ConfirmModal
    :show="showRemoveModal"
    title="Remove course?"
    :description="`This will remove ${courseToRemove?.course?.code ?? 'this course'} from this curriculum. This cannot be undone.`"
    confirm-text="Remove course"
    variant="danger"
    :icon="Trash2"
    :loading="removing"
    @close="showRemoveModal = false"
    @confirm="confirmRemove" />
</template>
