<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Archive as ArchiveIcon, Eye, GraduationCap, RotateCcw, Sparkles } from 'lucide-vue-next';

import ResourceToolbar from '@/shared/components/ResourceToolbar.vue';
import TableRowActions from '@/shared/components/TableRowActions.vue';
import BaseButton from '@/shared/components/ui/BaseButton.vue';
import BaseSelect from '@/shared/components/ui/BaseSelect.vue';
import BaseBadge from '@/shared/components/ui/BaseBadge.vue';
import AppPagination from '@/shared/components/AppPagination.vue';
import ConfirmModal from '@/shared/components/ConfirmModal.vue';

import SuggestionsPanel from '../components/SuggestionsPanel.vue';
import OfferingDetailModal from '../components/OfferingDetailModal.vue';

import { listOfferings, getArchivedOfferings, generateSuggestions, createOffering, getOffering, archiveOffering, restoreOffering } from '../api/courseOfferings';

import { getSemesters } from '@/modules/admin/semesters/api/semesters';

import type { CourseOffering, OfferingSuggestion, OfferingStatus } from '../types/courseOffering';

import type { Semester } from '@/modules/admin/semesters/types/semester';
import type { Pagination } from '@/shared/composables/useCrudResource';
import { useUiStore } from '@/stores/ui';

const uiStore = useUiStore();

// -----------------------------------------------------------------------------
// Columns
// -----------------------------------------------------------------------------

type OfferingColumn = 'course' | 'status' | 'sections' | 'instructors' | 'semester' | 'updated_at';

const columns: {
  key: OfferingColumn;
  label: string;
  required?: boolean;
}[] = [
  {
    key: 'course',
    label: 'Course',
    required: true,
  },
  {
    key: 'status',
    label: 'Status',
    required: true,
  },
  {
    key: 'sections',
    label: 'Sections',
  },
  {
    key: 'instructors',
    label: 'Instructors',
  },
  {
    key: 'semester',
    label: 'Semester',
  },
  {
    key: 'updated_at',
    label: 'Updated',
  },
];

const visibleColumns = ref<OfferingColumn[]>(['course', 'status', 'sections', 'instructors', 'semester']);

const showColumns = ref(false);

const isColumnVisible = (column: OfferingColumn) => visibleColumns.value.includes(column);

function toggleColumn(column: OfferingColumn) {
  const config = columns.find((item) => item.key === column);

  if (config?.required) {
    return;
  }

  if (isColumnVisible(column)) {
    visibleColumns.value = visibleColumns.value.filter((item) => item !== column);
  } else {
    visibleColumns.value = [...visibleColumns.value, column];
  }
}

function resetColumns() {
  visibleColumns.value = ['course', 'status', 'sections', 'instructors', 'semester'];
}

const totalTableColumns = computed(() => visibleColumns.value.length + 1);

// -----------------------------------------------------------------------------
// Semester
// -----------------------------------------------------------------------------

const semesters = ref<Semester[]>([]);
const selectedSemesterId = ref<number | null>(null);

const semesterOptions = computed(() =>
  semesters.value.map((semester) => ({
    value: String(semester.id),
    label: `${semester.name} · ${semester.academic_year}`,
  })),
);

// -----------------------------------------------------------------------------
// Tabs / Filters
// -----------------------------------------------------------------------------

const activeTab = ref<'active' | 'archived'>('active');

const search = ref('');
const statusFilter = ref<OfferingStatus | null>(null);

const statusFilterOptions = [
  {
    value: null,
    label: 'All statuses',
  },
  {
    value: 'draft',
    label: 'Draft',
  },
  {
    value: 'approved',
    label: 'Approved',
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

const hasActiveFilters = computed(() => statusFilter.value !== null);

function clearFilters() {
  search.value = '';
  statusFilter.value = null;
}

// -----------------------------------------------------------------------------
// List
// -----------------------------------------------------------------------------

const list = ref<CourseOffering[]>([]);

const pagination = ref<Pagination>({
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0,
  from: 0,
  to: 0,
});

const loading = ref(false);
const error = ref('');

// -----------------------------------------------------------------------------
// Suggestions
// -----------------------------------------------------------------------------

const showSuggestions = ref(false);
const suggestions = ref<OfferingSuggestion[]>([]);
const loadingSuggestions = ref(false);
const creatingCourseId = ref<number | null>(null);

// -----------------------------------------------------------------------------
// Detail
// -----------------------------------------------------------------------------

const detailOffering = ref<CourseOffering | null>(null);

const showDetail = ref(false);

// -----------------------------------------------------------------------------
// Archive / Restore
// -----------------------------------------------------------------------------

const showArchiveModal = ref(false);
const showRestoreModal = ref(false);

const selectedOffering = ref<CourseOffering | null>(null);

const archiving = ref(false);
const restoring = ref(false);

// -----------------------------------------------------------------------------
// Helpers
// -----------------------------------------------------------------------------

function statusVariant(status: OfferingStatus) {
  switch (status) {
    case 'approved':
      return 'success';

    case 'rejected':
      return 'danger';

    case 'cancelled':
      return 'neutral';

    default:
      return 'info';
  }
}

const emptyStateText = computed(() => {
  if (search.value.trim() || hasActiveFilters.value) {
    return 'No matching offerings found';
  }

  if (activeTab.value === 'archived') {
    return 'No archived offerings';
  }

  return 'No course offerings for this semester';
});

// -----------------------------------------------------------------------------
// Load offerings
// -----------------------------------------------------------------------------

async function load(page = 1) {
  if (!selectedSemesterId.value) {
    list.value = [];
    return;
  }

  loading.value = true;
  error.value = '';

  try {
    const fetcher = activeTab.value === 'active' ? listOfferings : getArchivedOfferings;

    const response = await fetcher(selectedSemesterId.value, page, 10, statusFilter.value ?? undefined);

    list.value = response.data;
    pagination.value = response.pagination;
  } catch (err: any) {
    error.value = err?.response?.data?.message || 'Failed to load course offerings.';
  } finally {
    loading.value = false;
  }
}

// -----------------------------------------------------------------------------
// Search
// -----------------------------------------------------------------------------

const filteredList = computed(() => {
  const query = search.value.trim().toLowerCase();

  if (!query) {
    return list.value;
  }

  return list.value.filter((offering) => {
    const searchableText = [offering.course?.code, offering.course?.name].filter(Boolean).join(' ').toLowerCase();

    return searchableText.includes(query);
  });
});

// -----------------------------------------------------------------------------
// Watchers
// -----------------------------------------------------------------------------

watch(statusFilter, () => {
  load(1);
});

watch(activeTab, () => {
  load(1);
});

// -----------------------------------------------------------------------------
// Semester
// -----------------------------------------------------------------------------

function onSemesterChange(value: string) {
  selectedSemesterId.value = Number(value);

  showSuggestions.value = false;
  suggestions.value = [];

  load(1);
}

// -----------------------------------------------------------------------------
// Tabs
// -----------------------------------------------------------------------------

function changeTab(tab: 'active' | 'archived') {
  activeTab.value = tab;
}

// -----------------------------------------------------------------------------
// Refresh
// -----------------------------------------------------------------------------

function retry() {
  load(pagination.value.current_page);
}

// -----------------------------------------------------------------------------
// Suggestions
// -----------------------------------------------------------------------------

async function openSuggestions() {
  if (!selectedSemesterId.value) {
    return;
  }

  showSuggestions.value = true;
  loadingSuggestions.value = true;
  suggestions.value = [];

  try {
    suggestions.value = await generateSuggestions(selectedSemesterId.value);
  } catch (err: any) {
    uiStore.showToast(err?.response?.data?.message || 'Failed to generate suggestions.', 'error');
  } finally {
    loadingSuggestions.value = false;
  }
}

async function createFromSuggestion(suggestion: OfferingSuggestion) {
  if (!selectedSemesterId.value) {
    return;
  }

  creatingCourseId.value = suggestion.course_id;

  try {
    await createOffering(suggestion.course_id, selectedSemesterId.value);

    /*
     * One course offering can serve multiple
     * programs. Therefore remove every suggestion
     * for this course after creating it.
     */
    suggestions.value = suggestions.value.filter((item) => item.course_id !== suggestion.course_id);

    uiStore.showToast(`${suggestion.course_code} offering created successfully.`, 'success');

    await load(1);
  } catch (err: any) {
    uiStore.showToast(err?.response?.data?.message || 'Failed to create course offering.', 'error');
  } finally {
    creatingCourseId.value = null;
  }
}

// -----------------------------------------------------------------------------
// Detail
// -----------------------------------------------------------------------------

async function openDetail(offering: CourseOffering) {
  try {
    detailOffering.value = await getOffering(offering.id);

    showDetail.value = true;
  } catch (err: any) {
    uiStore.showToast(err?.response?.data?.message || 'Failed to load course offering.', 'error');
  }
}

function onUpdated(updated: CourseOffering) {
  const index = list.value.findIndex((item) => item.id === updated.id);

  if (index !== -1) {
    list.value[index] = updated;
  }

  detailOffering.value = updated;
}

function onArchived(id: number) {
  list.value = list.value.filter((item) => item.id !== id);

  pagination.value.total = Math.max(0, pagination.value.total - 1);
}

// -----------------------------------------------------------------------------
// Archive
// -----------------------------------------------------------------------------

function openArchive(offering: CourseOffering) {
  selectedOffering.value = offering;
  showArchiveModal.value = true;
}

async function confirmArchive() {
  if (!selectedOffering.value) {
    return;
  }

  archiving.value = true;

  try {
    await archiveOffering(selectedOffering.value.id);

    uiStore.showToast('Course offering archived successfully.', 'success');

    showArchiveModal.value = false;
    selectedOffering.value = null;

    await load(Math.min(pagination.value.current_page, pagination.value.last_page));
  } catch (err: any) {
    uiStore.showToast(err?.response?.data?.message || 'Failed to archive course offering.', 'error');
  } finally {
    archiving.value = false;
  }
}

// -----------------------------------------------------------------------------
// Restore
// -----------------------------------------------------------------------------

function openRestore(offering: CourseOffering) {
  selectedOffering.value = offering;
  showRestoreModal.value = true;
}

async function confirmRestore() {
  if (!selectedOffering.value) {
    return;
  }

  restoring.value = true;

  try {
    await restoreOffering(selectedOffering.value.id);

    uiStore.showToast('Course offering restored successfully.', 'success');

    showRestoreModal.value = false;
    selectedOffering.value = null;

    await load(pagination.value.current_page);
  } catch (err: any) {
    uiStore.showToast(err?.response?.data?.message || 'Failed to restore course offering.', 'error');
  } finally {
    restoring.value = false;
  }
}

// -----------------------------------------------------------------------------
// Row actions
// -----------------------------------------------------------------------------

function handleRowAction(key: string, offering: CourseOffering) {
  if (key === 'view') {
    openDetail(offering);
  }
}

// -----------------------------------------------------------------------------
// Outside click for columns menu
// -----------------------------------------------------------------------------

function handleDocumentClick(event: MouseEvent) {
  const target = event.target as HTMLElement;

  if (!target.closest('[data-columns-container]')) {
    showColumns.value = false;
  }
}

// -----------------------------------------------------------------------------
// Lifecycle
// -----------------------------------------------------------------------------

onMounted(async () => {
  document.addEventListener('click', handleDocumentClick);

  try {
    const response = await getSemesters(1, 100);

    semesters.value = response.data;

    selectedSemesterId.value = semesters.value.find((semester) => semester.status === 'active')?.id ?? semesters.value[0]?.id ?? null;

    if (selectedSemesterId.value) {
      await load(1);
    }
  } catch (err: any) {
    error.value = err?.response?.data?.message || 'Failed to load semesters.';
  }
});

onBeforeUnmount(() => {
  document.removeEventListener('click', handleDocumentClick);
});
</script>

<template>
  <div class="mx-auto w-full max-w-360 space-y-6 px-6 py-6">
    <!-- Toolbar -->
    <div data-columns-container class="relative">
      <ResourceToolbar
        title="Course offerings"
        description="Review and manage course offerings for the semester."
        search-placeholder="Search by course code or name..."
        :show-search="true"
        :show-filter="true"
        :show-refresh="true"
        :show-columns="true"
        :show-fullscreen="true"
        :show-tabs="true"
        :active-tab="activeTab"
        :active-count="activeTab === 'active' ? pagination.total : undefined"
        :archived-count="activeTab === 'archived' ? pagination.total : undefined"
        :has-active-filters="hasActiveFilters"
        :refreshing="loading"
        @clear-filters="clearFilters"
        @change-tab="changeTab"
        @update:search="search = $event"
        @refresh="retry"
        @columns="showColumns = !showColumns">
        <template #actions>
          <BaseSelect :model-value="String(selectedSemesterId ?? '')" :options="semesterOptions" placeholder="Select semester" @update:model-value="onSemesterChange" />

          <BaseButton
            v-if="activeTab === 'active'"
            v-can="'course_offering.create'"
            :disabled="!selectedSemesterId || loadingSuggestions"
            :loading="loadingSuggestions"
            @click="openSuggestions">
            <template #icon>
              <Sparkles class="h-4 w-4" />
            </template>

            Generate suggestions
          </BaseButton>
        </template>

        <template #filters>
          <BaseSelect v-model="statusFilter" label="Status" :options="statusFilterOptions" />
        </template>
      </ResourceToolbar>

      <!-- Column selector -->
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
      </div>
    </div>

    <!-- Suggestions -->
    <SuggestionsPanel
      v-if="showSuggestions && activeTab === 'active'"
      :suggestions="suggestions"
      :creating-key="creatingCourseId ? String(creatingCourseId) : null"
      @create="createFromSuggestion"
      @close="showSuggestions = false" />

    <!-- Error -->
    <div v-if="error" class="flex items-start gap-3 rounded-md border border-error/30 bg-error/5 p-4">
      <p class="flex-1 text-sm font-medium text-error">
        {{ error }}
      </p>

      <button type="button" class="text-sm font-medium text-error hover:underline" @click="retry">Retry</button>
    </div>

    <!-- Table -->
    <div v-can="'course_offering.view'" class="overflow-hidden rounded-md border border-border bg-surface">
      <div class="overflow-x-auto">
        <table class="w-full min-w-190 border-collapse">
          <thead>
            <tr class="border-b border-border bg-text/2.5">
              <th v-if="isColumnVisible('course')" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Course</th>

              <th v-if="isColumnVisible('status')" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Status</th>

              <th v-if="isColumnVisible('sections')" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Sections</th>

              <th v-if="isColumnVisible('instructors')" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Instructors</th>

              <th v-if="isColumnVisible('semester')" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Semester</th>

              <th v-if="isColumnVisible('updated_at')" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Updated</th>

              <th class="w-20 px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-text/50">Actions</th>
            </tr>
          </thead>

          <!-- Loading -->
          <tbody v-if="loading" class="divide-y divide-border">
            <tr v-for="row in 6" :key="row">
              <td v-for="column in visibleColumns" :key="column" class="px-4 py-4">
                <div class="h-3.5 animate-pulse rounded bg-text/5" :class="column === 'course' ? 'w-44' : 'w-20'" />
              </td>

              <td class="px-4 py-4">
                <div class="ml-auto h-8 w-8 animate-pulse rounded-md bg-text/5" />
              </td>
            </tr>
          </tbody>

          <!-- Data -->
          <tbody v-else-if="filteredList.length" class="divide-y divide-border">
            <tr v-for="offering in filteredList" :key="offering.id" class="group cursor-pointer transition-colors hover:bg-text/2" @click="openDetail(offering)">
              <!-- Course -->
              <td v-if="isColumnVisible('course')" class="px-4 py-3.5">
                <div class="flex items-center gap-3">
                  <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md border border-border bg-bg text-text/50">
                    <GraduationCap class="h-4 w-4" />
                  </div>

                  <div class="min-w-0">
                    <p class="truncate text-sm font-medium text-text">
                      {{ offering.course?.name }}
                    </p>

                    <p class="font-mono text-xs text-text/50">
                      {{ offering.course?.code }}
                    </p>
                  </div>
                </div>
              </td>

              <!-- Status -->
              <td v-if="isColumnVisible('status')" class="px-4 py-3.5">
                <BaseBadge :variant="statusVariant(offering.status)">
                  {{ offering.status }}
                </BaseBadge>
              </td>

              <!-- Sections -->
              <td v-if="isColumnVisible('sections')" class="px-4 py-3.5">
                <div class="flex flex-col">
                  <span class="text-sm text-text/70">
                    {{ offering.sections?.length ?? 0 }}
                  </span>

                  <span class="text-[11px] text-text/40">
                    {{ (offering.sections?.length ?? 0) === 1 ? 'section' : 'sections' }}
                  </span>
                </div>
              </td>

              <!-- Instructor assignments -->
              <td v-if="isColumnVisible('instructors')" class="px-4 py-3.5">
                <div class="flex flex-col">
                  <span class="text-sm text-text/70">
                    {{ offering.instructor_assignments?.length ?? 0 }}
                  </span>

                  <span class="text-[11px] text-text/40">
                    {{ (offering.instructor_assignments?.length ?? 0) === 1 ? 'assignment' : 'assignments' }}
                  </span>
                </div>
              </td>

              <!-- Semester -->
              <td v-if="isColumnVisible('semester')" class="px-4 py-3.5">
                <span class="text-sm text-text/60">
                  {{ offering.semester?.name }}
                  ·
                  {{ offering.semester?.academic_year }}
                </span>
              </td>

              <!-- Updated -->
              <td v-if="isColumnVisible('updated_at')" class="px-4 py-3.5">
                <span class="font-mono text-xs tabular-nums text-text/60">
                  {{ offering.updated_at || '—' }}
                </span>
              </td>

              <!-- Actions -->
              <td class="px-4 py-3.5 text-right" @click.stop>
                <TableRowActions
                  :active-tab="activeTab"
                  archive-permission="course_offering.archive"
                  restore-permission="course_offering.restore"
                  :extra-actions="[
                    {
                      key: 'view',
                      label: 'View',
                      icon: Eye,
                      showOn: 'both',
                    },
                  ]"
                  @archive="openArchive(offering)"
                  @restore="openRestore(offering)"
                  @action="(key) => handleRowAction(key, offering)" />
              </td>
            </tr>
          </tbody>

          <!-- Empty -->
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

                  <BaseButton
                    v-if="!search && !hasActiveFilters && activeTab === 'active'"
                    v-can="'course_offering.create'"
                    variant="secondary"
                    class="mt-4"
                    @click="openSuggestions">
                    <template #icon>
                      <Sparkles class="h-4 w-4" />
                    </template>

                    Find required offerings
                  </BaseButton>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <AppPagination :pagination="pagination" @change-page="load" />
    </div>
  </div>

  <!-- Detail -->
  <OfferingDetailModal v-model="showDetail" :offering="detailOffering" @updated="onUpdated" @archived="onArchived" />

  <!-- Archive -->
  <ConfirmModal
    :show="showArchiveModal"
    title="Archive this offering?"
    :description="`This will remove the ${selectedOffering?.course?.code ?? 'course'} offering from the active list.`"
    confirm-text="Archive offering"
    variant="danger"
    :icon="ArchiveIcon"
    :loading="archiving"
    @close="showArchiveModal = false"
    @confirm="confirmArchive" />

  <!-- Restore -->
  <ConfirmModal
    :show="showRestoreModal"
    title="Restore this offering?"
    :description="`This will return the ${selectedOffering?.course?.code ?? 'course'} offering to the active list.`"
    confirm-text="Restore offering"
    variant="accent"
    :icon="RotateCcw"
    :loading="restoring"
    @close="showRestoreModal = false"
    @confirm="confirmRestore" />
</template>
