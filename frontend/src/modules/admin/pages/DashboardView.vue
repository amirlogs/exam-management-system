<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { AlertCircle, ArrowRight, BookOpen, CalendarDays, CheckCircle2, ClipboardCheck, Clock3, GraduationCap, Layers3, RefreshCw, UserRound, Users } from 'lucide-vue-next';

import api from '@/api/axios';

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

async function getCount(endpoint: string) {
  const response = await api.get<ListResponse<unknown>>(endpoint, {
    params: {
      page: 1,
      per_page: 1,
    },
  });

  return response.data.pagination?.total ?? 0;
}

async function loadDashboard(refresh = false) {
  if (refresh) {
    refreshing.value = true;
  } else {
    loading.value = true;
  }

  error.value = '';

  try {
    const [collegeCount, departmentCount, programCount, courseCount, userCount, instructorCount, semesterResponse] = await Promise.all([
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
      colleges: collegeCount,
      departments: departmentCount,
      programs: programCount,
      courses: courseCount,
      users: userCount,
      instructors: instructorCount,
    };

    const semesters = semesterResponse.data.data;

    activeSemester.value = semesters.find((semester) => semester.status === 'active') ?? semesters.find((semester) => semester.status === 'upcoming') ?? null;

    if (activeSemester.value) {
      const offeringResponse = await api.get<ListResponse<CourseOffering>>('/course-offerings', {
        params: {
          semester_id: activeSemester.value.id,
          page: 1,
          per_page: 100,
        },
      });

      offerings.value = offeringResponse.data.data;
    } else {
      offerings.value = [];
    }
  } catch (err: any) {
    error.value = err?.response?.data?.message || 'Unable to load dashboard data.';
  } finally {
    loading.value = false;
    refreshing.value = false;
  }
}

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
  <div class="mx-auto w-full max-w-360 space-y-6 px-6 py-6">
    <!-- Header -->
    <div class="flex flex-col gap-4 border-b border-border pb-5 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-accent">Administration</p>

        <h1 class="mt-1.5 text-2xl font-semibold tracking-tight text-text">Dashboard</h1>

        <p class="mt-1 max-w-2xl text-sm text-text/50">A current view of the university's academic and examination operations.</p>
      </div>

      <div class="flex items-center gap-2">
        <div v-if="activeSemester" class="flex items-center gap-2.5 rounded-md border border-border bg-surface px-3 py-2">
          <CalendarDays class="h-4 w-4 text-accent" />

          <div>
            <p class="text-[10px] font-medium uppercase tracking-wide text-text/40">Active semester</p>

            <p class="text-sm font-medium text-text">
              {{ activeSemester.name }}
              ·
              {{ activeSemester.academic_year }}
            </p>
          </div>
        </div>

        <button
          type="button"
          class="flex h-9 w-9 items-center justify-center rounded-md border border-border bg-surface text-text/50 transition-colors hover:bg-text/5 hover:text-text"
          title="Refresh"
          :disabled="refreshing"
          @click="retry">
          <RefreshCw class="h-4 w-4" :class="refreshing ? 'animate-spin' : ''" />
        </button>
      </div>
    </div>

    <!-- Error -->
    <div v-if="error" class="flex items-start gap-3 rounded-md border border-error/30 bg-error/5 p-4">
      <AlertCircle class="mt-0.5 h-4 w-4 shrink-0 text-error" />

      <div class="min-w-0 flex-1">
        <p class="text-sm font-medium text-error">
          {{ error }}
        </p>

        <button type="button" class="mt-1 text-xs font-medium text-error hover:underline" @click="retry">Try again</button>
      </div>
    </div>

    <!-- University overview -->
    <section>
      <div class="mb-3 flex items-end justify-between">
        <div>
          <h2 class="text-sm font-semibold text-text">University overview</h2>

          <p class="mt-0.5 text-xs text-text/40">Current academic structure.</p>
        </div>
      </div>

      <div class="grid grid-cols-2 divide-x divide-y divide-border overflow-hidden rounded-lg border border-border bg-surface sm:grid-cols-3 lg:grid-cols-6 lg:divide-y-0">
        <div
          v-for="item in [
            {
              label: 'Colleges',
              value: counts.colleges,
              icon: GraduationCap,
            },
            {
              label: 'Departments',
              value: counts.departments,
              icon: Layers3,
            },
            {
              label: 'Programs',
              value: counts.programs,
              icon: GraduationCap,
            },
            {
              label: 'Courses',
              value: counts.courses,
              icon: BookOpen,
            },
            {
              label: 'Instructors',
              value: counts.instructors,
              icon: UserRound,
            },
            {
              label: 'Users',
              value: counts.users,
              icon: Users,
            },
          ]"
          :key="item.label"
          class="min-w-0 p-4">
          <div class="flex items-center gap-2">
            <component :is="item.icon" class="h-3.5 w-3.5 text-text/35" />

            <span class="truncate text-xs font-medium text-text/50">
              {{ item.label }}
            </span>
          </div>

          <div v-if="loading" class="mt-3 h-7 w-12 animate-pulse rounded bg-text/5" />

          <p v-else class="mt-3 text-xl font-semibold tracking-tight text-text">
            {{ item.value }}
          </p>
        </div>
      </div>
    </section>

    <!-- Semester snapshot -->
    <section>
      <div class="mb-3 flex items-end justify-between">
        <div>
          <h2 class="text-sm font-semibold text-text">Semester snapshot</h2>

          <p class="mt-0.5 text-xs text-text/40">Course offering activity for the active semester.</p>
        </div>

        <span v-if="activeSemester" class="text-xs text-text/40">
          {{ formatDate(activeSemester.start_date) }}
          –
          {{ formatDate(activeSemester.end_date) }}
        </span>
      </div>

      <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Total -->
        <div class="rounded-lg border border-border bg-surface p-4">
          <div class="flex items-center gap-2">
            <BookOpen class="h-4 w-4 text-accent" />

            <span class="text-xs font-medium text-text/50"> Course offerings </span>
          </div>

          <p class="mt-3 text-xl font-semibold text-text">
            {{ loading ? '—' : offeringStats.total }}
          </p>

          <p class="mt-1 text-xs text-text/40">Created for this semester</p>
        </div>

        <!-- Draft -->
        <div class="rounded-lg border border-border bg-surface p-4">
          <div class="flex items-center gap-2">
            <Clock3 class="h-4 w-4 text-warning" />

            <span class="text-xs font-medium text-text/50"> Draft </span>
          </div>

          <p class="mt-3 text-xl font-semibold text-text">
            {{ loading ? '—' : offeringStats.draft }}
          </p>

          <p class="mt-1 text-xs text-text/40">Still being prepared</p>
        </div>

        <!-- Approved -->
        <div class="rounded-lg border border-border bg-surface p-4">
          <div class="flex items-center gap-2">
            <CheckCircle2 class="h-4 w-4 text-success" />

            <span class="text-xs font-medium text-text/50"> Approved </span>
          </div>

          <p class="mt-3 text-xl font-semibold text-text">
            {{ loading ? '—' : offeringStats.approved }}
          </p>

          <p class="mt-1 text-xs text-text/40">Ready for academic work</p>
        </div>

        <!-- Needs instructor -->
        <div class="rounded-lg border border-warning/20 bg-warning/5 p-4">
          <div class="flex items-center gap-2">
            <UserRound class="h-4 w-4 text-warning" />

            <span class="text-xs font-medium text-text/50"> Needs instructor </span>
          </div>

          <p class="mt-3 text-xl font-semibold text-warning">
            {{ loading ? '—' : offeringStats.needsInstructor }}
          </p>

          <p class="mt-1 text-xs text-text/40">Draft offerings without assignments</p>
        </div>
      </div>
    </section>

    <!-- Operational area -->
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-5">
      <!-- Needs attention -->
      <section class="overflow-hidden rounded-lg border border-border bg-surface xl:col-span-3">
        <div class="flex items-start justify-between border-b border-border px-5 py-4">
          <div>
            <h2 class="text-sm font-semibold text-text">Needs attention</h2>

            <p class="mt-1 text-xs text-text/40">Items that currently need administrative action.</p>
          </div>

          <AlertCircle class="h-4 w-4 text-warning" />
        </div>

        <div v-if="loading" class="divide-y divide-border">
          <div v-for="item in 3" :key="item" class="px-5 py-4">
            <div class="h-3.5 w-44 animate-pulse rounded bg-text/5" />

            <div class="mt-2 h-3 w-72 animate-pulse rounded bg-text/5" />
          </div>
        </div>

        <div v-else-if="attentionItems.length" class="divide-y divide-border">
          <div v-for="item in attentionItems" :key="`${item.label}-${item.title}`" class="flex items-start gap-3 px-5 py-4">
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md" :class="item.tone === 'danger' ? 'bg-error/10 text-error' : 'bg-warning/10 text-warning'">
              <component :is="item.icon" class="h-4 w-4" />
            </div>

            <div class="min-w-0 flex-1">
              <div class="flex flex-wrap items-center gap-2">
                <p class="text-sm font-medium text-text">
                  {{ item.title }}
                </p>

                <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold" :class="item.tone === 'danger' ? 'bg-error/10 text-error' : 'bg-warning/10 text-warning'">
                  {{ item.label }}
                </span>
              </div>

              <p class="mt-1 text-xs leading-5 text-text/50">
                {{ item.description }}
              </p>

              <p class="mt-1 font-mono text-[11px] text-text/35">
                {{ item.meta }}
              </p>
            </div>

            <button type="button" class="mt-1 shrink-0 text-xs font-medium text-accent transition-colors hover:text-accent/80">Review</button>
          </div>
        </div>

        <div v-else class="px-5 py-10 text-center">
          <CheckCircle2 class="mx-auto h-5 w-5 text-success" />

          <p class="mt-2 text-sm font-medium text-text">No action required</p>

          <p class="mt-1 text-xs text-text/40">The current semester has no flagged course offerings.</p>
        </div>
      </section>

      <!-- Workflow summary -->
      <section class="overflow-hidden rounded-lg border border-border bg-surface xl:col-span-2">
        <div class="border-b border-border px-5 py-4">
          <h2 class="text-sm font-semibold text-text">Workflow summary</h2>

          <p class="mt-1 text-xs text-text/40">Current course offering progress.</p>
        </div>

        <div class="p-5">
          <div class="space-y-5">
            <div>
              <div class="mb-2 flex items-center justify-between">
                <span class="text-xs font-medium text-text/55"> Approved </span>

                <span class="text-xs tabular-nums text-text/45">
                  {{ offeringStats.approved }}
                  /
                  {{ offeringStats.total }}
                </span>
              </div>

              <div class="h-1.5 overflow-hidden rounded-full bg-text/5">
                <div
                  class="h-full rounded-full bg-success transition-all"
                  :style="{
                    width: offeringStats.total ? `${(offeringStats.approved / offeringStats.total) * 100}%` : '0%',
                  }" />
              </div>
            </div>

            <div>
              <div class="mb-2 flex items-center justify-between">
                <span class="text-xs font-medium text-text/55"> Instructor assignments </span>

                <span class="text-xs tabular-nums text-text/45">
                  {{ offeringStats.assignments }}
                </span>
              </div>

              <div class="h-1.5 overflow-hidden rounded-full bg-text/5">
                <div
                  class="h-full rounded-full bg-accent transition-all"
                  :style="{
                    width: offeringStats.sections ? `${Math.min((offeringStats.assignments / offeringStats.sections) * 100, 100)}%` : '0%',
                  }" />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-3 border-t border-border pt-5">
              <div>
                <p class="text-xs text-text/40">Sections</p>

                <p class="mt-1 text-lg font-semibold text-text">
                  {{ offeringStats.sections }}
                </p>
              </div>

              <div>
                <p class="text-xs text-text/40">Rejected</p>

                <p class="mt-1 text-lg font-semibold" :class="offeringStats.rejected ? 'text-error' : 'text-text'">
                  {{ offeringStats.rejected }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>

    <!-- Recent course offerings -->
    <section class="overflow-hidden rounded-lg border border-border bg-surface">
      <div class="flex items-center justify-between border-b border-border px-5 py-4">
        <div>
          <h2 class="text-sm font-semibold text-text">Recent course offerings</h2>

          <p class="mt-1 text-xs text-text/40">Most recently updated offerings in the active semester.</p>
        </div>
      </div>

      <div v-if="loading" class="divide-y divide-border">
        <div v-for="item in 4" :key="item" class="flex items-center gap-4 px-5 py-4">
          <div class="h-8 w-8 animate-pulse rounded-md bg-text/5" />

          <div class="flex-1">
            <div class="h-3.5 w-48 animate-pulse rounded bg-text/5" />

            <div class="mt-2 h-3 w-32 animate-pulse rounded bg-text/5" />
          </div>
        </div>
      </div>

      <div v-else-if="recentOfferings.length" class="divide-y divide-border">
        <div v-for="offering in recentOfferings" :key="offering.id" class="flex items-center gap-4 px-5 py-4 transition-colors hover:bg-text/2">
          <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md border border-border bg-bg text-text/40">
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

            <p class="mt-1 font-mono text-xs text-text/40">
              {{ offering.course?.code }}
            </p>
          </div>

          <div class="hidden items-center gap-4 text-right sm:flex">
            <div>
              <p class="text-xs text-text/40">Sections</p>

              <p class="mt-0.5 text-sm font-medium text-text">
                {{ offering.sections?.length ?? 0 }}
              </p>
            </div>

            <div>
              <p class="text-xs text-text/40">Instructors</p>

              <p class="mt-0.5 text-sm font-medium text-text">
                {{ offering.instructor_assignments?.length ?? 0 }}
              </p>
            </div>

            <span class="w-14 text-xs text-text/35">
              {{ formatRelativeDate(offering.updated_at) }}
            </span>

            <ArrowRight class="h-4 w-4 text-text/25" />
          </div>
        </div>
      </div>

      <div v-else class="px-5 py-12 text-center">
        <BookOpen class="mx-auto h-5 w-5 text-text/25" />

        <p class="mt-2 text-sm font-medium text-text">No course offerings yet</p>

        <p class="mt-1 text-xs text-text/40">Create course offerings from the active semester workflow.</p>
      </div>
    </section>
  </div>
</template>
