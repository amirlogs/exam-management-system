<script setup lang="ts">
import { onMounted, onBeforeUnmount, ref, computed, watch } from 'vue';
import { Archive, BookOpen, Plus, RotateCcw } from 'lucide-vue-next';

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

import { sectionSchema } from '../schemas/section.schema';
import { getSections, getArchivedSections, createSection, updateSection, deleteSection, restoreSection } from '../api/sections';

import { getSemesters } from '@/modules/admin/semesters/api/semesters';
import { getPrograms } from '@/modules/admin/programs/api/programs';

import type { Section } from '../types/section';
import type { Semester } from '@/modules/admin/semesters/types/semester';
import type { Program } from '@/modules/admin/programs/types/program';

type SectionColumn = 'section' | 'year_level' | 'program' | 'semester' | 'created_at' | 'updated_at';

const uiStore = useUiStore();

const filters = ref({
  semester_id: null as number | null,
  program_id: null as number | null,
  year_level: null as number | null,
});

const crud = useCrudResource<Section>(
  {
    list: (params) =>
      getSections(params.page ?? 1, 12, params.search ?? '', {
        semester_id: filters.value.semester_id,
        program_id: filters.value.program_id,
        year_level: filters.value.year_level,
      }),

    listArchived: (params) =>
      getArchivedSections(params.page ?? 1, 12, params.search ?? '', {
        semester_id: filters.value.semester_id,
        program_id: filters.value.program_id,
        year_level: filters.value.year_level,
      }),

    remove: deleteSection,
    restore: restoreSection,
  },
  'name',
);

const columns = [
  {
    key: 'section' as SectionColumn,
    label: 'Section',
    required: true,
  },
  {
    key: 'year_level' as SectionColumn,
    label: 'Year level',
    required: false,
  },
  {
    key: 'program' as SectionColumn,
    label: 'Program',
    required: false,
  },
  {
    key: 'semester' as SectionColumn,
    label: 'Semester',
    required: false,
  },
  {
    key: 'created_at' as SectionColumn,
    label: 'Created',
    required: false,
  },
  {
    key: 'updated_at' as SectionColumn,
    label: 'Updated',
    required: false,
  },
];

const showColumns = ref(false);

const visibleColumns = ref<SectionColumn[]>(['section', 'year_level', 'program', 'semester']);

const isColumnVisible = (column: SectionColumn) => visibleColumns.value.includes(column);

const toggleColumn = (column: SectionColumn) => {
  const config = columns.find((item) => item.key === column);

  if (config?.required) return;

  if (isColumnVisible(column)) {
    visibleColumns.value = visibleColumns.value.filter((item) => item !== column);
  } else {
    visibleColumns.value = [...visibleColumns.value, column];
  }
};

const resetColumns = () => {
  visibleColumns.value = ['section', 'year_level', 'program', 'semester'];
};

const visibleColumnCount = computed(() => visibleColumns.value.length);

const totalTableColumns = computed(() => visibleColumnCount.value + 1);

const showFormModal = ref(false);
const showArchiveModal = ref(false);
const showRestoreModal = ref(false);

const selected = ref<Section | null>(null);

const saving = ref(false);
const archiving = ref(false);
const restoring = ref(false);

const semesters = ref<Semester[]>([]);
const programs = ref<Program[]>([]);

const semesterOptions = computed(() =>
  semesters.value.map((semester) => ({
    value: semester.id,
    label: `Semester ${semester.name} · ${semester.academic_year}`,
  })),
);

const programOptions = computed(() =>
  programs.value.map((program) => ({
    value: program.id,
    label: program.name,
  })),
);

const semesterFilterOptions = computed(() => [
  {
    value: null,
    label: 'All semesters',
  },
  ...semesterOptions.value,
]);

const programFilterOptions = computed(() => [
  {
    value: null,
    label: 'All programs',
  },
  ...programOptions.value,
]);

const yearLevelOptions = [
  {
    value: null,
    label: 'All year levels',
  },
  {
    value: 1,
    label: 'Year 1',
  },
  {
    value: 2,
    label: 'Year 2',
  },
  {
    value: 3,
    label: 'Year 3',
  },
  {
    value: 4,
    label: 'Year 4',
  },
  {
    value: 5,
    label: 'Year 5',
  },
];

const hasActiveFilters = computed(() => filters.value.semester_id !== null || filters.value.program_id !== null || filters.value.year_level !== null);

const { form, errors, validate, reset, applyServerErrors } = useResourceForm(sectionSchema, {
  semester_id: 0,
  program_id: 0,
  year_level: 1,
  name: 1,
});

async function loadOptions() {
  if (semesters.value.length && programs.value.length) {
    return;
  }

  try {
    const [semesterResponse, programResponse] = await Promise.all([getSemesters(1, 100), getPrograms(1, 100)]);

    semesters.value = semesterResponse.data;
    programs.value = programResponse.data;
  } catch {
    uiStore.showToast('Failed to load section options.', 'error');
  }
}

function clearFilters() {
  filters.value = {
    semester_id: null,
    program_id: null,
    year_level: null,
  };
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
    semester_id: filters.value.semester_id ?? 0,
    program_id: filters.value.program_id ?? 0,
    year_level: filters.value.year_level ?? 1,
    name: 1,
  });

  loadOptions();
  showFormModal.value = true;
}

function openEdit(section: Section) {
  selected.value = section;

  reset({
    semester_id: section.semester_id,
    program_id: section.program_id,
    year_level: section.year_level,
    name: Number(section.name),
  });

  loadOptions();
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
      await updateSection(selected.value.id, form);
    } else {
      await createSection(form);
    }

    closeFormModal();

    uiStore.showToast(isEditing ? 'Section updated.' : 'Section created.', 'success');

    await crud.load(crud.currentPagination().current_page);
  } catch (err: any) {
    if (err?.response?.status === 422) {
      applyServerErrors(err.response.data?.errors);

      uiStore.showToast('Please fix the errors below.', 'error');
    } else {
      uiStore.showToast(isEditing ? 'Failed to update section.' : 'Failed to save section.', 'error');
    }
  } finally {
    saving.value = false;
  }
}

function openArchive(section: Section) {
  selected.value = section;
  showArchiveModal.value = true;
}

function openRestore(section: Section) {
  selected.value = section;
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
    uiStore.showToast('Failed to archive section.', 'error');
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
    uiStore.showToast('Failed to restore section.', 'error');
  } finally {
    restoring.value = false;
  }
}

const emptyStateText = computed(() => {
  if (crud.search.value || hasActiveFilters.value) {
    return 'No matching sections found';
  }

  return crud.activeTab.value === 'archived' ? 'No archived sections' : 'No sections yet';
});

watch([() => filters.value.semester_id, () => filters.value.program_id, () => filters.value.year_level], () => {
  crud.load(1);
});
onMounted(async () => {
  document.addEventListener('click', handleDocumentClick);

  await loadOptions();
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
        title="Sections"
        description="Manage student sections across programs and semesters."
        search-placeholder="Search sections..."
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
        @clear-filters="clearFilters"
        @change-tab="crud.changeTab"
        @update:search="crud.setSearch"
        @refresh="retry"
        @columns="showColumns = !showColumns">
        <template #actions>
          <BaseButton v-can="'section.create'" :icon="Plus" @click="openCreate"> Add section </BaseButton>
        </template>

        <template #filters>
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <BaseSelect v-model="filters.semester_id" label="Semester" :options="semesterFilterOptions" placeholder="All semesters" />

            <BaseSelect v-model="filters.program_id" label="Program" :options="programFilterOptions" placeholder="All programs" />

            <BaseSelect v-model="filters.year_level" label="Year level" :options="yearLevelOptions" placeholder="All year levels" />
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

    <div v-can="'section.view'" class="overflow-hidden rounded-md border border-border bg-surface">
      <div class="overflow-x-auto">
        <table class="w-full min-w-215 border-collapse">
          <thead>
            <tr class="border-b border-border bg-text/2.5">
              <th v-if="isColumnVisible('section')" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Section</th>

              <th v-if="isColumnVisible('year_level')" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Year level</th>

              <th v-if="isColumnVisible('program')" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Program</th>

              <th v-if="isColumnVisible('semester')" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Semester</th>

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
                  :class="column === 'section' ? 'w-24' : column === 'program' ? 'w-40' : column === 'semester' ? 'w-36' : 'w-20'" />
              </td>

              <td class="px-4 py-4">
                <div class="ml-auto h-8 w-8 animate-pulse rounded-md bg-text/5" />
              </td>
            </tr>
          </tbody>

          <tbody v-else-if="crud.currentList().length" class="divide-y divide-border">
            <tr v-for="section in crud.currentList()" :key="section.id" class="group transition-colors hover:bg-text/2">
              <td v-if="isColumnVisible('section')" class="px-4 py-3.5">
                <div class="flex items-center gap-3">
                  <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md border border-border bg-bg text-text/50">
                    <BookOpen class="h-4 w-4" />
                  </div>

                  <p class="text-sm font-medium text-text">
                    Section
                    {{ section.name }}
                  </p>
                </div>
              </td>

              <td v-if="isColumnVisible('year_level')" class="px-4 py-3.5">
                <span class="text-sm text-text/60">
                  Year
                  {{ section.year_level }}
                </span>
              </td>

              <td v-if="isColumnVisible('program')" class="px-4 py-3.5">
                <span class="text-sm text-text/60">
                  {{ section.program?.name || '—' }}
                </span>
              </td>

              <td v-if="isColumnVisible('semester')" class="px-4 py-3.5">
                <span class="text-sm text-text/60">
                  {{ section.semester ? `Semester ${section.semester.name} · ${section.semester.academic_year}` : '—' }}
                </span>
              </td>

              <td v-if="isColumnVisible('created_at')" class="px-4 py-3.5">
                <span class="font-mono text-xs tabular-nums text-text/60">
                  {{ section.created_at || '—' }}
                </span>
              </td>

              <td v-if="isColumnVisible('updated_at')" class="px-4 py-3.5">
                <span class="font-mono text-xs tabular-nums text-text/60">
                  {{ section.updated_at || '—' }}
                </span>
              </td>

              <td class="px-4 py-3.5 text-right">
                <TableRowActions
                  :active-tab="crud.activeTab.value"
                  edit-permission="section.update"
                  archive-permission="section.archive"
                  restore-permission="section.restore"
                  @edit="openEdit(section)"
                  @archive="openArchive(section)"
                  @restore="openRestore(section)" />
              </td>
            </tr>
          </tbody>

          <tbody v-else>
            <tr>
              <td :colspan="totalTableColumns" class="px-6 py-16 text-center">
                <div class="mx-auto flex max-w-sm flex-col items-center">
                  <div class="flex h-10 w-10 items-center justify-center rounded-full bg-text/5 text-text/40">
                    <BookOpen class="h-5 w-5" />
                  </div>

                  <p class="mt-3 text-sm font-medium text-text">
                    {{ emptyStateText }}
                  </p>

                  <p v-if="crud.search.value || hasActiveFilters" class="mt-1 text-xs text-text/45">Try adjusting your search or filters.</p>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <AppPagination :pagination="crud.currentPagination()" @change-page="crud.load" />
    </div>
  </div>

  <BaseDialog :model-value="showFormModal" :title="selected ? 'Edit section' : 'Add section'" @update:model-value="closeFormModal">
    <div class="space-y-4">
      <BaseSelect v-model="form.semester_id" label="Semester" :options="semesterOptions" placeholder="Select semester" :error="errors.semester_id" :disabled="!!selected" />

      <BaseSelect v-model="form.program_id" label="Program" :options="programOptions" placeholder="Select program" :error="errors.program_id" :disabled="!!selected" />

      <BaseInput v-model.number="form.year_level" type="number" label="Year level" placeholder="1" :error="errors.year_level" />

      <BaseInput v-model.number="form.name" type="number" label="Section number" placeholder="1" :error="errors.name" />
    </div>

    <template #footer>
      <div class="flex justify-end gap-2">
        <BaseButton variant="secondary" @click="closeFormModal"> Cancel </BaseButton>

        <BaseButton :loading="saving" @click="submitForm">
          {{ selected ? 'Save changes' : 'Create section' }}
        </BaseButton>
      </div>
    </template>
  </BaseDialog>

  <ConfirmModal
    :show="showArchiveModal"
    title="Archive section?"
    :description="`This will remove Section ${selected?.name} from active section views.`"
    confirm-text="Archive section"
    variant="danger"
    :icon="Archive"
    :loading="archiving"
    @close="showArchiveModal = false"
    @confirm="confirmArchive" />

  <ConfirmModal
    :show="showRestoreModal"
    title="Restore section?"
    :description="`This will return Section ${selected?.name} to the active section list.`"
    confirm-text="Restore section"
    variant="accent"
    :icon="RotateCcw"
    :loading="restoring"
    @close="showRestoreModal = false"
    @confirm="confirmRestore" />
</template>
