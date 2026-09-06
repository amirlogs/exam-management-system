<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import {
  ArrowRight,
  BookOpen,
  CalendarDays,
  CheckCircle2,
  Clock3,
  GraduationCap,
  Users2,
  RefreshCw,
  AlertCircle,
  FileQuestion,
  Plus,
  LibraryBig,
  Sparkles,
  HelpCircle,
  Layers,
  ChevronRight,
  ShieldCheck,
  Send,
} from 'lucide-vue-next';

import BaseButton from '@/shared/components/ui/BaseButton.vue';
import BaseBadge from '@/shared/components/ui/BaseBadge.vue';
import BaseCard from '@/shared/components/ui/BaseCard.vue';
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

const router = useRouter();
const authStore = useAuthStore();
const uiStore = useUiStore();

const loading = ref(true);
const refreshing = ref(false);
const error = ref<string | null>(null);

const teachingList = ref<Teaching[]>([]);
const allExams = ref<Exam[]>([]);
const recentQuestions = ref<Question[]>([]);
const totalQuestionsCount = ref<number>(0);

const instructorName = computed(() => {
  const user = authStore.user;
  if (!user) return 'Instructor';
  return user.first_name || user.username || 'Instructor';
});

// Computed Metrics
const totalCourses = computed(() => teachingList.value.length);

const totalSections = computed(() => {
  const sectionIds = new Set(
    teachingList.value.flatMap((item) =>
      item.assignments?.map((assignment) => assignment.section?.id).filter((id): id is number => id !== undefined) ?? []
    )
  );
  return sectionIds.size;
});

const activeExams = computed(() => {
  return allExams.value.filter((e) => e.status === 'active');
});

const scheduledExams = computed(() => {
  return allExams.value.filter((e) => e.status === 'scheduled');
});

const draftExams = computed(() => {
  return allExams.value.filter((e) => e.status === 'draft' || e.status === 'rejected');
});

const pendingApprovalExams = computed(() => {
  return allExams.value.filter((e) => e.status === 'pending_approval');
});

const recentExams = computed(() => {
  return allExams.value.slice(0, 6);
});

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

async function loadDashboardData() {
  loading.value = true;
  error.value = null;

  try {
    // 1. Fetch teaching assignments (authorized instructor scope)
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
    error.value = 'Failed to synchronize instructor dashboard data.';
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

function navigateToExam(exam: Exam) {
  router.push({ name: 'instructor.exams.detail', params: { examId: exam.id } });
}

function navigateToCreateExam() {
  router.push({ name: 'instructor.exams.create' });
}

function navigateToAddQuestion() {
  router.push({ name: 'instructor.questions.add' });
}

function navigateToTeaching() {
  router.push({ name: 'instructor.teaching.list' });
}

function navigateToTeachingDetail(id: number) {
  router.push({ name: 'instructor.teaching.detail', params: { courseOfferingId: id } });
}

function navigateToQuestions() {
  router.push({ name: 'instructor.questions.list' });
}

function navigateToExams() {
  router.push({ name: 'instructor.exams.list' });
}

onMounted(() => {
  loadDashboardData();
});
</script>

<template>
  <div class="space-y-8 max-w-7xl mx-auto">
    <!-- ── 1. Header & Quick Action Hub ─────────────────────────────── -->
    <header class="flex flex-col md:flex-row md:items-end justify-between gap-4">
      <div>
        <div class="flex items-center gap-2 text-xs font-mono uppercase tracking-wider text-text/60 mb-1.5">
          <span class="w-1.5 h-1.5 rounded-full bg-accent inline-block" />
          <span>Instructor Workspace · Academic Term Overview</span>
        </div>
        <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-text font-display">
          Welcome back, {{ instructorName }}.
        </h1>
        <p class="text-sm text-text/60 mt-1">
          Monitor active assessment sessions, course sections, and examination compositions.
        </p>
      </div>

      <div class="flex items-center gap-2.5 flex-wrap">
        <BaseButton variant="secondary" :disabled="loading || refreshing" class="shadow-2xs" @click="handleRefresh">
          <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': refreshing }" />
          <span>Refresh</span>
        </BaseButton>

        <BaseButton variant="secondary" class="shadow-2xs" @click="navigateToAddQuestion">
          <Plus class="w-4 h-4 text-accent" />
          <span>Add Question</span>
        </BaseButton>

        <BaseButton variant="primary" class="font-semibold shadow-xs" @click="navigateToCreateExam">
          <Plus class="w-4 h-4" />
          <span>Create Exam</span>
        </BaseButton>
      </div>
    </header>

    <!-- ── 2. Error Banner ─────────────────────────────────────────── -->
    <div v-if="error" class="flex items-start justify-between gap-3 rounded-2xl border border-error/30 bg-error/10 p-4 text-sm text-error">
      <div class="flex items-start gap-3">
        <AlertCircle class="w-5 h-5 shrink-0 mt-0.5" />
        <div>
          <p class="font-semibold">Unable to load dashboard data</p>
          <p class="text-xs text-text/70 mt-0.5">{{ error }}</p>
        </div>
      </div>
      <BaseButton variant="secondary" size="sm" @click="loadDashboardData">Retry</BaseButton>
    </div>

    <!-- ── 3. Loading Skeletons ─────────────────────────────────────── -->
    <div v-if="loading" class="space-y-6 animate-pulse">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div v-for="i in 4" :key="i" class="h-32 bg-surface rounded-2xl border border-border" />
      </div>
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 h-96 bg-surface rounded-2xl border border-border" />
        <div class="h-96 bg-surface rounded-2xl border border-border" />
      </div>
    </div>

    <template v-else>
      <!-- ── 4. Live / Attention Needed Banner ──────────────────────── -->
      <div
        v-if="activeExams.length > 0"
        class="relative overflow-hidden rounded-2xl border border-success/30 bg-success/10 p-5 sm:p-6 shadow-sm">
        <div class="absolute -right-8 -top-8 w-44 h-44 bg-success/15 rounded-full blur-2xl pointer-events-none" />
        <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div class="flex items-start gap-3.5">
            <div class="w-10 h-10 rounded-xl bg-success/20 text-success flex items-center justify-center shrink-0">
              <Sparkles class="w-5 h-5" />
            </div>
            <div>
              <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-success animate-ping" />
                <span class="text-xs font-mono font-bold uppercase text-success tracking-wide">
                  Live Examination in Progress
                </span>
              </div>
              <h2 class="text-lg font-bold text-text font-display mt-0.5">
                {{ activeExams[0].title }}
              </h2>
              <p class="text-xs text-text/70 mt-0.5">
                {{ activeExams[0].total_questions || 0 }} questions · {{ activeExams[0].duration_minutes }} minutes duration.
              </p>
            </div>
          </div>
          <BaseButton variant="primary" class="shrink-0 font-semibold shadow-xs" @click="navigateToExam(activeExams[0])">
            <span>Monitor Live Exam</span>
            <ArrowRight class="w-4 h-4" />
          </BaseButton>
        </div>
      </div>

      <div
        v-else-if="draftExams.length > 0"
        class="rounded-2xl border border-border bg-surface p-5 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-start gap-3.5">
          <div class="w-10 h-10 rounded-xl bg-accent/10 text-accent flex items-center justify-center shrink-0">
            <FileQuestion class="w-5 h-5" />
          </div>
          <div>
            <div class="text-xs font-mono font-bold uppercase text-accent tracking-wide">
              Action Required · Exam Composition
            </div>
            <h2 class="text-base font-bold text-text font-display mt-0.5">
              {{ draftExams.length }} Draft Exam{{ draftExams.length !== 1 ? 's' : '' }} Pending Submission
            </h2>
            <p class="text-xs text-text/60 mt-0.5">
              Review and finalize question allocations before submitting for departmental approval.
            </p>
          </div>
        </div>
        <BaseButton variant="secondary" class="shrink-0 font-semibold text-xs" @click="navigateToExams">
          <span>Review Drafts</span>
          <ArrowRight class="w-4 h-4" />
        </BaseButton>
      </div>

      <!-- ── 5. KPI Statistics Cards Row ────────────────────────────── -->
      <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. Assigned Courses -->
        <div class="rounded-2xl border border-border bg-surface p-5 shadow-2xs flex flex-col justify-between hover:shadow-xs transition-shadow">
          <div class="flex items-center justify-between">
            <span class="text-xs font-mono uppercase tracking-wider font-semibold text-text/60">Teaching Load</span>
            <div class="w-9 h-9 rounded-xl bg-accent/10 text-accent flex items-center justify-center">
              <BookOpen class="w-4 h-4" />
            </div>
          </div>
          <div class="mt-4">
            <div class="text-3xl font-bold font-mono text-text">{{ totalCourses }}</div>
            <p class="text-xs text-text/60 mt-1">Course offerings assigned</p>
          </div>
          <div class="pt-3 border-t border-border/60 mt-3 flex items-center justify-between text-[11px] font-mono text-text/50">
            <span>{{ totalSections }} Sections</span>
            <span class="text-accent cursor-pointer hover:underline" @click="navigateToTeaching">Manage &rarr;</span>
          </div>
        </div>

        <!-- 2. Active & Scheduled Exams -->
        <div class="rounded-2xl border border-border bg-surface p-5 shadow-2xs flex flex-col justify-between hover:shadow-xs transition-shadow">
          <div class="flex items-center justify-between">
            <span class="text-xs font-mono uppercase tracking-wider font-semibold text-text/60">Assessments</span>
            <div class="w-9 h-9 rounded-xl bg-success/10 text-success flex items-center justify-center">
              <CalendarDays class="w-4 h-4" />
            </div>
          </div>
          <div class="mt-4">
            <div class="text-3xl font-bold font-mono text-text">{{ allExams.length }}</div>
            <p class="text-xs text-text/60 mt-1">
              {{ activeExams.length }} active · {{ scheduledExams.length }} scheduled
            </p>
          </div>
          <div class="pt-3 border-t border-border/60 mt-3 flex items-center justify-between text-[11px] font-mono text-text/50">
            <span>{{ allExams.length - activeExams.length - scheduledExams.length }} Draft / Pending</span>
            <span class="text-accent cursor-pointer hover:underline" @click="navigateToExams">View all &rarr;</span>
          </div>
        </div>

        <!-- 3. Question Bank -->
        <div class="rounded-2xl border border-border bg-surface p-5 shadow-2xs flex flex-col justify-between hover:shadow-xs transition-shadow">
          <div class="flex items-center justify-between">
            <span class="text-xs font-mono uppercase tracking-wider font-semibold text-text/60">Question Bank</span>
            <div class="w-9 h-9 rounded-xl bg-accent/10 text-accent flex items-center justify-center">
              <LibraryBig class="w-4 h-4" />
            </div>
          </div>
          <div class="mt-4">
            <div class="text-3xl font-bold font-mono text-text">{{ totalQuestionsCount }}</div>
            <p class="text-xs text-text/60 mt-1">Repository questions</p>
          </div>
          <div class="pt-3 border-t border-border/60 mt-3 flex items-center justify-between text-[11px] font-mono text-text/50">
            <span>MCQ · Short · Essay</span>
            <span class="text-accent cursor-pointer hover:underline" @click="navigateToQuestions">Explore &rarr;</span>
          </div>
        </div>

        <!-- 4. Academic Programs -->
        <div class="rounded-2xl border border-border bg-surface p-5 shadow-2xs flex flex-col justify-between hover:shadow-xs transition-shadow">
          <div class="flex items-center justify-between">
            <span class="text-xs font-mono uppercase tracking-wider font-semibold text-text/60">Cohort Scope</span>
            <div class="w-9 h-9 rounded-xl bg-accent/10 text-accent flex items-center justify-center">
              <GraduationCap class="w-4 h-4" />
            </div>
          </div>
          <div class="mt-4">
            <div class="text-3xl font-bold font-mono text-text">{{ uniquePrograms.length }}</div>
            <p class="text-xs text-text/60 mt-1">Degree programs taught</p>
          </div>
          <div class="pt-3 border-t border-border/60 mt-3 flex items-center justify-between text-[11px] font-mono text-text/50">
            <span>Undergraduate &amp; Major</span>
            <span class="text-text/40">Verified</span>
          </div>
        </div>
      </section>

      <!-- ── 6. Two-Column Operations Layout ────────────────────────── -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left: Examinations & Teaching Offerings (2 Columns) -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Recent Examinations -->
          <div class="bg-surface rounded-2xl border border-border shadow-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-border flex items-center justify-between">
              <div>
                <h2 class="font-bold text-text font-display">Recent Examinations</h2>
                <p class="text-xs text-text/60">Active sessions, scheduled assessments, and drafts</p>
              </div>
              <BaseButton variant="secondary" size="sm" @click="navigateToExams">
                <span>View All Exams</span>
                <ArrowRight class="w-3.5 h-3.5" />
              </BaseButton>
            </div>

            <!-- Empty Exams State -->
            <div v-if="allExams.length === 0" class="p-10 text-center space-y-3">
              <FileQuestion class="w-10 h-10 mx-auto text-text/30" />
              <h3 class="text-sm font-semibold text-text">No exams created yet</h3>
              <p class="text-xs text-text/60 max-w-sm mx-auto">
                Create formal assessment sessions associated with your assigned course offerings.
              </p>
              <BaseButton variant="primary" size="sm" @click="navigateToCreateExam">
                <Plus class="w-3.5 h-3.5" />
                <span>Create Exam</span>
              </BaseButton>
            </div>

            <!-- Exams List -->
            <div v-else class="divide-y divide-border/60">
              <div
                v-for="exam in recentExams"
                :key="exam.id"
                class="p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-bg/40 transition-colors cursor-pointer group"
                @click="navigateToExam(exam)">
                <div class="space-y-1.5 min-w-0">
                  <div class="flex items-center gap-2 flex-wrap text-xs">
                    <ExamStatusBadge :status="exam.status" />
                    <span v-if="(exam as any).courseCode" class="font-mono text-[11px] font-bold text-accent uppercase">
                      {{ (exam as any).courseCode }}
                    </span>
                    <span class="text-border">•</span>
                    <span class="text-[11px] text-text/60 font-mono capitalize">
                      {{ exam.type }}
                    </span>
                  </div>

                  <h3 class="font-bold text-text font-display text-base group-hover:text-accent transition-colors truncate">
                    {{ exam.title }}
                  </h3>

                  <div class="flex items-center gap-3 text-xs text-text/60 font-mono">
                    <span class="flex items-center gap-1">
                      <Clock3 class="w-3.5 h-3.5 text-text/40" />
                      {{ exam.duration_minutes }}m
                    </span>
                    <span>•</span>
                    <span class="flex items-center gap-1">
                      <Layers class="w-3.5 h-3.5 text-text/40" />
                      {{ exam.total_questions ?? 0 }} questions
                    </span>
                    <span>•</span>
                    <span>{{ exam.total_marks ?? 0 }} pts</span>
                  </div>
                </div>

                <div class="shrink-0 flex items-center gap-2">
                  <BaseButton variant="secondary" size="sm" class="font-semibold text-xs" @click.stop="navigateToExam(exam)">
                    <span>Manage</span>
                    <ChevronRight class="w-3.5 h-3.5 text-text/40" />
                  </BaseButton>
                </div>
              </div>
            </div>
          </div>

          <!-- My Course Offerings -->
          <div class="bg-surface rounded-2xl border border-border shadow-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-border flex items-center justify-between">
              <div>
                <h2 class="font-bold text-text font-display">Assigned Course Offerings</h2>
                <p class="text-xs text-text/60">Teaching commitments for current and upcoming terms</p>
              </div>
              <BaseButton variant="secondary" size="sm" @click="navigateToTeaching">
                <span>View All Teaching</span>
                <ArrowRight class="w-3.5 h-3.5" />
              </BaseButton>
            </div>

            <!-- Empty Teaching State -->
            <div v-if="teachingList.length === 0" class="p-10 text-center space-y-3">
              <BookOpen class="w-10 h-10 mx-auto text-text/30" />
              <h3 class="text-sm font-semibold text-text">No teaching assignments recorded</h3>
              <p class="text-xs text-text/60 max-w-sm mx-auto">
                Once faculty administrators assign you to course sections, they will appear here.
              </p>
            </div>

            <!-- Teaching List -->
            <div v-else class="divide-y divide-border/60">
              <div
                v-for="item in teachingList.slice(0, 5)"
                :key="item.id"
                class="p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-bg/40 transition-colors cursor-pointer group"
                @click="navigateToTeachingDetail(item.id)">
                <div class="space-y-1.5 min-w-0">
                  <div class="flex items-center gap-2 text-xs">
                    <span class="font-mono font-bold text-accent uppercase">
                      {{ item.course.code }}
                    </span>
                    <span class="text-border">•</span>
                    <span class="text-text/60">
                      {{ item.semester.name }} {{ item.semester.academic_year }}
                    </span>
                    <span class="text-border">•</span>
                    <span class="capitalize px-2 py-0.5 rounded-full bg-bg border border-border text-[11px] text-text/70 font-mono">
                      {{ item.status.replaceAll('_', ' ') }}
                    </span>
                  </div>

                  <h3 class="font-bold text-text font-display text-base group-hover:text-accent transition-colors">
                    {{ item.course.name }}
                  </h3>

                  <div class="flex flex-wrap items-center gap-1.5 pt-0.5">
                    <span
                      v-for="assignment in item.assignments"
                      :key="assignment.id"
                      class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-bg border border-border text-[11px] font-mono text-text/70">
                      <Users2 class="w-3 h-3 text-text/40" />
                      {{ assignment.section?.name }}
                    </span>
                  </div>
                </div>

                <div class="shrink-0 flex items-center gap-2">
                  <BaseButton variant="secondary" size="sm" class="font-semibold text-xs" @click.stop="navigateToTeachingDetail(item.id)">
                    <span>Course Hub</span>
                    <ChevronRight class="w-3.5 h-3.5 text-text/40" />
                  </BaseButton>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right: Actions, Questions & Programs (1 Column) -->
        <div class="space-y-6">
          <!-- Quick Operations Card -->
          <div class="bg-surface rounded-2xl border border-border p-5 shadow-xs space-y-4">
            <h2 class="font-bold text-text font-display">Quick Actions</h2>
            <div class="space-y-2">
              <button
                type="button"
                class="w-full flex items-center justify-between p-3 rounded-xl bg-bg/70 hover:bg-bg border border-border transition-colors text-left group cursor-pointer"
                @click="navigateToCreateExam">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-lg bg-accent/10 text-accent flex items-center justify-center">
                    <FileQuestion class="w-4 h-4" />
                  </div>
                  <div>
                    <span class="text-sm font-semibold text-text block leading-tight">Create Examination</span>
                    <span class="text-[11px] text-text/50">Draft new exam configuration</span>
                  </div>
                </div>
                <ArrowRight class="w-4 h-4 text-text/30 group-hover:text-accent group-hover:translate-x-0.5 transition-all" />
              </button>

              <button
                type="button"
                class="w-full flex items-center justify-between p-3 rounded-xl bg-bg/70 hover:bg-bg border border-border transition-colors text-left group cursor-pointer"
                @click="navigateToAddQuestion">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-lg bg-accent/10 text-accent flex items-center justify-center">
                    <Plus class="w-4 h-4" />
                  </div>
                  <div>
                    <span class="text-sm font-semibold text-text block leading-tight">Add Question</span>
                    <span class="text-[11px] text-text/50">Contribute to question bank</span>
                  </div>
                </div>
                <ArrowRight class="w-4 h-4 text-text/30 group-hover:text-accent group-hover:translate-x-0.5 transition-all" />
              </button>

              <button
                type="button"
                class="w-full flex items-center justify-between p-3 rounded-xl bg-bg/70 hover:bg-bg border border-border transition-colors text-left group cursor-pointer"
                @click="navigateToQuestions">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-lg bg-accent/10 text-accent flex items-center justify-center">
                    <LibraryBig class="w-4 h-4" />
                  </div>
                  <div>
                    <span class="text-sm font-semibold text-text block leading-tight">Question Bank</span>
                    <span class="text-[11px] text-text/50">Manage approved repository</span>
                  </div>
                </div>
                <ArrowRight class="w-4 h-4 text-text/30 group-hover:text-accent group-hover:translate-x-0.5 transition-all" />
              </button>
            </div>
          </div>

          <!-- Question Bank Activity Snapshot -->
          <div class="bg-surface rounded-2xl border border-border p-5 shadow-xs space-y-4">
            <div class="flex items-center justify-between border-b border-border pb-3">
              <div>
                <h2 class="font-bold text-text font-display">Recent Questions</h2>
                <p class="text-xs text-text/50">Authored question items</p>
              </div>
              <span class="text-xs font-mono font-bold text-accent">{{ totalQuestionsCount }} Total</span>
            </div>

            <div v-if="recentQuestions.length === 0" class="py-6 text-center text-xs text-text/50">
              No questions authored yet.
            </div>

            <div v-else class="space-y-2.5">
              <div
                v-for="q in recentQuestions"
                :key="q.id"
                class="p-3 rounded-xl bg-bg border border-border/80 text-xs space-y-1.5 hover:border-accent/30 transition-colors cursor-pointer"
                @click="router.push({ name: 'instructor.questions.detail', params: { questionId: q.id } })">
                <div class="flex items-center justify-between">
                  <span class="font-mono text-[10px] uppercase font-bold text-accent">
                    {{ q.type }}
                  </span>
                  <span
                    class="font-mono text-[10px] px-1.5 py-0.5 rounded capitalize"
                    :class="
                      q.difficulty === 'hard'
                        ? 'bg-error/15 text-error'
                        : q.difficulty === 'medium'
                          ? 'bg-warning/15 text-warning'
                          : 'bg-success/15 text-success'
                    ">
                    {{ q.difficulty || 'Normal' }}
                  </span>
                </div>
                <p class="text-text font-medium line-clamp-2 leading-snug">
                  {{ q.content }}
                </p>
              </div>
            </div>

            <BaseButton variant="secondary" size="sm" class="w-full text-xs font-semibold" @click="navigateToQuestions">
              <span>View All Questions</span>
              <ArrowRight class="w-3.5 h-3.5" />
            </BaseButton>
          </div>

          <!-- Academic Cohorts / Programs -->
          <div class="bg-surface rounded-2xl border border-border p-5 shadow-xs space-y-4">
            <div class="flex items-center justify-between border-b border-border pb-3">
              <div>
                <h2 class="font-bold text-text font-display">Degree Programs</h2>
                <p class="text-xs text-text/50">Represented in your sections</p>
              </div>
              <GraduationCap class="w-4 h-4 text-text/40" />
            </div>

            <div v-if="uniquePrograms.length === 0" class="py-6 text-center text-xs text-text/50">
              No program allocations found.
            </div>

            <div v-else class="space-y-2">
              <div
                v-for="program in uniquePrograms"
                :key="program.id"
                class="flex items-center justify-between p-3 rounded-xl bg-bg border border-border text-xs">
                <div class="min-w-0 pr-2">
                  <span class="font-semibold text-text block truncate">{{ program.name }}</span>
                  <span class="font-mono text-[10px] text-text/50 uppercase">{{ program.code }}</span>
                </div>
                <ShieldCheck class="w-4 h-4 text-success shrink-0" />
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
