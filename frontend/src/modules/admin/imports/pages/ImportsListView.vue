<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { Upload, ChevronRight } from 'lucide-vue-next';
import { useRouter } from 'vue-router';

import ResourceToolbar from '@/shared/components/ResourceToolbar.vue';
import BaseButton from '@/shared/components/ui/BaseButton.vue';
import BaseBadge from '@/shared/components/ui/BaseBadge.vue';
import BaseSelect from '@/shared/components/ui/BaseSelect.vue';
import AppPagination from '@/shared/components/AppPagination.vue';

import { listImports } from '../api/imports';
import { IMPORT_TYPE_CONFIG } from '../config/importTypes';

import type { ImportHistory, ImportType, ImportStatus } from '../types/import';

import type { Pagination } from '@/shared/composables/useCrudResource';
import { handleApiError } from '@/shared/utils/apiError';
import { useUiStore } from '@/stores/ui';

const router = useRouter();
const uiStore = useUiStore();

const imports = ref<ImportHistory[]>([]);
const loading = ref(false);
const error = ref('');

const filterType = ref<ImportType | ''>('');
const filterStatus = ref<ImportStatus | ''>('');

const pagination = ref<Pagination>({
  current_page: 1,
  last_page: 1,
  per_page: 12,
  total: 0,
  from: null,
  to: null,
});

const typeOptions = [
  { value: '', label: 'All types' },
  { value: 'students', label: 'Students' },
  { value: 'instructors', label: 'Instructors' },
  { value: 'sections', label: 'Sections' },
  { value: 'questions', label: 'Questions' },
  { value: 'users', label: 'Users' },
];

const statusOptions = [
  { value: '', label: 'All statuses' },
  { value: 'pending', label: 'Pending' },
  { value: 'processing', label: 'Processing' },
  { value: 'ready_for_review', label: 'Ready for review' },
  { value: 'confirmed', label: 'Confirmed' },
  { value: 'failed', label: 'Failed' },
];

const hasActiveFilters = computed(() => {
  return filterType.value !== '' || filterStatus.value !== '';
});

const filterCount = computed(() => {
  let count = 0;

  if (filterType.value !== '') count++;
  if (filterStatus.value !== '') count++;

  return count;
});

const statusVariant: Record<string, 'info' | 'danger' | 'success' | 'neutral'> = {
  pending: 'neutral',
  processing: 'neutral',
  ready_for_review: 'info',
  confirmed: 'success',
  failed: 'danger',
};

const statusLabel: Record<string, string> = {
  pending: 'Processing',
  processing: 'Processing',
  ready_for_review: 'Ready for review',
  confirmed: 'Confirmed',
  failed: 'Failed',
};

async function load(page = 1) {
  loading.value = true;
  error.value = '';

  try {
    const res = await listImports(filterType.value || undefined, page, 12, filterStatus.value || undefined);

    imports.value = res.data;
    pagination.value = res.pagination;
  } catch (err) {
    error.value = 'Failed to load imports.';
    handleApiError(err, uiStore, undefined, 'Failed to load imports.');
  } finally {
    loading.value = false;
  }
}
function retry() {
  load(pagination.value.current_page);
}

function clearFilters() {
  filterType.value = '';
  filterStatus.value = '';
  load(1);
}

function applyFilters() {
  load(1);
}

function openImport(importHistory: ImportHistory) {
  router.push({
    name: 'admin-import-detail',
    params: {
      id: importHistory.id,
    },
  });
}

onMounted(() => load(1));
</script>

<template>
  <div class="mx-auto w-full max-w-360 space-y-6 px-6 py-6">
    <ResourceToolbar
      title="Data imports"
      description="Review and manage CSV imports across the system."
      :show-search="false"
      :show-filter="true"
      :show-refresh="true"
      :refreshing="loading"
      :show-fullscreen="true"
      :has-active-filters="hasActiveFilters"
      :filter-count="filterCount"
      @refresh="retry"
      @clear-filters="clearFilters">
      <template #actions>
        <BaseButton @click="router.push({ name: 'admin-import-new' })">
          <template #icon>
            <Upload class="h-4 w-4" />
          </template>
          New import
        </BaseButton>
      </template>

      <template #filters>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <BaseSelect v-model="filterType" label="Import type" :options="typeOptions" @update:model-value="applyFilters" />

          <BaseSelect v-model="filterStatus" label="Status" :options="statusOptions" @update:model-value="applyFilters" />
        </div>
      </template>
    </ResourceToolbar>
    <div v-if="error" class="flex items-start gap-3 rounded-md border border-error/30 bg-error/5 p-4">
      <p class="flex-1 text-sm font-medium text-error">
        {{ error }}
      </p>

      <button type="button" class="text-sm font-medium text-error hover:underline" @click="retry">Retry</button>
    </div>

    <div class="rounded-md border border-border bg-surface">
      <div class="overflow-x-auto">
        <table class="w-full min-w-180 border-collapse">
          <thead>
            <tr class="border-b border-border bg-text/2.5">
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Import</th>

              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Type</th>

              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Rows</th>

              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Status</th>

              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Uploaded by</th>

              <th class="w-10 px-4 py-3" />
            </tr>
          </thead>

          <tbody v-if="loading" class="divide-y divide-border">
            <tr v-for="row in 6" :key="row">
              <td colspan="6" class="px-4 py-4">
                <div class="h-3.5 w-64 animate-pulse rounded bg-text/5" />
              </td>
            </tr>
          </tbody>

          <tbody v-else-if="imports.length" class="divide-y divide-border">
            <tr v-for="imp in imports" :key="imp.id" class="group cursor-pointer transition-colors hover:bg-text/2" @click="openImport(imp)">
              <td class="px-4 py-3.5">
                <span class="font-mono text-xs text-text/70"> #{{ imp.id }} </span>
              </td>

              <td class="px-4 py-3.5">
                <span class="text-sm font-medium text-text">
                  {{ IMPORT_TYPE_CONFIG[imp.type].label }}
                </span>
              </td>

              <td class="px-4 py-3.5 text-sm">
                <span class="font-medium text-success">
                  {{ imp.valid_count }}
                </span>

                <span class="ml-1 text-text/45"> valid </span>

                <span v-if="imp.error_count > 0" class="ml-2 font-medium text-error"> {{ imp.error_count }} errors </span>
              </td>

              <td class="px-4 py-3.5">
                <BaseBadge :variant="statusVariant[imp.status] || 'neutral'">
                  {{ statusLabel[imp.status] || imp.status }}
                </BaseBadge>
              </td>

              <td class="px-4 py-3.5 text-xs text-text/50">
                {{ imp.uploaded_by.name }}
              </td>

              <td class="px-4 py-3.5 text-right">
                <ChevronRight class="inline h-4 w-4 text-text/30 transition-transform group-hover:translate-x-0.5" />
              </td>
            </tr>
          </tbody>

          <tbody v-else>
            <tr>
              <td colspan="6" class="px-6 py-16 text-center text-sm text-text/55">No imports found.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <AppPagination :pagination="pagination" @change-page="load" />
    </div>
  </div>
</template>
