<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import {
  ArrowLeft,
  Users,
  CheckCircle2,
  Clock,
  Award,
  AlertCircle,
  FileEdit,
  Sparkles,
  ClipboardCheck,
  Eye,
} from 'lucide-vue-next';

import ResourceToolbar from '@/shared/components/ResourceToolbar.vue';
import BaseButton from '@/shared/components/ui/BaseButton.vue';
import BaseBadge from '@/shared/components/ui/BaseBadge.vue';
import BaseSelect from '@/shared/components/ui/BaseSelect.vue';
import AppPagination from '@/shared/components/AppPagination.vue';
import ManualGradingModal from '../components/ManualGradingModal.vue';
import { useUiStore } from '@/stores/ui';
import { handleApiError } from '@/shared/utils/apiError';

import { getExam } from '@/modules/instructor/exams/api/exams';
import { listSubmissions, autoGradeExam } from '../api/grading';
import type { Exam } from '@/modules/instructor/exams/types/exam';
import type { ExamAttemptSubmission } from '../types/grading';
import type { Pagination } from '@/shared/composables/useCrudResource';

type SubmissionColumn =
  | 'student'
  | 'student_number'
  | 'submitted_at'
  | 'status'
  | 'pending_count'
  | 'score'
  | 'started_at';

interface ColumnConfig {
  key: SubmissionColumn;
  label: string;
  required: boolean;
}

const route = useRoute();
const router = useRouter();
const uiStore = useUiStore();

const examId = computed(() => Number(route.params.examId));

const exam = ref<Exam | null>(null);
const submissions = ref<ExamAttemptSubmission[]>([]);
const loading = ref(false);
const refreshing = ref(false);
const autoGrading = ref(false);

const search = ref('');
const filterStatus = ref('');

const selectedSubmission = ref<ExamAttemptSubmission | null>(null);
const showGradingModal = ref(false);

const pagination = ref<Pagination>({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
  from: null,
  to: null,
});

const submissionColumns: ColumnConfig[] = [
  { key: 'student', label: 'Student', required: true },
  { key: 'student_number', label: 'Student ID', required: false },
  { key: 'submitted_at', label: 'Submitted At', required: false },
  { key: 'status', label: 'Status', required: false },
  { key: 'score', label: 'Score', required: true },
  { key: 'pending_count', label: 'Pending Review', required: false },
  { key: 'started_at', label: 'Started At', required: false },
];

const showColumns = ref(false);
const visibleColumns = ref<SubmissionColumn[]>([
  'student',
  'student_number',
  'submitted_at',
  'status',
  'score',
]);

const isColumnVisible = (col: SubmissionColumn) => visibleColumns.value.includes(col);

const toggleColumn = (col: SubmissionColumn) => {
  const cfg = submissionColumns.find((c) => c.key === col);
  if (cfg?.required) return;
  if (isColumnVisible(col)) {
    visibleColumns.value = visibleColumns.value.filter((c) => c !== col);
  } else {
    visibleColumns.value = [...visibleColumns.value, col];
  }
};

const resetColumns = () => {
  visibleColumns.value = ['student', 'student_number', 'submitted_at', 'status', 'score'];
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
  { value: '', label: 'All Attempt Statuses' },
  { value: 'graded', label: 'Graded' },
  { value: 'completed', label: 'Submitted / Completed' },
  { value: 'in_progress', label: 'In Progress' },
];

const hasActiveFilters = computed(() => Boolean(filterStatus.value));

function formatDateTime(value: string | null | undefined): string {
  if (!value) return '—';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  return new Intl.DateTimeFormat('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  }).format(date);
}

async function loadExamDetail() {
  if (!examId.value) return;
  try {
    const res = await getExam(examId.value);
    exam.value = res.data;
  } catch (err: any) {
    handleApiError(err, uiStore, undefined, 'Failed to load exam details.');
  }
}

async function loadSubmissions(page = 1) {
  if (!examId.value) return;
  loading.value = true;
  try {
    const filters: Record<string, string> = {};
    if (filterStatus.value) filters.status = filterStatus.value;
    if (search.value) filters.search = search.value;

    const res = await listSubmissions(examId.value, page, pagination.value.per_page, filters);
    submissions.value = res.data || [];
    if (res.pagination) {
      pagination.value = res.pagination;
    }
  } catch (err: any) {
    submissions.value = [];
    handleApiError(err, uiStore, undefined, 'Failed to load submissions.');
  } finally {
    loading.value = false;
  }
}

async function handleRefresh() {
  refreshing.value = true;
  try {
    await loadExamDetail();
    await loadSubmissions(pagination.value.current_page);
  } finally {
    refreshing.value = false;
  }
}

function clearFilters() {
  search.value = '';
  filterStatus.value = '';
  loadSubmissions(1);
}

async function handleTriggerAutoGrade() {
  if (!exam.value) return;
  autoGrading.value = true;
  try {
    await autoGradeExam(exam.value.id);
    uiStore.showToast('Auto-grading initiated. Evaluating objective responses.', 'success');
    await loadSubmissions(pagination.value.current_page);
  } catch (err: any) {
    handleApiError(err, uiStore, undefined, 'Failed to trigger auto-grading.');
  } finally {
    autoGrading.value = false;
  }
}

function openGradingModal(sub: ExamAttemptSubmission) {
  selectedSubmission.value = sub;
  showGradingModal.value = true;
}

function handleGraded() {
  loadSubmissions(pagination.value.current_page);
}

function goBack() {
  router.push({ name: 'instructor.grading.list' });
}

function openExamDetail() {
  if (!exam.value) return;
  router.push({
    name: 'instructor.exams.detail',
    params: { examId: exam.value.id },
  });
}

onMounted(async () => {
  document.addEventListener('click', handleDocumentClick);
  await Promise.all([loadExamDetail(), loadSubmissions(1)]);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', handleDocumentClick);
});
</script>

<template>
  <div class="mx-auto min-h-[calc(100vh-68px)] w-full max-w-360 space-y-6 px-6 py-6">
    <!-- Top Navigation -->
    <div class="flex items-center gap-3">
      <button
        type="button"
        class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-border bg-surface text-text/60 transition-colors hover:border-accent/40 hover:text-accent hover:bg-accent/5 cursor-pointer"
        title="Back to grading queue"
        @click="goBack"
      >
        <ArrowLeft class="h-4 w-4" />
      </button>

      <div>
        <h1 class="font-display text-xl font-bold text-text">Submissions & Grading</h1>
        <p class="text-xs text-text/45">Evaluate student examination attempts and review graded responses.</p>
      </div>
    </div>

    <!-- Exam Header Summary Card -->
    <div v-if="exam" class="rounded-xl border border-border bg-surface p-6 shadow-sm">
      <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex min-w-0 items-start gap-4">
          <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-accent/10">
            <ClipboardCheck class="h-6 w-6 text-accent" />
          </div>

          <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2.5">
              <h2 class="font-display text-2xl font-bold text-text">
                {{ exam.title }}
              </h2>
              <BaseBadge variant="neutral">
                {{ exam.type }}
              </BaseBadge>
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
            </div>

            <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs text-text/55">
              <span>Questions: <span class="font-medium text-text/80 font-mono">{{ exam.total_questions || 0 }}</span></span>
              <span>•</span>
              <span>Total Marks: <span class="font-medium text-text/80 font-mono">{{ exam.total_marks || 0 }} pts</span></span>
              <span>•</span>
              <span>Duration: <span class="font-medium text-text/80 font-mono">{{ exam.duration_minutes || 0 }} mins</span></span>
              <span>•</span>
              <span>Scheduled: <span class="font-medium text-text/80 font-mono">{{ exam.scheduled_start || '—' }}</span></span>
            </div>
          </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <BaseButton
            variant="secondary"
            @click="openExamDetail"
          >
            <template #icon>
              <Eye class="h-4 w-4 text-text/60" />
            </template>
            Exam Details
          </BaseButton>

          <BaseButton
            variant="primary"
            :loading="autoGrading"
            @click="handleTriggerAutoGrade"
          >
            <template #icon>
              <Sparkles class="h-4 w-4 text-white" />
            </template>
            Auto-grade all
          </BaseButton>
        </div>
      </div>
    </div>

    <!-- Submissions Filter Toolbar -->
    <div data-columns-container class="relative">
      <ResourceToolbar
        title="Submissions"
        description="Review student attempts and enter marks for open-ended questions."
        search-placeholder="Search by student name or student ID…"
        :search="search"
        :show-search="true"
        :show-filter="true"
        :show-refresh="true"
        :show-columns="true"
        :refreshing="refreshing"
        :has-active-filters="hasActiveFilters"
        :filter-count="filterStatus ? 1 : 0"
        @update:search="(val) => (search = val)"
        @refresh="handleRefresh"
        @clear-filters="clearFilters"
        @columns="showColumns = !showColumns"
      >
        <template #filters>
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <BaseSelect
              v-model="filterStatus"
              label="Attempt Status"
              :options="statusOptions"
              placeholder="All statuses"
              @update:model-value="loadSubmissions(1)"
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
            v-for="col in submissionColumns"
            :key="col.key"
            type="button"
            class="flex w-full items-center gap-3 rounded-md px-2 py-2 text-left transition-colors hover:bg-text/5 cursor-pointer"
            @click="toggleColumn(col.key)"
          >
            <span
              class="flex h-4 w-4 shrink-0 items-center justify-center rounded border transition-colors"
              :class="isColumnVisible(col.key) ? 'border-accent bg-accent text-white' : 'border-border bg-surface'"
            >
              <svg v-if="isColumnVisible(col.key)" viewBox="0 0 12 12" class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M2 6l2.5 2.5L10 3" />
              </svg>
            </span>

            <span class="flex-1 text-sm text-text/80">
              {{ col.label }}
            </span>

            <span v-if="col.required" class="text-[10px] font-medium text-text/35">
              Always
            </span>
          </button>
        </div>

        <div class="mt-1 border-t border-border px-2 pt-2">
          <p class="text-[11px] text-text/40">{{ visibleColumnCount }} columns visible</p>
        </div>
      </div>
    </div>

    <!-- Submissions Table Card -->
    <div class="overflow-hidden rounded-xl border border-border bg-surface shadow-sm">
      <div class="overflow-x-auto">
        <table class="w-full min-w-190 border-collapse">
          <thead>
            <tr class="border-b border-border bg-bg/40">
              <th v-if="isColumnVisible('student')" class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-text/45">Student</th>
              <th v-if="isColumnVisible('student_number')" class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-text/45">Student ID</th>
              <th v-if="isColumnVisible('submitted_at')" class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-text/45">Submitted At</th>
              <th v-if="isColumnVisible('started_at')" class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-text/45">Started At</th>
              <th v-if="isColumnVisible('status')" class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-text/45">Status</th>
              <th v-if="isColumnVisible('pending_count')" class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-text/45">Pending Review</th>
              <th v-if="isColumnVisible('score')" class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-text/45">Score</th>
              <th class="w-20 px-6 py-3.5 text-right text-xs font-bold uppercase tracking-wide text-text/45">Actions</th>
            </tr>
          </thead>

          <!-- Skeleton Loading -->
          <tbody v-if="loading" class="divide-y divide-border">
            <tr v-for="i in 5" :key="i">
              <td :colspan="totalTableColumns" class="px-6 py-5">
                <div class="space-y-2">
                  <div class="h-4 w-48 animate-pulse rounded bg-bg" />
                  <div class="h-3 w-28 animate-pulse rounded bg-bg" />
                </div>
              </td>
            </tr>
          </tbody>

          <!-- Submissions Rows -->
          <tbody v-else-if="submissions.length" class="divide-y divide-border">
            <tr
              v-for="sub in submissions"
              :key="sub.id"
              class="transition-colors hover:bg-bg/40 cursor-pointer"
              @click="openGradingModal(sub)"
            >
              <!-- Student -->
              <td v-if="isColumnVisible('student')" class="px-6 py-5">
                <div class="flex items-center gap-3">
                  <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-accent/10 font-bold text-accent text-xs font-mono">
                    {{ sub.student?.user?.first_name ? sub.student.user.first_name.charAt(0) : 'S' }}
                  </div>
                  <div class="min-w-0">
                    <p class="truncate text-sm font-medium text-text">
                      {{ sub.student?.user?.full_name || sub.student?.user?.first_name || 'Student' }}
                    </p>
                    <p class="mt-0.5 font-mono text-xs text-text/50 truncate">
                      {{ sub.student?.user?.email || '—' }}
                    </p>
                  </div>
                </div>
              </td>

              <!-- Student ID -->
              <td v-if="isColumnVisible('student_number')" class="px-6 py-5">
                <span class="font-mono text-xs font-semibold text-text whitespace-nowrap">
                  {{ sub.student?.student_number || '—' }}
                </span>
              </td>

              <!-- Submitted At (Month Day, Year H:i) -->
              <td v-if="isColumnVisible('submitted_at')" class="px-6 py-5 font-mono text-xs text-text/70 whitespace-nowrap">
                {{ formatDateTime(sub.submitted_at) }}
              </td>

              <!-- Started At (Month Day, Year H:i) -->
              <td v-if="isColumnVisible('started_at')" class="px-6 py-5 font-mono text-xs text-text/70 whitespace-nowrap">
                {{ formatDateTime(sub.started_at) }}
              </td>

              <!-- Status -->
              <td v-if="isColumnVisible('status')" class="px-6 py-5 whitespace-nowrap">
                <BaseBadge
                  v-if="sub.status === 'graded'"
                  variant="success"
                >
                  Graded
                </BaseBadge>
                <BaseBadge
                  v-else-if="sub.status === 'completed' || sub.status === 'auto_submitted'"
                  variant="info"
                >
                  Submitted
                </BaseBadge>
                <BaseBadge
                  v-else-if="sub.status === 'in_progress'"
                  variant="warning"
                >
                  In Progress
                </BaseBadge>
                <BaseBadge
                  v-else
                  variant="neutral"
                >
                  {{ sub.status }}
                </BaseBadge>
              </td>

              <!-- Pending Questions Count -->
              <td v-if="isColumnVisible('pending_count')" class="px-6 py-5 whitespace-nowrap">
                <span
                  v-if="sub.pending_grading_count && sub.pending_grading_count > 0"
                  class="inline-flex items-center gap-1.5 text-xs font-semibold text-warning"
                >
                  <AlertCircle class="h-3.5 w-3.5" />
                  {{ sub.pending_grading_count }} to grade
                </span>
                <span v-else class="text-xs text-text/45 font-mono">
                  All evaluated
                </span>
              </td>

              <!-- Score -->
              <td v-if="isColumnVisible('score')" class="px-6 py-5 whitespace-nowrap">
                <span v-if="sub.score !== null && sub.score !== undefined" class="font-mono text-sm font-bold text-text">
                  {{ sub.score }} <span class="text-xs font-normal text-text/50">/ {{ exam?.total_marks || 100 }}</span>
                </span>
                <span v-else class="text-xs text-text/40 italic">
                  Pending
                </span>
              </td>

              <!-- Actions -->
              <td class="px-6 py-5 text-right whitespace-nowrap" @click.stop>
                <BaseButton
                  variant="secondary"
                  class="text-xs px-3 py-1.5"
                  @click="openGradingModal(sub)"
                >
                  <template #icon>
                    <FileEdit class="h-3.5 w-3.5" />
                  </template>
                  Grade
                </BaseButton>
              </td>
            </tr>
          </tbody>

          <!-- Empty State -->
          <tbody v-else>
            <tr>
              <td :colspan="totalTableColumns" class="px-6 py-16 text-center">
                <div class="mx-auto flex max-w-sm flex-col items-center">
                  <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-bg">
                    <Users class="h-6 w-6 text-text/30" />
                  </div>
                  <p class="text-sm font-medium text-text">No submissions found</p>
                  <p class="mt-1 text-sm text-text/45">
                    No students have submitted attempts matching your selected filters.
                  </p>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <AppPagination :pagination="pagination" @change-page="loadSubmissions" />
    </div>

    <!-- Manual Grading Modal -->
    <ManualGradingModal
      :open="showGradingModal"
      :exam="exam"
      :submission="selectedSubmission"
      @close="showGradingModal = false"
      @graded="handleGraded"
    />
  </div>
</template>
