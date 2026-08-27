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

import { programSchema } from '../schemas/program.schema';
import { getPrograms, getArchivedPrograms, createProgram, updateProgram, deleteProgram, restoreProgram } from '../api/programs';

import { getDepartments } from '@/modules/admin/departments/api/departments';

import type { Program } from '../types/program';
import type { Department } from '@/modules/admin/departments/types/department';

type ProgramColumn = 'program' | 'code' | 'department' | 'duration' | 'created_at' | 'updated_at';

const uiStore = useUiStore();

const search = ref('');

const filters = ref({
  department_id: null as number | null,
});

const crud = useCrudResource<Program>(
  {
    list: (page) =>
      getPrograms(page, 10, search.value, {
        department_id: filters.value.department_id,
      }),

    listArchived: (page) =>
      getArchivedPrograms(page, 10, search.value, {
        department_id: filters.value.department_id,
      }),

    remove: deleteProgram,
    restore: restoreProgram,
  },
  'name',
);

const columns = [
  {
    key: 'program' as ProgramColumn,
    label: 'Program',
    required: true,
  },
  {
    key: 'code' as ProgramColumn,
    label: 'Code',
    required: false,
  },
  {
    key: 'department' as ProgramColumn,
    label: 'Department',
    required: false,
  },
  {
    key: 'duration' as ProgramColumn,
    label: 'Duration',
    required: false,
  },
  {
    key: 'created_at' as ProgramColumn,
    label: 'Created',
    required: false,
  },
  {
    key: 'updated_at' as ProgramColumn,
    label: 'Updated',
    required: false,
  },
];

const showColumns = ref(false);

const visibleColumns = ref<ProgramColumn[]>(['program', 'code', 'department', 'duration']);

const isColumnVisible = (column: ProgramColumn) => visibleColumns.value.includes(column);

const toggleColumn = (column: ProgramColumn) => {
  const config = columns.find((item) => item.key === column);

  if (config?.required) return;

  if (isColumnVisible(column)) {
    visibleColumns.value = visibleColumns.value.filter((item) => item !== column);
  } else {
    visibleColumns.value = [...visibleColumns.value, column];
  }
};

const resetColumns = () => {
  visibleColumns.value = ['program', 'code', 'department', 'duration'];
};

const visibleColumnCount = computed(() => visibleColumns.value.length);

const totalTableColumns = computed(() => visibleColumnCount.value + 1);

const showFormModal = ref(false);
const showArchiveModal = ref(false);
const showRestoreModal = ref(false);

const selected = ref<Program | null>(null);

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

const hasActiveFilters = computed(() => filters.value.department_id !== null);

const { form, errors, validate, reset, applyServerErrors } = useResourceForm(programSchema, {
  department_id: 0,
  code: '',
  name: '',
  duration_years: 4,
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

watch([search, () => filters.value.department_id], () => {
  crud.load(1);
});

function clearFilters() {
  search.value = '';
  filters.value.department_id = null;
}

function retry() {
  crud.load(crud.currentPagination().current_page);
}

function handleDocumentClick(event: MouseEvent) {
  const target = event.target as HTMLElement;

  if (!target.closest('[data-columns-container]')) {
    showColumns.value = false;
  }
}

function openCreate() {
  selected.value = null;

  reset({
    department_id: 0,
    code: '',
    name: '',
    duration_years: 4,
  });

  loadDepartmentOptions();
  showFormModal.value = true;
}

function openEdit(program: Program) {
  selected.value = program;

  reset({
    department_id: program.department?.id ?? 0,
    code: program.code,
    name: program.name,
    duration_years: program.duration_years,
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
      await updateProgram(selected.value.id, form);
    } else {
      await createProgram(form);
    }

    closeFormModal();

    uiStore.showToast(isEditing ? 'Program updated.' : 'Program created.', 'success');

    await crud.load(crud.currentPagination().current_page);
  } catch (err: any) {
    if (err?.response?.status === 422) {
      applyServerErrors(err.response.data?.errors);

      uiStore.showToast('Please fix the errors below.', 'error');
    } else {
      uiStore.showToast(isEditing ? 'Failed to update program.' : 'Failed to save program.', 'error');
    }
  } finally {
    saving.value = false;
  }
}

function openArchive(program: Program) {
  selected.value = program;
  showArchiveModal.value = true;
}

function openRestore(program: Program) {
  selected.value = program;
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
    uiStore.showToast('Failed to archive program.', 'error');
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
    uiStore.showToast('Failed to restore program.', 'error');
  } finally {
    restoring.value = false;
  }
}

const emptyStateText = computed(() => {
  if (search.value || hasActiveFilters.value) {
    return 'No matching programs found';
  }

  return crud.activeTab.value === 'archived' ? 'No archived programs' : 'No programs yet';
});

onMounted(async () => {
  document.addEventListener('click', handleDocumentClick);

  await loadDepartmentOptions();
  await crud.load(1);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', handleDocumentClick);
});
</script>

<template>
  <div class="mx-auto w-full max-w-360 space-y-6 px-6 py-6">
    <div data-columns-container class="relative">
      <ResourceToolbar
        title="Programs"
        description="Manage academic programs under each department."
        search-placeholder="Search programs..."
        :show-search="true"
        :show-filter="true"
        :show-refresh="true"
        :show-columns="true"
        :show-fullscreen="true"
        :show-tabs="true"
        :active-tab="crud.activeTab.value"
        :active-count="crud.activePagination.value.total"
        :archived-count="crud.archivedPagination.value.total"
        :has-active-filters="hasActiveFilters"
        @clear-filters="clearFilters"
        @change-tab="crud.changeTab"
        @update:search="search = $event"
        @refresh="retry"
        @columns="showColumns = !showColumns">
        <template #actions>
          <BaseButton v-can="'program.create'" :icon="Plus" @click="openCreate"> Add program </BaseButton>
        </template>

        <template #filters>
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <BaseSelect v-model="filters.department_id" label="Department" :options="departmentFilterOptions" placeholder="All departments" />
          </div>
        </template>
      </ResourceToolbar>

      <div v-if="showColumns" class="absolute right-0 top-full z-40 mt-2 w-64 rounded-lg border border-border bg-surface p-2 shadow-xl" @click.stop>
        <div class="flex items-center justify-between px-2 py-2">
          <div>
            <p class="text-sm font-semibold text-text">Columns</p>

            <p class="mt-0.5 text-xs text-text/45">Choose what appears in the table</p>
          </div>

          <button type="button" class="rounded-md px-2 py-1 text-xs font-medium text-text/50 transition-colors hover:bg-text/5 hover:text-accent" @click="resetColumns">
            Reset
          </button>
        </div>

        <div class="my-1 border-t border-border" />

        <div class="space-y-0.5">
          <button
            v-for="column in columns"
            :key="column.key"
            type="button"
            class="flex w-full items-center gap-3 rounded-md px-2 py-2 text-left transition-colors hover:bg-text/5"
            @click="toggleColumn(column.key)">
            <span
              class="flex h-4 w-4 shrink-0 items-center justify-center rounded border transition-colors"
              :class="isColumnVisible(column.key) ? 'border-accent bg-accent text-white' : 'border-border bg-surface'">
              <svg v-if="isColumnVisible(column.key)" viewBox="0 0 12 12" class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M2 6l2.5 2.5L10 3" />
              </svg>
            </span>

            <span class="flex-1 text-sm text-text/80">
              {{ column.label }}
            </span>

            <span v-if="column.required" class="text-[10px] font-medium text-text/35"> Always </span>
          </button>
        </div>

        <div class="mt-1 border-t border-border px-2 pt-2">
          <p class="text-[11px] text-text/40">
            {{ visibleColumnCount }}
            columns visible
          </p>
        </div>
      </div>
    </div>

    <div v-if="crud.error.value" class="flex items-start gap-3 rounded-md border border-error/30 bg-error/5 p-4">
      <p class="flex-1 text-sm font-medium text-error">
        {{ crud.error.value }}
      </p>

      <button type="button" class="text-sm font-medium text-error hover:underline" @click="retry">Retry</button>
    </div>

    <div v-can="'program.view'" class="overflow-hidden rounded-md border border-border bg-surface">
      <div class="overflow-x-auto">
        <table class="w-full min-w-190 border-collapse">
          <thead>
            <tr class="border-b border-border bg-text/2.5">
              <th v-if="isColumnVisible('program')" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Program</th>

              <th v-if="isColumnVisible('code')" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Code</th>

              <th v-if="isColumnVisible('department')" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Department</th>

              <th v-if="isColumnVisible('duration')" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Duration</th>

              <th v-if="isColumnVisible('created_at')" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Created</th>

              <th v-if="isColumnVisible('updated_at')" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Updated</th>

              <th class="w-16 px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-text/50">
                <span class="sr-only"> Actions </span>
              </th>
            </tr>
          </thead>

          <tbody v-if="crud.loading.value" class="divide-y divide-border">
            <tr v-for="row in 6" :key="row">
              <td v-for="column in visibleColumns" :key="column" class="px-4 py-4">
                <div
                  class="h-3.5 animate-pulse rounded bg-text/5"
                  :class="column === 'program' ? 'w-44' : column === 'department' ? 'w-32' : column === 'code' ? 'w-16' : 'w-20'" />
              </td>

              <td class="px-4 py-4">
                <div class="ml-auto h-8 w-8 animate-pulse rounded-md bg-text/5" />
              </td>
            </tr>
          </tbody>

          <tbody v-else-if="crud.currentList().length" class="divide-y divide-border">
            <tr v-for="program in crud.currentList()" :key="program.id" class="group transition-colors hover:bg-text/2">
              <td v-if="isColumnVisible('program')" class="px-4 py-3.5">
                <div class="flex items-center gap-3">
                  <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md border border-border bg-bg text-text/50">
                    <GraduationCap class="h-4 w-4" />
                  </div>

                  <p class="text-sm font-medium text-text">
                    {{ program.name }}
                  </p>
                </div>
              </td>

              <td v-if="isColumnVisible('code')" class="px-4 py-3.5">
                <span class="font-mono text-xs tabular-nums text-text/80">
                  {{ program.code || '—' }}
                </span>
              </td>

              <td v-if="isColumnVisible('department')" class="px-4 py-3.5">
                <span class="text-sm text-text/60">
                  {{ program.department?.name || '—' }}
                </span>
              </td>

              <td v-if="isColumnVisible('duration')" class="px-4 py-3.5">
                <span class="text-sm text-text/60">
                  {{ program.duration_years }}
                  yrs
                </span>
              </td>

              <td v-if="isColumnVisible('created_at')" class="px-4 py-3.5">
                <span class="font-mono text-xs tabular-nums text-text/60">
                  {{ program.created_at || '—' }}
                </span>
              </td>

              <td v-if="isColumnVisible('updated_at')" class="px-4 py-3.5">
                <span class="font-mono text-xs tabular-nums text-text/60">
                  {{ program.updated_at || '—' }}
                </span>
              </td>

              <td class="px-4 py-3.5 text-right">
                <TableRowActions
                  :active-tab="crud.activeTab.value"
                  edit-permission="program.update"
                  archive-permission="program.archive"
                  restore-permission="program.restore"
                  @edit="openEdit(program)"
                  @archive="openArchive(program)"
                  @restore="openRestore(program)" />
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

                  <p class="mt-3 text-sm font-medium text-text">
                    {{ emptyStateText }}
                  </p>

                  <p v-if="search || hasActiveFilters" class="mt-1 text-xs text-text/45">Try adjusting your search or filters.</p>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <AppPagination :pagination="crud.currentPagination()" @change-page="crud.load" />
    </div>
  </div>

  <BaseDialog :model-value="showFormModal" :title="selected ? 'Edit program' : 'Add program'" @update:model-value="closeFormModal">
    <div class="space-y-4">
      <BaseSelect v-model="form.department_id" label="Department" :options="departmentOptions" placeholder="Select department" :error="errors.department_id" />

      <BaseInput v-model="form.code" label="Program code" placeholder="e.g. SE" :error="errors.code" />

      <BaseInput v-model="form.name" label="Program name" placeholder="e.g. Software Engineering" :error="errors.name" />

      <BaseInput v-model.number="form.duration_years" type="number" label="Duration (years)" placeholder="4" :error="errors.duration_years" />
    </div>

    <template #footer>
      <div class="flex justify-end gap-2">
        <BaseButton variant="secondary" @click="closeFormModal"> Cancel </BaseButton>

        <BaseButton :loading="saving" @click="submitForm">
          {{ selected ? 'Save changes' : 'Create program' }}
        </BaseButton>
      </div>
    </template>
  </BaseDialog>

  <ConfirmModal
    :show="showArchiveModal"
    title="Archive program?"
    :description="`This will remove ${selected?.name} from active program views.`"
    confirm-text="Archive program"
    variant="danger"
    :icon="Archive"
    :loading="archiving"
    @close="showArchiveModal = false"
    @confirm="confirmArchive" />

  <ConfirmModal
    :show="showRestoreModal"
    title="Restore program?"
    :description="`This will return ${selected?.name} to the active program list.`"
    confirm-text="Restore program"
    variant="accent"
    :icon="RotateCcw"
    :loading="restoring"
    @close="showRestoreModal = false"
    @confirm="confirmRestore" />
</template>
