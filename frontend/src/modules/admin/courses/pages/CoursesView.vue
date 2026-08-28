<script setup lang="ts">
import { onMounted, onBeforeUnmount, ref, computed, watch } from 'vue';
import { Archive, GraduationCap, Plus, RotateCcw } from 'lucide-vue-next';

import ResourceToolbar from '@/shared/components/ResourceToolbar.vue';
import TableRowActions from '@/shared/components/TableRowActions.vue';
import BaseDialog from '@/shared/components/ui/BaseDialog.vue';
import ConfirmModal from '@/shared/components/ConfirmModal.vue';
import AppPagination from '@/shared/components/AppPagination.vue';
import BaseButton from '@/shared/components/ui/BaseButton.vue';
import BaseInput from '@/shared/components/ui/BaseInput.vue';
import BaseSelect from '@/shared/components/ui/BaseSelect.vue';

import { useCrudResource } from '@/shared/composables/useCrudResource';
import { useResourceForm } from '@/shared/composables/useResourceForm';
import { useUiStore } from '@/stores/ui';

import { courseSchema } from '../schemas/course.schema';
import { getCourses, getArchivedCourses, createCourse, updateCourse, deleteCourse, restoreCourse } from '../api/courses';

import { getDepartments } from '@/modules/admin/departments/api/departments';

import type { Course } from '../types/course';
import type { Department } from '@/modules/admin/departments/types/department';

type CourseColumn = 'course' | 'code' | 'department' | 'credit_hours' | 'created_at' | 'updated_at';

const uiStore = useUiStore();

const showColumns = ref(false);

const columns = [
  {
    key: 'course' as CourseColumn,
    label: 'Course',
    required: true,
  },
  {
    key: 'code' as CourseColumn,
    label: 'Code',
    required: false,
  },
  {
    key: 'department' as CourseColumn,
    label: 'Department',
    required: false,
  },
  {
    key: 'credit_hours' as CourseColumn,
    label: 'Credit Hours',
    required: false,
  },
  {
    key: 'created_at' as CourseColumn,
    label: 'Created',
    required: false,
  },
  {
    key: 'updated_at' as CourseColumn,
    label: 'Updated',
    required: false,
  },
];

const visibleColumns = ref<CourseColumn[]>(['course', 'code', 'department', 'credit_hours']);

const isColumnVisible = (column: CourseColumn) => {
  return visibleColumns.value.includes(column);
};

function toggleColumn(column: CourseColumn) {
  const config = columns.find((item) => item.key === column);

  if (config?.required) return;

  if (isColumnVisible(column)) {
    visibleColumns.value = visibleColumns.value.filter((item) => item !== column);
  } else {
    visibleColumns.value = [...visibleColumns.value, column];
  }
}

function resetColumns() {
  visibleColumns.value = ['course', 'code', 'department', 'credit_hours'];
}

function toggleColumnSelector() {
  setTimeout(() => {
    showColumns.value = !showColumns.value;
  }, 0);
}

function handleColumnOutsideClick(event: MouseEvent) {
  if (!showColumns.value) return;

  const target = event.target as HTMLElement;

  const clickedTrigger = target.closest('[data-columns-trigger]');

  const clickedMenu = target.closest('[data-columns-menu]');

  if (!clickedTrigger && !clickedMenu) {
    showColumns.value = false;
  }
}

const visibleColumnCount = computed(() => visibleColumns.value.length);

const filters = ref({
  department_id: null as number | null,
  credit_hours: null as number | null,
});

const crud = useCrudResource<Course>(
  {
    list: (params) =>
      getCourses(params.page ?? 1, 10, params.search ?? '', {
        department_id: filters.value.department_id,
        credit_hours: filters.value.credit_hours,
      }),

    listArchived: (params) =>
      getArchivedCourses(params.page ?? 1, 10, params.search ?? '', {
        department_id: filters.value.department_id,
        credit_hours: filters.value.credit_hours,
      }),

    remove: deleteCourse,
    restore: restoreCourse,
  },
  'name',
);

const hasActiveFilters = computed(() => {
  return filters.value.department_id !== null || filters.value.credit_hours !== null;
});

const filterCount = computed(() => {
  let count = 0;

  if (filters.value.department_id !== null) {
    count++;
  }

  if (filters.value.credit_hours !== null) {
    count++;
  }

  return count;
});

const showFormModal = ref(false);
const showArchiveModal = ref(false);
const showRestoreModal = ref(false);

const selected = ref<Course | null>(null);

const saving = ref(false);
const archiving = ref(false);
const restoring = ref(false);

const departments = ref<Department[]>([]);

const departmentOptions = computed(() =>
  departments.value.map((department) => ({
    value: department.id,
    label: department.name,
  })),
);

const departmentFilterOptions = computed(() => [
  {
    value: null,
    label: 'All departments',
  },
  ...departmentOptions.value,
]);

const creditHourOptions = [
  {
    value: null,
    label: 'All credit hours',
  },
  ...Array.from({ length: 30 }, (_, index) => ({
    value: index + 1,
    label: `${index + 1} ${index === 0 ? 'credit' : 'credits'}`,
  })),
];

const { form, errors, validate, reset, applyServerErrors } = useResourceForm(courseSchema, {
  department_id: 0,
  code: '',
  name: '',
  credit_hours: 3,
});

async function loadDepartmentOptions() {
  if (departments.value.length) return;

  try {
    const res = await getDepartments(1, 100);

    departments.value = res.data;
  } catch {
    uiStore.showToast('Failed to load departments.', 'error');
  }
}

onMounted(async () => {
  document.addEventListener('click', handleColumnOutsideClick);

  await loadDepartmentOptions();
  await crud.load(1);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', handleColumnOutsideClick);
});


watch(
  filters,
  () => {
    crud.load(1);
  },
  {
    deep: true,
  },
);

function clearFilters() {
  filters.value = {
    department_id: null,
    credit_hours: null,
  };
}

function retry() {
  crud.load(crud.currentPagination().current_page);
}

function openCreate() {
  selected.value = null;

  reset({
    department_id: 0,
    code: '',
    name: '',
    credit_hours: 3,
  });

  loadDepartmentOptions();

  showFormModal.value = true;
}

function openEdit(course: Course) {
  selected.value = course;

  reset({
    department_id: course.department?.id ?? 0,
    code: course.code,
    name: course.name,
    credit_hours: course.credit_hours,
  });

  loadDepartmentOptions();

  showFormModal.value = true;
}

function closeFormModal() {
  showFormModal.value = false;
  selected.value = null;
}

async function submitForm() {
  if (!validate()) return;

  saving.value = true;

  const isEditing = !!selected.value;

  try {
    if (selected.value) {
      await updateCourse(selected.value.id, form);
    } else {
      await createCourse(form);
    }

    closeFormModal();

    uiStore.showToast(isEditing ? 'Course updated.' : 'Course created.', 'success');

    await crud.load(crud.currentPagination().current_page);
  } catch (err: any) {
    if (err?.response?.status === 422) {
      applyServerErrors(err.response.data?.errors);

      uiStore.showToast('Please fix the errors below.', 'error');
    } else {
      uiStore.showToast(isEditing ? 'Failed to update course.' : 'Failed to save course.', 'error');
    }
  } finally {
    saving.value = false;
  }
}

function openArchive(course: Course) {
  selected.value = course;
  showArchiveModal.value = true;
}

function openRestore(course: Course) {
  selected.value = course;
  showRestoreModal.value = true;
}

async function confirmArchive() {
  if (!selected.value) return;

  archiving.value = true;

  try {
    await crud.archive(selected.value);

    showArchiveModal.value = false;
    selected.value = null;
  } catch {
    uiStore.showToast('Failed to archive course.', 'error');
  } finally {
    archiving.value = false;
  }
}

async function confirmRestore() {
  if (!selected.value) return;

  restoring.value = true;

  try {
    await crud.restore(selected.value);

    showRestoreModal.value = false;
    selected.value = null;
  } catch {
    uiStore.showToast('Failed to restore course.', 'error');
  } finally {
    restoring.value = false;
  }
}

const emptyStateText = computed(() => {
  if (crud.search.value || hasActiveFilters.value) {
    return 'No matching courses found';
  }

  return crud.activeTab.value === 'archived' ? 'No archived courses' : 'No courses yet';
});
</script>

<template>
  <div class="mx-auto w-full max-w-360 space-y-6 px-6 py-6">
  <ResourceToolbar
    title="Courses"
    description="Manage courses offered under each department."
    search-placeholder="Search courses..."
    :search="crud.search.value"
    :show-search="true"
    :show-filter="true"
    :show-refresh="true"
    :refreshing="crud.loading.value"
    :show-columns="true"
    :show-fullscreen="true"
    :show-tabs="true"
    :active-tab="crud.activeTab.value"
    :active-count="crud.activePagination.value.total"
    :archived-count="crud.archivedPagination.value.total"
    :has-active-filters="hasActiveFilters"
    :filter-count="filterCount"
    @update:search="crud.setSearch"
    @refresh="retry"
    @clear-filters="clearFilters"
    @change-tab="crud.changeTab"
    @columns="toggleColumnSelector">
      <template #actions>
        <BaseButton v-can="'course.create'" :icon="Plus" @click="openCreate"> Add course </BaseButton>
      </template>

      <template #filters>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <BaseSelect v-model="filters.department_id" label="Department" :options="departmentFilterOptions" placeholder="All departments" />

          <BaseSelect v-model="filters.credit_hours" label="Credit hours" :options="creditHourOptions" placeholder="All credit hours" />
        </div>
      </template>
    </ResourceToolbar>

    <!-- COLUMN SELECTOR -->

    <div v-if="showColumns" class="relative">
      <div data-columns-menu class="absolute right-0 z-50 mt-[-12px] w-56 rounded-lg border border-border bg-surface p-2 shadow-lg">
        <div class="flex items-center justify-between px-2 py-1.5">
          <span class="text-xs font-semibold text-text"> Columns </span>

          <button type="button" class="text-xs font-medium text-text/50 transition-colors hover:text-accent" @click="resetColumns">Reset</button>
        </div>

        <div class="my-1 border-t border-border" />

        <button
          v-for="column in columns"
          :key="column.key"
          type="button"
          class="flex w-full items-center gap-2 rounded-md px-2 py-2 text-left text-sm transition-colors hover:bg-text/5"
          @click="toggleColumn(column.key)">
          <span
            class="flex h-4 w-4 shrink-0 items-center justify-center rounded border transition-colors"
            :class="isColumnVisible(column.key) ? 'border-accent bg-accent text-white' : 'border-border bg-surface'">
            <svg v-if="isColumnVisible(column.key)" viewBox="0 0 12 12" class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M2 6l2.5 2.5L10 3" />
            </svg>
          </span>

          <span class="flex-1 text-text/80">
            {{ column.label }}
          </span>

          <span v-if="column.required" class="text-[10px] text-text/40"> Required </span>
        </button>

        <div class="mt-1 border-t border-border pt-1">
          <div class="px-2 py-1 text-[11px] text-text/40">{{ visibleColumnCount }} columns visible</div>
        </div>
      </div>
    </div>

    <!-- ERROR -->

    <div v-if="crud.error.value" class="flex items-start gap-3 rounded-md border border-error/30 bg-error/5 p-4">
      <p class="flex-1 text-sm font-medium text-error">
        {{ crud.error.value }}
      </p>

      <button type="button" class="text-sm font-medium text-error hover:underline" @click="retry">Retry</button>
    </div>

    <!-- TABLE -->

    <div v-can="'course.view'" class="rounded-md border border-border bg-surface">
      <div class="overflow-x-auto">
        <table class="w-full min-w-190 border-collapse">
          <thead>
            <tr class="border-b border-border bg-text/2.5">
              <th v-if="isColumnVisible('course')" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Course</th>

              <th v-if="isColumnVisible('code')" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Code</th>

              <th v-if="isColumnVisible('department')" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Department</th>

              <th v-if="isColumnVisible('credit_hours')" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Credit Hours</th>

              <th v-if="isColumnVisible('created_at')" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Created</th>

              <th v-if="isColumnVisible('updated_at')" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Updated</th>

              <th class="w-16 px-4 py-3 text-right">
                <span class="sr-only"> Actions </span>
              </th>
            </tr>
          </thead>

          <!-- LOADING -->

          <tbody v-if="crud.loading.value" class="divide-y divide-border">
            <tr v-for="row in 6" :key="row">
              <td v-if="isColumnVisible('course')" class="px-4 py-4">
                <div class="flex items-center gap-3">
                  <div class="h-9 w-9 animate-pulse rounded-md bg-text/5" />
                  <div class="h-3.5 w-40 animate-pulse rounded bg-text/5" />
                </div>
              </td>

              <td v-if="isColumnVisible('code')" class="px-4 py-4">
                <div class="h-3.5 w-16 animate-pulse rounded bg-text/5" />
              </td>

              <td v-if="isColumnVisible('department')" class="px-4 py-4">
                <div class="h-3.5 w-32 animate-pulse rounded bg-text/5" />
              </td>

              <td v-if="isColumnVisible('credit_hours')" class="px-4 py-4">
                <div class="h-3.5 w-10 animate-pulse rounded bg-text/5" />
              </td>

              <td v-if="isColumnVisible('created_at')" class="px-4 py-4">
                <div class="h-3.5 w-24 animate-pulse rounded bg-text/5" />
              </td>

              <td v-if="isColumnVisible('updated_at')" class="px-4 py-4">
                <div class="h-3.5 w-24 animate-pulse rounded bg-text/5" />
              </td>

              <td />
            </tr>
          </tbody>

          <!-- DATA -->

          <tbody v-else-if="crud.currentList().length" class="divide-y divide-border">
            <tr v-for="course in crud.currentList()" :key="course.id" class="group transition-colors hover:bg-text/2">
              <td v-if="isColumnVisible('course')" class="px-4 py-3.5">
                <div class="flex items-center gap-3">
                  <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md border border-border bg-bg text-text/50">
                    <GraduationCap class="h-4 w-4" />
                  </div>

                  <p class="text-sm font-medium text-text">
                    {{ course.name }}
                  </p>
                </div>
              </td>

              <td v-if="isColumnVisible('code')" class="px-4 py-3.5">
                <span class="font-mono text-xs tabular-nums text-text/80">
                  {{ course.code }}
                </span>
              </td>

              <td v-if="isColumnVisible('department')" class="px-4 py-3.5">
                <span class="text-sm text-text/60">
                  {{ course.department?.name || '—' }}
                </span>
              </td>

              <td v-if="isColumnVisible('credit_hours')" class="px-4 py-3.5">
                <span class="text-sm text-text/60">
                  {{ course.credit_hours }}
                </span>
              </td>

              <td v-if="isColumnVisible('created_at')" class="px-4 py-3.5">
                <span class="font-mono text-xs tabular-nums text-text/60">
                  {{ course.created_at || '—' }}
                </span>
              </td>

              <td v-if="isColumnVisible('updated_at')" class="px-4 py-3.5">
                <span class="font-mono text-xs tabular-nums text-text/60">
                  {{ course.updated_at || '—' }}
                </span>
              </td>

              <td class="px-4 py-3.5 text-right">
                <TableRowActions
                  :active-tab="crud.activeTab.value"
                  edit-permission="course.update"
                  archive-permission="course.archive"
                  restore-permission="course.restore"
                  @edit="openEdit(course)"
                  @archive="openArchive(course)"
                  @restore="openRestore(course)" />
              </td>
            </tr>
          </tbody>

          <!-- EMPTY -->

          <tbody v-else>
            <tr>
              <td :colspan="visibleColumnCount + 1" class="px-6 py-16 text-center text-sm text-text/55">
                {{ emptyStateText }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <AppPagination :pagination="crud.currentPagination()" @change-page="crud.load" />
    </div>
  </div>

  <!-- FORM -->

  <BaseDialog :model-value="showFormModal" :title="selected ? 'Edit course' : 'Add course'" @update:model-value="closeFormModal">
    <div class="space-y-4">
      <BaseSelect v-model="form.department_id" label="Department" :options="departmentOptions" placeholder="Select department" :error="errors.department_id" />

      <BaseInput v-model="form.code" label="Course code" placeholder="e.g. CS201" :error="errors.code" />

      <BaseInput v-model="form.name" label="Course name" placeholder="e.g. Discrete Mathematics" :error="errors.name" />

      <BaseInput v-model.number="form.credit_hours" type="number" min="1" max="30" label="Credit hours" placeholder="3" :error="errors.credit_hours" />
    </div>

    <template #footer>
      <div class="flex justify-end gap-2">
        <BaseButton variant="secondary" @click="closeFormModal"> Cancel </BaseButton>

        <BaseButton :loading="saving" @click="submitForm">
          {{ selected ? 'Save changes' : 'Create course' }}
        </BaseButton>
      </div>
    </template>
  </BaseDialog>

  <!-- ARCHIVE -->

  <ConfirmModal
    :show="showArchiveModal"
    title="Archive course?"
    :description="`This will remove ${selected?.name} from active course views. It can be restored later.`"
    confirm-text="Archive course"
    variant="danger"
    :icon="Archive"
    :loading="archiving"
    @close="showArchiveModal = false"
    @confirm="confirmArchive" />

  <!-- RESTORE -->

  <ConfirmModal
    :show="showRestoreModal"
    title="Restore course?"
    :description="`This will return ${selected?.name} to the active course list.`"
    confirm-text="Restore course"
    variant="accent"
    :icon="RotateCcw"
    :loading="restoring"
    @close="showRestoreModal = false"
    @confirm="confirmRestore" />
</template>
