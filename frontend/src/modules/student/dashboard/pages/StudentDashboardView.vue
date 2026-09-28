<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import {
  AlertCircle,
  ArrowRight,
  Award,
  BookOpen,
  Calendar,
  CalendarDays,
  CheckCircle2,
  ChevronRight,
  Clock3,
  ExternalLink,
  FileCheck,
  FileText,
  Play,
  RefreshCw,
  Sparkles,
  TrendingUp,
  UserRound,
} from 'lucide-vue-next';

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

const currentDate = ref(new Intl.DateTimeFormat('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' }).format(new Date()));
let timer: ReturnType<typeof setInterval>;

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

// Unified overview KPI items
const overviewItems = computed(() => [
  {
    label: 'Enrolled Courses',
    value: enrolledCourses.value.length,
    to: '/student/exams',
    icon: BookOpen,
  },
  {
    label: 'Registered Exams',
    value: exams.value.length,
    to: '/student/exams',
    icon: FileText,
  },
  {
    label: 'Upcoming Timetable',
    value: upcomingExams.value.length,
    to: '/student/exams',
    icon: CalendarDays,
  },
  {
    label: 'Completed Papers',
    value: completedExams.value.length,
    to: '/student/results',
    icon: FileCheck,
  },
]);

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
  timer = setInterval(() => {
    currentDate.value = new Intl.DateTimeFormat('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' }).format(new Date());
  }, 60000);
});

onUnmounted(() => {
  clearInterval(timer);
});
</script>

<template>
  <div class="mx-auto w-full max-w-7xl px-4 py-6 md:px-8 space-y-8">
    <!-- 1. Executive Header & Academic Term Command Strip -->
    <header class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
      <div>
        <p class="text-[11px] font-mono font-medium uppercase tracking-widest text-text/40 mb-1">Student Portal</p>
        <h1 class="font-display text-2xl sm:text-3xl font-bold tracking-tight text-text">
          Welcome back, {{ studentFullName }}
        </h1>
        <p class="mt-1.5 text-sm text-text/50 font-medium">
          {{ currentDate }}
        </p>
      </div>

      <div class="flex items-center gap-3">
        <!-- Active Semester -->
        <span v-if="activeSemester" class="text-xs sm:text-sm text-text/60 font-medium">
          {{ activeSemester.name }} ({{ activeSemester.academic_year }})
        </span>

        <!-- Refresh Button -->
        <button
          type="button"
          :disabled="refreshing || loading"
          class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-border bg-surface text-text/60 transition-colors hover:border-accent/40 hover:text-accent disabled:opacity-50 cursor-pointer shadow-sm"
          title="Refresh"
          aria-label="Refresh"
          @click="handleRefresh">
          <RefreshCw class="h-4 w-4" :class="{ 'animate-spin': refreshing }" />
        </button>
      </div>
    </header>

    <!-- Quick Tools / Shortcuts Bar -->
    <div class="flex flex-wrap items-center gap-3">
      <router-link to="/student/exams" class="group flex items-center gap-2.5 rounded-full border border-border bg-surface px-4 py-2 text-xs font-medium text-text/70 transition-all hover:border-accent/30 hover:bg-accent/5 hover:text-accent shadow-sm">
        <FileText class="w-3.5 h-3.5" />
        My Examinations
      </router-link>
      <router-link to="/student/practice" class="group flex items-center gap-2.5 rounded-full border border-border bg-surface px-4 py-2 text-xs font-medium text-text/70 transition-all hover:border-accent/30 hover:bg-accent/5 hover:text-accent shadow-sm">
        <Sparkles class="w-3.5 h-3.5" />
        Practice Hub
      </router-link>
      <router-link to="/student/results" class="group flex items-center gap-2.5 rounded-full border border-border bg-surface px-4 py-2 text-xs font-medium text-text/70 transition-all hover:border-accent/30 hover:bg-accent/5 hover:text-accent shadow-sm">
        <Award class="w-3.5 h-3.5" />
        My Results
      </router-link>
      <router-link to="/profile" class="group flex items-center gap-2.5 rounded-full border border-border bg-surface px-4 py-2 text-xs font-medium text-text/70 transition-all hover:border-accent/30 hover:bg-accent/5 hover:text-accent shadow-sm">
        <UserRound class="w-3.5 h-3.5" />
        Profile &amp; Settings
      </router-link>
    </div>

    <!-- Skeleton Loading -->
    <div v-if="loading" class="space-y-6 animate-pulse">
      <div class="h-32 rounded-2xl border border-border bg-surface" />
      <div class="h-24 rounded-2xl border border-border bg-surface" />
      <div class="grid grid-cols-1 gap-6 xl:grid-cols-5">
        <div class="xl:col-span-3 h-80 rounded-2xl border border-border bg-surface" />
        <div class="xl:col-span-2 h-80 rounded-2xl border border-border bg-surface" />
      </div>
    </div>

    <template v-else>
      <!-- ── 2. Featured Active Exam Hero Card (When an active exam exists) ── -->
      <div
        v-if="featuredActiveExam"
        class="relative w-full overflow-hidden rounded-2xl border border-accent/40 bg-surface p-6 sm:p-7 shadow-sm transition-all hover:border-accent/60">
        <!-- Left accent line -->
        <div class="absolute bottom-0 left-0 top-0 w-1.5 bg-accent rounded-l-2xl" />

        <div class="relative z-10 flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
          <div class="max-w-2xl space-y-2.5">
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

            <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-text/60 sm:text-sm font-mono">
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

      <!-- ── 3. Unified Student Academic KPI Deck ── -->
      <section>
        <div class="rounded-2xl border border-border bg-surface shadow-sm overflow-hidden">
          <div class="grid grid-cols-2 lg:grid-cols-4 divide-y lg:divide-y-0 sm:divide-x divide-border">
            <router-link
              v-for="item in overviewItems"
              :key="item.label"
              :to="item.to"
              class="group relative flex flex-col p-5 transition-colors hover:bg-bg/50 focus:outline-none focus-visible:bg-bg/50">
              <div class="flex items-center justify-between mb-4">
                <span class="text-[10px] font-mono text-text/45 uppercase tracking-wider">{{ item.label }}</span>
                <component :is="item.icon" class="h-4 w-4 text-text/30 group-hover:text-accent transition-colors" />
              </div>
              <div class="flex items-end justify-between">
                <div v-if="loading" class="h-8 w-16 animate-pulse rounded bg-text/5" />
                <span v-else class="font-display text-2xl sm:text-3xl font-bold tracking-tight text-text tabular-nums">{{ item.value }}</span>
                <ChevronRight class="h-4 w-4 text-text/20 transition-all group-hover:text-accent group-hover:translate-x-0.5 mb-1" />
              </div>
            </router-link>
          </div>
        </div>
      </section>

      <!-- ── 4. Operational Pulse (Two-column Command Center) ── -->
      <div class="grid grid-cols-1 gap-6 xl:grid-cols-5">
        <!-- Column A: Upcoming Examination Timetable -->
        <section class="xl:col-span-3 rounded-2xl border border-border bg-surface shadow-sm overflow-hidden flex flex-col">
          <div class="px-6 py-5 border-b border-border flex items-center justify-between">
            <div>
              <h2 class="text-sm font-semibold text-text">Upcoming Examination Timetable</h2>
              <p class="text-xs text-text/50 mt-1">Scheduled examination dates for your registered courses.</p>
            </div>
            <router-link to="/student/exams" class="text-xs font-medium text-accent hover:text-accent/80 transition-colors flex items-center gap-1">
              <span>View all</span>
              <ArrowRight class="h-3 w-3" />
            </router-link>
          </div>

          <div v-if="upcomingExams.length" class="divide-y divide-border flex-1">
            <div
              v-for="exam in upcomingExams.slice(0, 5)"
              :key="exam.id"
              class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-5 transition-colors hover:bg-bg/50">
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
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-text/50 font-mono">
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
                <BaseButton variant="secondary" size="sm" @click="router.push({ name: 'student.exams.overview', params: { examId: exam.id } })">
                  View Details
                </BaseButton>
              </div>
            </div>
          </div>

          <div v-else class="flex flex-1 flex-col items-center justify-center p-12 text-center text-text/45">
            <Calendar class="mb-2 h-8 w-8 text-text/25" />
            <p class="text-sm font-medium text-text">No upcoming exams scheduled</p>
            <p class="mt-0.5 text-xs text-text/40 max-w-xs">New schedules will appear here as your instructors publish examination timetables.</p>
          </div>
        </section>

        <!-- Column B: Academic Standing & Progress -->
        <section class="xl:col-span-2 rounded-2xl border border-border bg-surface shadow-sm overflow-hidden flex flex-col justify-between">
          <div class="px-6 py-5 border-b border-border">
            <h2 class="text-sm font-semibold text-text">Academic Standing &amp; Progress</h2>
            <p class="text-xs text-text/50 mt-1">Evaluation metrics across your enrolled courses.</p>
          </div>

          <div class="p-6 space-y-6 flex-1 flex flex-col justify-between">
            <!-- Academic Performance Dial -->
            <div v-if="gradedExams.length" class="space-y-3">
              <div class="flex items-baseline justify-between">
                <span class="text-xs font-medium text-text/60">Average Examination Score</span>
                <span class="font-display text-2xl font-bold text-text tabular-nums"> {{ averageScorePercentage }}% </span>
              </div>

              <div class="h-2.5 w-full rounded-full bg-bg overflow-hidden border border-border">
                <div
                  class="h-full rounded-full transition-all duration-500"
                  :class="(averageScorePercentage ?? 0) >= 80 ? 'bg-success' : (averageScorePercentage ?? 0) >= 50 ? 'bg-accent' : 'bg-error'"
                  :style="{ width: `${averageScorePercentage}%` }" />
              </div>

              <div class="flex items-center justify-between pt-2 border-t border-border text-xs text-text/50 font-mono">
                <span class="text-success font-medium">{{ gradedExams.length }} Graded</span>
                <span class="text-text/60">{{ pendingGradingCount }} Pending Evaluation</span>
              </div>
            </div>

            <div v-else class="text-center py-6 text-xs text-text/40">
              <Award class="mx-auto mb-2 h-7 w-7 text-text/25" />
              <p class="font-medium text-text/60">No graded assessments available yet</p>
            </div>

            <!-- Registered Courses Scope -->
            <div class="space-y-3 pt-4 border-t border-border">
              <div class="flex items-center justify-between text-xs">
                <span class="font-medium text-text/70">Registered Courses Scope</span>
                <span class="font-mono text-xs font-bold text-accent">{{ enrolledCourses.length }} active</span>
              </div>

              <div v-if="enrolledCourses.length" class="space-y-2">
                <div
                  v-for="c in enrolledCourses.slice(0, 3)"
                  :key="c.id"
                  class="flex items-center justify-between rounded-xl border border-border bg-bg/50 p-2.5 transition-colors hover:bg-bg">
                  <div class="min-w-0 pr-2">
                    <span class="font-mono text-[11px] font-bold text-accent block">{{ c.code }}</span>
                    <p class="text-xs font-medium text-text truncate">{{ c.name }}</p>
                  </div>
                  <span class="rounded-lg bg-surface border border-border px-2 py-0.5 font-mono text-[10px] text-text/60 shrink-0">
                    {{ c.examCount }} paper{{ c.examCount === 1 ? '' : 's' }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </section>
      </div>

      <!-- ── 5. Recent Submissions & Performance Registry ── -->
      <section class="rounded-2xl border border-border bg-surface shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-border flex items-center justify-between">
          <div>
            <h2 class="text-sm font-semibold text-text">Recent Submissions &amp; Results</h2>
            <p class="text-xs text-text/50 mt-1">Performance and evaluation records for completed assessments.</p>
          </div>
          <router-link to="/student/results" class="text-xs font-medium text-accent hover:text-accent/80 transition-colors flex items-center gap-1">
            <span>View all results</span>
            <ArrowRight class="h-3 w-3" />
          </router-link>
        </div>

        <div v-if="recentSubmissions.length" class="divide-y divide-border">
          <div
            v-for="sub in recentSubmissions"
            :key="sub.id"
            class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-5 transition-colors hover:bg-bg/50 cursor-pointer"
            @click="router.push({ name: 'student.exams.overview', params: { examId: sub.id } })">
            <div class="space-y-1.5 min-w-0">
              <div class="flex items-center gap-2">
                <span class="font-mono text-xs font-bold text-accent">
                  {{ sub.course?.code }}
                </span>
                <span class="text-border">•</span>
                <span class="text-xs text-text/45 font-mono">Submitted {{ sub.attempt?.submitted_at ? formatDateTime(sub.attempt.submitted_at) : formatDate(sub.created_at) }}</span>
              </div>
              <h3 class="text-sm font-bold text-text truncate">
                {{ sub.title }}
              </h3>

              <!-- Score Percentage Bar -->
              <div v-if="getScorePercentage(sub) !== null" class="flex items-center gap-3 pt-0.5">
                <div class="h-1.5 w-32 rounded-full bg-bg overflow-hidden border border-border">
                  <div
                    class="h-full rounded-full transition-all duration-300"
                    :class="(getScorePercentage(sub) ?? 0) >= 80 ? 'bg-success' : (getScorePercentage(sub) ?? 0) >= 50 ? 'bg-accent' : 'bg-error'"
                    :style="{ width: `${getScorePercentage(sub)}%` }" />
                </div>
                <span class="font-mono text-xs font-bold text-text/75"> {{ getScorePercentage(sub) }}% </span>
              </div>
            </div>

            <div class="flex items-center gap-4 shrink-0">
              <div v-if="sub.attempt?.score !== null && sub.attempt?.score !== undefined" class="text-right">
                <p class="font-mono text-base font-bold text-text">
                  {{ sub.attempt.score }} <span class="text-xs text-text/45">/ {{ sub.total_marks }}</span>
                </p>
                <BaseBadge :variant="getScoreBadgeVariant(getScorePercentage(sub))" size="sm"> Graded </BaseBadge>
              </div>
              <div v-else class="text-right">
                <BaseBadge variant="neutral" size="sm"> Under Review </BaseBadge>
              </div>

              <ChevronRight class="h-4 w-4 text-text/30" />
            </div>
          </div>
        </div>

        <div v-else class="flex flex-col items-center justify-center p-12 text-center text-text/45">
          <FileCheck class="mb-2 h-8 w-8 text-text/25" />
          <p class="text-sm font-medium text-text">No completed submissions yet</p>
          <p class="mt-0.5 text-xs text-text/40">When you complete an exam, results and score reports will show here.</p>
        </div>
      </section>
    </template>
  </div>
</template>

