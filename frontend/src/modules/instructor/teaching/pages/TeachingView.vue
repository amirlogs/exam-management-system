<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import { BookOpen, RefreshCw } from 'lucide-vue-next';

import ResourceToolbar from '@/shared/components/ResourceToolbar.vue';
import BaseBadge from '@/shared/components/ui/BaseBadge.vue';
import AppPagination from '@/shared/components/AppPagination.vue';
import { useUiStore } from '@/stores/ui';

import * as api from '../api/teaching';
import type { Teaching } from '../types/teaching';
import type { Pagination } from '@/shared/composables/useCrudResource';
import BaseSelect from '@/shared/components/ui/BaseSelect.vue';

const router = useRouter();
const uiStore = useUiStore();

const teachings = ref<Teaching[]>([]);

const pagination = ref<Pagination>({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
  from: null,
  to: null,
});

const loading = ref(false);
const refreshing = ref(false);
const search = ref('');
const filterStatus = ref('');

const statusOptions = [
  {
    value: '',
    label: 'All statuses',
  },
  {
    value: 'approved',
    label: 'Approved',
  },
  {
    value: 'pending_approval',
    label: 'Pending Approval',
  },
  {
    value: 'draft',
    label: 'Draft',
  },
  {
    value: 'rejected',
    label: 'Rejected',
  },
  {
    value: 'cancelled',
    label: 'Cancelled',
  },
];

const statusVariant: Record<string, 'neutral' | 'info' | 'danger' | 'warning' | 'success' | 'dark'> = {
  approved: 'success',
  pending_approval: 'warning',
  draft: 'neutral',
  rejected: 'danger',
  cancelled: 'danger',
};

async function load(page = 1) {
  loading.value = true;

  try {
    const params: Record<string, any> = {};

    if (search.value) {
      params.search = search.value;
    }

    if (filterStatus.value) {
      params.status = filterStatus.value;
    }

    const res = await api.getTeaching(page, pagination.value.per_page, params);

    teachings.value = res.data;
    pagination.value = res.pagination;
  } catch (error: any) {
    uiStore.showToast(error?.response?.data?.message || 'Failed to load your teaching assignments.', 'error');
  } finally {
    loading.value = false;
  }
}

async function handleRefresh() {
  refreshing.value = true;

  try {
    await load(pagination.value.current_page);
  } finally {
    refreshing.value = false;
  }
}

function clearFilters() {
  search.value = '';
  filterStatus.value = '';
}

function openTeaching(teaching: Teaching) {
  router.push({
    name: 'instructor.teaching.detail',
    params: {
      courseOfferingId: teaching.id,
    },
  });
}

function formatRole(role: string) {
  return role
    .split('_')
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ');
}

function getPrimaryRole(teaching: Teaching) {
  if (!teaching.assignments.length) {
    return null;
  }

  const leadInstructor = teaching.assignments.find((assignment) => assignment.type === 'lead_instructor');

  return leadInstructor?.type ?? teaching.assignments[0]?.type ?? null;
}

function getSectionCount(teaching: Teaching) {
  return new Set(teaching.assignments.map((assignment) => assignment.section?.id).filter((id): id is number => id !== undefined)).size;
}

watch([search, filterStatus], () => {
  load(1);
});

onMounted(() => {
  load(1);
});
</script>

<template>
  <div class="mx-auto min-h-[calc(100vh-68px)] w-full max-w-360 space-y-6 px-6 py-8">
    <ResourceToolbar
      title="My Teaching"
      description="View the courses, sections, and teaching assignments assigned to you."
      search-placeholder="Search by course name or code…"
      :search="search"
      :show-search="true"
      :show-filter="true"
      :show-refresh="true"
      :refreshing="refreshing"
      :has-active-filters="!!filterStatus"
      :filter-count="filterStatus ? 1 : 0"
      @update:search="(value) => (search = value)"
      @refresh="handleRefresh"
      @clear-filters="clearFilters">
      <template #filters>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <BaseSelect v-model="filterStatus" label="Status" :options="statusOptions" placeholder="All statuses" />
        </div>
      </template>
    </ResourceToolbar>

    <div class="overflow-hidden rounded-xl border border-border bg-surface shadow-sm">
      <table class="w-full border-collapse">
        <thead>
          <tr class="border-b border-border bg-bg/40">
            <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-text/45">Course</th>

            <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-text/45">Semester</th>

            <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-text/45">Role</th>

            <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-text/45">Sections</th>

            <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-text/45">Status</th>

            <th class="w-10"></th>
          </tr>
        </thead>

        <tbody v-if="loading" class="divide-y divide-border">
          <tr v-for="i in 5" :key="i">
            <td colspan="6" class="px-6 py-5">
              <div class="space-y-2">
                <div class="h-4 w-56 animate-pulse rounded bg-bg" />
                <div class="h-3 w-24 animate-pulse rounded bg-bg" />
              </div>
            </td>
          </tr>
        </tbody>

        <tbody v-else-if="teachings.length" class="divide-y divide-border">
          <tr v-for="teaching in teachings" :key="teaching.id" class="cursor-pointer transition-colors hover:bg-bg/40" @click="openTeaching(teaching)">
            <td class="px-6 py-5">
              <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-accent/10">
                  <BookOpen class="h-5 w-5 text-accent" />
                </div>

                <div class="min-w-0">
                  <p class="truncate text-sm font-medium text-text">
                    {{ teaching.course?.name || '—' }}
                  </p>

                  <p class="mt-0.5 font-mono text-xs text-text/50">
                    {{ teaching.course?.code || '—' }}
                  </p>
                </div>
              </div>
            </td>

            <td class="px-6 py-5">
              <p class="text-sm text-text/70">
                {{ teaching.semester?.name || '—' }}
              </p>

              <p class="mt-0.5 text-xs text-text/45">
                {{ teaching.semester?.academic_year || '—' }}
              </p>
            </td>

            <td class="px-6 py-5">
              <span class="text-sm text-text/70">
                {{ getPrimaryRole(teaching) ? formatRole(getPrimaryRole(teaching)!) : '—' }}
              </span>
            </td>

            <td class="px-6 py-5">
              <span class="text-sm tabular-nums text-text/70">
                {{ getSectionCount(teaching) }}
              </span>
            </td>

            <td class="px-6 py-5">
              <BaseBadge :variant="statusVariant[teaching.status] || 'neutral'">
                {{ formatRole(teaching.status) }}
              </BaseBadge>
            </td>

            <td class="px-6 py-5 text-right">
              <span class="text-text/30">
                <svg class="inline-block h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="m9 18 6-6-6-6" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
              </span>
            </td>
          </tr>
        </tbody>

        <tbody v-else>
          <tr>
            <td colspan="6" class="px-6 py-16 text-center">
              <div class="mx-auto flex max-w-sm flex-col items-center">
                <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-bg">
                  <BookOpen class="h-6 w-6 text-text/30" />
                </div>

                <p class="text-sm font-medium text-text">No teaching assignments found</p>

                <p class="mt-1 text-sm text-text/45">Your assigned course offerings will appear here.</p>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <AppPagination :pagination="pagination" @change-page="load" />
    </div>
  </div>
</template>
