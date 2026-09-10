<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import {
  FileText,
  Clock,
  CheckCircle2,
  Play,
  ArrowRight,
  BookOpen,
  Calendar,
  Layers,
  ShieldCheck,
  Lock,
  Info,
  Check,
  ChevronRight,
  Search,
  Filter,
  Award,
  AlertCircle,
} from 'lucide-vue-next';

import BaseBadge from '@/shared/components/ui/BaseBadge.vue';
import BaseButton from '@/shared/components/ui/BaseButton.vue';
import AppPagination from '@/shared/components/AppPagination.vue';
import { useUiStore } from '@/stores/ui';
import { handleApiError } from '@/shared/utils/apiError';

import * as api from '../api/studentExams';
import type { StudentExam } from '../types/studentExam';
import type { Pagination } from '../api/studentExams';

const router = useRouter();
const uiStore = useUiStore();

const rawExams = ref<StudentExam[]>([]);
const loading = ref(false);
const search = ref('');
const activeTab = ref<'all' | 'active' | 'scheduled' | 'completed'>('all');

const pagination = ref<Pagination>({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
  from: null,
  to: null,
});

type ExamDisplayState = 'graded' | 'submitted' | 'in_progress' | 'active' | 'scheduled' | 'closed';

function getExamState(exam: StudentExam): ExamDisplayState {
  if (exam.attempt) {
    if (exam.attempt.status === 'graded' || (exam.attempt.score !== null && exam.attempt.score !== undefined)) {
      return 'graded';
    }
    if (exam.attempt.status === 'in_progress') {
      return 'in_progress';
    }
    if (exam.attempt.status === 'completed' || exam.attempt.status === 'auto_submitted' || exam.attempt.status === 'submitted') {
      return 'submitted';
    }
  }

  if (exam.status === 'active') {
    return 'active';
  }

  if (exam.status === 'completed' || exam.status === 'cancelled' || exam.status === 'archived') {
    return 'closed';
  }

  return 'scheduled';
}

const filteredExams = computed(() => {
  return rawExams.value.filter((exam) => {
    const state = getExamState(exam);

    if (activeTab.value === 'active') {
      return state === 'active' || state === 'in_progress';
    }
    if (activeTab.value === 'scheduled') {
      return state === 'scheduled';
    }
    if (activeTab.value === 'completed') {
      return state === 'graded' || state === 'submitted' || state === 'closed';
    }
    return true;
  });
});

async function load(page = 1) {
  loading.value = true;
  try {
    const params: Record<string, any> = {};
    if (search.value) params.search = search.value;

    const res = await api.getStudentExams(page, pagination.value.per_page, params);
    rawExams.value = res.data || [];
    if (res.pagination) {
      pagination.value = res.pagination;
    }
  } catch (err: any) {
    handleApiError(err, uiStore, undefined, 'Failed to load exams list.');
  } finally {
    loading.value = false;
  }
}

function handleSearch() {
  pagination.value.current_page = 1;
  load(1);
}

function setTab(tab: 'all' | 'active' | 'scheduled' | 'completed') {
  activeTab.value = tab;
}

function handlePageChange(page: number) {
  pagination.value.current_page = page;
  load(page);
}

function navigateToExam(exam: StudentExam) {
  router.push({ name: 'student.exams.overview', params: { examId: exam.id } });
}

onMounted(() => {
  load(1);
});
</script>

<template>
  <div class="max-w-4xl mx-auto space-y-6">
    <!-- ── Page Header ─────────────────────────────────────────────── -->
    <header class="space-y-1">
      <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-text font-display">My Exams</h1>
      <p class="text-xs sm:text-sm text-text/50">Your assigned examinations, upcoming schedules, and completed assessment records.</p>
    </header>

    <!-- ── Filter Tabs + Search ────────────────────────────────────── -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-border pb-3">
      <!-- Status tab pills -->
      <div class="flex items-center gap-1.5 overflow-x-auto w-full sm:w-auto">
        <button
          v-for="tab in [
            { key: 'all', label: 'All' },
            { key: 'active', label: 'Active' },
            { key: 'scheduled', label: 'Upcoming' },
            { key: 'completed', label: 'Completed' },
          ] as const"
          :key="tab.key"
          type="button"
          class="px-3 py-1 rounded-md text-xs font-medium transition-all cursor-pointer whitespace-nowrap"
          :class="activeTab === tab.key ? 'bg-accent text-white shadow-xs font-semibold' : 'bg-surface border border-border text-text/70 hover:text-text hover:border-text/40'"
          @click="setTab(tab.key)">
          {{ tab.label }}
        </button>
      </div>

      <!-- Search -->
      <div class="relative w-full sm:w-64 shrink-0">
        <Search class="w-3.5 h-3.5 text-text/40 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
        <input
          v-model="search"
          type="text"
          placeholder="Filter by title or course..."
          class="w-full bg-surface border border-border rounded-lg pl-8 pr-3 py-1.5 text-xs sm:text-sm text-text placeholder:text-text/40 focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent transition-colors"
          @keyup.enter="handleSearch" />
      </div>
    </div>

    <!-- ── Loading Skeleton ────────────────────────────────────────── -->
    <div v-if="loading" class="space-y-4 animate-pulse">
      <div v-for="i in 3" :key="i" class="h-36 bg-surface rounded-2xl border border-border" />
    </div>

    <!-- ── Empty State ─────────────────────────────────────────────── -->
    <div v-else-if="filteredExams.length === 0" class="text-center py-16 bg-surface rounded-2xl border border-border space-y-2">
      <FileText class="w-10 h-10 mx-auto text-text/30" />
      <h3 class="text-sm font-semibold text-text">No examinations found</h3>
      <p class="text-xs text-text/50 max-w-xs mx-auto">There are no examinations matching your selected filter.</p>
    </div>

    <!-- ── Exam Cards ──────────────────────────────────────────────── -->
    <div v-else class="space-y-3.5">
      <article
        v-for="exam in filteredExams"
        :key="exam.id"
        class="relative bg-surface rounded-xl border border-border shadow-2xs p-5 sm:p-6 overflow-hidden transition-all duration-150 hover:border-accent/40 group">
        <!-- Left accent line for active/in-progress exams -->
        <div v-if="getExamState(exam) === 'active' || getExamState(exam) === 'in_progress'" class="absolute left-0 top-0 bottom-0 w-1 bg-accent rounded-l-xl" />

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <!-- Left: meta + title + metrics -->
          <div class="flex flex-col min-w-0 space-y-2">
            <!-- Status badges row -->
            <div class="flex items-center flex-wrap gap-2 text-xs">
              <!-- Graded -->
              <span
                v-if="getExamState(exam) === 'graded'"
                class="inline-flex items-center gap-1.5 font-mono text-[11px] px-2.5 py-0.5 rounded-full bg-success/15 text-success font-semibold">
                <CheckCircle2 class="w-3.5 h-3.5" />
                Graded · {{ exam.attempt?.score }} / {{ exam.total_marks }} pts
              </span>

              <!-- Submitted / Under Review -->
              <span
                v-else-if="getExamState(exam) === 'submitted'"
                class="inline-flex items-center gap-1.5 font-mono text-[11px] px-2.5 py-0.5 rounded-full bg-accent/10 text-accent font-semibold">
                <Check class="w-3.5 h-3.5" />
                Submitted · Under Review
              </span>

              <!-- In Progress -->
              <span
                v-else-if="getExamState(exam) === 'in_progress'"
                class="inline-flex items-center gap-1.5 font-mono text-[11px] px-2.5 py-0.5 rounded-full bg-warning/15 text-warning font-semibold">
                <span class="w-1.5 h-1.5 rounded-full bg-warning animate-pulse" />
                In Progress
              </span>

              <!-- Active Now -->
              <span
                v-else-if="getExamState(exam) === 'active'"
                class="inline-flex items-center gap-1.5 font-mono text-[11px] px-2.5 py-0.5 rounded-full bg-success/15 text-success font-semibold">
                <span class="w-1.5 h-1.5 rounded-full bg-success animate-pulse" />
                Active Now
              </span>

              <!-- Closed / Missed -->
              <span
                v-else-if="getExamState(exam) === 'closed'"
                class="inline-flex items-center gap-1 font-mono text-[11px] px-2.5 py-0.5 rounded-full bg-bg border border-border text-text/50 font-medium">
                Concluded
              </span>

              <!-- Scheduled -->
              <span v-else class="inline-flex items-center gap-1 font-mono text-[11px] px-2.5 py-0.5 rounded-full bg-bg border border-border text-text/60 font-medium">
                <Calendar class="w-3 h-3" />
                Scheduled
              </span>

              <!-- Scheduled start if not completed -->
              <span v-if="exam.scheduled_start && (getExamState(exam) === 'scheduled' || getExamState(exam) === 'active')" class="inline-flex items-center gap-1 text-text/50 font-mono text-[11px]">
                <Clock class="w-3 h-3 text-text/40" />
                {{ exam.scheduled_start }}
              </span>
            </div>

            <!-- Title + Course Code -->
            <div>
              <span class="font-mono text-xs font-semibold text-accent uppercase tracking-wider block">
                {{ exam.course?.code }} · {{ exam.course?.name }}
              </span>
              <h2 class="text-lg sm:text-xl font-bold tracking-tight text-text font-display group-hover:text-accent transition-colors leading-snug">
                {{ exam.title }}
              </h2>
            </div>

            <!-- Metrics row -->
            <div class="flex items-center gap-3 text-xs text-text/60 flex-wrap">
              <span class="flex items-center gap-1 font-mono">
                <Clock class="w-3.5 h-3.5 text-text/40" />
                {{ exam.duration_minutes }} mins
              </span>
              <span class="text-border">·</span>
              <span class="flex items-center gap-1 font-mono">
                <Layers class="w-3.5 h-3.5 text-text/40" />
                {{ exam.total_questions }} questions
              </span>
              <span class="text-border">·</span>
              <span class="flex items-center gap-1 font-mono">
                <Award class="w-3.5 h-3.5 text-text/40" />
                {{ exam.total_marks }} pts
              </span>
            </div>
          </div>

          <!-- Right: Action CTA -->
          <div class="sm:shrink-0 flex items-center justify-start sm:justify-end">
            <!-- Active / Resume -->
            <BaseButton
              v-if="getExamState(exam) === 'active' || getExamState(exam) === 'in_progress'"
              variant="primary"
              class="w-full sm:w-auto font-semibold shadow-xs"
              @click="navigateToExam(exam)">
              <span>{{ getExamState(exam) === 'in_progress' ? 'Resume Exam' : 'Start Exam' }}</span>
              <ArrowRight class="w-4 h-4 group-hover:translate-x-0.5 transition-transform" />
            </BaseButton>

            <!-- Graded: View Results -->
            <BaseButton
              v-else-if="getExamState(exam) === 'graded'"
              variant="secondary"
              class="w-full sm:w-auto font-medium"
              @click="router.push({ name: 'student.results.list' })">
              <span>View Results</span>
              <ChevronRight class="w-4 h-4 text-text/50" />
            </BaseButton>

            <!-- Submitted: Overview -->
            <BaseButton
              v-else-if="getExamState(exam) === 'submitted'"
              variant="secondary"
              class="w-full sm:w-auto font-medium"
              @click="navigateToExam(exam)">
              <span>Overview</span>
              <ChevronRight class="w-4 h-4 text-text/50" />
            </BaseButton>

            <!-- Scheduled or Closed: Details -->
            <BaseButton v-else variant="secondary" class="w-full sm:w-auto font-medium" @click="navigateToExam(exam)">
              <span>Details</span>
            </BaseButton>
          </div>
        </div>
      </article>
    </div>

    <!-- ── Footer Note ─────────────────────────────────────────────── -->
    <div class="pt-4 flex items-start gap-2.5 text-text/60 text-xs leading-relaxed border-t border-border/40">
      <Info class="w-4 h-4 text-accent shrink-0 mt-0.5" />
      <p>
        Examination links activate precisely at scheduled start times. Ensure your browser is up to date and a stable internet connection is maintained throughout the evaluation window.
      </p>
    </div>

    <!-- ── Pagination ──────────────────────────────────────────────── -->
    <AppPagination
      v-if="pagination.last_page > 1"
      :pagination="pagination"
      @change-page="handlePageChange" />
  </div>
</template>
