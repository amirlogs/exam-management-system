<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import {
  AlertCircle,
  ArrowRight,
  BookOpen,
  CalendarDays,
  CheckCircle2,
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

// Overview resource cards
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
    label: 'Total Assessments',
    value: allExams.value.length,
    to: '/instructor/exams',
    icon: Layers3,
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
});
</script>

<template>
  <div class="mx-auto w-full max-w-7xl space-y-7 px-4 py-6 md:px-8">
    <!-- Header -->
    <div class="flex flex-col gap-4 border-b border-border pb-6 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <p class="text-xs font-mono font-medium uppercase tracking-wider text-text/40">Instructor Workspace</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight text-text md:text-3xl">Dashboard</h1>
        <p class="mt-1 max-w-2xl text-sm text-text/50">A current overview of your teaching load, active examinations, and assessment readiness.</p>
      </div>

      <div class="flex flex-wrap items-center gap-2.5">
        <!-- Active Semester Pill -->
        <div v-if="activeSemester" class="flex items-center gap-2.5 rounded-xl border border-border bg-surface px-3 py-2 text-xs shadow-2xs">
          <CalendarDays class="h-4 w-4 text-text/50 shrink-0" />
          <div class="min-w-0">
            <p class="text-[10px] font-mono font-medium uppercase tracking-wide text-text/40">Active semester</p>
            <p class="font-medium text-text truncate">{{ activeSemester.name }} · {{ activeSemester.academic_year }}</p>
          </div>
        </div>

        <!-- Refresh Button -->
        <button
          type="button"
          class="flex h-10 w-10 items-center justify-center rounded-xl border border-border bg-surface text-text/60 transition-colors hover:border-text/30 hover:text-text cursor-pointer disabled:opacity-50"
          title="Refresh dashboard"
          :disabled="refreshing"
          @click="retry">
          <RefreshCw class="h-4 w-4" :class="refreshing ? 'animate-spin' : ''" />
        </button>
      </div>
    </div>

    <!-- Error Banner -->
    <div v-if="error" class="flex items-start gap-3 rounded-xl border border-error/30 bg-error/5 p-4 text-sm text-error">
      <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" />
      <div class="min-w-0 flex-1">
        <p class="font-medium">{{ error }}</p>
        <button type="button" class="mt-1 text-xs font-semibold underline cursor-pointer" @click="retry">Try again</button>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="flex flex-wrap items-center gap-2 pt-1">
      <span class="text-xs font-mono uppercase text-text/40 tracking-wider mr-1">Quick Links:</span>
      <router-link
        to="/instructor/teaching"
        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-border bg-surface text-xs font-medium text-text/75 hover:text-text hover:border-text/30 hover:bg-text/[0.02] transition-colors">
        <BookOpen class="w-3.5 h-3.5 text-text/50" />
        <span>Teaching Assignments</span>
      </router-link>
      <router-link
        to="/instructor/exams"
        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-border bg-surface text-xs font-medium text-text/75 hover:text-text hover:border-text/30 hover:bg-text/[0.02] transition-colors">
        <Layers3 class="w-3.5 h-3.5 text-text/50" />
        <span>Examinations</span>
      </router-link>
      <router-link
        to="/instructor/questions"
        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-border bg-surface text-xs font-medium text-text/75 hover:text-text hover:border-text/30 hover:bg-text/[0.02] transition-colors">
        <FileQuestion class="w-3.5 h-3.5 text-text/50" />
        <span>Question Bank</span>
      </router-link>
      <router-link
        to="/instructor/exams/create"
        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-border bg-surface text-xs font-medium text-text/75 hover:text-text hover:border-text/30 hover:bg-text/[0.02] transition-colors">
        <Plus class="w-3.5 h-3.5 text-text/50" />
        <span>Create Exam</span>
      </router-link>
    </div>

    <!-- 1. Teaching & Academic Overview (Clickable Resource Cards) -->
    <section class="space-y-3">
      <div>
        <h2 class="text-sm font-semibold text-text">Teaching Overview</h2>
        <p class="text-xs text-text/40">Your assigned courses, student sections, and authored repositories.</p>
      </div>

      <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-4">
        <router-link
          v-for="item in overviewItems"
          :key="item.label"
          :to="item.to"
          class="group relative flex flex-col justify-between rounded-xl border border-border bg-surface p-4 transition-all duration-150 hover:border-text/30 hover:shadow-2xs cursor-pointer">
          <div class="flex items-center justify-between">
            <component :is="item.icon" class="h-4 w-4 text-text/40 transition-colors group-hover:text-text" />
            <ArrowRight class="h-3.5 w-3.5 text-text/25 opacity-0 transition-all group-hover:opacity-100 group-hover:translate-x-0.5" />
          </div>

          <div class="mt-4">
            <div v-if="loading" class="h-7 w-12 animate-pulse rounded bg-text/5" />
            <p v-else class="text-2xl font-semibold tracking-tight text-text tabular-nums">
              {{ item.value }}
            </p>
            <p class="mt-0.5 text-xs font-medium text-text/50 group-hover:text-text/70 transition-colors">
              {{ item.label }}
            </p>
          </div>
        </router-link>
      </div>
    </section>

    <!-- 2. Assessment Snapshot -->
    <section class="space-y-3">
      <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <h2 class="text-sm font-semibold text-text">Assessment Snapshot</h2>
          <p class="text-xs text-text/40">Examination breakdown across your course offerings.</p>
        </div>

        <span v-if="activeSemester" class="text-xs font-mono text-text/40"> {{ formatDate(activeSemester.start_date) }} – {{ formatDate(activeSemester.end_date) }} </span>
      </div>

      <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Active Exams -->
        <div class="rounded-xl border border-border bg-surface p-4.5 shadow-2xs">
          <div class="flex items-center justify-between">
            <span class="text-xs font-medium text-text/55">Active Session</span>
            <Sparkles class="h-4 w-4 text-success" />
          </div>
          <p class="mt-3 text-2xl font-semibold text-text tabular-nums">
            {{ loading ? '—' : activeExams.length }}
          </p>
          <p class="mt-1 text-xs text-text/40">Exams currently in progress</p>
        </div>

        <!-- Scheduled Exams -->
        <div class="rounded-xl border border-border bg-surface p-4.5 shadow-2xs">
          <div class="flex items-center justify-between">
            <span class="text-xs font-medium text-text/55">Scheduled</span>
            <CalendarDays class="h-4 w-4 text-text/40" />
          </div>
          <p class="mt-3 text-2xl font-semibold text-text tabular-nums">
            {{ loading ? '—' : scheduledExams.length }}
          </p>
          <p class="mt-1 text-xs text-text/40">Approved and on timetable</p>
        </div>

        <!-- Draft & In Preparation -->
        <div class="rounded-xl border border-border bg-surface p-4.5 shadow-2xs">
          <div class="flex items-center justify-between">
            <span class="text-xs font-medium text-text/55">In Preparation</span>
            <Clock3 class="h-4 w-4 text-text/40" />
          </div>
          <p class="mt-3 text-2xl font-semibold text-text tabular-nums">
            {{ loading ? '—' : draftExams.length }}
          </p>
          <p class="mt-1 text-xs text-text/40">Draft or awaiting submission</p>
        </div>

        <!-- Completed Exams -->
        <div class="rounded-xl border border-border bg-surface p-4.5 shadow-2xs">
          <div class="flex items-center justify-between">
            <span class="text-xs font-medium text-text/55">Completed</span>
            <CheckCircle2 class="h-4 w-4 text-text/40" />
          </div>
          <p class="mt-3 text-2xl font-semibold text-text tabular-nums">
            {{ loading ? '—' : completedExams.length }}
          </p>
          <p class="mt-1 text-xs text-text/40">Concluded examination sessions</p>
        </div>
      </div>
    </section>

    <!-- 3. Operational Area (Needs Attention + Assessment Progress) -->
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-5">
      <!-- Needs Attention -->
      <section class="flex flex-col justify-between overflow-hidden rounded-xl border border-border bg-surface shadow-2xs xl:col-span-3">
        <div class="flex items-center justify-between border-b border-border/70 px-5 py-4">
          <div>
            <h2 class="text-sm font-semibold text-text">Needs Attention</h2>
            <p class="text-xs text-text/40">Actionable items requiring your follow-up.</p>
          </div>
          <span v-if="attentionItems.length" class="px-2 py-0.5 rounded-full text-[11px] font-mono font-semibold bg-accent/10 text-accent border border-accent/20">
            {{ attentionItems.length }}
          </span>
          <AlertCircle v-else class="h-4 w-4 text-text/40 shrink-0" />
        </div>

        <!-- Loading Skeletons -->
        <div v-if="loading" class="divide-y divide-border/60">
          <div v-for="item in 3" :key="item" class="p-5 space-y-2">
            <div class="h-3.5 w-44 animate-pulse rounded bg-text/5" />
            <div class="h-3 w-72 animate-pulse rounded bg-text/5" />
          </div>
        </div>

        <!-- Attention Items List -->
        <div v-else-if="attentionItems.length" class="divide-y divide-border/60 flex-1">
          <div v-for="item in attentionItems" :key="`${item.label}-${item.title}`" class="flex items-start justify-between gap-4 p-4.5 transition-colors hover:bg-text/[0.02]">
            <div class="flex items-start gap-3 min-w-0">
              <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-text/[0.04] border border-border/70 text-text/70">
                <component :is="item.icon" class="h-4 w-4" />
              </div>

              <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                  <p class="text-sm font-medium text-text truncate">
                    {{ item.title }}
                  </p>
                  <BaseBadge variant="neutral" class="text-[10px]">
                    {{ item.label }}
                  </BaseBadge>
                </div>

                <p class="mt-1 text-xs text-text/50 leading-relaxed">
                  {{ item.description }}
                </p>

                <p class="mt-1 font-mono text-[11px] text-text/40">
                  {{ item.meta }}
                </p>
              </div>
            </div>

            <button
              type="button"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-border bg-surface text-xs font-medium text-text hover:border-accent hover:text-accent transition-colors cursor-pointer shrink-0"
              @click="router.push(item.to)">
              <span>Review</span>
              <ArrowRight class="w-3 h-3" />
            </button>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else class="flex-1 px-5 py-12 text-center flex flex-col items-center justify-center">
          <div class="w-10 h-10 rounded-full bg-text/[0.04] border border-border/60 flex items-center justify-center text-text/45 mb-3">
            <CheckCircle2 class="h-5 w-5" />
          </div>
          <p class="text-sm font-medium text-text">No action required</p>
          <p class="mt-1 text-xs text-text/40 max-w-xs">All assigned courses and examination workflows are progressing normally.</p>
        </div>
      </section>

      <!-- Workflow & Readiness Progress -->
      <section class="overflow-hidden rounded-xl border border-border bg-surface shadow-2xs xl:col-span-2">
        <div class="border-b border-border/70 px-5 py-4">
          <h2 class="text-sm font-semibold text-text">Assessment Progress</h2>
          <p class="text-xs text-text/40">Preparation rate and academic reach.</p>
        </div>

        <div class="p-5 space-y-5">
          <!-- Readiness Progress Bar -->
          <div>
            <div class="mb-2 flex items-center justify-between text-xs">
              <span class="font-medium text-text/60">Scheduled &amp; Concluded</span>
              <span class="tabular-nums font-mono text-text/50"> {{ readyExamsCount }} / {{ allExams.length }} </span>
            </div>
            <div class="h-2 overflow-hidden rounded-full bg-text/5">
              <div
                class="h-full rounded-full bg-success transition-all duration-300"
                :style="{
                  width: allExams.length ? `${(readyExamsCount / allExams.length) * 100}%` : '0%',
                }" />
            </div>
          </div>

          <!-- Pending Composition Progress Bar -->
          <div>
            <div class="mb-2 flex items-center justify-between text-xs">
              <span class="font-medium text-text/60">In Preparation / Draft</span>
              <span class="tabular-nums font-mono text-text/50"> {{ draftExams.length }} / {{ allExams.length || 1 }} </span>
            </div>
            <div class="h-2 overflow-hidden rounded-full bg-text/5">
              <div
                class="h-full rounded-full bg-text/70 transition-all duration-300"
                :style="{
                  width: allExams.length ? `${Math.min((draftExams.length / allExams.length) * 100, 100)}%` : '0%',
                }" />
            </div>
          </div>

          <!-- Bottom Metric Tiles -->
          <div class="grid grid-cols-2 gap-3 border-t border-border/70 pt-5">
            <div class="rounded-lg bg-text/[0.02] border border-border/50 p-3">
              <p class="text-[11px] font-mono uppercase text-text/40 tracking-wider">Sections Taught</p>
              <p class="mt-1 text-xl font-semibold text-text tabular-nums">
                {{ totalSections }}
              </p>
            </div>

            <div class="rounded-lg bg-text/[0.02] border border-border/50 p-3">
              <p class="text-[11px] font-mono uppercase text-text/40 tracking-wider">Degree Programs</p>
              <p class="mt-1 text-xl font-semibold text-text tabular-nums">
                {{ uniquePrograms.length }}
              </p>
            </div>
          </div>
        </div>
      </section>
    </div>

    <!-- 4. Recent Examinations Table -->
    <section class="overflow-hidden rounded-xl border border-border bg-surface shadow-2xs">
      <div class="flex items-center justify-between border-b border-border/70 px-5 py-4">
        <div>
          <h2 class="text-sm font-semibold text-text">Recent Examinations</h2>
          <p class="text-xs text-text/40">Latest assessment activity and scheduling.</p>
        </div>

        <router-link to="/instructor/exams" class="text-xs font-medium text-text/60 hover:text-text transition-colors flex items-center gap-1">
          <span>View all</span>
          <ArrowRight class="w-3.5 h-3.5" />
        </router-link>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="divide-y divide-border/60">
        <div v-for="item in 4" :key="item" class="flex items-center gap-4 px-5 py-4">
          <div class="h-9 w-9 animate-pulse rounded-lg bg-text/5" />
          <div class="flex-1 space-y-1.5">
            <div class="h-3.5 w-48 animate-pulse rounded bg-text/5" />
            <div class="h-3 w-32 animate-pulse rounded bg-text/5" />
          </div>
        </div>
      </div>

      <!-- Exams Rows -->
      <div v-else-if="recentExams.length" class="divide-y divide-border/60">
        <div
          v-for="exam in recentExams"
          :key="exam.id"
          class="flex items-center gap-4 px-5 py-3.5 transition-colors hover:bg-text/[0.02] cursor-pointer"
          @click="router.push({ name: 'instructor.exams.detail', params: { examId: exam.id } })">
          <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-border/80 bg-surface text-text/50">
            <Layers3 class="h-4 w-4" />
          </div>

          <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
              <p class="truncate text-sm font-medium text-text">
                {{ exam.title }}
              </p>
              <ExamStatusBadge :status="exam.status" />
            </div>

            <p class="mt-0.5 font-mono text-xs text-text/40">{{ exam.courseCode ?? 'EXAM' }} · {{ exam.type }}</p>
          </div>

          <div class="hidden items-center gap-6 text-right sm:flex">
            <div>
              <p class="text-[11px] font-mono uppercase text-text/40">Duration</p>
              <p class="text-sm font-medium text-text tabular-nums">{{ exam.duration_minutes }}m</p>
            </div>

            <div>
              <p class="text-[11px] font-mono uppercase text-text/40">Questions</p>
              <p class="text-sm font-medium text-text tabular-nums">
                {{ exam.total_questions ?? 0 }}
              </p>
            </div>

            <div>
              <p class="text-[11px] font-mono uppercase text-text/40">Points</p>
              <p class="text-sm font-medium text-text tabular-nums">
                {{ exam.total_marks ?? 0 }}
              </p>
            </div>

            <span class="w-16 text-xs text-text/40">
              {{ formatRelativeDate(exam.updated_at) }}
            </span>

            <router-link
              :to="{ name: 'instructor.exams.detail', params: { examId: exam.id } }"
              class="text-text/30 hover:text-text transition-colors p-1"
              title="View examination details"
              @click.stop>
              <ArrowRight class="h-4 w-4" />
            </router-link>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="px-5 py-14 text-center">
        <FileQuestion class="mx-auto h-7 w-7 text-text/25" />
        <p class="mt-2 text-sm font-medium text-text">No examinations created yet</p>
        <p class="mt-1 text-xs text-text/40">Create formal assessments associated with your assigned courses.</p>
        <router-link
          to="/instructor/exams/create"
          class="mt-4 inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg border border-border text-xs font-medium text-text hover:bg-text/[0.04] transition-colors">
          <span>Create Examination</span>
          <ArrowRight class="w-3.5 h-3.5" />
        </router-link>
      </div>
    </section>
  </div>
</template>
