<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { Clock, Layers, CheckCircle2, ArrowLeft, ArrowRight, ShieldCheck, Lock, School, FileCheck2, Award, Calendar, AlertCircle } from 'lucide-vue-next';

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

const isAttemptCompleted = computed(() => {
  return exam.value?.attempt?.status === 'completed' || exam.value?.attempt?.status === 'graded';
});

const isAttemptInProgress = computed(() => {
  return exam.value?.attempt?.status === 'in_progress';
});

const canStart = computed(() => {
  return exam.value?.status === 'active' && !isAttemptCompleted.value;
});

const scorePercent = computed(() => {
  if (exam.value?.attempt?.score === null || exam.value?.attempt?.score === undefined || !exam.value?.total_marks) return null;
  return Math.round((exam.value.attempt.score / exam.value.total_marks) * 100);
});

function gradeFromPercent(pct: number): { letter: string; color: string } {
  if (pct >= 90) return { letter: 'A', color: 'bg-success/15 text-success' };
  if (pct >= 85) return { letter: 'A-', color: 'bg-success/15 text-success' };
  if (pct >= 80) return { letter: 'B+', color: 'bg-accent/15 text-accent' };
  if (pct >= 75) return { letter: 'B', color: 'bg-accent/15 text-accent' };
  if (pct >= 70) return { letter: 'C+', color: 'bg-warning/15 text-warning' };
  if (pct >= 60) return { letter: 'C', color: 'bg-warning/15 text-warning' };
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
  <div class="relative w-full min-h-[calc(100vh-10rem)] flex flex-col items-center justify-center py-6">
    <!-- Ambient glows -->
    <div class="pointer-events-none absolute -top-24 -left-24 w-96 h-96 rounded-full bg-accent/5 blur-3xl" />
    <div class="pointer-events-none absolute -bottom-28 -right-20 w-80 h-80 rounded-full bg-success/5 blur-3xl" />

    <div class="w-full max-w-2xl mx-auto flex flex-col items-center space-y-4">
      <!-- Top Navigation & Status -->
      <div class="w-full flex items-center justify-between text-xs">
        <button
          type="button"
          class="group inline-flex items-center gap-1.5 text-xs text-text/60 hover:text-accent transition-colors font-medium cursor-pointer"
          @click="router.push({ name: 'student.exams.list' })">
          <ArrowLeft class="w-4 h-4 transition-transform group-hover:-translate-x-1" />
          <span>Back to Examination Schedule</span>
        </button>

        <div class="flex items-center gap-1.5 font-mono text-[11px] text-text/60">
          <span class="inline-block w-1.5 h-1.5 rounded-full bg-success animate-pulse" />
          <span>SECURE EXAM PROTOCOL</span>
        </div>
      </div>

      <!-- Loading skeleton -->
      <div v-if="loading" class="w-full bg-surface rounded-2xl border border-border p-8 animate-pulse space-y-6">
        <div class="h-6 bg-border/60 rounded w-1/3 mx-auto" />
        <div class="h-10 bg-border/60 rounded w-3/4 mx-auto" />
        <div class="h-24 bg-border/40 rounded w-full" />
      </div>

      <!-- Main Overview Card (Stitch Reference Design) -->
      <div v-else-if="exam" class="w-full bg-surface rounded-2xl border border-border shadow-md p-6 sm:p-10 flex flex-col relative overflow-hidden space-y-6">
        <!-- Gradient accent bar across top -->
        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-accent via-accent/70 to-border" />

        <!-- Header -->
        <div class="flex flex-col items-center text-center space-y-2.5">
          <!-- Status pill -->
          <div class="flex flex-wrap items-center justify-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-bg border border-border font-mono text-xs text-text/70 uppercase tracking-wider">
              <School class="w-3.5 h-3.5 text-accent" />
              <span>{{ exam.course?.code }} · {{ exam.type }} Assessment</span>
            </span>

            <span v-if="isAttemptInProgress" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-warning/15 text-warning font-mono text-[11px] font-semibold">
              <span class="w-1.5 h-1.5 rounded-full bg-warning animate-pulse" />
              In Progress
            </span>

            <span
              v-else-if="exam.status === 'active' && !isAttemptCompleted"
              class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-success/15 text-success font-mono text-[11px] font-semibold">
              <span class="w-1.5 h-1.5 rounded-full bg-success animate-pulse" />
              Active Now
            </span>
          </div>

          <h1 class="text-2xl sm:text-3xl font-bold font-display text-text tracking-tight max-w-lg">
            {{ exam.title }}
          </h1>

          <!-- Course name & semester if available -->
          <p class="text-xs text-text/60 font-mono">
            {{ exam.course?.name }} <span v-if="exam.semester?.name">· {{ exam.semester.name }}</span>
          </p>

          <p class="text-xs sm:text-sm text-text/60 max-w-md leading-relaxed pt-1">
            Ensure you have a stable network connection and a quiet environment. Once started, your session will be locked to this browser until submission.
          </p>
        </div>

        <!-- 3-box Metric Summary Row -->
        <div class="grid grid-cols-3 gap-3 sm:gap-4 p-3 sm:p-4 rounded-xl bg-bg border border-border/80">
          <div class="flex flex-col items-center justify-center p-3 rounded-lg bg-surface text-center shadow-2xs">
            <span class="text-xs text-text/60 flex items-center gap-1">
              <Clock class="w-3.5 h-3.5 text-accent" />
              Duration
            </span>
            <span class="mt-1 font-mono text-base font-bold text-text"> {{ exam.duration_minutes }} <span class="text-xs font-normal text-text/50">Mins</span> </span>
          </div>

          <div class="flex flex-col items-center justify-center p-3 rounded-lg bg-surface text-center shadow-2xs">
            <span class="text-xs text-text/60 flex items-center gap-1">
              <Layers class="w-3.5 h-3.5 text-accent" />
              Questions
            </span>
            <span class="mt-1 font-mono text-base font-bold text-text"> {{ exam.total_questions }} <span class="text-xs font-normal text-text/50">Items</span> </span>
          </div>

          <div class="flex flex-col items-center justify-center p-3 rounded-lg bg-surface text-center shadow-2xs">
            <span class="text-xs text-text/60 flex items-center gap-1">
              <CheckCircle2 class="w-3.5 h-3.5 text-accent" />
              Total Marks
            </span>
            <span class="mt-1 font-mono text-base font-bold text-text"> {{ exam.total_marks }} <span class="text-xs font-normal text-text/50">Pts</span> </span>
          </div>
        </div>

        <!-- Controlled Environment Warning Box -->
        <div class="p-4 rounded-xl bg-bg border border-border/80 flex items-start gap-3 text-xs">
          <Lock class="w-4 h-4 text-accent shrink-0 mt-0.5" />
          <div class="space-y-0.5 text-left">
            <span class="font-semibold text-text block">Controlled Examination Chamber</span>
            <span class="text-text/60 leading-relaxed">
              Full-screen workstation will activate upon launch. You can navigate freely between questions, save written answers, and review your attempts before final submission.
            </span>
          </div>
        </div>

        <!-- ── In-Progress Attempt Alert ─────────────────────────────── -->
        <div v-if="isAttemptInProgress" class="p-4 rounded-xl bg-warning/10 border border-warning/25 flex items-start gap-3 text-xs">
          <AlertCircle class="w-4 h-4 text-warning shrink-0 mt-0.5" />
          <div class="space-y-0.5 text-left">
            <span class="font-semibold text-text block">Session Already in Progress</span>
            <span class="text-text/70 leading-relaxed">
              You have an active sitting for this examination started at {{ exam.attempt?.started_at }}. Server time continues counting. Click below to resume your assessment
              workstation.
            </span>
          </div>
        </div>

        <!-- ── Completed Attempt Receipt ─────────────────────────────── -->
        <div v-else-if="isAttemptCompleted" class="p-4 rounded-xl bg-success/10 border border-success/20 flex items-center justify-between gap-4">
          <div class="flex items-center gap-3">
            <div
              v-if="scorePercent !== null"
              class="w-12 h-12 rounded-full flex items-center justify-center text-sm font-bold shrink-0 shadow-2xs"
              :class="gradeFromPercent(scorePercent).color">
              {{ gradeFromPercent(scorePercent).letter }}
            </div>
            <div class="text-xs">
              <div class="flex items-center gap-1.5 text-success font-semibold mb-0.5">
                <FileCheck2 class="w-4 h-4" />
                Examination Submitted
              </div>
              <span class="text-text/60 font-mono">
                {{ exam.attempt?.submitted_at || 'Submission Verified' }}
              </span>
            </div>
          </div>
          <span class="font-bold text-text font-mono text-sm">
            {{ exam.attempt?.score !== null && exam.attempt?.score !== undefined ? `${exam.attempt.score} / ${exam.total_marks} pts` : 'Grading in progress' }}
          </span>
        </div>

        <!-- ── Start / Action Area ───────────────────────────────────── -->
        <div class="flex flex-col items-center space-y-2 pt-2">
          <!-- Active Start / Resume CTA -->
          <BaseButton
            v-if="canStart"
            variant="primary"
            :loading="starting"
            class="w-full py-4 px-6 font-semibold shadow-md flex items-center justify-center gap-2 group text-base"
            @click="handleStartOrResume">
            <span>{{ isAttemptInProgress ? 'Resume Examination' : 'Start Examination' }}</span>
            <ArrowRight class="w-4 h-4 transition-transform group-hover:translate-x-1" />
          </BaseButton>

          <!-- Completed State Button -->
          <BaseButton v-else-if="isAttemptCompleted" variant="secondary" class="w-full py-3.5" @click="router.push({ name: 'student.results.list' })">
            <Award class="w-4 h-4 mr-1.5" />
            View Official Records in Portal
          </BaseButton>

          <!-- Scheduled State Box -->
          <div v-else class="w-full p-4 rounded-xl bg-bg border border-border text-center space-y-1 text-xs">
            <div class="flex items-center justify-center gap-1.5 font-semibold text-text">
              <Calendar class="w-4 h-4 text-accent" />
              <span>Examination Window Not Yet Open</span>
            </div>
            <p class="text-text/60">
              Scheduled Start: <span class="font-mono text-text">{{ exam.scheduled_start || 'Announced soon' }}</span>
            </p>
          </div>

          <p class="text-[11px] text-text/50 text-center pt-1">You may review and modify your answers at any point before final submission.</p>
        </div>

        <!-- Footer Meta -->
        <div class="pt-4 border-t border-border flex flex-wrap items-center justify-between gap-2 text-xs text-text/60">
          <div class="flex items-center gap-1.5 text-success font-medium">
            <ShieldCheck class="w-3.5 h-3.5" />
            <span>Session Integrity Verified</span>
          </div>
          <div class="font-mono text-[11px] text-text/50">
            <span>EXAM #{{ exam.id }} · {{ exam.course?.code }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
