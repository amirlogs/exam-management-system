<script setup lang="ts">
import { onMounted, onBeforeUnmount, ref, computed, watch } from 'vue';
import { Archive, Check, Layers, Plus, RotateCcw } from 'lucide-vue-next';

import ResourceToolbar from '@/shared/components/ResourceToolbar.vue';
import TableRowActions from '@/shared/components/TableRowActions.vue';
import BaseDialog from '@/shared/components/ui/BaseDialog.vue';
import AppPagination from '@/shared/components/AppPagination.vue';
import BaseButton from '@/shared/components/ui/BaseButton.vue';
import BaseInput from '@/shared/components/ui/BaseInput.vue';
import BaseSelect from '@/shared/components/ui/BaseSelect.vue';
import BaseBadge from '@/shared/components/ui/BaseBadge.vue';
import ConfirmModal from '@/shared/components/ConfirmModal.vue';

import { useCrudResource } from '@/shared/composables/useCrudResource';
import { useResourceForm } from '@/shared/composables/useResourceForm';
import { useUiStore } from '@/stores/ui';

import { departmentSchema } from '../schemas/department.schema';
import { getDepartments, getArchivedDepartments, createDepartment, updateDepartment, deleteDepartment, restoreDepartment } from '../api/departments';
import { getColleges } from '@/modules/admin/colleges/api/colleges';

import type { Department } from '../types/department';
import type { College } from '@/modules/admin/colleges/types/college';

const uiStore = useUiStore();

// Column Visibility State
type DepartmentColumn = 'department' | 'college' | 'type' | 'created_at';

const showColumns = ref(false);
const columns = [
  { key: 'department' as DepartmentColumn, label: 'Department', required: true },
  { key: 'college' as DepartmentColumn, label: 'College', required: false },
  { key: 'type' as DepartmentColumn, label: 'Type', required: false },
  { key: 'created_at' as DepartmentColumn, label: 'Created', required: false },
];

const visibleColumns = ref<DepartmentColumn[]>(['department', 'college', 'type', 'created_at']);

const isColumnVisible = (column: DepartmentColumn) => {
  return visibleColumns.value.includes(column);
};

function toggleColumn(column: DepartmentColumn) {
  const config = columns.find((item) => item.key === column);
  if (config?.required) return;
  if (isColumnVisible(column)) {
    visibleColumns.value = visibleColumns.value.filter((item) => item !== column);
  } else {
    visibleColumns.value = [...visibleColumns.value, column];
  }
}

function resetColumns() {
  visibleColumns.value = ['department', 'college', 'type', 'created_at'];
}

function toggleColumnSelector() {
  setTimeout(() => {
    showColumns.value = !showColumns.value;
  }, 0);
}

function handleColumnOutsideClick(event: MouseEvent) {
  if (!showColumns.value) return;
  const target = event.target as HTMLElement;
  const clickedTrigger = target.closest('[title="Show/hide columns"]');
  const clickedMenu = target.closest('[data-columns-menu]');
  if (!clickedTrigger && !clickedMenu) {
    showColumns.value = false;
  }
}

const visibleColumnCount = computed(() => visibleColumns.value.length);

//  Filters
const filters = ref({
  college_id: null as number | null,
  type: '' as string,
});

// CRUD
const crud = useCrudResource<Department>(
  {
    list: (params) =>
      getDepartments(params.page ?? 1, 10, params.search ?? '', {
        college_id: filters.value.college_id,
        type: filters.value.type,
      }),

    listArchived: (params) =>
      getArchivedDepartments(params.page ?? 1, 10, params.search ?? '', {
        college_id: filters.value.college_id,
        type: filters.value.type,
      }),

    remove: deleteDepartment,
    restore: restoreDepartment,
  },
  'name',
);
// UI State
const showFormModal = ref(false);
const showArchiveModal = ref(false);
const showRestoreModal = ref(false);

const selected = ref<Department | null>(null);

const saving = ref(false);
const archiving = ref(false);
const restoring = ref(false);

const colleges = ref<College[]>([]);

// Filter Options
const collegeOptions = computed(() =>
  colleges.value.map((college) => ({
    value: college.id,
    label: college.name,
  })),
);

const collegeFilterOptions = computed(() => [{ value: null, label: 'All colleges' }, ...collegeOptions.value]);

const typeOptions = [
  { value: 'service_only', label: 'Service only' },
  { value: 'degree_granting', label: 'Degree granting' },
];

const typeFilterOptions = [{ value: '', label: 'All types' }, ...typeOptions];

// Active Filter State
const filterCount = computed(() => {
  let count = 0;

  if (filters.value.college_id !== null) {
    count++;
  }

  if (filters.value.type !== '') {
    count++;
  }

  return count;
});

const hasActiveFilters = computed(() => filterCount.value > 0);

// Form
const { form, errors, validate, reset, applyServerErrors } = useResourceForm(departmentSchema, {
  college_id: null as number | null,
  name: '',
  type: 'service_only' as const,
});

// College Options
async function loadCollegeOptions() {
  if (colleges.value.length) return;

  try {
    const res = await getColleges(1, 100);
    colleges.value = res.data;
  } catch (err) {
    console.error('Failed to load colleges for dropdown:', err);
    uiStore.showToast('Failed to load colleges.', 'error');
  }
}

// Loading
onMounted(() => {
  loadCollegeOptions();
  crud.load(1);
  document.addEventListener('click', handleColumnOutsideClick);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', handleColumnOutsideClick);
});

// Filter changes
watch(
  filters,
  () => {
    crud.load(1);
  },
  { deep: true },
);

// Filters
function clearFilters() {
  filters.value = {
    college_id: null,
    type: '',
  };

  crud.load(1);
}

// Create / Edit
function openCreate() {
  selected.value = null;

  reset({
    college_id: null,
    name: '',
    type: 'service_only',
  });

  loadCollegeOptions();
  showFormModal.value = true;
}

function openEdit(dept: Department) {
  selected.value = dept;

  reset({
    college_id: dept.college_id,
    name: dept.name,
    type: dept.type,
  });

  loadCollegeOptions();
  showFormModal.value = true;
}

function closeFormModal() {
  showFormModal.value = false;
  selected.value = null;
}

// Save
async function submitForm() {
  if (!validate()) return;

  saving.value = true;

  const isEditing = !!selected.value;

  try {
    if (selected.value) {
      await updateDepartment(selected.value.id, form);
    } else {
      await createDepartment(form);
    }

    closeFormModal();

    uiStore.showToast(isEditing ? 'Department updated.' : 'Department created.', 'success');

    await crud.load(crud.currentPagination().current_page);
  } catch (err: any) {
    if (err?.response?.status === 422) {
      applyServerErrors(err.response.data?.errors);

      uiStore.showToast('Please fix the errors below.', 'error');
    } else {
      uiStore.showToast(isEditing ? 'Failed to update department.' : 'Failed to save department.', 'error');
    }
  } finally {
    saving.value = false;
  }
}

// Archive / Restore
function openArchive(dept: Department) {
  selected.value = dept;
  showArchiveModal.value = true;
}

function openRestore(dept: Department) {
  selected.value = dept;
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
    uiStore.showToast('Failed to archive department.', 'error');
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
    uiStore.showToast('Failed to restore department.', 'error');
  } finally {
    restoring.value = false;
  }
}

// --------------------------------------------------
// Table State
// --------------------------------------------------

const emptyStateText = computed(() => {
  const hasSearch = crud.search.value.trim().length > 0;

  if (hasSearch || hasActiveFilters.value) {
    return 'No matching departments found';
  }

  return crud.activeTab.value === 'archived' ? 'No archived departments' : 'No departments yet';
});

function retry() {
  crud.load(crud.currentPagination().current_page);
}

// Department Type
function typeVariant(type: string) {
  return type === 'degree_granting' ? 'success' : 'neutral';
}

function typeLabel(type: string) {
  return type === 'degree_granting' ? 'Degree granting' : 'Service only';
}
</script>

<template>
  <div class="mx-auto w-full max-w-360 space-y-6 px-6 py-6">
    <!-- TOOLBAR -->
    <ResourceToolbar
      title="Departments"
      description="Manage departments under each college."
      search-placeholder="Search departments..."
      :search="crud.search.value"
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
      :filter-count="filterCount"
      @clear-filters="clearFilters"
      @change-tab="crud.changeTab"
      @update:search="crud.setSearch"
      @refresh="retry"
      @columns="toggleColumnSelector">
      <!-- Primary Action -->
      <template #actions>
        <BaseButton v-can="'department.create'" :icon="Plus" @click="openCreate"> Add department </BaseButton>
      </template>

      <!-- Column Picker Menu -->
      <template #columns>
        <div
          v-if="showColumns"
          data-columns-menu
          class="absolute right-0 top-full mt-2 z-50 w-56 rounded-xl border border-border bg-surface p-2 shadow-xl">
          <div class="flex items-center justify-between px-2 py-1.5">
            <span class="text-xs font-semibold text-text">Columns</span>
            <button
              type="button"
              class="text-xs font-medium text-text/50 transition-colors hover:text-accent cursor-pointer"
              @click="resetColumns">
              Reset
            </button>
          </div>

          <div class="my-1 border-t border-border" />

          <button
            v-for="column in columns"
            :key="column.key"
            type="button"
            class="flex w-full items-center gap-2 rounded-lg px-2 py-1.5 text-left text-sm transition-colors hover:bg-text/5 cursor-pointer"
            @click="toggleColumn(column.key)">
            <span
              class="flex h-4 w-4 shrink-0 items-center justify-center rounded border transition-colors"
              :class="isColumnVisible(column.key) ? 'border-accent bg-accent text-white' : 'border-border bg-surface'">
              <Check v-if="isColumnVisible(column.key)" class="h-3 w-3" />
            </span>

            <span class="flex-1 text-xs font-medium text-text/80">
              {{ column.label }}
            </span>

            <span v-if="column.required" class="text-[10px] font-mono text-text/40">
              Req
            </span>
          </button>

          <div class="mt-1 border-t border-border pt-1">
            <div class="px-2 py-1 text-[11px] text-text/40">
              {{ visibleColumnCount }} columns visible
            </div>
          </div>
        </div>
      </template>

      <!-- Filters -->
      <template #filters>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <BaseSelect v-model="filters.college_id" label="College" :options="collegeFilterOptions" placeholder="All colleges" />

          <BaseSelect v-model="filters.type" label="Type" :options="typeFilterOptions" placeholder="All types" />
        </div>
      </template>
    </ResourceToolbar>

    <!-- ERROR -->
    <div v-if="crud.error.value" class="flex items-start gap-3 rounded-md border border-error/30 bg-error/5 p-4">
      <p class="flex-1 text-sm font-medium text-error">
        {{ crud.error.value }}
      </p>

      <button type="button" class="text-sm font-medium text-error hover:underline" @click="retry">Retry</button>
    </div>

    <!-- TABLE -->
    <div v-can="'department.view'" class="rounded-md border border-border bg-surface">
      <div class="overflow-x-auto">
        <table class="w-full min-w-190 border-collapse">
          <!-- HEAD -->
          <thead>
            <tr class="border-b border-border bg-text/2.5">
              <th v-if="isColumnVisible('department')" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Department</th>

              <th v-if="isColumnVisible('college')" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">College</th>

              <th v-if="isColumnVisible('type')" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Type</th>

              <th v-if="isColumnVisible('created_at')" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Created</th>

              <th class="w-16 px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-text/50">
                <span class="sr-only"> Actions </span>
              </th>
            </tr>
          </thead>

          <!-- LOADING -->
          <tbody v-if="crud.loading.value" class="divide-y divide-border">
            <tr v-for="row in 6" :key="row">
              <td v-if="isColumnVisible('department')" class="px-4 py-4">
                <div class="flex items-center gap-3">
                  <div class="h-9 w-9 animate-pulse rounded-md bg-text/5" />

                  <div class="h-3.5 w-40 animate-pulse rounded bg-text/5" />
                </div>
              </td>

              <td v-if="isColumnVisible('college')" class="px-4 py-4">
                <div class="h-3.5 w-32 animate-pulse rounded bg-text/5" />
              </td>

              <td v-if="isColumnVisible('type')" class="px-4 py-4">
                <div class="h-6 w-24 animate-pulse rounded bg-text/5" />
              </td>

              <td v-if="isColumnVisible('created_at')" class="px-4 py-4">
                <div class="h-3.5 w-24 animate-pulse rounded bg-text/5" />
              </td>

              <td />
            </tr>
          </tbody>

          <!-- DATA -->
          <tbody v-else-if="crud.currentList().length" class="divide-y divide-border">
            <tr v-for="dept in crud.currentList()" :key="dept.id" class="group transition-colors hover:bg-text/2">
              <td v-if="isColumnVisible('department')" class="px-4 py-3.5">
                <div class="flex items-center gap-3">
                  <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md border border-border bg-bg text-text/50">
                    <Layers class="h-4 w-4" />
                  </div>

                  <p class="text-sm font-medium text-text">
                    {{ dept.name }}
                  </p>
                </div>
              </td>

              <td v-if="isColumnVisible('college')" class="px-4 py-3.5">
                <span class="text-sm text-text/60">
                  {{ dept.college?.name || '—' }}
                </span>
              </td>

              <td v-if="isColumnVisible('type')" class="px-4 py-3.5">
                <BaseBadge :variant="typeVariant(dept.type)">
                  {{ typeLabel(dept.type) }}
                </BaseBadge>
              </td>

              <td v-if="isColumnVisible('created_at')" class="px-4 py-3.5">
                <span class="font-mono text-xs tabular-nums text-text/60">
                  {{ dept.created_at }}
                </span>
              </td>

              <td class="px-4 py-3.5 text-right">
                <TableRowActions
                  :active-tab="crud.activeTab.value"
                  edit-permission="department.update"
                  archive-permission="department.archive"
                  restore-permission="department.restore"
                  @edit="openEdit(dept)"
                  @archive="openArchive(dept)"
                  @restore="openRestore(dept)" />
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

      <!-- PAGINATION -->
      <div class="border-t border-border px-4 py-3">
        <AppPagination
          :pagination="crud.currentPagination()"
          :loading="crud.loading.value"
          @change-page="crud.load" />
      </div>
    </div>
  </div>

  <!-- CREATE / EDIT -->
  <BaseDialog :model-value="showFormModal" :title="selected ? 'Edit department' : 'Add department'" @update:model-value="closeFormModal">
    <div class="space-y-4">
      <BaseSelect v-model="form.college_id" label="College" :options="collegeOptions" placeholder="Select college" :error="errors.college_id" />

      <BaseInput v-model="form.name" label="Department name" placeholder="e.g. Computer Science" :error="errors.name" />

      <BaseSelect v-model="form.type" label="Type" :options="typeOptions" :error="errors.type" />
    </div>

    <template #footer>
      <div class="flex justify-end gap-2">
        <BaseButton variant="secondary" @click="closeFormModal"> Cancel </BaseButton>

        <BaseButton :loading="saving" @click="submitForm">
          {{ selected ? 'Save changes' : 'Create department' }}
        </BaseButton>
      </div>
    </template>
  </BaseDialog>

  <!-- ARCHIVE -->
  <ConfirmModal
    :show="showArchiveModal"
    title="Archive department?"
    :description="`This will remove ${selected?.name} from active department views. It can be restored later.`"
    confirm-text="Archive department"
    variant="danger"
    :icon="Archive"
    :loading="archiving"
    @close="showArchiveModal = false"
    @confirm="confirmArchive" />

  <!-- RESTORE -->
  <ConfirmModal
    :show="showRestoreModal"
    title="Restore department?"
    :description="`This will return ${selected?.name} to the active department list.`"
    confirm-text="Restore department"
    variant="accent"
    :icon="RotateCcw"
    :loading="restoring"
    @close="showRestoreModal = false"
    @confirm="confirmRestore" />
</template>
