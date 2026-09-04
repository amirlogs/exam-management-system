<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { GraduationCap, RotateCcw } from 'lucide-vue-next';

import ResourceToolbar from '@/shared/components/ResourceToolbar.vue';
import BaseBadge from '@/shared/components/ui/BaseBadge.vue';
import BaseSelect from '@/shared/components/ui/BaseSelect.vue';
import AppPagination from '@/shared/components/AppPagination.vue';

import { getStudents } from '../api/students';

import { getPrograms } from '@/modules/admin/programs/api/programs';
import { getSections } from '@/modules/admin/sections/api/sections';

import { handleApiError } from '@/shared/utils/apiError';
import { useUiStore } from '@/stores/ui';

import type { Student } from '../types/student';
import type { Pagination } from '@/shared/composables/useCrudResource';

const uiStore = useUiStore();

const students = ref<Student[]>([]);

const programs = ref<
  {
    id: number;
    name: string;
    code: string;
  }[]
>([]);

const sections = ref<
  {
    id: number;
    name: string;
    year_level: number;
    program_id?: number;
    semester_id?: number;
  }[]
>([]);

const loading = ref(false);
const refreshing = ref(false);

const search = ref('');

const filters = ref<{
  program_id: number | null;
  section_id: number | null;
  status: string | null;
}>({
  program_id: null,
  section_id: null,
  status: null,
});

const pagination = ref<Pagination>({
  current_page: 1,
  last_page: 1,
  per_page: 12,
  total: 0,
  from: null,
  to: null,
});

const programOptions = computed(() => [
  {
    value: null,
    label: 'All programs',
  },

  ...programs.value.map((program) => ({
    value: program.id,
    label: `${program.code} — ${program.name}`,
  })),
]);

const sectionOptions = computed(() => [
  {
    value: null,
    label: filters.value.program_id
      ? 'All sections'
      : 'Select a program first',
  },

  ...sections.value.map((section) => ({
    value: section.id,
    label: `${section.name} — Year ${section.year_level}`,
  })),
]);

const statusOptions = [
  {
    value: '',
    label: 'All statuses',
  },
  {
    value: 'active',
    label: 'Active',
  },
  {
    value: 'inactive',
    label: 'Inactive',
  },
];

const hasActiveFilters = computed(() => {
  return (
    filters.value.program_id !== null ||
    filters.value.section_id !== null ||
    (filters.value.status !== null &&
      filters.value.status !== '')
  );
});

const filterCount = computed(() => {
  let count = 0;

  if (filters.value.program_id !== null) {
    count++;
  }

  if (filters.value.section_id !== null) {
    count++;
  }

  if (
    filters.value.status !== null &&
    filters.value.status !== ''
  ) {
    count++;
  }

  return count;
});

async function loadPrograms() {
  if (programs.value.length) {
    return;
  }

  const res = await getPrograms(1, 200);

  programs.value = res.data;
}

async function loadSections() {
  if (filters.value.program_id === null) {
    sections.value = [];
    return;
  }

  const res = await getSections(
    1,
    200,
    '',
    {
      program_id: filters.value.program_id,
    },
  );

  sections.value = res.data;
}

async function load(page = 1) {
  loading.value = true;

  try {
    const activeFilters = {
      search: search.value || undefined,
      program_id:
        filters.value.program_id ?? undefined,
      section_id:
        filters.value.section_id ?? undefined,
      status:
        filters.value.status || undefined,
    };

    const res = await getStudents(
      page,
      12,
      activeFilters,
    );

    students.value = res.data;
    pagination.value = res.pagination;
  } catch (err) {
    handleApiError(
      err,
      uiStore,
      undefined,
      'Failed to load students.',
    );
  } finally {
    loading.value = false;
  }
}

function handleSearch(value: string) {
  search.value = value;
  load(1);
}

async function handleProgramChange() {
  filters.value.section_id = null;

  await loadSections();
  await load(1);
}

async function handleSectionChange() {
  await load(1);
}

async function handleStatusChange() {
  await load(1);
}

function clearFilters() {
  search.value = '';

  filters.value = {
    program_id: null,
    section_id: null,
    status: null,
  };

  sections.value = [];

  load(1);
}

async function handleRefresh() {
  refreshing.value = true;

  try {
    await load(pagination.value.current_page);
  } finally {
    refreshing.value = false;
  }
}

function initials(student: Student) {
  const first =
    student.user.first_name?.[0] ?? '';

  const last =
    student.user.last_name?.[0] ?? '';

  return (
    first + last
  ).toUpperCase() || '—';
}

onMounted(async () => {
  try {
    await loadPrograms();
    await load(1);
  } catch (err) {
    handleApiError(
      err,
      uiStore,
      undefined,
      'Failed to load student data.',
    );
  }
});
</script>

<template>
  <div class="mx-auto w-full max-w-360 space-y-6 px-6 py-6">
    <ResourceToolbar
      title="Students"
      description="View students and their academic section assignments."
      search-placeholder="Search by name or email…"
      :search="search"
      :show-search="true"
      :show-filter="true"
      :show-refresh="true"
      :refreshing="refreshing"
      :has-active-filters="hasActiveFilters"
      :filter-count="filterCount"
      @update:search="handleSearch"
      @refresh="handleRefresh"
      @clear-filters="clearFilters"
    >
      <template #filters>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
          <BaseSelect
            v-model="filters.program_id"
            label="Program"
            :options="programOptions"
            placeholder="All programs"
            @update:model-value="handleProgramChange"
          />

          <BaseSelect
            v-model="filters.section_id"
            label="Section"
            :options="sectionOptions"
            :disabled="filters.program_id === null"
            placeholder="All sections"
            @update:model-value="handleSectionChange"
          />

          <BaseSelect
            v-model="filters.status"
            label="Status"
            :options="statusOptions"
            placeholder="All statuses"
            @update:model-value="handleStatusChange"
          />
        </div>

        <div
          v-if="hasActiveFilters"
          class="mt-4 flex justify-end"
        >
          <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-xs font-medium text-text/55 transition-colors hover:bg-text/5 hover:text-accent"
            @click="clearFilters"
          >
            <RotateCcw class="h-3.5 w-3.5" />
            Reset filters
          </button>
        </div>
      </template>
    </ResourceToolbar>

    <div class="rounded-md border border-border bg-surface">
      <div class="overflow-x-auto">
        <table class="w-full min-w-[980px] border-collapse">
          <thead>
            <tr class="border-b border-border bg-text/2.5">
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">
                Student
              </th>

              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">
                Student Number
              </th>

              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">
                Program
              </th>

              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">
                Section
              </th>

              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">
                Entry Year
              </th>

              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">
                Status
              </th>
            </tr>
          </thead>

          <tbody
            v-if="loading"
            class="divide-y divide-border"
          >
            <tr v-for="row in 6" :key="row">
              <td colspan="6" class="px-4 py-4">
                <div class="h-3.5 w-full max-w-sm animate-pulse rounded bg-text/5" />
              </td>
            </tr>
          </tbody>

          <tbody
            v-else-if="students.length"
            class="divide-y divide-border"
          >
            <tr
              v-for="student in students"
              :key="student.id"
              class="hover:bg-text/2"
            >
              <td class="px-4 py-3.5">
                <div class="flex items-center gap-3">
                  <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-accent/10 text-xs font-bold text-accent">
                    {{ initials(student) }}
                  </div>

                  <div class="min-w-0">
                    <p class="truncate text-sm font-medium text-text">
                      {{ student.user.first_name }}
                      {{ student.user.last_name }}
                    </p>

                    <p class="truncate text-xs text-text/50">
                      {{ student.user.email }}
                    </p>
                  </div>
                </div>
              </td>

              <td class="px-4 py-3.5 font-mono text-xs text-text/70">
                {{ student.student_number || '—' }}
              </td>

              <td class="px-4 py-3.5 text-sm text-text/60">
                {{ student.program_id || '—' }}
              </td>

              <td class="px-4 py-3.5 text-sm text-text/60">
                {{
                  student.section
                    ? `${student.section.name} (Yr ${student.section.year_level})`
                    : '—'
                }}
              </td>

              <td class="px-4 py-3.5 font-mono text-xs text-text/60">
                {{ student.entry_year || '—' }}
              </td>

              <td class="px-4 py-3.5">
                <BaseBadge
                  :variant="
                    student.status === 'active'
                      ? 'success'
                      : 'neutral'
                  "
                >
                  {{ student.status }}
                </BaseBadge>
              </td>
            </tr>
          </tbody>

          <tbody v-else>
            <tr>
              <td colspan="6" class="px-6 py-14 text-center">
                <div class="mx-auto flex max-w-sm flex-col items-center">
                  <div class="flex h-10 w-10 items-center justify-center rounded-full bg-accent/10 text-accent">
                    <GraduationCap class="h-5 w-5" />
                  </div>

                  <p class="mt-3 text-sm font-medium text-text">
                    No students found.
                  </p>

                  <p class="mt-1 text-xs text-text/45">
                    Try changing your search or filters.
                  </p>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <AppPagination
        :pagination="pagination"
        @change-page="load"
      />
    </div>
  </div>
</template>
