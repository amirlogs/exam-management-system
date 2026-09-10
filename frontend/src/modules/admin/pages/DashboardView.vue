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
  ExternalLink,
  GraduationCap,
  Layers3,
  RefreshCw,
  Sparkles,
  Upload,
  UserRound,
  Users,
} from 'lucide-vue-next';

import api from '@/api/axios';
import BaseBadge from '@/shared/components/ui/BaseBadge.vue';
import BaseButton from '@/shared/components/ui/BaseButton.vue';
import BaseCard from '@/shared/components/ui/BaseCard.vue';

const router = useRouter();

interface Pagination {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number;
  to: number;
}

interface ListResponse<T> {
  data: T[];
  pagination: Pagination;
}

interface Semester {
  id: number;
  name: string;
  academic_year: string;
  status: string;
  start_date?: string;
  end_date?: string;
}

interface CourseOffering {
  id: number;
  course_id: number;
  semester_id: number;
  status: 'draft' | 'approved' | 'rejected' | 'cancelled';
  rejection_reason: string | null;
  updated_at: string;
  course?: {
    id: number;
    code: string;
    name: string;
    credit_hours: number;
  };
  sections?: Array<{
    id: number;
    name: string;
    year_level: number;
    program_id: number;
  }>;
  instructor_assignments?: Array<{
    id: number;
    section_id: number;
    instructor_id: number;
    type: 'lead_instructor' | 'instructor';
  }>;
}

interface DashboardCounts {
  colleges: number;
  departments: number;
  programs: number;
  courses: number;
  users: number;
  instructors: number;
}

const loading = ref(true);
const refreshing = ref(false);
const error = ref('');

const counts = ref<DashboardCounts>({
  colleges: 0,
  departments: 0,
  programs: 0,
  courses: 0,
  users: 0,
  instructors: 0,
});

const activeSemester = ref<Semester | null>(null);
const offerings = ref<CourseOffering[]>([]);

async function getCount(endpoint: string): Promise<number> {
  try {
    const response = await api.get<ListResponse<unknown>>(endpoint, {
      params: {
        page: 1,
        per_page: 1,
      },
    });
    return response.data.pagination?.total ?? 0;
  } catch {
    return 0;
  }
}

async function loadDashboard(refresh = false) {
  if (refresh) {
    refreshing.value = true;
  } else {
    loading.value = true;
  }

  error.value = '';

  try {
    const [collegesRes, departmentsRes, programsRes, coursesRes, usersRes, instructorsRes, semesterRes] = await Promise.allSettled([
      getCount('/colleges'),
      getCount('/departments'),
      getCount('/programs'),
      getCount('/courses'),
      getCount('/users'),
      getCount('/instructors'),
      api.get<ListResponse<Semester>>('/semesters', {
        params: {
          page: 1,
          per_page: 100,
        },
      }),
    ]);

    counts.value = {
      colleges: collegesRes.status === 'fulfilled' ? collegesRes.value : 0,
      departments: departmentsRes.status === 'fulfilled' ? departmentsRes.value : 0,
      programs: programsRes.status === 'fulfilled' ? programsRes.value : 0,
      courses: coursesRes.status === 'fulfilled' ? coursesRes.value : 0,
      users: usersRes.status === 'fulfilled' ? usersRes.value : 0,
      instructors: instructorsRes.status === 'fulfilled' ? instructorsRes.value : 0,
    };

    if (semesterRes.status === 'fulfilled') {
      const semesters = semesterRes.value.data.data;
      activeSemester.value = semesters.find((semester) => semester.status === 'active') ?? semesters.find((semester) => semester.status === 'upcoming') ?? null;

      if (activeSemester.value) {
        try {
          const offeringResponse = await api.get<ListResponse<CourseOffering>>('/course-offerings', {
            params: {
              semester_id: activeSemester.value.id,
              page: 1,
              per_page: 100,
            },
          });
          offerings.value = offeringResponse.data.data;
        } catch {
          offerings.value = [];
        }
      } else {
        offerings.value = [];
      }
    }
  } catch (err: any) {
    error.value = err?.response?.data?.message || 'Unable to load dashboard data.';
  } finally {
    loading.value = false;
    refreshing.value = false;
  }
}

const overviewItems = computed(() => [
  { label: 'Colleges', value: counts.value.colleges, icon: GraduationCap, to: '/admin/colleges' },
  { label: 'Departments', value: counts.value.departments, icon: Layers3, to: '/admin/departments' },
  { label: 'Programs', value: counts.value.programs, icon: GraduationCap, to: '/admin/programs' },
  { label: 'Courses', value: counts.value.courses, icon: BookOpen, to: '/admin/courses' },
  { label: 'Instructors', value: counts.value.instructors, icon: UserRound, to: '/admin/instructors' },
  { label: 'Users', value: counts.value.users, icon: Users, to: '/admin/users' },
]);

const offeringStats = computed(() => {
  const data = offerings.value;

  return {
    total: data.length,
    draft: data.filter((item) => item.status === 'draft').length,
    approved: data.filter((item) => item.status === 'approved').length,
    rejected: data.filter((item) => item.status === 'rejected').length,
    needsInstructor: data.filter((item) => item.status === 'draft' && !item.instructor_assignments?.length).length,
    sections: data.reduce((sum, item) => sum + (item.sections?.length ?? 0), 0),
    assignments: data.reduce((sum, item) => sum + (item.instructor_assignments?.length ?? 0), 0),
  };
});

const attentionItems = computed(() => {
  const items: Array<{
    title: string;
    description: string;
    meta: string;
    label: string;
    icon: typeof AlertCircle;
    tone: 'warning' | 'danger';
  }> = [];

  for (const offering of offerings.value) {
    if (offering.status === 'draft' && !offering.instructor_assignments?.length) {
      items.push({
        title: offering.course?.name ?? 'Course offering',
        description: 'Instructor assignment is still required before this offering can move forward.',
        meta: offering.course?.code ?? 'Course offering',
        label: 'Assignment needed',
        icon: UserRound,
        tone: 'warning',
      });
    }

    if (offering.status === 'rejected') {
      items.push({
        title: offering.course?.name ?? 'Course offering',
        description: offering.rejection_reason ?? 'This offering was rejected and needs review.',
        meta: offering.course?.code ?? 'Course offering',
        label: 'Rejected',
        icon: AlertCircle,
        tone: 'danger',
      });
    }
  }

  return items.slice(0, 5);
});

const recentOfferings = computed(() => [...offerings.value].sort((a, b) => new Date(b.updated_at).getTime() - new Date(a.updated_at).getTime()).slice(0, 5));

function formatRelativeDate(value: string) {
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

function formatDate(value?: string) {
  if (!value) return '—';

  return new Date(value).toLocaleDateString(undefined, {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  });
}

function retry() {
  loadDashboard(true);
}

onMounted(() => {
  loadDashboard();
});
</script>

<template>
  <div class="mx-auto w-full max-w-7xl space-y-7 px-4 py-6 md:px-8">
    <!-- Header -->
    <div class="flex flex-col gap-4 border-b border-border pb-6 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <p class="text-xs font-mono font-medium uppercase tracking-wider text-text/40">Administration</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight text-text md:text-3xl">Dashboard</h1>
        <p class="mt-1 max-w-2xl text-sm text-text/50">A current overview of university structure, semester operations, and academic activity.</p>
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
        to="/admin/course-offerings"
        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-border bg-surface text-xs font-medium text-text/75 hover:text-text hover:border-text/30 hover:bg-text/[0.02] transition-colors">
        <BookOpen class="w-3.5 h-3.5 text-text/50" />
        <span>Course Offerings</span>
      </router-link>
      <router-link
        to="/admin/questions"
        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-border bg-surface text-xs font-medium text-text/75 hover:text-text hover:border-text/30 hover:bg-text/[0.02] transition-colors">
        <Layers3 class="w-3.5 h-3.5 text-text/50" />
        <span>Question Bank</span>
      </router-link>
      <router-link
        to="/admin/imports"
        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-border bg-surface text-xs font-medium text-text/75 hover:text-text hover:border-text/30 hover:bg-text/[0.02] transition-colors">
        <Upload class="w-3.5 h-3.5 text-text/50" />
        <span>Data Imports</span>
      </router-link>
      <router-link
        to="/admin/users"
        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-border bg-surface text-xs font-medium text-text/75 hover:text-text hover:border-text/30 hover:bg-text/[0.02] transition-colors">
        <Users class="w-3.5 h-3.5 text-text/50" />
        <span>Users</span>
      </router-link>
    </div>

    <!-- 1. University Overview (Clickable Resource Cards) -->
    <section class="space-y-3">
      <div>
        <h2 class="text-sm font-semibold text-text">University Structure</h2>
        <p class="text-xs text-text/40">Registered academic entities and users.</p>
      </div>

      <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
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

    <!-- 2. Semester Snapshot -->
    <section class="space-y-3">
      <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <h2 class="text-sm font-semibold text-text">Semester Snapshot</h2>
          <p class="text-xs text-text/40">Course offering activity for the active term.</p>
        </div>

        <span v-if="activeSemester" class="text-xs font-mono text-text/40"> {{ formatDate(activeSemester.start_date) }} – {{ formatDate(activeSemester.end_date) }} </span>
      </div>

      <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Total Offerings -->
        <div class="rounded-xl border border-border bg-surface p-4.5 shadow-2xs">
          <div class="flex items-center justify-between">
            <span class="text-xs font-medium text-text/55">Course Offerings</span>
            <BookOpen class="h-4 w-4 text-text/40" />
          </div>
          <p class="mt-3 text-2xl font-semibold text-text tabular-nums">
            {{ loading ? '—' : offeringStats.total }}
          </p>
          <p class="mt-1 text-xs text-text/40">Registered for current semester</p>
        </div>

        <!-- Draft Offerings -->
        <div class="rounded-xl border border-border bg-surface p-4.5 shadow-2xs">
          <div class="flex items-center justify-between">
            <span class="text-xs font-medium text-text/55">Draft</span>
            <Clock3 class="h-4 w-4 text-text/40" />
          </div>
          <p class="mt-3 text-2xl font-semibold text-text tabular-nums">
            {{ loading ? '—' : offeringStats.draft }}
          </p>
          <p class="mt-1 text-xs text-text/40">Currently being prepared</p>
        </div>

        <!-- Approved Offerings -->
        <div class="rounded-xl border border-border bg-surface p-4.5 shadow-2xs">
          <div class="flex items-center justify-between">
            <span class="text-xs font-medium text-text/55">Approved</span>
            <CheckCircle2 class="h-4 w-4 text-text/40" />
          </div>
          <p class="mt-3 text-2xl font-semibold text-text tabular-nums">
            {{ loading ? '—' : offeringStats.approved }}
          </p>
          <p class="mt-1 text-xs text-text/40">Active and ready for instruction</p>
        </div>

        <!-- Needs Instructor -->
        <div class="rounded-xl border border-border bg-surface p-4.5 shadow-2xs">
          <div class="flex items-center justify-between">
            <span class="text-xs font-medium text-text/55">Needs Instructor</span>
            <UserRound class="h-4 w-4 text-text/40" />
          </div>
          <p class="mt-3 text-2xl font-semibold text-text tabular-nums">
            {{ loading ? '—' : offeringStats.needsInstructor }}
          </p>
          <p class="mt-1 text-xs text-text/40">Offerings awaiting assignments</p>
        </div>
      </div>
    </section>

    <!-- 3. Operational Area (Needs Attention + Workflow Progress) -->
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-5">
      <!-- Needs Attention -->
      <section class="flex flex-col justify-between overflow-hidden rounded-xl border border-border bg-surface shadow-2xs xl:col-span-3">
        <div class="flex items-center justify-between border-b border-border/70 px-5 py-4">
          <div>
            <h2 class="text-sm font-semibold text-text">Needs Attention</h2>
            <p class="text-xs text-text/40">Actionable items requiring administrative follow-up.</p>
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

            <!-- Review CTA Button (matches theme: secondary button with hover:border-accent hover:text-accent) -->
            <button
              type="button"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-border bg-surface text-xs font-medium text-text hover:border-accent hover:text-accent transition-colors cursor-pointer shrink-0"
              @click="router.push('/admin/course-offerings')">
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
          <p class="mt-1 text-xs text-text/40 max-w-xs">All course offerings for this semester are progressing normally.</p>
        </div>
      </section>

      <!-- Workflow Summary -->
      <section class="overflow-hidden rounded-xl border border-border bg-surface shadow-2xs xl:col-span-2">
        <div class="border-b border-border/70 px-5 py-4">
          <h2 class="text-sm font-semibold text-text">Workflow Progress</h2>
          <p class="text-xs text-text/40">Offering readiness and assignment rates.</p>
        </div>

        <div class="p-5 space-y-5">
          <!-- Approved Progress Bar -->
          <div>
            <div class="mb-2 flex items-center justify-between text-xs">
              <span class="font-medium text-text/60">Approved Offerings</span>
              <span class="tabular-nums font-mono text-text/50"> {{ offeringStats.approved }} / {{ offeringStats.total }} </span>
            </div>
            <div class="h-2 overflow-hidden rounded-full bg-text/5">
              <div
                class="h-full rounded-full bg-success transition-all duration-300"
                :style="{
                  width: offeringStats.total ? `${(offeringStats.approved / offeringStats.total) * 100}%` : '0%',
                }" />
            </div>
          </div>

          <!-- Instructor Assignments Progress Bar -->
          <div>
            <div class="mb-2 flex items-center justify-between text-xs">
              <span class="font-medium text-text/60">Instructor Coverage</span>
              <span class="tabular-nums font-mono text-text/50"> {{ offeringStats.assignments }} / {{ offeringStats.sections }} </span>
            </div>
            <div class="h-2 overflow-hidden rounded-full bg-text/5">
              <div
                class="h-full rounded-full bg-text/70 transition-all duration-300"
                :style="{
                  width: offeringStats.sections ? `${Math.min((offeringStats.assignments / offeringStats.sections) * 100, 100)}%` : '0%',
                }" />
            </div>
          </div>

          <!-- Bottom Metric Tiles -->
          <div class="grid grid-cols-2 gap-3 border-t border-border/70 pt-5">
            <div class="rounded-lg bg-text/[0.02] border border-border/50 p-3">
              <p class="text-[11px] font-mono uppercase text-text/40 tracking-wider">Total Sections</p>
              <p class="mt-1 text-xl font-semibold text-text tabular-nums">
                {{ offeringStats.sections }}
              </p>
            </div>

            <div class="rounded-lg bg-text/[0.02] border border-border/50 p-3">
              <p class="text-[11px] font-mono uppercase text-text/40 tracking-wider">Rejected</p>
              <p class="mt-1 text-xl font-semibold tabular-nums" :class="offeringStats.rejected ? 'text-error' : 'text-text'">
                {{ offeringStats.rejected }}
              </p>
            </div>
          </div>
        </div>
      </section>
    </div>

    <!-- 4. Recent Course Offerings Table -->
    <section class="overflow-hidden rounded-xl border border-border bg-surface shadow-2xs">
      <div class="flex items-center justify-between border-b border-border/70 px-5 py-4">
        <div>
          <h2 class="text-sm font-semibold text-text">Recent Course Offerings</h2>
          <p class="text-xs text-text/40">Latest activity in the active semester.</p>
        </div>

        <router-link to="/admin/course-offerings" class="text-xs font-medium text-text/60 hover:text-text transition-colors flex items-center gap-1">
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

      <!-- Offerings Rows -->
      <div v-else-if="recentOfferings.length" class="divide-y divide-border/60">
        <div v-for="offering in recentOfferings" :key="offering.id" class="flex items-center gap-4 px-5 py-3.5 transition-colors hover:bg-text/[0.02]">
          <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-border/80 bg-surface text-text/50">
            <BookOpen class="h-4 w-4" />
          </div>

          <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
              <p class="truncate text-sm font-medium text-text">
                {{ offering.course?.name }}
              </p>
              <BaseBadge :variant="offering.status === 'approved' ? 'success' : offering.status === 'rejected' ? 'danger' : offering.status === 'cancelled' ? 'neutral' : 'info'">
                {{ offering.status }}
              </BaseBadge>
            </div>

            <p class="mt-0.5 font-mono text-xs text-text/40">
              {{ offering.course?.code }}
            </p>
          </div>

          <div class="hidden items-center gap-6 text-right sm:flex">
            <div>
              <p class="text-[11px] font-mono uppercase text-text/40">Sections</p>
              <p class="text-sm font-medium text-text tabular-nums">
                {{ offering.sections?.length ?? 0 }}
              </p>
            </div>

            <div>
              <p class="text-[11px] font-mono uppercase text-text/40">Instructors</p>
              <p class="text-sm font-medium text-text tabular-nums">
                {{ offering.instructor_assignments?.length ?? 0 }}
              </p>
            </div>

            <span class="w-16 text-xs text-text/40">
              {{ formatRelativeDate(offering.updated_at) }}
            </span>

            <router-link to="/admin/course-offerings" class="text-text/30 hover:text-text transition-colors p-1" title="View in course offerings">
              <ArrowRight class="h-4 w-4" />
            </router-link>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="px-5 py-14 text-center">
        <BookOpen class="mx-auto h-7 w-7 text-text/25" />
        <p class="mt-2 text-sm font-medium text-text">No course offerings yet</p>
        <p class="mt-1 text-xs text-text/40">Create course offerings from the active semester workflow.</p>
        <router-link
          to="/admin/course-offerings"
          class="mt-4 inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg border border-border text-xs font-medium text-text hover:bg-text/[0.04] transition-colors">
          <span>Go to Course Offerings</span>
          <ArrowRight class="w-3.5 h-3.5" />
        </router-link>
      </div>
    </section>
  </div>
</template>
