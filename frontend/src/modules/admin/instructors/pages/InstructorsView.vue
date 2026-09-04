<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { GraduationCap, RotateCcw } from 'lucide-vue-next';

import ResourceToolbar from '@/shared/components/ResourceToolbar.vue';
import BaseBadge from '@/shared/components/ui/BaseBadge.vue';
import BaseSelect from '@/shared/components/ui/BaseSelect.vue';
import AppPagination from '@/shared/components/AppPagination.vue';

import { getInstructors } from '../api/instructors';
import { getDepartments } from '@/modules/admin/departments/api/departments';

import { handleApiError } from '@/shared/utils/apiError';
import { useUiStore } from '@/stores/ui';

import type { Instructor } from '../types/instructor';
import type { Pagination } from '@/shared/composables/useCrudResource';

const uiStore = useUiStore();

const instructors = ref<Instructor[]>([]);
const departments = ref<
  {
    id: number;
    name: string;
  }[]
>([]);

const loading = ref(false);
const refreshing = ref(false);

const search = ref('');

const filters = ref<{
  department_id: number | null;
  status: string | null;
}>({
  department_id: null,
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

const departmentOptions = computed(() => [
  {
    value: null,
    label: 'All departments',
  },
  ...departments.value.map((department) => ({
    value: department.id,
    label: department.name,
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
  return filters.value.department_id !== null || (filters.value.status !== null && filters.value.status !== '');
});

const filterCount = computed(() => {
  let count = 0;

  if (filters.value.department_id !== null) {
    count++;
  }

  if (filters.value.status !== null && filters.value.status !== '') {
    count++;
  }

  return count;
});

async function loadDepartments() {
  if (departments.value.length) {
    return;
  }

  const res = await getDepartments(1, 200);

  departments.value = res.data;
}

async function load(page = 1) {
  loading.value = true;

  try {
    const activeFilters = {
      search: search.value || undefined,
      department_id: filters.value.department_id ?? undefined,
      status: filters.value.status || undefined,
    };

    const res = await getInstructors(page, 12, activeFilters);

    instructors.value = res.data;
    pagination.value = res.pagination;
  } catch (err) {
    handleApiError(err, uiStore, undefined, 'Failed to load instructors.');
  } finally {
    loading.value = false;
  }
}

function handleSearch(value: string) {
  search.value = value;
  load(1);
}

function applyFilters() {
  load(1);
}

function clearFilters() {
  filters.value = {
    department_id: null,
    status: null,
  };

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

function initials(instructor: Instructor) {
  const first = instructor.user.first_name?.[0] ?? '';

  const last = instructor.user.last_name?.[0] ?? '';

  return (first + last).toUpperCase() || '—';
}

onMounted(async () => {
  try {
    await loadDepartments();
    await load(1);
  } catch (err) {
    handleApiError(err, uiStore, undefined, 'Failed to load instructor data.');
  }
});
</script>

<template>
  <div class="mx-auto w-full max-w-360 space-y-6 px-6 py-6">
    <ResourceToolbar
      title="Instructors"
      description="View instructor profiles and their department assignments."
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
      @clear-filters="clearFilters">
      <template #filters>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <BaseSelect v-model="filters.department_id" label="Department" :options="departmentOptions" placeholder="All departments" @update:model-value="applyFilters" />

          <BaseSelect v-model="filters.status" label="Status" :options="statusOptions" placeholder="All statuses" @update:model-value="applyFilters" />
        </div>

        <div v-if="hasActiveFilters" class="mt-4 flex justify-end">
          <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-xs font-medium text-text/55 transition-colors hover:bg-text/5 hover:text-accent"
            @click="clearFilters">
            <RotateCcw class="h-3.5 w-3.5" />
            Reset filters
          </button>
        </div>
      </template>
    </ResourceToolbar>

    <div class="rounded-md border border-border bg-surface">
      <div class="overflow-x-auto">
        <table class="w-full min-w-210 border-collapse">
          <thead>
            <tr class="border-b border-border bg-text/2.5">
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Instructor</th>

              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Employee #</th>

              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Department</th>

              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Rank</th>

              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Status</th>
            </tr>
          </thead>

          <tbody v-if="loading" class="divide-y divide-border">
            <tr v-for="row in 6" :key="row">
              <td colspan="5" class="px-4 py-4">
                <div class="h-3.5 w-full max-w-sm animate-pulse rounded bg-text/5" />
              </td>
            </tr>
          </tbody>

          <tbody v-else-if="instructors.length" class="divide-y divide-border">
            <tr v-for="instructor in instructors" :key="instructor.id" class="hover:bg-text/2">
              <td class="px-4 py-3.5">
                <div class="flex items-center gap-3">
                  <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-accent/10 text-xs font-bold text-accent">
                    {{ initials(instructor) }}
                  </div>

                  <div class="min-w-0">
                    <p class="truncate text-sm font-medium text-text">
                      {{ instructor.user.first_name }}
                      {{ instructor.user.last_name }}
                    </p>

                    <p class="truncate text-xs text-text/50">
                      {{ instructor.user.email }}
                    </p>
                  </div>
                </div>
              </td>

              <td class="px-4 py-3.5 font-mono text-xs text-text/70">
                {{ instructor.employee_number || '—' }}
              </td>

              <td class="px-4 py-3.5 text-sm text-text/60">
                {{ instructor.department?.name || '—' }}
              </td>

              <td class="px-4 py-3.5 text-sm text-text/60">
                {{ instructor.academic_rank || '—' }}
              </td>

              <td class="px-4 py-3.5">
                <BaseBadge :variant="instructor.status === 'active' ? 'success' : 'neutral'">
                  {{ instructor.status }}
                </BaseBadge>
              </td>
            </tr>
          </tbody>

          <tbody v-else>
            <tr>
              <td colspan="5" class="px-6 py-14 text-center">
                <div class="mx-auto flex max-w-sm flex-col items-center">
                  <div class="flex h-10 w-10 items-center justify-center rounded-full bg-accent/10 text-accent">
                    <GraduationCap class="h-5 w-5" />
                  </div>

                  <p class="mt-3 text-sm font-medium text-text">No instructors found.</p>

                  <p class="mt-1 text-xs text-text/45">Try changing your search or filters.</p>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <AppPagination :pagination="pagination" @change-page="load" />
    </div>
  </div>
</template>
