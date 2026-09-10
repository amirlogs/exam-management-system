<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import {
  Award,
  BookOpen,
  Calendar,
  CheckCircle2,
  Clock,
  ArrowRight,
  TrendingUp,
  FileCheck,
  ShieldCheck,
  Search,
  School,
  AlertCircle,
} from 'lucide-vue-next';

import BaseBadge from '@/shared/components/ui/BaseBadge.vue';
import BaseButton from '@/shared/components/ui/BaseButton.vue';
import AppPagination from '@/shared/components/AppPagination.vue';
import { useUiStore } from '@/stores/ui';
import { handleApiError } from '@/shared/utils/apiError';

import * as api from '../../exams/api/studentExams';
import type { StudentExam } from '../../exams/types/studentExam';
import type { Pagination } from '../../exams/api/studentExams';

const router = useRouter();
const uiStore = useUiStore();

const results = ref<StudentExam[]>([]);
const loading = ref(false);
const search = ref('');

const pagination = ref<Pagination>({
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0,
  from: null,
  to: null,
});

async function loadResults(page = 1) {
  loading.value = true;
  try {
    const params: Record<string, any> = {};
    if (search.value) params.search = search.value;

    const res = await api.getStudentExams(page, pagination.value.per_page, params);
    // Filter to exams the student actually took
    results.value = (res.data || []).filter((e) => e.attempt !== null);
    if (res.pagination) {
      pagination.value = {
        ...res.pagination,
        total: results.value.length,
      };
    }
  } catch (err: any) {
    handleApiError(err, uiStore, undefined, 'Failed to load results.');
  } finally {
    loading.value = false;
  }
}

function handleSearch() {
  pagination.value.current_page = 1;
  loadResults(1);
}

function handlePageChange(page: number) {
  pagination.value.current_page = page;
  loadResults(page);
}

function calculateGrade(score: number | string | null | undefined, total: number | string | null | undefined): { letter: string; color: string } {
  if (score === null || score === undefined) {
    return { letter: '—', color: 'bg-bg text-text/50 border border-border' };
  }
  const scoreNum = Number(score);
  const totalNum = Number(total);
  if (totalNum <= 0) return { letter: 'N/A', color: 'bg-bg text-text/50 border border-border' };

  const pct = (scoreNum / totalNum) * 100;
  if (pct >= 90) return { letter: 'A', color: 'bg-success/15 text-success' };
  if (pct >= 85) return { letter: 'A-', color: 'bg-success/15 text-success' };
  if (pct >= 80) return { letter: 'B+', color: 'bg-accent/15 text-accent' };
  if (pct >= 75) return { letter: 'B', color: 'bg-accent/15 text-accent' };
  if (pct >= 70) return { letter: 'C+', color: 'bg-warning/15 text-warning' };
  if (pct >= 60) return { letter: 'C', color: 'bg-warning/15 text-warning' };
  if (pct >= 50) return { letter: 'D', color: 'bg-warning/15 text-warning' };
  return { letter: 'F', color: 'bg-error/15 text-error' };
}

function scorePercent(exam: StudentExam): number | null {
  if (exam.attempt?.score === null || exam.attempt?.score === undefined) return null;
  const total = Number(exam.total_marks);
  if (!total || total <= 0) return null;
  return Math.round((Number(exam.attempt.score) / total) * 100);
}

onMounted(() => {
  loadResults(1);
});
</script>

<template>
  <div class="max-w-4xl mx-auto space-y-8">
    <!-- ── Page Header ─────────────────────────────────────────────── -->
    <header class="space-y-2">
      <div class="flex items-center gap-2 text-xs font-mono uppercase tracking-wider text-text/60">
        <span class="w-1.5 h-1.5 rounded-full bg-accent inline-block" />
        <span>Academic Performance &amp; Evaluation Records</span>
      </div>
      <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-text font-display">My Results</h1>
      <p class="text-sm text-text/60">Official examination transcripts, scored submissions, and academic grades.</p>
    </header>

    <!-- ── Summary stats + Search bar ─────────────────────────────── -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-border/60 pb-4">
      <!-- Stats bar -->
      <div class="flex items-center gap-5">
        <div class="flex items-center gap-1.5 text-xs">
          <ShieldCheck class="w-4 h-4 text-success" />
          <span class="font-medium text-text">{{ results.length }}</span>
          <span class="text-text/60">completed assessment{{ results.length !== 1 ? 's' : '' }}</span>
        </div>
      </div>

      <!-- Search -->
      <div class="relative w-full sm:w-64 shrink-0">
        <Search class="w-3.5 h-3.5 text-text/40 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
        <input
          v-model="search"
          type="text"
          placeholder="Search by exam or course..."
          class="w-full bg-surface border border-border rounded-lg pl-8 pr-3 py-2 text-sm text-text placeholder:text-text/40 focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent transition-colors"
          @keyup.enter="handleSearch"
        />
      </div>
    </div>

    <!-- ── Loading Skeleton ────────────────────────────────────────── -->
    <div v-if="loading" class="space-y-4 animate-pulse">
      <div v-for="i in 3" :key="i" class="h-36 bg-surface rounded-2xl border border-border" />
    </div>

    <!-- ── Empty State ─────────────────────────────────────────────── -->
    <div v-else-if="results.length === 0" class="text-center py-20 bg-surface rounded-2xl border border-border space-y-3">
      <Award class="w-12 h-12 mx-auto text-text/30" />
      <h3 class="text-base font-semibold text-text">No assessment results recorded</h3>
      <p class="text-xs text-text/60 max-w-sm mx-auto">
        Submitted examinations will appear here once evaluation is completed.
      </p>
    </div>

    <!-- ── Result Cards ────────────────────────────────────────────── -->
    <div v-else class="space-y-4">
      <article
        v-for="exam in results"
        :key="exam.id"
        class="bg-surface rounded-2xl border border-border shadow-xs hover:shadow-md hover:border-accent/30 transition-all overflow-hidden group"
      >
        <div class="p-5 sm:p-6 flex flex-col gap-4">
          <!-- Top row: icon + title + grade -->
          <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
            <div class="flex items-start gap-4">
              <!-- Grade circle -->
              <div
                v-if="exam.attempt?.score !== null && exam.attempt?.score !== undefined"
                class="w-12 h-12 rounded-full flex items-center justify-center text-sm font-bold shrink-0 shadow-2xs font-mono"
                :class="calculateGrade(exam.attempt.score, exam.total_marks).color"
              >
                {{ calculateGrade(exam.attempt.score, exam.total_marks).letter }}
              </div>
              <div
                v-else
                class="w-12 h-12 rounded-full flex items-center justify-center shrink-0 bg-bg text-text/50 text-[11px] font-semibold border border-border font-mono text-center leading-tight"
              >
                Pending
              </div>

              <div class="space-y-0.5 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                  <span class="text-xs font-mono font-bold text-accent uppercase">
                    {{ exam.course?.code }}
                  </span>
                  <span class="text-xs text-text/40">·</span>
                  <span class="text-xs text-text/60 capitalize">{{ exam.type }} Assessment</span>
                </div>
                <h3 class="text-lg font-bold text-text font-display leading-snug group-hover:text-accent transition-colors">
                  {{ exam.title }}
                </h3>
                <p class="text-xs text-text/60">{{ exam.course?.name }}</p>
              </div>
            </div>

            <!-- Score block -->
            <div class="text-right shrink-0">
              <div class="font-mono text-lg font-bold text-text">
                {{ exam.attempt?.score !== null && exam.attempt?.score !== undefined ? `${exam.attempt.score} / ${exam.total_marks}` : 'Under Review' }}
              </div>
              <div class="font-mono text-xs text-text/60">
                {{ exam.attempt?.score !== null && exam.attempt?.score !== undefined ? `${scorePercent(exam)}% Total` : 'Grading in progress' }}
              </div>
            </div>
          </div>

          <!-- Progress bar for graded attempts -->
          <div
            v-if="exam.attempt?.score !== null && exam.attempt?.score !== undefined && exam.total_marks > 0"
            class="space-y-1"
          >
            <div class="h-2 w-full bg-bg rounded-full overflow-hidden border border-border/80">
              <div
                class="h-full rounded-full transition-all duration-300"
                :class="(scorePercent(exam) ?? 0) >= 50 ? 'bg-success' : 'bg-error'"
                :style="{ width: `${scorePercent(exam)}%` }"
              />
            </div>
          </div>

          <!-- Footer: submission date + CTA -->
          <div class="flex items-center justify-between text-xs text-text/60 border-t border-border/40 pt-3">
            <div class="flex items-center gap-1.5">
              <Calendar class="w-3.5 h-3.5 text-text/40" />
              <span>Completed: {{ exam.attempt?.submitted_at ? new Date(exam.attempt.submitted_at).toLocaleDateString() : 'Recorded' }}</span>
            </div>

            <BaseButton
              variant="secondary"
              class="text-xs font-semibold group/btn"
              @click="router.push({ name: 'student.exams.overview', params: { examId: exam.id } })"
            >
              <span>Overview &amp; Breakdown</span>
              <ArrowRight class="w-3.5 h-3.5 group-hover/btn:translate-x-0.5 transition-transform" />
            </BaseButton>
          </div>
        </div>
      </article>
    </div>

    <!-- ── Pagination ──────────────────────────────────────────────── -->
    <AppPagination
      v-if="pagination.last_page > 1"
      :pagination="pagination"
      @change-page="handlePageChange"
    />
  </div>
</template>
