<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import {
  Clock,
  Layers,
  CheckCircle2,
  ArrowLeft,
  ArrowRight,
  ShieldCheck,
  Award,
  Calendar,
  AlertCircle,
  Check,
  X,
  Sparkles,
  FileCheck,
} from 'lucide-vue-next';

import BaseButton from '@/shared/components/ui/BaseButton.vue';
import BaseBadge from '@/shared/components/ui/BaseBadge.vue';
import { useUiStore } from '@/stores/ui';
import { handleApiError } from '@/shared/utils/apiError';

import * as api from '../api/studentExams';
import type { StudentExam, ExamReviewData } from '../types/studentExam';

const route = useRoute();
const router = useRouter();
const uiStore = useUiStore();

const examId = Number(route.params.examId);
const exam = ref<StudentExam | null>(null);
const loading = ref(true);
const starting = ref(false);
const reviewData = ref<ExamReviewData | null>(null);
const loadingReview = ref(false);

const questionFilter = ref<'all' | 'correct' | 'incorrect'>('all');

async function loadReview() {
  if (exam.value?.grading_status !== 'published') return;
  loadingReview.value = true;
  try {
    const res = await api.getStudentExamReview(examId);
    reviewData.value = res.data;
  } catch {
    // Non-blocking
  } finally {
    loadingReview.value = false;
  }
}

async function loadExam() {
  loading.value = true;
  try {
    const res = await api.getStudentExam(examId);
    exam.value = res.data;
    if (exam.value?.grading_status === 'published') {
      await loadReview();
    }
  } catch (err: any) {
    handleApiError(err, uiStore, undefined, 'Failed to load exam details.');
  } finally {
    loading.value = false;
  }
}

const isAttemptDone = computed(() => {
  const s = exam.value?.attempt?.status;
  return s === 'completed' || s === 'submitted' || s === 'auto_submitted' || s === 'graded';
});

const isPublished = computed(() => {
  return exam.value?.grading_status === 'published';
});

const isGradedAndPublished = computed(() => {
  return isPublished.value && exam.value?.attempt?.score !== null && exam.value?.attempt?.score !== undefined;
});

const isAttemptInProgress = computed(() => {
  return exam.value?.attempt?.status === 'in_progress';
});

const canStart = computed(() => {
  return exam.value?.status === 'active' && !isAttemptDone.value;
});

const scorePercent = computed(() => {
  if (!isGradedAndPublished.value || exam.value?.attempt?.score === null || exam.value?.attempt?.score === undefined) return null;
  const total = Number(exam.value?.total_marks);
  if (!total || total <= 0) return null;
  return Math.round((Number(exam.value.attempt.score) / total) * 100);
});

const correctAnswersCount = computed(() => {
  if (!reviewData.value?.questions) return 0;
  return reviewData.value.questions.filter((q) => q.is_correct).length;
});

const incorrectAnswersCount = computed(() => {
  if (!reviewData.value?.questions) return 0;
  return reviewData.value.questions.filter((q) => !q.is_correct).length;
});

const filteredQuestions = computed(() => {
  if (!reviewData.value?.questions) return [];
  if (questionFilter.value === 'correct') {
    return reviewData.value.questions.filter((q) => q.is_correct);
  }
  if (questionFilter.value === 'incorrect') {
    return reviewData.value.questions.filter((q) => !q.is_correct);
  }
  return reviewData.value.questions;
});

function gradeFromPercent(pct: number): { letter: string; color: string } {
  if (pct >= 90) return { letter: 'A', color: 'bg-success/15 text-success border-success/30' };
  if (pct >= 85) return { letter: 'A-', color: 'bg-success/15 text-success border-success/30' };
  if (pct >= 80) return { letter: 'B+', color: 'bg-accent/15 text-accent border-accent/30' };
  if (pct >= 75) return { letter: 'B', color: 'bg-accent/15 text-accent border-accent/30' };
  if (pct >= 70) return { letter: 'C+', color: 'bg-warning/15 text-warning border-warning/30' };
  if (pct >= 60) return { letter: 'C', color: 'bg-warning/15 text-warning border-warning/30' };
  if (pct >= 50) return { letter: 'D', color: 'bg-warning/15 text-warning border-warning/30' };
  return { letter: 'F', color: 'bg-error/15 text-error border-error/30' };
}

function formatOptionLabel(idx: number): string {
  return String.fromCharCode(65 + idx);
}

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

async function handleStartOrResume() {
  if (!exam.value) return;
  starting.value = true;
  try {
    const res = await api.startStudentExam(exam.value.id);
    if (res.data) {
      router.push({
        name: 'student.exams.take',
        params: { examId: exam.value.id },
      });
    }
  } catch (err: any) {
    handleApiError(err, uiStore, undefined, 'Could not start the exam.');
  } finally {
    starting.value = false;
  }
}

onMounted(() => {
  loadExam();
});
</script>

<template>
  <div class="w-full max-w-5xl mx-auto py-6 sm:py-8 px-4 sm:px-6 space-y-6">
    <!-- Top Bar: Navigation & Action -->
    <div class="flex items-center justify-between gap-4">
      <button
        type="button"
        class="inline-flex items-center gap-2 text-sm font-medium text-text/60 hover:text-accent transition-colors cursor-pointer"
        @click="router.push({ name: 'student.exams.list' })"
      >
        <ArrowLeft class="w-4 h-4" />
        <span>Back to Assessments</span>
      </button>

      <span v-if="exam?.course" class="hidden sm:inline-flex items-center gap-1.5 text-xs font-mono text-text/50">
        <span>{{ exam.course.code }}</span>
        <span>/</span>
        <span class="truncate max-w-[200px]">{{ exam.title }}</span>
      </span>
    </div>

    <!-- Loading Skeleton -->
    <div v-if="loading" class="w-full bg-surface rounded-2xl border border-border p-8 animate-pulse space-y-6 shadow-xs">
      <div class="flex justify-between items-center">
        <div class="h-6 bg-border/60 rounded-md w-1/4" />
        <div class="h-6 bg-border/60 rounded-full w-24" />
      </div>
      <div class="h-9 bg-border/60 rounded-lg w-2/3" />
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div v-for="i in 4" :key="i" class="h-20 bg-border/40 rounded-xl" />
      </div>
    </div>

    <!-- Main Overview Content -->
    <div v-else-if="exam" class="space-y-6">
      <!-- Hero Overview Card -->
      <div class="bg-surface rounded-2xl border border-border p-6 sm:p-8 space-y-6 shadow-xs">
        <div class="space-y-3">
          <!-- Course & Status Pill Row -->
          <div class="flex items-center justify-between gap-3 flex-wrap">
            <div class="inline-flex items-center gap-2">
              <span class="px-2.5 py-1 rounded-md bg-accent/10 border border-accent/20 text-accent font-mono text-xs font-semibold uppercase tracking-wider">
                {{ exam.course?.code }}
              </span>
              <span class="text-xs text-text/60 font-medium truncate max-w-xs sm:max-w-md">
                {{ exam.course?.name }}
              </span>
            </div>

            <!-- Status Badge -->
            <div class="flex items-center gap-2">
              <BaseBadge v-if="isGradedAndPublished" variant="success">
                <CheckCircle2 class="w-3.5 h-3.5 mr-1" />
                Results Published
              </BaseBadge>
              <BaseBadge v-else-if="isAttemptDone" variant="secondary">
                <FileCheck class="w-3.5 h-3.5 mr-1 text-accent" />
                Under Evaluation
              </BaseBadge>
              <BaseBadge v-else-if="isAttemptInProgress" variant="warning">
                <span class="w-1.5 h-1.5 rounded-full bg-warning animate-pulse mr-1" />
                In Progress
              </BaseBadge>
              <BaseBadge v-else-if="canStart" variant="success">
                <span class="w-1.5 h-1.5 rounded-full bg-success animate-pulse mr-1" />
                Active Exam
              </BaseBadge>
              <BaseBadge v-else-if="exam.status === 'completed'" variant="neutral">
                Concluded
              </BaseBadge>
              <BaseBadge v-else variant="neutral">
                <Calendar class="w-3 h-3 mr-1" />
                Scheduled
              </BaseBadge>
            </div>
          </div>

          <!-- Exam Title -->
          <h1 class="text-2xl sm:text-3xl font-bold font-display text-text tracking-tight">
            {{ exam.title }}
          </h1>

          <!-- Meta Description / Semester Details -->
          <div class="flex items-center gap-3 text-xs text-text/50 font-mono flex-wrap">
            <span v-if="exam.semester?.name">Semester {{ exam.semester.name }}</span>
            <span v-if="exam.semester?.name">·</span>
            <span class="capitalize">{{ exam.type }} Assessment</span>
            <span v-if="exam.scheduled_start">·</span>
            <span v-if="exam.scheduled_start">Start: {{ formatDateTime(exam.scheduled_start) }}</span>
          </div>
        </div>

        <!-- 4-Column Metric Strip -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
          <div class="p-3.5 rounded-xl bg-bg border border-border/80 text-center space-y-1">
            <span class="text-xs text-text/50 inline-flex items-center justify-center gap-1 font-medium">
              <Clock class="w-3.5 h-3.5 text-accent" />
              Duration
            </span>
            <p class="font-mono text-lg font-bold text-text">
              {{ exam.duration_minutes }} <span class="text-xs font-normal text-text/50">mins</span>
            </p>
          </div>

          <div class="p-3.5 rounded-xl bg-bg border border-border/80 text-center space-y-1">
            <span class="text-xs text-text/50 inline-flex items-center justify-center gap-1 font-medium">
              <Layers class="w-3.5 h-3.5 text-accent" />
              Questions
            </span>
            <p class="font-mono text-lg font-bold text-text">
              {{ exam.total_questions }}
            </p>
          </div>

          <div class="p-3.5 rounded-xl bg-bg border border-border/80 text-center space-y-1">
            <span class="text-xs text-text/50 inline-flex items-center justify-center gap-1 font-medium">
              <Award class="w-3.5 h-3.5 text-accent" />
              Total Marks
            </span>
            <p class="font-mono text-lg font-bold text-text">
              {{ exam.total_marks }} <span class="text-xs font-normal text-text/50">pts</span>
            </p>
          </div>

          <div class="p-3.5 rounded-xl bg-bg border border-border/80 text-center space-y-1">
            <span class="text-xs text-text/50 inline-flex items-center justify-center gap-1 font-medium">
              <ShieldCheck class="w-3.5 h-3.5 text-accent" />
              Passing Score
            </span>
            <p class="font-mono text-lg font-bold text-text">
              {{ exam.passing_marks ? `${exam.passing_marks} pts` : '50%' }}
            </p>
          </div>
        </div>

        <!-- Official Results Banner (When Graded & Published) -->
        <div
          v-if="isGradedAndPublished"
          class="p-5 rounded-2xl bg-gradient-to-r from-success/15 via-success/10 to-transparent border border-success/30 flex flex-col sm:flex-row sm:items-center justify-between gap-4"
        >
          <div class="flex items-center gap-4">
            <div
              v-if="scorePercent !== null"
              class="w-14 h-14 rounded-2xl border-2 flex items-center justify-center text-xl font-bold shrink-0 font-mono shadow-xs"
              :class="gradeFromPercent(scorePercent).color"
            >
              {{ gradeFromPercent(scorePercent).letter }}
            </div>
            <div class="space-y-1">
              <div class="flex items-center gap-1.5 text-success font-semibold text-sm">
                <CheckCircle2 class="w-4 h-4" />
                <span>Official Evaluation Published</span>
              </div>
              <p class="text-xs text-text/60">
                Your examination has been reviewed and officially recorded.
              </p>
              <span v-if="exam.attempt?.submitted_at" class="text-[11px] font-mono text-text/50 block">
                Submitted on {{ formatDateTime(exam.attempt.submitted_at) }}
              </span>
            </div>
          </div>

          <div class="sm:text-right pt-2 sm:pt-0 border-t sm:border-t-0 border-success/20">
            <span class="text-xs text-text/50 uppercase tracking-wider font-mono block">Final Score</span>
            <div class="font-mono text-2xl font-bold text-text">
              {{ exam.attempt?.score }} <span class="text-sm font-normal text-text/50">/ {{ exam.total_marks }} pts</span>
            </div>
            <span v-if="scorePercent !== null" class="inline-block mt-0.5 text-xs font-semibold text-success font-mono">
              {{ scorePercent }}% Total Accuracy
            </span>
          </div>
        </div>

        <!-- Awaiting Publication Banner -->
        <div
          v-else-if="isAttemptDone"
          class="p-5 rounded-2xl bg-accent/5 border border-accent/20 flex flex-col sm:flex-row sm:items-center justify-between gap-4"
        >
          <div class="flex items-start sm:items-center gap-3.5">
            <div class="w-10 h-10 rounded-xl bg-accent/10 border border-accent/20 flex items-center justify-center text-accent shrink-0">
              <FileCheck class="w-5 h-5" />
            </div>
            <div class="space-y-0.5 text-xs">
              <span class="font-semibold text-sm text-text block">Submission Successfully Recorded</span>
              <p class="text-text/60">
                Your responses have been collected and are currently being evaluated. Official grades will appear here once published by your instructor.
              </p>
              <span v-if="exam.attempt?.submitted_at" class="font-mono text-[11px] text-text/50 block pt-1">
                Completed on {{ formatDateTime(exam.attempt.submitted_at) }}
              </span>
            </div>
          </div>
          <BaseBadge variant="secondary" class="self-start sm:self-center whitespace-nowrap">
            Awaiting Publication
          </BaseBadge>
        </div>

        <!-- Active Exam Session Alert -->
        <div
          v-else-if="isAttemptInProgress"
          class="p-4 rounded-xl bg-warning/10 border border-warning/25 flex items-start gap-3 text-xs"
        >
          <AlertCircle class="w-4 h-4 text-warning shrink-0 mt-0.5" />
          <div class="space-y-0.5">
            <span class="font-semibold text-text block">Active Examination Session</span>
            <span class="text-text/70 leading-relaxed">
              You have an unfinished attempt in progress. The examination timer continues to run.
            </span>
          </div>
        </div>

        <!-- Scheduled Notice -->
        <div
          v-else-if="!canStart && exam.status === 'scheduled'"
          class="p-4 rounded-xl bg-bg border border-border text-center space-y-1 text-xs"
        >
          <div class="flex items-center justify-center gap-1.5 font-semibold text-text">
            <Calendar class="w-4 h-4 text-accent" />
            <span>Examination Scheduled</span>
          </div>
          <p class="text-text/60">
            Window Opens: <span class="font-mono text-text font-medium">{{ exam.scheduled_start ? formatDateTime(exam.scheduled_start) : 'To be announced' }}</span>
          </p>
        </div>

        <!-- Concluded Notice -->
        <div
          v-else-if="!canStart && exam.status === 'completed'"
          class="p-4 rounded-xl bg-bg border border-border text-center space-y-1 text-xs"
        >
          <span class="font-semibold text-text/70 block">Examination Window Concluded</span>
          <p class="text-text/50">The examination period for this assessment has officially ended.</p>
        </div>

        <!-- Action CTAs -->
        <div v-if="canStart" class="pt-2">
          <BaseButton
            variant="primary"
            :loading="starting"
            class="w-full py-3.5 px-6 font-semibold shadow-xs flex items-center justify-center gap-2 group text-sm"
            @click="handleStartOrResume"
          >
            <span>{{ isAttemptInProgress ? 'Resume Examination' : 'Start Examination Now' }}</span>
            <ArrowRight class="w-4 h-4 transition-transform group-hover:translate-x-0.5" />
          </BaseButton>
        </div>
      </div>

      <!-- ── Question Performance & Solution Breakdown Section ──────────────────── -->
      <section v-if="isGradedAndPublished && reviewData && reviewData.questions.length > 0" class="space-y-5">
        <!-- Section Header with Filter Controls -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-border">
          <div>
            <h2 class="text-lg sm:text-xl font-bold font-display text-text">
              Question Performance &amp; Solutions
            </h2>
            <p class="text-xs text-text/60 mt-0.5">
              Review your responses against official solutions and point allocations.
            </p>
          </div>

          <!-- Interactive Filter Tabs -->
          <div class="flex items-center gap-1.5 bg-surface border border-border p-1 rounded-xl">
            <button
              type="button"
              class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors cursor-pointer"
              :class="questionFilter === 'all' ? 'bg-accent text-white font-semibold shadow-2xs' : 'text-text/60 hover:text-text'"
              @click="questionFilter = 'all'"
            >
              All ({{ reviewData.questions.length }})
            </button>
            <button
              type="button"
              class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors cursor-pointer inline-flex items-center gap-1"
              :class="questionFilter === 'correct' ? 'bg-success text-white font-semibold shadow-2xs' : 'text-text/60 hover:text-text'"
              @click="questionFilter = 'correct'"
            >
              <Check class="w-3 h-3" />
              <span>Correct ({{ correctAnswersCount }})</span>
            </button>
            <button
              type="button"
              class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors cursor-pointer inline-flex items-center gap-1"
              :class="questionFilter === 'incorrect' ? 'bg-error text-white font-semibold shadow-2xs' : 'text-text/60 hover:text-text'"
              @click="questionFilter = 'incorrect'"
            >
              <X class="w-3 h-3" />
              <span>Incorrect ({{ incorrectAnswersCount }})</span>
            </button>
          </div>
        </div>

        <!-- Question Cards List -->
        <div class="space-y-4">
          <div
            v-for="(q, idx) in filteredQuestions"
            :key="q.id"
            class="bg-surface rounded-2xl border transition-all p-5 sm:p-6 space-y-4 shadow-2xs"
            :class="q.is_correct ? 'border-success/30 hover:border-success/50' : 'border-error/25 hover:border-error/40'"
          >
            <!-- Question Meta Header -->
            <div class="flex items-start justify-between gap-4">
              <div class="flex items-center gap-2 flex-wrap">
                <span class="font-mono text-xs font-bold text-text px-2.5 py-1 rounded-lg bg-bg border border-border">
                  Question {{ q.order_number || idx + 1 }}
                </span>
                <span class="font-mono text-[11px] uppercase tracking-wider text-text/50">
                  {{ q.type }}
                </span>
              </div>

              <!-- Marks & Status Badge -->
              <div>
                <span
                  v-if="q.is_correct"
                  class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-success/15 text-success font-mono text-xs font-semibold"
                >
                  <Check class="w-3.5 h-3.5" />
                  Correct (+{{ q.marks_awarded }} pts)
                </span>
                <span
                  v-else-if="q.marks_awarded && q.marks_awarded > 0"
                  class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-warning/15 text-warning font-mono text-xs font-semibold"
                >
                  <AlertCircle class="w-3.5 h-3.5" />
                  Partial (+{{ q.marks_awarded }} / {{ q.marks }} pts)
                </span>
                <span
                  v-else
                  class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-error/15 text-error font-mono text-xs font-semibold"
                >
                  <X class="w-3.5 h-3.5" />
                  Incorrect (0 / {{ q.marks }} pts)
                </span>
              </div>
            </div>

            <!-- Question Stem -->
            <p class="text-sm sm:text-base font-medium text-text leading-relaxed whitespace-pre-wrap">
              {{ q.content }}
            </p>

            <!-- Multiple Choice & True/False Options -->
            <div v-if="q.options && q.options.length > 0" class="space-y-2.5 pt-1">
              <div
                v-for="(opt, optIdx) in q.options"
                :key="opt.id"
                class="flex items-start gap-3.5 p-3.5 rounded-xl border text-sm transition-all"
                :class="[
                  // Student chose this AND it's correct
                  opt.id === q.selected_answer_id && opt.is_correct
                    ? 'bg-success/15 border-success text-text font-medium ring-1 ring-success/30'
                    // Student chose this BUT it's incorrect
                    : opt.id === q.selected_answer_id && !opt.is_correct
                    ? 'bg-error/15 border-error text-text font-medium ring-1 ring-error/30'
                    // Correct option that student missed
                    : opt.is_correct
                    ? 'bg-success/10 border-success/50 text-text'
                    // Neutral unselected options
                    : 'bg-bg/40 border-border text-text/75'
                ]"
              >
                <!-- Option Letter Badge (A, B, C, D) -->
                <div
                  class="w-7 h-7 rounded-lg font-mono text-xs font-bold flex items-center justify-center shrink-0 border"
                  :class="[
                    opt.id === q.selected_answer_id && opt.is_correct
                      ? 'bg-success text-white border-success'
                      : opt.id === q.selected_answer_id && !opt.is_correct
                      ? 'bg-error text-white border-error'
                      : opt.is_correct
                      ? 'bg-success/20 text-success border-success/40'
                      : 'bg-surface text-text/60 border-border'
                  ]"
                >
                  {{ formatOptionLabel(optIdx) }}
                </div>

                <!-- Text & Selection Indicator Label -->
                <div class="flex-1 min-w-0 pt-0.5">
                  <div class="flex items-center justify-between gap-2 flex-wrap">
                    <span class="text-sm">{{ opt.option_text }}</span>
                    <span
                      v-if="opt.id === q.selected_answer_id && opt.is_correct"
                      class="inline-flex items-center gap-1 text-[11px] font-mono font-bold text-success uppercase tracking-wider"
                    >
                      <Check class="w-3.5 h-3.5 stroke-[3]" />
                      Your Choice · Correct
                    </span>
                    <span
                      v-else-if="opt.id === q.selected_answer_id && !opt.is_correct"
                      class="inline-flex items-center gap-1 text-[11px] font-mono font-bold text-error uppercase tracking-wider"
                    >
                      <X class="w-3.5 h-3.5 stroke-[3]" />
                      Your Choice · Incorrect
                    </span>
                    <span
                      v-else-if="opt.is_correct"
                      class="inline-flex items-center gap-1 text-[11px] font-mono font-semibold text-success uppercase tracking-wider"
                    >
                      <Check class="w-3 h-3 stroke-[2.5]" />
                      Correct Solution
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Subjective / Written Response -->
            <div v-else-if="q.answer_text !== undefined" class="p-3.5 rounded-xl bg-bg border border-border text-sm space-y-1.5">
              <span class="text-xs font-mono font-medium text-text/50 uppercase tracking-wider">Your Written Answer:</span>
              <p class="text-text whitespace-pre-wrap font-sans">{{ q.answer_text || '(No response recorded)' }}</p>
            </div>

            <!-- Solution Explanation Box -->
            <div v-if="q.explanation" class="p-4 rounded-xl bg-accent/5 border border-accent/20 text-xs space-y-1.5">
              <div class="flex items-center gap-1.5 text-accent font-semibold uppercase font-mono tracking-wider">
                <Sparkles class="w-3.5 h-3.5" />
                <span>Solution Explanation</span>
              </div>
              <p class="text-text/80 leading-relaxed font-sans">{{ q.explanation }}</p>
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>

