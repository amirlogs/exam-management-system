<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import {
  AlertCircle,
  ArrowRight,
  BookOpen,
  CalendarDays,
  CheckCircle2,
  ChevronRight,
  Clock3,
  FileQuestion,
  GraduationCap,
  Layers3,
  Plus,
  RefreshCw,
  Sparkles,
  UserRound,
  Users,
} from 'lucide-vue-next';

import BaseBadge from '@/shared/components/ui/BaseBadge.vue';
import BaseButton from '@/shared/components/ui/BaseButton.vue';
import ExamStatusBadge from '../exams/components/ExamStatusBadge.vue';

import { useAuthStore } from '@/stores/auth';
import { useUiStore } from '@/stores/ui';
import { handleApiError } from '@/shared/utils/apiError';

import { getTeaching } from '../teaching/api/teaching';
import { listExams } from '../exams/api/exams';
import { listQuestions } from '../questions/api/questions';

import type { Teaching } from '../teaching/types/teaching';
import type { Exam } from '../exams/types/exam';
import type { Question } from '../questions/types/question';

interface EnrichedExam extends Exam {
  courseCode?: string;
  courseName?: string;
}

const router = useRouter();
const authStore = useAuthStore();
const uiStore = useUiStore();

const loading = ref(true);
const refreshing = ref(false);
const error = ref<string | null>(null);

const currentDate = ref(new Intl.DateTimeFormat('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' }).format(new Date()));
let timer: ReturnType<typeof setInterval>;

const teachingList = ref<Teaching[]>([]);
const allExams = ref<EnrichedExam[]>([]);
const recentQuestions = ref<Question[]>([]);
const totalQuestionsCount = ref<number>(0);

const instructorName = computed(() => {
  const user = authStore.user;
  if (!user) return 'Instructor';
  return user.first_name || user.username || 'Instructor';
});

// Active Semester resolution from teaching assignments
const activeSemester = computed(() => {
  const activeTeaching = teachingList.value.find((t) => t.semester?.status === 'active');
  return activeTeaching?.semester ?? teachingList.value[0]?.semester ?? null;
});

// Computed Metrics
const totalCourses = computed(() => teachingList.value.length);

const totalSections = computed(() => {
  const sectionIds = new Set(
    teachingList.value.flatMap((item) => item.assignments?.map((assignment) => assignment.section?.id).filter((id): id is number => id !== undefined) ?? []),
  );
  return sectionIds.size;
});

const activeExams = computed(() => allExams.value.filter((e) => e.status === 'active'));
const scheduledExams = computed(() => allExams.value.filter((e) => e.status === 'scheduled'));
const draftExams = computed(() => allExams.value.filter((e) => e.status === 'draft' || e.status === 'rejected'));
const pendingApprovalExams = computed(() => allExams.value.filter((e) => e.status === 'pending_approval'));
const completedExams = computed(() => allExams.value.filter((e) => e.status === 'completed'));

const recentExams = computed(() => allExams.value.slice(0, 5));

const uniquePrograms = computed(() => {
  const programs = new Map<number, any>();
  teachingList.value.forEach((item) => {
    item.assignments?.forEach((assignment) => {
      const program = assignment.section?.program;
      if (program) {
        programs.set(program.id, program);
      }
    });
  });
  return Array.from(programs.values());
});

// Unified overview resource cards
const overviewItems = computed(() => [
  {
    label: 'Assigned Courses',
    value: totalCourses.value,
    to: '/instructor/teaching',
    icon: BookOpen,
  },
  {
    label: 'Course Sections',
    value: totalSections.value,
    to: '/instructor/teaching',
    icon: Users,
  },
  {
    label: 'Active Now',
    value: activeExams.value.length,
    to: '/instructor/exams',
    icon: Sparkles,
  },
  {
    label: 'Scheduled Exams',
    value: scheduledExams.value.length,
    to: '/instructor/exams',
    icon: CalendarDays,
  },
  {
    label: 'In Preparation',
    value: draftExams.value.length,
    to: '/instructor/exams',
    icon: Clock3,
  },
  {
    label: 'Question Bank',
    value: totalQuestionsCount.value,
    to: '/instructor/questions',
    icon: FileQuestion,
  },
]);

// Actionable Needs Attention items
const attentionItems = computed(() => {
  const items: Array<{
    title: string;
    label: string;
    description: string;
    meta: string;
    to: string;
    icon: any;
  }> = [];

  if (activeExams.value.length > 0) {
    const live = activeExams.value[0];
    items.push({
      title: live.title,
      label: 'Live Exam',
      description: 'An assessment session is actively running. Monitor submissions and live activity.',
      meta: `${live.duration_minutes}m duration · ${live.total_questions || 0} questions`,
      to: `/instructor/exams/${live.id}`,
      icon: Sparkles,
    });
  }

  const rejected = allExams.value.filter((e) => e.status === 'rejected');
  if (rejected.length > 0) {
    items.push({
      title: `${rejected.length} Rejected Examination${rejected.length > 1 ? 's' : ''}`,
      label: 'Action Required',
      description: 'Review departmental comments and revise the questions or exam configuration.',
      meta: 'Revisions requested before re-submission',
      to: '/instructor/exams',
      icon: AlertCircle,
    });
  }

  const drafts = allExams.value.filter((e) => e.status === 'draft');
  if (drafts.length > 0) {
    items.push({
      title: `${drafts.length} Draft Exam${drafts.length > 1 ? 's' : ''} in Preparation`,
      label: 'Composition',
      description: 'Finalize question selection and parameters to submit for formal approval.',
      meta: 'Allocations pending submission',
      to: '/instructor/exams',
      icon: Clock3,
    });
  }

  if (teachingList.value.some((t) => !t.assignments || t.assignments.length === 0)) {
    items.push({
      title: 'Course Without Section Assignment',
      label: 'Workload',
      description: 'One or more assigned course offerings do not have designated student sections yet.',
      meta: 'Verify teaching assignment with department head',
      to: '/instructor/teaching',
      icon: UserRound,
    });
  }

  return items;
});

// Assessment Readiness metrics
const readyExamsCount = computed(() => scheduledExams.value.length + activeExams.value.length + completedExams.value.length);

function formatRelativeDate(value?: string | null) {
  if (!value) return '—';
  const date = new Date(value);
  const now = new Date();
  const diff = Math.max(0, now.getTime() - date.getTime());
  const minutes = Math.floor(diff / 60000);

  if (minutes < 1) return 'Just now';
  if (minutes < 60) return `${minutes}m ago`;

  const hours = Math.floor(minutes / 60);
  if (hours < 24) return `${hours}h ago`;

  const days = Math.floor(hours / 24);
  if (days < 7) return `${days}d ago`;

  return date.toLocaleDateString(undefined, {
    month: 'short',
    day: 'numeric',
  });
}

function formatDate(value?: string | null) {
  if (!value) return '—';

  return new Date(value).toLocaleDateString(undefined, {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  });
}

async function loadDashboardData() {
  loading.value = true;
  error.value = null;

  try {
    // 1. Fetch teaching assignments
    const teachingRes = await getTeaching(1, 50);
    teachingList.value = teachingRes.data || [];

    // 2. Fetch exams for each assigned offering in parallel
    const examPromises = teachingList.value.map(async (offering) => {
      try {
        const examRes = await listExams(offering.id, 1, 20);
        return (examRes.data || []).map((exam) => ({
          ...exam,
          courseCode: offering.course?.code,
          courseName: offering.course?.name,
        }));
      } catch {
        return [];
      }
    });

    const examResults = await Promise.all(examPromises);
    allExams.value = examResults.flat().sort((a, b) => (b.id || 0) - (a.id || 0));

    // 3. Fetch question bank metrics
    try {
      const qRes = await listQuestions(1, 5);
      recentQuestions.value = qRes.data || [];
      totalQuestionsCount.value = qRes.pagination?.total ?? recentQuestions.value.length;
    } catch {
      recentQuestions.value = [];
      totalQuestionsCount.value = 0;
    }
  } catch (err: any) {
    error.value = 'Failed to load instructor dashboard data.';
    handleApiError(err, uiStore, undefined, 'Unable to load your teaching assignments.');
  } finally {
    loading.value = false;
    refreshing.value = false;
  }
}

async function handleRefresh() {
  refreshing.value = true;
  await loadDashboardData();
}

function retry() {
  handleRefresh();
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
        <p class="text-[11px] font-mono font-medium uppercase tracking-widest text-text/40 mb-1">Instructor Portal</p>
        <h1 class="font-display text-2xl sm:text-3xl font-bold tracking-tight text-text">
          Welcome back, {{ instructorName }}
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
          @click="retry">
          <RefreshCw class="h-4 w-4" :class="{ 'animate-spin': refreshing }" />
        </button>
      </div>
    </header>

    <!-- Error Banner -->
    <div v-if="error" class="flex items-start gap-3 rounded-xl border border-error/20 bg-error/5 p-4 text-sm text-error shadow-sm">
      <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" />
      <div class="min-w-0 flex-1">
        <p class="font-medium">{{ error }}</p>
        <button type="button" class="mt-1 text-xs font-semibold underline cursor-pointer hover:text-error/80" @click="retry">Try again</button>
      </div>
    </div>

    <!-- Quick Tools / Shortcuts Bar -->
    <div class="flex flex-wrap items-center gap-3">
      <router-link to="/instructor/teaching" class="group flex items-center gap-2.5 rounded-full border border-border bg-surface px-4 py-2 text-xs font-medium text-text/70 transition-all hover:border-accent/30 hover:bg-accent/5 hover:text-accent shadow-sm">
        <BookOpen class="w-3.5 h-3.5" />
        Teaching Assignments
      </router-link>
      <router-link to="/instructor/exams" class="group flex items-center gap-2.5 rounded-full border border-border bg-surface px-4 py-2 text-xs font-medium text-text/70 transition-all hover:border-accent/30 hover:bg-accent/5 hover:text-accent shadow-sm">
        <Layers3 class="w-3.5 h-3.5" />
        Examinations
      </router-link>
      <router-link to="/instructor/questions" class="group flex items-center gap-2.5 rounded-full border border-border bg-surface px-4 py-2 text-xs font-medium text-text/70 transition-all hover:border-accent/30 hover:bg-accent/5 hover:text-accent shadow-sm">
        <FileQuestion class="w-3.5 h-3.5" />
        Question Bank
      </router-link>
      <router-link to="/instructor/grading" class="group flex items-center gap-2.5 rounded-full border border-border bg-surface px-4 py-2 text-xs font-medium text-text/70 transition-all hover:border-accent/30 hover:bg-accent/5 hover:text-accent shadow-sm">
        <CheckCircle2 class="w-3.5 h-3.5" />
        Grading Queue
      </router-link>
      <router-link to="/instructor/exams/create" class="group flex items-center gap-2.5 rounded-full border border-accent/30 bg-accent/10 px-4 py-2 text-xs font-semibold text-accent transition-all hover:bg-accent/15 shadow-sm">
        <Plus class="w-3.5 h-3.5" />
        Create Exam
      </router-link>
    </div>

    <!-- 2. Unified Teaching & Academic KPI Deck -->
    <section>
      <div class="rounded-2xl border border-border bg-surface shadow-sm overflow-hidden">
        <div class="grid grid-cols-2 lg:grid-cols-6 divide-y lg:divide-y-0 lg:divide-x divide-border">
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

    <!-- 3. Active Semester Operational Pulse -->
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-5">
      <!-- Column A: Assessment Pipeline & Readiness -->
      <section class="xl:col-span-3 rounded-2xl border border-border bg-surface shadow-sm overflow-hidden flex flex-col">
        <div class="px-6 py-5 border-b border-border">
          <h2 class="text-sm font-semibold text-text">Assessment Pipeline &amp; Readiness</h2>
          <p class="text-xs text-text/50 mt-1">Lifecycle status and preparation readiness across your courses.</p>
        </div>

        <div class="p-6 flex-1 flex flex-col justify-between gap-8">
          <!-- Pipeline Stages -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="flex flex-col gap-1">
              <span class="text-[11px] font-mono uppercase text-text/50 tracking-wider">Total Papers</span>
              <span class="text-2xl font-bold font-display text-text tabular-nums">{{ loading ? '—' : allExams.length }}</span>
            </div>
            <div class="flex flex-col gap-1">
              <span class="text-[11px] font-mono uppercase text-warning tracking-wider">Draft / Prep</span>
              <span class="text-2xl font-bold font-display text-text tabular-nums">{{ loading ? '—' : draftExams.length }}</span>
            </div>
            <div class="flex flex-col gap-1">
              <span class="text-[11px] font-mono uppercase text-accent tracking-wider">Scheduled</span>
              <span class="text-2xl font-bold font-display text-text tabular-nums">{{ loading ? '—' : scheduledExams.length }}</span>
            </div>
            <div class="flex flex-col gap-1">
              <span class="text-[11px] font-mono uppercase text-success tracking-wider">Active / Done</span>
              <span class="text-2xl font-bold font-display text-text tabular-nums">{{ loading ? '—' : activeExams.length + completedExams.length }}</span>
            </div>
          </div>

          <!-- Multi-segment Progress Bar: Assessment Lifecycle -->
          <div class="space-y-3">
            <div class="flex items-center justify-between text-xs font-medium">
              <span class="text-text/70">Assessment Lifecycle Status</span>
              <span class="text-text/50 tabular-nums">100% Total</span>
            </div>
            <div class="h-2.5 w-full rounded-full bg-bg border border-border/50 flex overflow-hidden">
              <div class="h-full bg-success transition-all duration-500" :style="{ width: allExams.length ? `${((activeExams.length + completedExams.length) / allExams.length) * 100}%` : '0%' }" title="Active / Completed"></div>
              <div class="h-full bg-accent transition-all duration-500" :style="{ width: allExams.length ? `${(scheduledExams.length / allExams.length) * 100}%` : '0%' }" title="Scheduled"></div>
              <div class="h-full bg-warning transition-all duration-500" :style="{ width: allExams.length ? `${(draftExams.length / allExams.length) * 100}%` : '0%' }" title="Draft / Prep"></div>
            </div>
          </div>

          <!-- Question Bank Health -->
          <div class="space-y-3">
            <div class="flex items-center justify-between text-xs font-medium">
              <span class="text-text/70">Question Bank Repository</span>
              <span class="text-text tabular-nums font-mono">{{ totalQuestionsCount }} questions authored</span>
            </div>
            <div class="h-2.5 w-full rounded-full bg-bg border border-border/50 overflow-hidden">
              <div class="h-full bg-accent transition-all duration-500" :style="{ width: `${Math.min(totalQuestionsCount * 5, 100)}%` }"></div>
            </div>
          </div>

          <div class="pt-4 border-t border-border flex items-center justify-between text-xs text-text/60">
            <span>Workload Coverage</span>
            <span class="font-medium text-text tabular-nums bg-surface border border-border px-2.5 py-0.5 rounded-full font-mono">{{ totalSections }} Section{{ totalSections === 1 ? '' : 's' }} across {{ totalCourses }} Course{{ totalCourses === 1 ? '' : 's' }}</span>
          </div>
        </div>
      </section>

      <!-- Column B: Academic Action Queue -->
      <section class="xl:col-span-2 rounded-2xl border border-border bg-surface shadow-sm overflow-hidden flex flex-col">
        <div class="px-6 py-5 border-b border-border flex items-center justify-between">
          <div>
            <h2 class="text-sm font-semibold text-text">Needs Attention</h2>
            <p class="text-xs text-text/50 mt-1">Actionable items requiring your follow-up.</p>
          </div>
          <span v-if="attentionItems.length" class="inline-flex items-center rounded-full bg-warning/10 px-2.5 py-0.5 text-xs font-semibold text-warning font-mono">
            {{ attentionItems.length }}
          </span>
        </div>

        <!-- Attention Items List -->
        <div v-if="attentionItems.length" class="divide-y divide-border flex-1">
          <div v-for="item in attentionItems" :key="`${item.label}-${item.title}`" class="flex items-start justify-between gap-4 p-5 transition-colors hover:bg-bg/50">
            <div class="min-w-0 flex-1">
              <div class="flex items-center gap-2 mb-1">
                <BaseBadge :variant="item.icon === AlertCircle ? 'danger' : 'warning'" size="sm">
                  {{ item.label }}
                </BaseBadge>
                <span class="font-mono text-xs text-text/50 truncate">{{ item.meta }}</span>
              </div>
              <p class="text-xs font-semibold text-text">{{ item.title }}</p>
              <p class="text-xs text-text/60 mt-0.5 leading-relaxed">{{ item.description }}</p>
            </div>
            <BaseButton variant="secondary" size="sm" class="shrink-0 text-xs" @click="router.push(item.to)">
              Resolve
              <template #icon>
                <ArrowRight class="h-3 w-3" />
              </template>
            </BaseButton>
          </div>
        </div>

        <!-- All Clear Empty State -->
        <div v-else class="flex flex-1 flex-col items-center justify-center p-8 text-center">
          <div class="flex h-10 w-10 items-center justify-center rounded-full bg-success/10 text-success mb-3">
            <CheckCircle2 class="h-5 w-5" />
          </div>
          <p class="text-sm font-semibold text-text">All systems operational</p>
          <p class="text-xs text-text/50 mt-1 max-w-[200px]">No pending instructor actions require your attention.</p>
        </div>
      </section>
    </div>

    <!-- 4. Recent Examinations Registry -->
    <section class="rounded-2xl border border-border bg-surface shadow-sm overflow-hidden">
      <div class="px-6 py-5 border-b border-border flex items-center justify-between">
        <div>
          <h2 class="text-sm font-semibold text-text">Recent Examinations</h2>
          <p class="text-xs text-text/50 mt-1">Latest assessment activity, schedules, and grading status.</p>
        </div>
        <router-link to="/instructor/exams" class="text-xs font-medium text-accent hover:text-accent/80 transition-colors flex items-center gap-1">
          <span>View all examinations</span>
          <ArrowRight class="h-3 w-3" />
        </router-link>
      </div>

      <div v-if="loading" class="divide-y divide-border">
        <div v-for="i in 3" :key="i" class="p-5 animate-pulse flex items-center gap-4">
          <div class="h-8 w-8 rounded-lg bg-text/5" />
          <div class="flex-1 space-y-2">
            <div class="h-4 w-1/3 rounded bg-text/5" />
            <div class="h-3 w-1/4 rounded bg-text/5" />
          </div>
        </div>
      </div>

      <div v-else-if="recentExams.length" class="divide-y divide-border">
        <div
          v-for="exam in recentExams"
          :key="exam.id"
          class="group flex flex-col gap-4 p-5 transition-colors hover:bg-bg/50 sm:flex-row sm:items-center sm:justify-between cursor-pointer"
          @click="router.push({ name: 'instructor.exams.detail', params: { examId: exam.id } })">
          <div class="flex items-start gap-4 min-w-0">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-accent/10 border border-accent/20 text-accent">
              <Layers3 class="h-5 w-5" />
            </div>
            <div class="min-w-0 space-y-1">
              <div class="flex flex-wrap items-center gap-2">
                <span class="font-mono text-xs font-bold text-accent">{{ exam.courseCode ?? 'EXAM' }}</span>
                <span class="text-xs font-semibold text-text truncate max-w-xs sm:max-w-md">{{ exam.title }}</span>
                <ExamStatusBadge :status="exam.status" />
              </div>
              <p class="text-xs text-text/50 font-mono">
                {{ exam.type }} Assessment · {{ exam.duration_minutes }} mins · {{ exam.total_marks ?? 0 }} pts · {{ exam.total_questions ?? 0 }} questions
              </p>
            </div>
          </div>

          <div class="flex items-center gap-4 shrink-0 sm:text-right">
            <div class="text-xs text-text/50 font-mono">
              <span>{{ formatRelativeDate(exam.updated_at) }}</span>
            </div>
            <ChevronRight class="h-4 w-4 text-text/30 transition-transform group-hover:translate-x-0.5 group-hover:text-accent" />
          </div>
        </div>
      </div>

      <div v-else class="p-12 text-center text-text/40">
        <FileQuestion class="mx-auto h-8 w-8 text-text/25 mb-2" />
        <p class="text-sm font-medium text-text">No examinations created yet</p>
        <p class="mt-0.5 text-xs text-text/40">Create formal assessments associated with your assigned courses.</p>
        <router-link
          to="/instructor/exams/create"
          class="mt-4 inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg border border-border text-xs font-medium text-text hover:bg-accent/10 hover:border-accent/30 hover:text-accent transition-colors">
          <Plus class="w-3.5 h-3.5" />
          <span>Create Examination</span>
        </router-link>
      </div>
    </section>
  </div>
</template>
