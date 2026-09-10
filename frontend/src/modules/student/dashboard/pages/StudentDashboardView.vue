<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { ArrowRight, Award, BookOpen, Calendar, CalendarDays, CheckCircle2, Clock3, ExternalLink, FileCheck, FileText, Play, RefreshCw, TrendingUp } from 'lucide-vue-next';

import BaseBadge from '@/shared/components/ui/BaseBadge.vue';
import BaseButton from '@/shared/components/ui/BaseButton.vue';
import BaseCard from '@/shared/components/ui/BaseCard.vue';

import { useAuthStore } from '@/stores/auth';
import { useUiStore } from '@/stores/ui';
import { handleApiError } from '@/shared/utils/apiError';

import * as api from '../../exams/api/studentExams';
import type { StudentExam } from '../../exams/types/studentExam';

const router = useRouter();
const authStore = useAuthStore();
const uiStore = useUiStore();

const exams = ref<StudentExam[]>([]);
const loading = ref(true);
const refreshing = ref(false);

const studentFullName = computed(() => {
  const user = authStore.user;
  if (!user) return 'Student';
  if (user.first_name && user.last_name) {
    return `${user.first_name} ${user.last_name}`;
  }
  return user.first_name || user.username || 'Student';
});

// Active Semester extracted from exam relations
const activeSemester = computed(() => {
  return exams.value.find((e) => e.semester)?.semester ?? null;
});

function isAttemptDone(e: StudentExam): boolean {
  const s = e.attempt?.status;
  return s === 'completed' || s === 'submitted' || s === 'auto_submitted' || s === 'graded';
}

// Categorized exams
const activeExams = computed(() => {
  return exams.value.filter((e) => e.status === 'active' && !isAttemptDone(e));
});

const featuredActiveExam = computed(() => {
  return activeExams.value[0] || null;
});

const upcomingExams = computed(() => {
  return exams.value.filter((e) => e.status === 'scheduled' && !isAttemptDone(e));
});

const completedExams = computed(() => {
  return exams.value.filter((e) => isAttemptDone(e) || e.status === 'completed' || e.status === 'published');
});

const recentSubmissions = computed(() => {
  return completedExams.value.slice(0, 5);
});

// Graded submissions and computed performance
const gradedExams = computed(() => {
  return completedExams.value.filter((e) => e.attempt?.score !== null && e.attempt?.score !== undefined && Number(e.total_marks) > 0);
});

const pendingGradingCount = computed(() => {
  return completedExams.value.length - gradedExams.value.length;
});

const averageScorePercentage = computed(() => {
  if (!gradedExams.value.length) return null;
  const totalPercentage = gradedExams.value.reduce((acc, e) => {
    return acc + (Number(e.attempt!.score) / Number(e.total_marks)) * 100;
  }, 0);
  return Math.round(totalPercentage / gradedExams.value.length);
});

// Unique enrolled courses extracted from exam records
const enrolledCourses = computed(() => {
  const courseMap = new Map<number, { id: number; code: string; name: string; examCount: number }>();
  for (const exam of exams.value) {
    if (exam.course) {
      const existing = courseMap.get(exam.course.id);
      if (existing) {
        existing.examCount++;
      } else {
        courseMap.set(exam.course.id, {
          id: exam.course.id,
          code: exam.course.code,
          name: exam.course.name,
          examCount: 1,
        });
      }
    }
  }
  return Array.from(courseMap.values());
});

function getScorePercentage(exam: StudentExam): number | null {
  if (exam.attempt?.score === null || exam.attempt?.score === undefined || !exam.total_marks) return null;
  return Math.round((Number(exam.attempt.score) / Number(exam.total_marks)) * 100);
}

function getScoreBadgeVariant(pct: number | null): 'success' | 'warning' | 'danger' | 'neutral' {
  if (pct === null) return 'neutral';
  if (pct >= 80) return 'success';
  if (pct >= 50) return 'warning';
  return 'danger';
}

function formatDate(val?: string | null) {
  if (!val) return '—';
  return new Date(val).toLocaleDateString(undefined, {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  });
}

function formatDateTime(val?: string | null) {
  if (!val) return '—';
  const d = new Date(val);
  if (Number.isNaN(d.getTime())) return val;
  return new Intl.DateTimeFormat('en-US', {
    month: 'short',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  }).format(d);
}

async function loadDashboardData() {
  loading.value = true;
  try {
    const res = await api.getStudentExams(1, 50);
    exams.value = res.data || [];
  } catch (err: any) {
    handleApiError(err, uiStore, undefined, 'Failed to load dashboard data.');
  } finally {
    loading.value = false;
  }
}

async function handleRefresh() {
  refreshing.value = true;
  try {
    const res = await api.getStudentExams(1, 50);
    exams.value = res.data || [];
    uiStore.showToast('Dashboard updated.', 'success');
  } catch (err: any) {
    handleApiError(err, uiStore, undefined, 'Failed to refresh dashboard.');
  } finally {
    refreshing.value = false;
  }
}

onMounted(() => {
  loadDashboardData();
});
</script>

<template>
  <div class="mx-auto w-full max-w-7xl space-y-7 px-4 py-6 md:px-8">
    <!-- Header -->
    <div class="flex flex-col gap-4 border-b border-border pb-6 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <p class="text-xs font-mono font-medium uppercase tracking-wider text-text/40">Student Workspace</p>
        <h1 class="mt-1 font-display text-2xl font-bold tracking-tight text-text md:text-3xl">Welcome back, {{ studentFullName }}.</h1>
        <p class="mt-1 max-w-2xl text-sm text-text/50">View your assigned examinations, track upcoming schedules, and review completed assessment results.</p>
      </div>

      <div class="flex flex-wrap items-center gap-2.5">
        <!-- Active Semester / Academic Term Pill -->
        <div v-if="activeSemester" class="flex items-center gap-2.5 rounded-xl border border-border bg-surface px-3 py-2 text-xs shadow-2xs">
          <CalendarDays class="h-4 w-4 text-text/50 shrink-0" />
          <div class="min-w-0">
            <p class="text-[10px] font-mono font-medium uppercase tracking-wide text-text/40">Academic Term</p>
            <p class="font-medium text-text truncate">
              {{ activeSemester.name }}
            </p>
          </div>
        </div>

        <BaseButton variant="secondary" :loading="refreshing" @click="handleRefresh">
          <template #icon>
            <RefreshCw class="h-4 w-4" :class="{ 'animate-spin': refreshing }" />
          </template>
          Refresh
        </BaseButton>
      </div>
    </div>

    <!-- Skeleton Loading -->
    <div v-if="loading" class="space-y-6 animate-pulse">
      <div class="h-40 rounded-2xl border border-border bg-surface" />
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div v-for="i in 4" :key="i" class="h-24 rounded-xl border border-border bg-surface" />
      </div>
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
          <div class="h-64 rounded-xl border border-border bg-surface" />
          <div class="h-64 rounded-xl border border-border bg-surface" />
        </div>
        <div class="space-y-6">
          <div class="h-48 rounded-xl border border-border bg-surface" />
          <div class="h-48 rounded-xl border border-border bg-surface" />
        </div>
      </div>
    </div>

    <template v-else>
      <!-- ── 1. Featured Active Exam Hero Card (Only when an active exam exists) ── -->
      <div
        v-if="featuredActiveExam"
        class="relative w-full overflow-hidden rounded-2xl border border-accent/30 bg-surface p-6 sm:p-8 shadow-xs transition-all hover:border-accent/50">
        <!-- Left accent line -->
        <div class="absolute bottom-0 left-0 top-0 w-1.5 bg-accent rounded-l-2xl" />

        <div class="relative z-10 flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
          <div class="max-w-2xl space-y-3">
            <div class="flex flex-wrap items-center gap-2">
              <span class="inline-flex items-center gap-1.5 rounded-full bg-success/15 px-3 py-1 font-mono text-xs font-semibold text-success">
                <span class="h-2 w-2 rounded-full bg-success animate-ping" />
                Active Now
              </span>
              <span class="inline-flex rounded bg-bg border border-border px-2.5 py-0.5 font-mono text-xs uppercase font-medium text-text/70">
                {{ featuredActiveExam.type }}
              </span>
            </div>

            <div>
              <p class="font-mono text-xs font-semibold uppercase tracking-wider text-accent">{{ featuredActiveExam.course?.code }} — {{ featuredActiveExam.course?.name }}</p>
              <h2 class="mt-1 font-display text-2xl font-bold tracking-tight text-text sm:text-3xl">
                {{ featuredActiveExam.title }}
              </h2>
            </div>

            <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-text/60 sm:text-sm">
              <div class="flex items-center gap-1.5">
                <Clock3 class="h-4 w-4 text-accent" />
                <span>{{ featuredActiveExam.duration_minutes }} mins</span>
              </div>
              <span class="text-border">·</span>
              <div class="flex items-center gap-1.5">
                <FileText class="h-4 w-4 text-text/45" />
                <span>{{ featuredActiveExam.total_questions }} Questions</span>
              </div>
              <span class="text-border">·</span>
              <div class="flex items-center gap-1.5">
                <CheckCircle2 class="h-4 w-4 text-text/45" />
                <span>{{ featuredActiveExam.total_marks }} Marks</span>
              </div>
            </div>
          </div>

          <div class="flex flex-col items-start gap-2 shrink-0 lg:items-end">
            <BaseButton
              variant="primary"
              class="w-full sm:w-auto lg:w-48 py-3 px-6 font-semibold shadow-xs flex items-center justify-center gap-2 group"
              @click="router.push({ name: 'student.exams.overview', params: { examId: featuredActiveExam.id } })">
              <span>{{ featuredActiveExam.attempt?.status === 'in_progress' ? 'Resume Exam' : 'Start Exam' }}</span>
              <ArrowRight class="h-4 w-4 transition-transform group-hover:translate-x-0.5" />
            </BaseButton>
          </div>
        </div>
      </div>

      <!-- No Active Exam state -->
      <div v-else class="flex flex-col items-start justify-between gap-4 rounded-2xl border border-border bg-surface p-6 shadow-2xs sm:flex-row sm:items-center">
        <div class="flex items-center gap-3.5">
          <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-success/10 text-success">
            <CheckCircle2 class="h-5 w-5" />
          </div>
          <div>
            <h3 class="text-sm font-semibold text-text">No active examinations</h3>
            <p class="mt-0.5 text-xs text-text/50">There are no active exam papers waiting to be taken right now.</p>
          </div>
        </div>
        <BaseButton variant="secondary" @click="router.push({ name: 'student.exams.list' })"> View Examination Timetable </BaseButton>
      </div>

      <!-- ── 2. Stat Cards Row (4 Columns) ──────────────────────────── -->
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Total Registered -->
        <div class="rounded-xl border border-border bg-surface p-5 shadow-2xs">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold uppercase tracking-wider text-text/45">Total Registered</span>
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-bg text-text/50">
              <FileText class="h-4 w-4" />
            </div>
          </div>
          <p class="mt-3 font-display text-2xl font-bold tracking-tight text-text sm:text-3xl">
            {{ exams.length }}
          </p>
          <p class="mt-1 text-xs text-text/50">Assigned assessment papers</p>
        </div>

        <!-- Active Now -->
        <div class="rounded-xl border border-border bg-surface p-5 shadow-2xs">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold uppercase tracking-wider text-text/45">Active Now</span>
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-success/10 text-success">
              <Play class="h-4 w-4" />
            </div>
          </div>
          <p class="mt-3 font-display text-2xl font-bold tracking-tight text-text sm:text-3xl">
            {{ activeExams.length }}
          </p>
          <p class="mt-1 text-xs text-text/50">Ready to sit</p>
        </div>

        <!-- Upcoming Timetable -->
        <div class="rounded-xl border border-border bg-surface p-5 shadow-2xs">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold uppercase tracking-wider text-text/45">Upcoming</span>
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-accent/10 text-accent">
              <Calendar class="h-4 w-4" />
            </div>
          </div>
          <p class="mt-3 font-display text-2xl font-bold tracking-tight text-text sm:text-3xl">
            {{ upcomingExams.length }}
          </p>
          <p class="mt-1 text-xs text-text/50">Scheduled on calendar</p>
        </div>

        <!-- Completed Papers -->
        <div class="rounded-xl border border-border bg-surface p-5 shadow-2xs">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold uppercase tracking-wider text-text/45">Completed</span>
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-success/10 text-success">
              <FileCheck class="h-4 w-4" />
            </div>
          </div>
          <p class="mt-3 font-display text-2xl font-bold tracking-tight text-text sm:text-3xl">
            {{ completedExams.length }}
          </p>
          <p class="mt-1 text-xs text-text/50">Submitted papers</p>
        </div>
      </div>

      <!-- ── 3. Quick Links / Workspace Overview ─────────────────────── -->
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <BaseCard hover class="cursor-pointer transition-all hover:border-accent/40" @click="router.push({ name: 'student.exams.list' })">
          <div class="flex items-start justify-between">
            <div>
              <p class="text-xs font-mono font-medium uppercase tracking-wider text-text/40">Timetable & Sitting</p>
              <h3 class="mt-1 text-base font-semibold text-text">Examinations</h3>
              <p class="mt-1 text-xs text-text/50">Browse all scheduled, active, and past examination papers.</p>
            </div>
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-bg text-text/60">
              <Calendar class="h-4 w-4" />
            </div>
          </div>
          <div class="mt-4 flex items-center justify-between border-t border-border/60 pt-3 text-xs">
            <span class="font-mono font-medium text-text/60">{{ exams.length }} Total Papers</span>
            <span class="inline-flex items-center gap-1 font-medium text-accent hover:underline">
              View Schedule
              <ArrowRight class="h-3.5 w-3.5" />
            </span>
          </div>
        </BaseCard>

        <BaseCard hover class="cursor-pointer transition-all hover:border-accent/40" @click="router.push({ name: 'student.results.list' })">
          <div class="flex items-start justify-between">
            <div>
              <p class="text-xs font-mono font-medium uppercase tracking-wider text-text/40">Grades & Records</p>
              <h3 class="mt-1 text-base font-semibold text-text">Examination Results</h3>
              <p class="mt-1 text-xs text-text/50">View your scores, grading statuses, and evaluated papers.</p>
            </div>
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-bg text-text/60">
              <Award class="h-4 w-4" />
            </div>
          </div>
          <div class="mt-4 flex items-center justify-between border-t border-border/60 pt-3 text-xs">
            <span class="font-mono font-medium text-text/60">{{ completedExams.length }} Submissions</span>
            <span class="inline-flex items-center gap-1 font-medium text-accent hover:underline">
              View Results
              <ArrowRight class="h-3.5 w-3.5" />
            </span>
          </div>
        </BaseCard>
      </div>

      <!-- ── 4. Main Section (2/3 Left, 1/3 Right) ────────────────────── -->
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- ── Left Column: Timetable & Recent Submissions ────────────── -->
        <div class="space-y-6 lg:col-span-2">
          <!-- Upcoming Examinations -->
          <div class="rounded-xl border border-border bg-surface shadow-sm overflow-hidden">
            <div class="flex items-center justify-between border-b border-border px-6 py-4">
              <div>
                <h2 class="text-base font-semibold text-text">Upcoming Examination Timetable</h2>
                <p class="text-xs text-text/45">Scheduled exams for your enrolled courses.</p>
              </div>
              <BaseButton variant="ghost" size="sm" @click="router.push({ name: 'student.exams.list' })">
                <span>View all</span>
                <template #icon>
                  <ArrowRight class="h-3.5 w-3.5" />
                </template>
              </BaseButton>
            </div>

            <div v-if="upcomingExams.length" class="divide-y divide-border">
              <div
                v-for="exam in upcomingExams.slice(0, 5)"
                :key="exam.id"
                class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-5 transition-colors hover:bg-bg/40">
                <div class="space-y-1 min-w-0">
                  <div class="flex items-center gap-2">
                    <span class="font-mono text-xs font-bold text-accent">
                      {{ exam.course?.code }}
                    </span>
                    <span class="text-border">•</span>
                    <span class="inline-flex rounded bg-bg border border-border px-2 py-0.5 font-mono text-[10px] uppercase font-medium text-text/70">
                      {{ exam.type }}
                    </span>
                  </div>
                  <h3 class="text-sm font-bold text-text truncate">
                    {{ exam.title }}
                  </h3>
                  <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-text/50">
                    <span class="inline-flex items-center gap-1">
                      <CalendarDays class="h-3.5 w-3.5 text-text/40" />
                      {{ formatDateTime(exam.scheduled_start) }}
                    </span>
                    <span>•</span>
                    <span class="inline-flex items-center gap-1">
                      <Clock3 class="h-3.5 w-3.5 text-text/40" />
                      {{ exam.duration_minutes }} mins
                    </span>
                    <span>•</span>
                    <span>{{ exam.total_marks }} marks</span>
                  </div>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                  <BaseButton variant="secondary" size="sm" @click="router.push({ name: 'student.exams.overview', params: { examId: exam.id } })"> View Details </BaseButton>
                </div>
              </div>
            </div>

            <div v-else class="flex flex-col items-center justify-center p-12 text-center text-text/45">
              <Calendar class="mb-2 h-8 w-8 text-text/25" />
              <p class="text-sm font-medium">No upcoming exams scheduled</p>
              <p class="mt-0.5 text-xs text-text/40">New schedules will appear here as instructors set dates.</p>
            </div>
          </div>

          <!-- Recent Submissions & Performance -->
          <div class="rounded-xl border border-border bg-surface shadow-sm overflow-hidden">
            <div class="flex items-center justify-between border-b border-border px-6 py-4">
              <div>
                <h2 class="text-base font-semibold text-text">Recent Submissions & Performance</h2>
                <p class="text-xs text-text/45">Performance and evaluation records for completed assessments.</p>
              </div>
              <BaseButton variant="ghost" size="sm" @click="router.push({ name: 'student.results.list' })">
                <span>All Results</span>
                <template #icon>
                  <ArrowRight class="h-3.5 w-3.5" />
                </template>
              </BaseButton>
            </div>

            <div v-if="recentSubmissions.length" class="divide-y divide-border">
              <div v-for="sub in recentSubmissions" :key="sub.id" class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-5 transition-colors hover:bg-bg/40">
                <div class="space-y-1.5 min-w-0">
                  <div class="flex items-center gap-2">
                    <span class="font-mono text-xs font-semibold text-text/60">
                      {{ sub.course?.code }}
                    </span>
                    <span class="text-border">•</span>
                    <span class="text-xs text-text/45"> Submitted {{ sub.attempt?.submitted_at || formatDate(sub.created_at) }} </span>
                  </div>
                  <h3 class="text-sm font-bold text-text truncate">
                    {{ sub.title }}
                  </h3>

                  <!-- Progress Bar for Graded Papers -->
                  <div v-if="getScorePercentage(sub) !== null" class="flex items-center gap-3 pt-1">
                    <div class="h-1.5 w-36 rounded-full bg-bg overflow-hidden border border-border">
                      <div
                        class="h-full rounded-full transition-all"
                        :class="(getScorePercentage(sub) ?? 0) >= 80 ? 'bg-success' : (getScorePercentage(sub) ?? 0) >= 50 ? 'bg-accent' : 'bg-error'"
                        :style="{ width: `${getScorePercentage(sub)}%` }" />
                    </div>
                    <span class="font-mono text-xs font-bold text-text/75"> {{ getScorePercentage(sub) }}% </span>
                  </div>
                </div>

                <div class="flex items-center gap-3 shrink-0">
                  <div v-if="sub.attempt?.score !== null && sub.attempt?.score !== undefined" class="text-right">
                    <p class="font-mono text-base font-bold text-text">
                      {{ sub.attempt.score }} <span class="text-xs text-text/45">/ {{ sub.total_marks }}</span>
                    </p>
                    <BaseBadge :variant="getScoreBadgeVariant(getScorePercentage(sub))" size="sm"> Graded </BaseBadge>
                  </div>
                  <div v-else class="text-right">
                    <BaseBadge variant="neutral" size="sm"> Pending Grading </BaseBadge>
                  </div>

                  <BaseButton variant="ghost" size="sm" @click="router.push({ name: 'student.exams.overview', params: { examId: sub.id } })">
                    <template #icon>
                      <ExternalLink class="h-3.5 w-3.5" />
                    </template>
                  </BaseButton>
                </div>
              </div>
            </div>

            <div v-else class="flex flex-col items-center justify-center p-12 text-center text-text/45">
              <FileCheck class="mb-2 h-8 w-8 text-text/25" />
              <p class="text-sm font-medium">No completed submissions yet</p>
              <p class="mt-0.5 text-xs text-text/40">When you complete an exam, results and score reports will show here.</p>
            </div>
          </div>
        </div>

        <!-- ── Right Column: Registered Courses & Academic Performance ─ -->
        <div class="space-y-6">
          <!-- Registered Courses Scope -->
          <div class="rounded-xl border border-border bg-surface p-5 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="text-sm font-bold text-text">Registered Courses</h3>
                <p class="text-xs text-text/45">Courses with examination assignments</p>
              </div>
              <span class="rounded-md bg-accent/10 px-2 py-0.5 font-mono text-xs font-bold text-accent">
                {{ enrolledCourses.length }}
              </span>
            </div>

            <div v-if="enrolledCourses.length" class="space-y-2">
              <div v-for="c in enrolledCourses" :key="c.id" class="flex items-center justify-between rounded-lg border border-border bg-bg/50 p-3 transition-colors hover:bg-bg">
                <div class="min-w-0">
                  <span class="font-mono text-xs font-bold text-accent">{{ c.code }}</span>
                  <p class="text-xs font-medium text-text truncate">{{ c.name }}</p>
                </div>
                <span class="rounded bg-surface border border-border px-2 py-0.5 font-mono text-[11px] text-text/60 shrink-0">
                  {{ c.examCount }} paper{{ c.examCount === 1 ? '' : 's' }}
                </span>
              </div>
            </div>

            <div v-else class="text-center py-6 text-xs text-text/40">No registered courses found.</div>
          </div>

          <!-- Academic Performance Summary (Calculated from real attempt data) -->
          <div class="rounded-xl border border-border bg-surface p-5 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <TrendingUp class="h-4 w-4 text-accent" />
                <h3 class="text-sm font-bold text-text">Academic Performance</h3>
              </div>
            </div>

            <div v-if="gradedExams.length" class="space-y-3">
              <div class="flex items-baseline justify-between">
                <span class="text-xs text-text/60">Average Grade</span>
                <span class="font-display text-2xl font-bold text-text"> {{ averageScorePercentage }}% </span>
              </div>

              <div class="h-2 w-full rounded-full bg-bg overflow-hidden border border-border">
                <div
                  class="h-full rounded-full transition-all"
                  :class="(averageScorePercentage ?? 0) >= 80 ? 'bg-success' : (averageScorePercentage ?? 0) >= 50 ? 'bg-accent' : 'bg-error'"
                  :style="{ width: `${averageScorePercentage}%` }" />
              </div>

              <div class="flex items-center justify-between pt-2 border-t border-border text-xs text-text/50 font-mono">
                <span>{{ gradedExams.length }} Graded</span>
                <span>{{ pendingGradingCount }} Pending</span>
              </div>
            </div>

            <div v-else class="text-center py-6 text-xs text-text/40">
              <Award class="mx-auto mb-1.5 h-6 w-6 text-text/25" />
              <p>No graded assessments available yet.</p>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
