<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue';
import { useRouter } from 'vue-router';
import {
  ClipboardCheck,
  Sparkles,
  Send,
  Users,
  Eye,
} from 'lucide-vue-next';

import ResourceToolbar from '@/shared/components/ResourceToolbar.vue';
import TableRowActions from '@/shared/components/TableRowActions.vue';
import BaseBadge from '@/shared/components/ui/BaseBadge.vue';
import BaseSelect from '@/shared/components/ui/BaseSelect.vue';
import AppPagination from '@/shared/components/AppPagination.vue';
import ConfirmModal from '@/shared/components/ConfirmModal.vue';
import { useUiStore } from '@/stores/ui';
import { handleApiError } from '@/shared/utils/apiError';

import { getTeaching } from '@/modules/instructor/teaching/api/teaching';
import { listExams } from '@/modules/instructor/exams/api/exams';
import { autoGradeExam, submitVerification } from '../api/grading';
import type { Exam } from '@/modules/instructor/exams/types/exam';
import type { Pagination } from '@/shared/composables/useCrudResource';

type GradingColumn =
  | 'title'
  | 'type'
  | 'questions'
  | 'marks'
  | 'duration'
  | 'grading_status'
  | 'exam_status'
  | 'scheduled_start'
  | 'created_at';

interface ColumnConfig {
  key: GradingColumn;
  label: string;
  required: boolean;
}

const router = useRouter();
const uiStore = useUiStore();

const loading = ref(false);
const refreshing = ref(false);
const autoGradingId = ref<number | null>(null);
const submittingVerifyId = ref<number | null>(null);

const teachings = ref<any[]>([]);
const selectedTeachingId = ref<string>('');
const exams = ref<Exam[]>([]);

const search = ref('');
const filterStatus = ref('');
const filterType = ref('');

const showVerifyModal = ref(false);
const examToVerify = ref<Exam | null>(null);

const pagination = ref<Pagination>({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
  from: null,
  to: null,
});

// Column Picker Configurations
const columns: ColumnConfig[] = [
  { key: 'title', label: 'Exam Title', required: true },
  { key: 'type', label: 'Type', required: false },
  { key: 'questions', label: 'Questions', required: false },
  { key: 'marks', label: 'Total Marks', required: false },
  { key: 'grading_status', label: 'Grading Status', required: true },
  { key: 'duration', label: 'Duration', required: false },
  { key: 'exam_status', label: 'Exam Status', required: false },
  { key: 'scheduled_start', label: 'Scheduled Start', required: false },
  { key: 'created_at', label: 'Created Date', required: false },
];

const showColumns = ref(false);
// Default visible columns: compact set so shrinking doesn't break
const visibleColumns = ref<GradingColumn[]>([
  'title',
  'type',
  'questions',
  'marks',
  'grading_status',
]);

const isColumnVisible = (column: GradingColumn) => visibleColumns.value.includes(column);

const toggleColumn = (column: GradingColumn) => {
  const config = columns.find((item) => item.key === column);
  if (config?.required) return;

  if (isColumnVisible(column)) {
    visibleColumns.value = visibleColumns.value.filter((item) => item !== column);
  } else {
    visibleColumns.value = [...visibleColumns.value, column];
  }
};

const resetColumns = () => {
  visibleColumns.value = ['title', 'type', 'questions', 'marks', 'grading_status'];
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
  { value: '', label: 'All Grading Statuses' },
  { value: 'needs_grading', label: 'Needs Grading / In Progress' },
  { value: 'completed', label: 'Graded (Completed)' },
  { value: 'not_started', label: 'Not Graded' },
];

const typeOptions = [
  { value: '', label: 'All Types' },
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

const selectedTeaching = computed(() =>
  teachings.value.find((t) => String(t.id) === String(selectedTeachingId.value)),
);

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

    if (filterStatus.value) {
      if (filterStatus.value === 'completed' && exam.grading_status !== 'completed') return false;
      if (filterStatus.value === 'needs_grading' && exam.grading_status === 'completed') return false;
      if (filterStatus.value === 'not_started' && exam.grading_status && exam.grading_status !== 'not_started') return false;
    }

    return true;
  });
});

function formatDate(value: string | null | undefined): string {
  if (!value) return '—';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  return new Intl.DateTimeFormat('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  }).format(date);
}

async function loadTeaching() {
  try {
    const res = await getTeaching(1, 100);
    teachings.value = res.data || [];
    if (!selectedTeachingId.value && teachings.value.length) {
      selectedTeachingId.value = String(teachings.value[0].id);
    }
  } catch (err: any) {
    handleApiError(err, uiStore, undefined, 'Failed to load assigned courses.');
  }
}

async function loadExams(page = 1) {
  if (!selectedTeachingId.value) {
    exams.value = [];
    return;
  }

  loading.value = true;
  try {
    const res = await listExams(Number(selectedTeachingId.value), page, pagination.value.per_page);
    exams.value = res.data || [];
    if (res.pagination) {
      pagination.value = res.pagination;
    }
  } catch (err: any) {
    exams.value = [];
    handleApiError(err, uiStore, undefined, 'Failed to load exams.');
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
  search.value = '';
  filterStatus.value = '';
  filterType.value = '';
}

async function handleAutoGrade(exam: Exam) {
  autoGradingId.value = exam.id;
  try {
    await autoGradeExam(exam.id);
    uiStore.showToast(`Auto-grading initiated for "${exam.title}".`, 'success');
    exam.grading_status = 'in_progress';
    await loadExams(pagination.value.current_page);
  } catch (err: any) {
    handleApiError(err, uiStore, undefined, 'Failed to trigger auto-grading.');
  } finally {
    autoGradingId.value = null;
  }
}

function openVerifyModal(exam: Exam) {
  examToVerify.value = exam;
  showVerifyModal.value = true;
}

async function handleConfirmSubmitVerification() {
  if (!examToVerify.value || !selectedTeachingId.value) return;
  submittingVerifyId.value = examToVerify.value.id;
  try {
    await submitVerification(Number(selectedTeachingId.value), examToVerify.value.id);
    uiStore.showToast('Grades submitted for department verification successfully.', 'success');
    showVerifyModal.value = false;
    await loadExams(pagination.value.current_page);
  } catch (err: any) {
    handleApiError(err, uiStore, undefined, 'Failed to submit grades for verification.');
  } finally {
    submittingVerifyId.value = null;
  }
}

function navigateToSubmissions(exam: Exam) {
  router.push({
    name: 'instructor.grading.submissions',
    params: { examId: exam.id },
  });
}

function openExamDetail(exam: Exam) {
  router.push({
    name: 'instructor.exams.detail',
    params: { examId: exam.id },
  });
}

function getRowExtraActions(exam: Exam) {
  const actions: any[] = [
    {
      key: 'submissions',
      label: 'View submissions',
      icon: Users,
    },
    {
      key: 'view_exam',
      label: 'View exam details',
      icon: Eye,
    },
    {
      key: 'autograde',
      label: 'Run auto-grade',
      icon: Sparkles,
      permission: 'grade.autograde',
    },
  ];

  if (exam.status === 'completed') {
    actions.push({
      key: 'verify',
      label: 'Submit verification',
      icon: Send,
      permission: 'grade.submit',
    });
  }

  return actions;
}

function handleRowAction(key: string, exam: Exam) {
  if (key === 'submissions') {
    navigateToSubmissions(exam);
  } else if (key === 'view_exam') {
    openExamDetail(exam);
  } else if (key === 'autograde') {
    handleAutoGrade(exam);
  } else if (key === 'verify') {
    openVerifyModal(exam);
  }
}

watch(selectedTeachingId, () => {
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
  <div class="mx-auto min-h-[calc(100vh-68px)] w-full max-w-360 space-y-6 px-6 py-6">
    <div data-columns-container class="relative">
      <ResourceToolbar
        title="Grading"
        :description="
          selectedTeaching
            ? `Evaluate assessments for ${selectedTeaching.course?.code} — ${selectedTeaching.course?.name}`
            : 'Evaluate student submissions, run auto-grading, and submit verified grades.'
        "
        search-placeholder="Search exams by title or type…"
        :search="search"
        :show-search="true"
        :show-filter="true"
        :show-refresh="true"
        :show-columns="true"
        :refreshing="refreshing"
        :has-active-filters="hasActiveFilters"
        :filter-count="filterCount"
        @update:search="(val) => (search = val)"
        @refresh="handleRefresh"
        @clear-filters="clearFilters"
        @columns="showColumns = !showColumns"
      >
        <template #filters>
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <BaseSelect
              v-model="selectedTeachingId"
              label="Course Offering"
              :options="teachingOptions"
              placeholder="Select course offering"
            />

            <BaseSelect
              v-model="filterType"
              label="Exam Type"
              :options="typeOptions"
              placeholder="All types"
            />

            <BaseSelect
              v-model="filterStatus"
              label="Grading Status"
              :options="statusOptions"
              placeholder="All statuses"
            />
          </div>
        </template>
      </ResourceToolbar>

      <!-- Customize Columns Dropdown -->
      <div
        v-if="showColumns"
        class="absolute right-0 top-full z-40 mt-2 w-64 rounded-lg border border-border bg-surface p-2 shadow-xl"
        @click.stop
      >
        <div class="flex items-center justify-between px-2 py-2">
          <div>
            <p class="text-sm font-semibold text-text">Columns</p>
            <p class="mt-0.5 text-xs text-text/45">Choose what appears in the table</p>
          </div>

          <button
            type="button"
            class="rounded-md px-2 py-1 text-xs font-medium text-text/50 transition-colors hover:bg-text/5 hover:text-accent cursor-pointer"
            @click="resetColumns"
          >
            Reset
          </button>
        </div>

        <div class="my-1 border-t border-border" />

        <div class="space-y-0.5">
          <button
            v-for="column in columns"
            :key="column.key"
            type="button"
            class="flex w-full items-center gap-3 rounded-md px-2 py-2 text-left transition-colors hover:bg-text/5 cursor-pointer"
            @click="toggleColumn(column.key)"
          >
            <span
              class="flex h-4 w-4 shrink-0 items-center justify-center rounded border transition-colors"
              :class="isColumnVisible(column.key) ? 'border-accent bg-accent text-white' : 'border-border bg-surface'"
            >
              <svg v-if="isColumnVisible(column.key)" viewBox="0 0 12 12" class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M2 6l2.5 2.5L10 3" />
              </svg>
            </span>

            <span class="flex-1 text-sm text-text/80">
              {{ column.label }}
            </span>

            <span v-if="column.required" class="text-[10px] font-medium text-text/35">
              Always
            </span>
          </button>
        </div>

        <div class="mt-1 border-t border-border px-2 pt-2">
          <p class="text-[11px] text-text/40">{{ visibleColumnCount }} columns visible</p>
        </div>
      </div>
    </div>

    <!-- Table Container -->
    <div class="overflow-hidden rounded-xl border border-border bg-surface shadow-sm">
      <div class="overflow-x-auto">
        <table class="w-full min-w-180 border-collapse">
          <thead>
            <tr class="border-b border-border bg-bg/40">
              <th v-if="isColumnVisible('title')" class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-text/45">Exam</th>
              <th v-if="isColumnVisible('type')" class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-text/45">Type</th>
              <th v-if="isColumnVisible('questions')" class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-text/45">Questions</th>
              <th v-if="isColumnVisible('marks')" class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-text/45">Total Marks</th>
              <th v-if="isColumnVisible('grading_status')" class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-text/45">Grading Status</th>
              <th v-if="isColumnVisible('duration')" class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-text/45">Duration</th>
              <th v-if="isColumnVisible('exam_status')" class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-text/45">Exam Status</th>
              <th v-if="isColumnVisible('scheduled_start')" class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-text/45">Scheduled Start</th>
              <th v-if="isColumnVisible('created_at')" class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-text/45">Created</th>
              <th class="w-16 px-6 py-3.5 text-right text-xs font-bold uppercase tracking-wide text-text/45">Actions</th>
            </tr>
          </thead>

          <!-- Skeleton Loading -->
          <tbody v-if="loading" class="divide-y divide-border">
            <tr v-for="i in 5" :key="i">
              <td :colspan="totalTableColumns" class="px-6 py-5">
                <div class="space-y-2">
                  <div class="h-4 w-56 animate-pulse rounded bg-bg" />
                  <div class="h-3 w-28 animate-pulse rounded bg-bg" />
                </div>
              </td>
            </tr>
          </tbody>

          <!-- Exam Rows -->
          <tbody v-else-if="filteredExams.length" class="divide-y divide-border">
            <tr
              v-for="exam in filteredExams"
              :key="exam.id"
              class="group cursor-pointer transition-colors hover:bg-text/[0.02]"
              @click="navigateToSubmissions(exam)"
            >
              <!-- Exam Title & Info -->
              <td v-if="isColumnVisible('title')" class="px-6 py-4">
                <p class="font-medium text-text text-sm group-hover:text-accent transition-colors">
                  {{ exam.title }}
                </p>
                <p class="mt-0.5 font-mono text-xs text-text/45">
                  ID: #{{ exam.id }}
                </p>
              </td>

              <!-- Type -->
              <td v-if="isColumnVisible('type')" class="px-6 py-4">
                <span class="inline-flex rounded-md bg-bg border border-border px-2.5 py-1 font-mono text-xs font-medium uppercase text-text/70">
                  {{ exam.type }}
                </span>
              </td>

              <!-- Questions -->
              <td v-if="isColumnVisible('questions')" class="px-6 py-4 font-mono text-sm tabular-nums text-text/80 whitespace-nowrap">
                {{ exam.total_questions || 0 }}
              </td>

              <!-- Total Marks -->
              <td v-if="isColumnVisible('marks')" class="px-6 py-4 font-mono text-sm tabular-nums text-text/80 whitespace-nowrap">
                {{ exam.total_marks ?? '—' }} pts
              </td>

              <!-- Grading Status -->
              <td v-if="isColumnVisible('grading_status')" class="px-6 py-4 whitespace-nowrap">
                <BaseBadge
                  v-if="exam.grading_status === 'completed'"
                  variant="success"
                >
                  Graded
                </BaseBadge>
                <BaseBadge
                  v-else-if="exam.grading_status === 'in_progress'"
                  variant="warning"
                >
                  In Progress
                </BaseBadge>
                <BaseBadge
                  v-else
                  variant="neutral"
                >
                  Not Graded
                </BaseBadge>
              </td>

              <!-- Duration (Optional Column) -->
              <td v-if="isColumnVisible('duration')" class="px-6 py-4 text-sm text-text/70 whitespace-nowrap">
                {{ exam.duration_minutes }} min
              </td>

              <!-- Exam Status (Optional Column) -->
              <td v-if="isColumnVisible('exam_status')" class="px-6 py-4 whitespace-nowrap">
                <BaseBadge variant="neutral">
                  {{ exam.status }}
                </BaseBadge>
              </td>

              <!-- Scheduled Start (Optional Column, formatted Month Day, Year) -->
              <td v-if="isColumnVisible('scheduled_start')" class="px-6 py-4 font-mono text-xs text-text/70 whitespace-nowrap">
                {{ formatDate(exam.scheduled_start) }}
              </td>

              <!-- Created At (Optional Column, formatted Month Day, Year) -->
              <td v-if="isColumnVisible('created_at')" class="px-6 py-4 font-mono text-xs text-text/50 whitespace-nowrap">
                {{ formatDate(exam.created_at) }}
              </td>

              <!-- Actions -->
              <td class="w-16 px-6 py-4 text-right" @click.stop>
                <TableRowActions
                  active-tab="active"
                  :extra-actions="getRowExtraActions(exam)"
                  @action="handleRowAction($event, exam)"
                />
              </td>
            </tr>
          </tbody>

          <!-- Empty State -->
          <tbody v-else>
            <tr>
              <td :colspan="totalTableColumns" class="px-6 py-16 text-center">
                <div class="mx-auto flex max-w-sm flex-col items-center">
                  <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-bg">
                    <ClipboardCheck class="h-6 w-6 text-text/30" />
                  </div>
                  <p class="text-sm font-medium text-text">No exams found</p>
                  <p class="mt-1 text-sm text-text/45">
                    There are no exams available for grading under the selected course offering.
                  </p>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <AppPagination :pagination="pagination" @change-page="loadExams" />
    </div>

    <!-- Confirm Submit Verification Modal -->
    <ConfirmModal
      :show="showVerifyModal"
      title="Submit Grades for Verification"
      :description="`Are you sure you want to submit grades for '${examToVerify?.title}' for departmental review and verification?`"
      confirm-text="Submit for Review"
      variant="accent"
      :icon="Send"
      :loading="submittingVerifyId !== null"
      @confirm="handleConfirmSubmitVerification"
      @close="showVerifyModal = false"
    />
  </div>
</template>
