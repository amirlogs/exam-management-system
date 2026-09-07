<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Archive, CalendarDays, Eye, FileQuestion, Plus } from 'lucide-vue-next';
import { useRoute, useRouter } from 'vue-router';

import ResourceToolbar from '@/shared/components/ResourceToolbar.vue';
import TableRowActions from '@/shared/components/TableRowActions.vue';
import ConfirmModal from '@/shared/components/ConfirmModal.vue';
import BaseButton from '@/shared/components/ui/BaseButton.vue';
import BaseSelect from '@/shared/components/ui/BaseSelect.vue';
import AppPagination from '@/shared/components/AppPagination.vue';
import ExamStatusBadge from '../components/ExamStatusBadge.vue';

import { archiveExam, listExams } from '../api/exams';
import { getTeaching } from '@/modules/instructor/teaching/api/teaching';
import { useUiStore } from '@/stores/ui';
import { handleApiError } from '@/shared/utils/apiError';

import type { Exam } from '../types/exam';
import type { Pagination } from '@/shared/composables/useCrudResource';

type ExamColumn = 'title' | 'course' | 'type' | 'questions' | 'marks' | 'duration' | 'status' | 'created_at';

const router = useRouter();
const route = useRoute();
const uiStore = useUiStore();

const loading = ref(false);
const refreshing = ref(false);
const actionLoading = ref(false);

const teachings = ref<any[]>([]);
const exams = ref<Exam[]>([]);

const selectedTeachingId = ref<string | number | null>(null);
const search = ref('');
const filterStatus = ref('');
const filterType = ref('');

const showArchiveModal = ref(false);
const selectedExamForArchive = ref<Exam | null>(null);

const pagination = ref<Pagination>({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
  from: null,
  to: null,
});

const columns = [
  { key: 'title' as ExamColumn, label: 'Exam Title', required: true },
  { key: 'course' as ExamColumn, label: 'Course', required: false },
  { key: 'type' as ExamColumn, label: 'Type', required: false },
  { key: 'questions' as ExamColumn, label: 'Questions', required: false },
  { key: 'marks' as ExamColumn, label: 'Total Marks', required: false },
  { key: 'duration' as ExamColumn, label: 'Duration', required: false },
  { key: 'status' as ExamColumn, label: 'Status', required: false },
  { key: 'created_at' as ExamColumn, label: 'Created', required: false },
];

const showColumns = ref(false);
const visibleColumns = ref<ExamColumn[]>(['title', 'type', 'questions', 'marks', 'duration', 'status']);

const isColumnVisible = (column: ExamColumn) => visibleColumns.value.includes(column);

const toggleColumn = (column: ExamColumn) => {
  const config = columns.find((item) => item.key === column);
  if (config?.required) return;

  if (isColumnVisible(column)) {
    visibleColumns.value = visibleColumns.value.filter((item) => item !== column);
  } else {
    visibleColumns.value = [...visibleColumns.value, column];
  }
};

const resetColumns = () => {
  visibleColumns.value = ['title', 'type', 'questions', 'marks', 'duration', 'status'];
};

const visibleColumnCount = computed(() => visibleColumns.value.length);
const totalTableColumns = computed(() => visibleColumnCount.value + 1);

function handleDocumentClick(event: MouseEvent) {
  const target = event.target as HTMLElement;
  if (!target.closest('[data-columns-container]')) {
    showColumns.value = false;
  }
}

const statusOptions = [
  { value: '', label: 'All statuses' },
  { value: 'draft', label: 'Draft' },
  { value: 'pending_approval', label: 'Pending approval' },
  { value: 'approved', label: 'Approved' },
  { value: 'rejected', label: 'Rejected' },
  { value: 'scheduled', label: 'Scheduled' },
  { value: 'active', label: 'Active' },
  { value: 'completed', label: 'Completed' },
  { value: 'cancelled', label: 'Cancelled' },
];

const typeOptions = [
  { value: '', label: 'All types' },
  { value: 'MIDTERM', label: 'Midterm' },
  { value: 'FINAL', label: 'Final' },
];

const teachingOptions = computed(() =>
  teachings.value
    .filter((item) => item?.id && item?.course)
    .map((item) => ({
      value: String(item.id),
      label: `${item.course.code} — ${item.course.name} (${item.semester?.name || 'Current'})`,
    })),
);

const selectedTeaching = computed(() => teachings.value.find((teaching) => Number(teaching.id) === Number(selectedTeachingId.value)));

const hasActiveFilters = computed(() => Boolean(filterStatus.value || filterType.value));

const filterCount = computed(() => {
  let count = 0;
  if (filterStatus.value) count++;
  if (filterType.value) count++;
  return count;
});

const filteredExams = computed(() => {
  const query = search.value.trim().toLowerCase();

  return exams.value.filter((exam) => {
    if (query && !exam.title?.toLowerCase().includes(query) && !exam.type?.toLowerCase().includes(query)) {
      return false;
    }

    if (filterType.value && exam.type !== filterType.value) {
      return false;
    }

    if (filterStatus.value && exam.status !== filterStatus.value) {
      return false;
    }

    return true;
  });
});

async function loadTeaching() {
  try {
    const response = await getTeaching(1, 1000);
    teachings.value = response.data ?? [];

    const queryId = route.query.courseOfferingId ? Number(route.query.courseOfferingId) : null;
    if (queryId && teachings.value.some((t) => Number(t.id) === queryId)) {
      selectedTeachingId.value = String(queryId);
    } else if (!selectedTeachingId.value && teachings.value.length) {
      selectedTeachingId.value = String(teachings.value[0].id);
    }
  } catch (err) {
    handleApiError(err, uiStore, undefined, 'Unable to load your teaching assignments.');
  }
}

async function loadExams(page = 1) {
  if (!selectedTeachingId.value) {
    exams.value = [];
    return;
  }

  loading.value = true;

  try {
    const filters: Record<string, string> = {};
    if (filterStatus.value) filters.status = filterStatus.value;
    if (filterType.value) filters.type = filterType.value;

    const response = await listExams(Number(selectedTeachingId.value), page, pagination.value.per_page, filters);

    exams.value = response.data ?? [];
    if (response.pagination) {
      pagination.value = response.pagination;
    }
  } catch (err) {
    exams.value = [];
    handleApiError(err, uiStore, undefined, 'Unable to load exams for the selected course.');
  } finally {
    loading.value = false;
  }
}

async function handleRefresh() {
  refreshing.value = true;
  try {
    await loadTeaching();
    await loadExams(pagination.value.current_page);
  } finally {
    refreshing.value = false;
  }
}

function clearFilters() {
  filterStatus.value = '';
  filterType.value = '';
  search.value = '';
  loadExams(1);
}

function openExam(exam: Exam) {
  router.push({
    name: 'instructor.exams.detail',
    params: {
      examId: exam.id,
    },
  });
}

function openQuestions(exam: Exam) {
  router.push({
    name: 'instructor.exams.questions',
    params: {
      examId: exam.id,
    },
  });
}

function createExam() {
  if (!selectedTeachingId.value) {
    return;
  }

  router.push({
    name: 'instructor.exams.create',
    query: {
      courseOfferingId: String(selectedTeachingId.value),
    },
  });
}

function getRowExtraActions(exam: Exam) {
  return [
    {
      key: 'view',
      label: 'View details',
      icon: Eye,
    },
    {
      key: 'questions',
      label: 'Manage questions',
      icon: FileQuestion,
    },
  ];
}

function handleRowAction(key: string, exam: Exam) {
  if (key === 'view') {
    openExam(exam);
  } else if (key === 'questions') {
    openQuestions(exam);
  }
}

function openArchive(exam: Exam) {
  selectedExamForArchive.value = exam;
  showArchiveModal.value = true;
}

async function confirmArchive() {
  if (!selectedExamForArchive.value) return;

  actionLoading.value = true;
  try {
    await archiveExam(selectedExamForArchive.value.id);
    uiStore.showToast('Exam archived successfully.', 'success');
    showArchiveModal.value = false;
    selectedExamForArchive.value = null;
    await loadExams(pagination.value.current_page);
  } catch (error) {
    handleApiError(error, uiStore, undefined, 'Failed to archive exam.');
  } finally {
    actionLoading.value = false;
  }
}

function formatDate(value: string | null | undefined) {
  if (!value) return '—';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  return new Intl.DateTimeFormat('en-US', {
    dateStyle: 'medium',
  }).format(date);
}

watch([filterStatus, filterType], () => {
  loadExams(1);
});

onMounted(async () => {
  document.addEventListener('click', handleDocumentClick);
  await loadTeaching();
  await loadExams(1);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', handleDocumentClick);
});
</script>

<template>
  <div class="mx-auto w-full max-w-360 space-y-6 px-6 py-6 min-h-[calc(100vh-68px)]">
    <div data-columns-container class="relative">
      <ResourceToolbar
        title="Exams"
        :description="
          selectedTeaching
            ? `Manage exams for ${selectedTeaching.course?.code} — ${selectedTeaching.course?.name}`
            : 'Create, schedule, and manage exams for your assigned course offerings.'
        "
        search-placeholder="Search exams by title or type…"
        :search="search"
        :show-search="true"
        :show-filter="true"
        :show-refresh="true"
        :show-columns="true"
        :show-fullscreen="true"
        :refreshing="refreshing"
        :has-active-filters="hasActiveFilters"
        :filter-count="filterCount"
        @update:search="(val) => (search = val)"
        @clear-filters="clearFilters"
        @refresh="handleRefresh"
        @columns="showColumns = !showColumns">
        <template #actions>
          <BaseButton v-can="'exam.create'" :disabled="!selectedTeachingId" @click="createExam">
            <template #icon>
              <Plus class="h-4 w-4" />
            </template>
            Create exam
          </BaseButton>
        </template>

        <template #filters>
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <BaseSelect v-model="selectedTeachingId" label="Course Offering" :options="teachingOptions" placeholder="Select course offering" @update:model-value="loadExams(1)" />

            <BaseSelect v-model="filterType" label="Exam Type" :options="typeOptions" placeholder="All types" />

            <BaseSelect v-model="filterStatus" label="Exam Status" :options="statusOptions" placeholder="All statuses" />
          </div>
        </template>
      </ResourceToolbar>

      <!-- Customize Columns Dropdown -->
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
          <p class="text-[11px] text-text/40">{{ visibleColumnCount }} columns visible</p>
        </div>
      </div>
    </div>

    <!-- Exam Table -->
    <div class="overflow-hidden rounded-xl border border-border bg-surface shadow-sm">
      <div class="overflow-x-auto">
        <table class="w-full border-collapse">
          <thead>
            <tr class="border-b border-border bg-bg/40">
              <th v-if="isColumnVisible('title')" class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-text/45">Exam</th>

              <th v-if="isColumnVisible('course')" class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-text/45">Course</th>

              <th v-if="isColumnVisible('type')" class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-text/45">Type</th>

              <th v-if="isColumnVisible('questions')" class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-text/45">Questions</th>

              <th v-if="isColumnVisible('marks')" class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-text/45">Marks</th>

              <th v-if="isColumnVisible('duration')" class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-text/45">Duration</th>

              <th v-if="isColumnVisible('status')" class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-text/45">Status</th>

              <th v-if="isColumnVisible('created_at')" class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-text/45">Created</th>

              <th class="w-16 px-6 py-3.5 text-right text-xs font-bold uppercase tracking-wide text-text/45">Actions</th>
            </tr>
          </thead>

          <tbody v-if="loading" class="divide-y divide-border">
            <tr v-for="i in 5" :key="i">
              <td :colspan="totalTableColumns" class="px-6 py-4">
                <div class="h-4 w-full animate-pulse rounded bg-bg" />
              </td>
            </tr>
          </tbody>

          <tbody v-else-if="filteredExams.length" class="divide-y divide-border">
            <tr v-for="exam in filteredExams" :key="exam.id" class="group cursor-pointer transition-colors hover:bg-text/[0.02]" @click="openExam(exam)">
              <td v-if="isColumnVisible('title')" class="px-6 py-4">
                <p class="font-medium text-text text-sm group-hover:text-accent transition-colors">
                  {{ exam.title }}
                </p>
                <p class="mt-0.5 text-xs text-text/45">ID: #{{ exam.id }}</p>
              </td>

              <td v-if="isColumnVisible('course')" class="px-6 py-4">
                <span class="font-mono text-xs text-text/70">
                  {{ selectedTeaching?.course?.code || '—' }}
                </span>
              </td>

              <td v-if="isColumnVisible('type')" class="px-6 py-4">
                <span class="inline-flex rounded-md bg-bg border border-border px-2.5 py-1 font-mono text-xs font-medium uppercase text-text/70">
                  {{ exam.type }}
                </span>
              </td>

              <td v-if="isColumnVisible('questions')" class="px-6 py-4 font-mono text-sm tabular-nums text-text/80">
                {{ exam.total_questions }}
              </td>

              <td v-if="isColumnVisible('marks')" class="px-6 py-4 font-mono text-sm tabular-nums text-text/80">
                {{ exam.total_marks }}
              </td>

              <td v-if="isColumnVisible('duration')" class="px-6 py-4 text-sm text-text/70">{{ exam.duration_minutes }} min</td>

              <td v-if="isColumnVisible('status')" class="px-6 py-4">
                <ExamStatusBadge :status="exam.status" />
              </td>

              <td v-if="isColumnVisible('created_at')" class="px-6 py-4 text-xs text-text/50">
                {{ formatDate(exam.created_at) }}
              </td>

              <td class="px-6 py-4 text-right" @click.stop>
                <TableRowActions
                  :row="exam"
                  :extra-actions="getRowExtraActions(exam)"
                  :show-edit="false"
                  :show-archive="exam.status === 'draft' || exam.status === 'completed'"
                  archive-permission="exam.archive"
                  @action="handleRowAction($event, exam)"
                  @archive="openArchive(exam)" />
              </td>
            </tr>
          </tbody>

          <tbody v-else>
            <tr>
              <td :colspan="totalTableColumns" class="px-6 py-16 text-center">
                <div class="mx-auto flex max-w-sm flex-col items-center">
                  <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-bg">
                    <CalendarDays class="h-6 w-6 text-text/30" />
                  </div>
                  <p class="text-sm font-medium text-text">No exams found</p>
                  <p class="mt-1 text-sm text-text/45">
                    {{ hasActiveFilters || search ? 'Try adjusting your filters or search query.' : 'Create your first exam for this course offering to get started.' }}
                  </p>
                  <BaseButton v-if="!hasActiveFilters && !search && selectedTeachingId" v-can="'exam.create'" class="mt-4" @click="createExam">
                    <template #icon>
                      <Plus class="h-4 w-4" />
                    </template>
                    Create exam
                  </BaseButton>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <AppPagination :pagination="pagination" @change-page="loadExams" />
    </div>

    <!-- Confirm Archive Modal -->
    <ConfirmModal
      :show="showArchiveModal"
      title="Archive Exam"
      :description="`Are you sure you want to archive '${selectedExamForArchive?.title}'?`"
      confirm-text="Archive exam"
      variant="danger"
      :icon="Archive"
      :loading="actionLoading"
      @close="showArchiveModal = false"
      @confirm="confirmArchive" />
  </div>
</template>
