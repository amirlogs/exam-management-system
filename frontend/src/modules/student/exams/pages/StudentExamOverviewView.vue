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
  School,
  FileCheck2,
  Award,
  Calendar,
  AlertCircle,
  Check,
} from 'lucide-vue-next';

import BaseButton from '@/shared/components/ui/BaseButton.vue';
import { useUiStore } from '@/stores/ui';
import { handleApiError } from '@/shared/utils/apiError';

import * as api from '../api/studentExams';
import type { StudentExam } from '../types/studentExam';

const route = useRoute();
const router = useRouter();
const uiStore = useUiStore();

const examId = Number(route.params.examId);
const exam = ref<StudentExam | null>(null);
const loading = ref(true);
const starting = ref(false);

async function loadExam() {
  loading.value = true;
  try {
    const res = await api.getStudentExam(examId);
    exam.value = res.data;
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

const isGraded = computed(() => {
  return exam.value?.attempt?.status === 'graded' || (exam.value?.attempt?.score !== null && exam.value?.attempt?.score !== undefined);
});

const isAttemptInProgress = computed(() => {
  return exam.value?.attempt?.status === 'in_progress';
});

const canStart = computed(() => {
  return exam.value?.status === 'active' && !isAttemptDone.value;
});

const scorePercent = computed(() => {
  if (exam.value?.attempt?.score === null || exam.value?.attempt?.score === undefined) return null;
  const total = Number(exam.value?.total_marks);
  if (!total || total <= 0) return null;
  return Math.round((Number(exam.value.attempt.score) / total) * 100);
});

function gradeFromPercent(pct: number): { letter: string; color: string } {
  if (pct >= 90) return { letter: 'A', color: 'bg-success/15 text-success' };
  if (pct >= 85) return { letter: 'A-', color: 'bg-success/15 text-success' };
  if (pct >= 80) return { letter: 'B+', color: 'bg-accent/15 text-accent' };
  if (pct >= 75) return { letter: 'B', color: 'bg-accent/15 text-accent' };
  if (pct >= 70) return { letter: 'C+', color: 'bg-warning/15 text-warning' };
  if (pct >= 60) return { letter: 'C', color: 'bg-warning/15 text-warning' };
  if (pct >= 50) return { letter: 'D', color: 'bg-warning/15 text-warning' };
  return { letter: 'F', color: 'bg-error/15 text-error' };
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
  <div class="w-full min-h-[calc(100vh-10rem)] flex flex-col items-center justify-center py-8 px-4">
    <div class="w-full max-w-xl mx-auto space-y-4">
      <!-- Back Link -->
      <button
        type="button"
        class="inline-flex items-center gap-2 text-sm font-medium text-text/60 transition-colors hover:text-accent cursor-pointer mb-1"
        @click="router.push({ name: 'student.exams.list' })"
      >
        <ArrowLeft class="w-4 h-4" />
        <span>Back to Exams</span>
      </button>

      <!-- Loading skeleton -->
      <div v-if="loading" class="w-full bg-surface rounded-2xl border border-border p-8 animate-pulse space-y-5">
        <div class="h-5 bg-border/60 rounded w-1/3" />
        <div class="h-8 bg-border/60 rounded w-3/4" />
        <div class="h-20 bg-border/40 rounded w-full" />
      </div>

      <!-- Main Overview / Card -->
      <div v-else-if="exam" class="w-full bg-surface rounded-2xl border border-border p-6 sm:p-8 flex flex-col space-y-6 shadow-xs">
        <!-- Header -->
        <div class="space-y-2">
          <div class="flex items-center justify-between gap-2">
            <span class="font-mono text-xs font-semibold text-accent uppercase tracking-wider">
              {{ exam.course?.code }} · {{ exam.course?.name }}
            </span>

            <!-- Accurate State Badge -->
            <span v-if="isGraded" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-success/15 text-success font-mono text-[11px] font-semibold">
              <CheckCircle2 class="w-3.5 h-3.5" />
              Graded
            </span>
            <span v-else-if="isAttemptDone" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-accent/10 text-accent font-mono text-[11px] font-semibold">
              <Check class="w-3.5 h-3.5" />
              Submitted
            </span>
            <span v-else-if="isAttemptInProgress" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-warning/15 text-warning font-mono text-[11px] font-semibold">
              <span class="w-1.5 h-1.5 rounded-full bg-warning animate-pulse" />
              In Progress
            </span>
            <span v-else-if="canStart" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-success/15 text-success font-mono text-[11px] font-semibold">
              <span class="w-1.5 h-1.5 rounded-full bg-success animate-pulse" />
              Active
            </span>
            <span v-else-if="exam.status === 'completed'" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-bg border border-border text-text/50 font-mono text-[11px] font-medium">
              Concluded
            </span>
            <span v-else class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-bg border border-border text-text/60 font-mono text-[11px] font-medium">
              <Calendar class="w-3 h-3" />
              Scheduled
            </span>
          </div>

          <h1 class="text-2xl sm:text-3xl font-bold font-display text-text tracking-tight">
            {{ exam.title }}
          </h1>

          <p v-if="exam.semester?.name" class="text-xs text-text/50 font-mono">
            Semester {{ exam.semester.name }} <span class="capitalize">· {{ exam.type }} Assessment</span>
          </p>
        </div>

        <!-- Metric Details Row -->
        <div class="grid grid-cols-3 gap-3 p-4 rounded-xl bg-bg border border-border text-center">
          <div class="space-y-1">
            <span class="text-xs text-text/50 flex items-center justify-center gap-1">
              <Clock class="w-3.5 h-3.5 text-accent" />
              Duration
            </span>
            <p class="font-mono text-base font-bold text-text">{{ exam.duration_minutes }} <span class="text-xs font-normal text-text/50">mins</span></p>
          </div>

          <div class="space-y-1">
            <span class="text-xs text-text/50 flex items-center justify-center gap-1">
              <Layers class="w-3.5 h-3.5 text-accent" />
              Questions
            </span>
            <p class="font-mono text-base font-bold text-text">
              {{ exam.total_questions }}
            </p>
          </div>

          <div class="space-y-1">
            <span class="text-xs text-text/50 flex items-center justify-center gap-1">
              <Award class="w-3.5 h-3.5 text-accent" />
              Total Marks
            </span>
            <p class="font-mono text-base font-bold text-text">{{ exam.total_marks }} <span class="text-xs font-normal text-text/50">pts</span></p>
          </div>
        </div>

        <!-- Graded Result Card -->
        <div v-if="isGraded" class="p-4 rounded-xl bg-success/10 border border-success/20 flex items-center justify-between gap-4">
          <div class="flex items-center gap-3">
            <div
              v-if="scorePercent !== null"
              class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold shrink-0 font-mono"
              :class="gradeFromPercent(scorePercent).color"
            >
              {{ gradeFromPercent(scorePercent).letter }}
            </div>
            <div class="text-xs space-y-0.5">
              <div class="flex items-center gap-1.5 text-success font-semibold">
                <CheckCircle2 class="w-4 h-4" />
                Assessment Graded
              </div>
              <span v-if="exam.attempt?.submitted_at" class="text-text/50 font-mono text-[11px] block">
                Submitted: {{ exam.attempt.submitted_at }}
              </span>
            </div>
          </div>
          <div class="text-right">
            <span class="font-bold text-text font-mono text-base block">
              {{ exam.attempt?.score }} / {{ exam.total_marks }} pts
            </span>
            <span v-if="scorePercent !== null" class="text-xs text-text/60 font-mono">
              {{ scorePercent }}% Score
            </span>
          </div>
        </div>

        <!-- Submitted But Awaiting Grading -->
        <div v-else-if="isAttemptDone" class="p-4 rounded-xl bg-accent/10 border border-accent/20 flex items-center justify-between gap-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-surface border border-border flex items-center justify-center text-xs font-mono font-bold text-text/70">
              ✓
            </div>
            <div class="text-xs space-y-0.5">
              <span class="font-semibold text-text block">Submission Received</span>
              <span class="text-text/60 block">Your responses have been saved and are pending grading.</span>
            </div>
          </div>
          <span class="text-xs font-semibold text-accent px-2.5 py-1 rounded-full bg-accent/10 whitespace-nowrap">
            Under Review
          </span>
        </div>

        <!-- In-Progress Alert -->
        <div v-else-if="isAttemptInProgress" class="p-4 rounded-xl bg-warning/10 border border-warning/25 flex items-start gap-3 text-xs">
          <AlertCircle class="w-4 h-4 text-warning shrink-0 mt-0.5" />
          <div class="space-y-0.5 text-left">
            <span class="font-semibold text-text block">Assessment Session Active</span>
            <span class="text-text/70 leading-relaxed">
              You have an active examination attempt in progress. Timer continues to elapse.
            </span>
          </div>
        </div>

        <!-- Scheduled Notice -->
        <div v-else-if="!canStart && exam.status === 'scheduled'" class="p-4 rounded-xl bg-bg border border-border text-center space-y-1 text-xs">
          <div class="flex items-center justify-center gap-1.5 font-semibold text-text">
            <Calendar class="w-4 h-4 text-accent" />
            <span>Examination Scheduled</span>
          </div>
          <p class="text-text/60">
            Window Opens: <span class="font-mono text-text font-medium">{{ exam.scheduled_start || 'To be announced' }}</span>
          </p>
        </div>

        <!-- Closed / Missed Exam Notice -->
        <div v-else-if="!canStart && exam.status === 'completed'" class="p-4 rounded-xl bg-bg border border-border text-center space-y-1 text-xs">
          <span class="font-semibold text-text/70 block">Examination Concluded</span>
          <p class="text-text/50">The examination window for this assessment has ended.</p>
        </div>

        <!-- Action Button -->
        <div class="pt-2">
          <BaseButton
            v-if="canStart"
            variant="primary"
            :loading="starting"
            class="w-full py-3.5 px-6 font-semibold shadow-xs flex items-center justify-center gap-2 group text-sm"
            @click="handleStartOrResume"
          >
            <span>{{ isAttemptInProgress ? 'Resume Exam' : 'Start Exam' }}</span>
            <ArrowRight class="w-4 h-4 transition-transform group-hover:translate-x-0.5" />
          </BaseButton>

          <BaseButton
            v-else-if="isGraded"
            variant="secondary"
            class="w-full py-3 font-semibold"
            @click="router.push({ name: 'student.results.list' })"
          >
            <Award class="w-4 h-4 mr-1.5" />
            View Results Record
          </BaseButton>
        </div>
      </div>
    </div>
  </div>
</template>
