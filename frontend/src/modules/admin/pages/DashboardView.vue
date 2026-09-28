<script setup lang="ts">
import { computed, onMounted, ref, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import {
  AlertCircle,
  ArrowRight,
  BookOpen,
  CheckCircle2,
  Clock3,
  GraduationCap,
  Layers3,
  RefreshCw,
  Upload,
  UserRound,
  Users,
  ChevronRight,
  Activity
} from 'lucide-vue-next';

import api from '@/api/axios';
import BaseBadge from '@/shared/components/ui/BaseBadge.vue';
import BaseButton from '@/shared/components/ui/BaseButton.vue';
import { useAuthStore } from '@/stores/auth';
import { useUiStore } from '@/stores/ui';
import { handleApiError } from '@/shared/utils/apiError';

const router = useRouter();
const authStore = useAuthStore();
const uiStore = useUiStore();

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

const currentDate = ref(new Intl.DateTimeFormat('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' }).format(new Date()));
let timer: ReturnType<typeof setInterval>;

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

const adminName = computed(() => {
  const user = authStore.user;
  if (!user) return 'Administrator';
  if (user.first_name && user.last_name) {
    return `${user.first_name} ${user.last_name}`;
  }
  return user.first_name || user.username || 'Administrator';
});

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

    if (refresh) {
      uiStore.showToast('Dashboard data refreshed.', 'success');
    }
  } catch (err: any) {
    error.value = err?.response?.data?.message || 'Unable to load dashboard data.';
    handleApiError(err, uiStore, undefined, 'Unable to load dashboard data.');
  } finally {
    loading.value = false;
    refreshing.value = false;
  }
}

const overviewItems = computed(() => [
  { label: 'Colleges', value: counts.value.colleges, icon: GraduationCap, to: '/admin/colleges' },
  { label: 'Departments', value: counts.value.departments, icon: Layers3, to: '/admin/departments' },
  { label: 'Programs', value: counts.value.programs, icon: BookOpen, to: '/admin/programs' },
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

function retry() {
  loadDashboard(true);
}

onMounted(() => {
  loadDashboard();
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
        <p class="text-[11px] font-mono font-medium uppercase tracking-widest text-text/40 mb-1">Administration Portal</p>
        <h1 class="font-display text-2xl sm:text-3xl font-bold tracking-tight text-text">
          Welcome back, {{ adminName }}
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
      <router-link to="/admin/course-offerings" class="group flex items-center gap-2.5 rounded-full border border-border bg-surface px-4 py-2 text-xs font-medium text-text/70 transition-all hover:border-accent/30 hover:bg-accent/5 hover:text-accent shadow-sm">
        <Activity class="w-3.5 h-3.5" />
        Course Offerings
      </router-link>
      <router-link to="/admin/questions" class="group flex items-center gap-2.5 rounded-full border border-border bg-surface px-4 py-2 text-xs font-medium text-text/70 transition-all hover:border-accent/30 hover:bg-accent/5 hover:text-accent shadow-sm">
        <Layers3 class="w-3.5 h-3.5" />
        Question Bank
      </router-link>
      <router-link to="/admin/imports" class="group flex items-center gap-2.5 rounded-full border border-border bg-surface px-4 py-2 text-xs font-medium text-text/70 transition-all hover:border-accent/30 hover:bg-accent/5 hover:text-accent shadow-sm">
        <Upload class="w-3.5 h-3.5" />
        Import Data
      </router-link>
      <router-link to="/admin/users" class="group flex items-center gap-2.5 rounded-full border border-border bg-surface px-4 py-2 text-xs font-medium text-text/70 transition-all hover:border-accent/30 hover:bg-accent/5 hover:text-accent shadow-sm">
        <Users class="w-3.5 h-3.5" />
        User Directory
      </router-link>
    </div>

    <!-- 2. Unified Institutional Structure KPI Deck -->
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
      <!-- Column A: Offering Pipeline & Readiness -->
      <section class="xl:col-span-3 rounded-2xl border border-border bg-surface shadow-sm overflow-hidden flex flex-col">
        <div class="px-6 py-5 border-b border-border">
          <h2 class="text-sm font-semibold text-text">Offering Pipeline & Readiness</h2>
          <p class="text-xs text-text/50 mt-1">Lifecycle and staffing coverage for active semester.</p>
        </div>
        
        <div class="p-6 flex-1 flex flex-col justify-between gap-8">
          <!-- Pipeline Stages -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="flex flex-col gap-1">
              <span class="text-[11px] font-mono uppercase text-text/50 tracking-wider">Total Offerings</span>
              <span class="text-2xl font-bold font-display text-text tabular-nums">{{ loading ? '—' : offeringStats.total }}</span>
            </div>
            <div class="flex flex-col gap-1">
              <span class="text-[11px] font-mono uppercase text-warning tracking-wider">Draft / Prep</span>
              <span class="text-2xl font-bold font-display text-text tabular-nums">{{ loading ? '—' : offeringStats.draft }}</span>
            </div>
            <div class="flex flex-col gap-1">
              <span class="text-[11px] font-mono uppercase text-success tracking-wider">Approved</span>
              <span class="text-2xl font-bold font-display text-text tabular-nums">{{ loading ? '—' : offeringStats.approved }}</span>
            </div>
            <div class="flex flex-col gap-1">
              <span class="text-[11px] font-mono uppercase text-error tracking-wider">Rejected</span>
              <span class="text-2xl font-bold font-display text-text tabular-nums">{{ loading ? '—' : offeringStats.rejected }}</span>
            </div>
          </div>

          <!-- Multi-segment Progress Bar: Offering Lifecycle -->
          <div class="space-y-3">
            <div class="flex items-center justify-between text-xs font-medium">
              <span class="text-text/70">Offering Lifecycle Status</span>
              <span class="text-text/50 tabular-nums">100% Total</span>
            </div>
            <div class="h-2.5 w-full rounded-full bg-bg border border-border/50 flex overflow-hidden">
              <div class="h-full bg-success transition-all duration-500" :style="{ width: offeringStats.total ? `${(offeringStats.approved / offeringStats.total) * 100}%` : '0%' }" title="Approved"></div>
              <div class="h-full bg-warning transition-all duration-500" :style="{ width: offeringStats.total ? `${(offeringStats.draft / offeringStats.total) * 100}%` : '0%' }" title="Draft"></div>
              <div class="h-full bg-error transition-all duration-500" :style="{ width: offeringStats.total ? `${(offeringStats.rejected / offeringStats.total) * 100}%` : '0%' }" title="Rejected"></div>
            </div>
          </div>

          <!-- Instructor Coverage -->
          <div class="space-y-3">
             <div class="flex items-center justify-between text-xs font-medium">
              <span class="text-text/70">Instructor Staffing Coverage</span>
              <span class="text-text tabular-nums">{{ offeringStats.assignments }} / {{ offeringStats.sections }} Sections ({{ offeringStats.sections ? Math.round((offeringStats.assignments / offeringStats.sections) * 100) : 0 }}%)</span>
            </div>
            <div class="h-2.5 w-full rounded-full bg-bg border border-border/50 overflow-hidden">
              <div class="h-full bg-accent transition-all duration-500" :style="{ width: offeringStats.sections ? `${(offeringStats.assignments / offeringStats.sections) * 100}%` : '0%' }"></div>
            </div>
          </div>
          
          <div class="pt-4 border-t border-border flex items-center justify-between text-xs text-text/60">
            <span>Requires Faculty Assignment</span>
            <span class="font-medium text-warning tabular-nums bg-warning/10 px-2 py-0.5 rounded-full">{{ offeringStats.needsInstructor }} Offerings</span>
          </div>
        </div>
      </section>

      <!-- Column B: Administrative Action Queue -->
      <section class="xl:col-span-2 rounded-2xl border border-border bg-surface shadow-sm overflow-hidden flex flex-col">
        <div class="px-6 py-5 border-b border-border flex items-center justify-between">
          <div>
            <h2 class="text-sm font-semibold text-text">Needs Attention</h2>
            <p class="text-xs text-text/50 mt-1">Operational triage feed.</p>
          </div>
          <BaseBadge v-if="attentionItems.length" variant="warning" class="shadow-sm">{{ attentionItems.length }} Tasks</BaseBadge>
        </div>

        <div v-if="loading" class="p-6 space-y-4">
           <div v-for="i in 3" :key="i" class="animate-pulse flex flex-col gap-2">
             <div class="h-4 w-1/3 bg-text/5 rounded"></div>
             <div class="h-3 w-2/3 bg-text/5 rounded"></div>
           </div>
        </div>
        <div v-else-if="attentionItems.length" class="divide-y divide-border overflow-y-auto max-h-[380px]">
          <div v-for="(item, idx) in attentionItems" :key="idx" class="p-5 hover:bg-bg/40 transition-colors">
            <div class="flex items-start justify-between gap-4">
              <div class="min-w-0">
                <div class="flex items-center gap-2 mb-1.5">
                   <BaseBadge :variant="item.tone === 'danger' ? 'danger' : 'warning'" class="!px-1.5 !py-0.5 !text-[10px] uppercase font-mono">{{ item.label }}</BaseBadge>
                   <span class="text-[10px] font-mono bg-bg border border-border/80 px-1.5 py-0.5 rounded text-text/60">{{ item.meta }}</span>
                </div>
                <p class="text-sm font-medium text-text truncate mb-1">{{ item.title }}</p>
                <p class="text-xs text-text/60 leading-relaxed">{{ item.description }}</p>
              </div>
              <BaseButton variant="secondary" class="shrink-0 !py-1.5 !px-3 text-xs" @click="router.push('/admin/course-offerings')">Resolve</BaseButton>
            </div>
          </div>
        </div>
        <div v-else class="flex-1 flex flex-col items-center justify-center p-8 text-center bg-bg/20">
          <div class="w-12 h-12 rounded-full bg-success/10 border border-success/20 flex items-center justify-center text-success mb-4">
            <CheckCircle2 class="w-6 h-6" />
          </div>
          <h3 class="text-sm font-medium text-text">All systems operational</h3>
          <p class="text-xs text-text/50 mt-1 max-w-[200px]">No pending administrative actions require your attention.</p>
        </div>
      </section>
    </div>

    <!-- 4. Recent Course Offerings Registry -->
    <section class="rounded-2xl border border-border bg-surface shadow-sm overflow-hidden">
      <div class="px-6 py-5 border-b border-border flex items-center justify-between">
        <div>
          <h2 class="text-sm font-semibold text-text">Recent Course Offerings</h2>
          <p class="text-xs text-text/50 mt-1">Registry of recent academic offerings and their statuses.</p>
        </div>
        <router-link to="/admin/course-offerings" class="text-xs font-medium text-text/60 hover:text-accent transition-colors flex items-center gap-1 group">
          View all offerings
          <ArrowRight class="w-3.5 h-3.5 transition-transform group-hover:translate-x-0.5" />
        </router-link>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm whitespace-nowrap">
          <thead class="bg-bg/50 border-b border-border text-xs text-text/50">
            <tr>
              <th class="px-6 py-3.5 font-medium">Course</th>
              <th class="px-6 py-3.5 font-medium">Status</th>
              <th class="px-6 py-3.5 font-medium">Sections</th>
              <th class="px-6 py-3.5 font-medium">Instructors</th>
              <th class="px-6 py-3.5 font-medium">Last Updated</th>
              <th class="px-6 py-3.5 font-medium text-right"></th>
            </tr>
          </thead>
          <tbody v-if="loading" class="divide-y divide-border">
            <tr v-for="i in 4" :key="i" class="animate-pulse">
               <td class="px-6 py-4"><div class="h-4 w-32 bg-text/5 rounded"></div></td>
               <td class="px-6 py-4"><div class="h-4 w-16 bg-text/5 rounded"></div></td>
               <td class="px-6 py-4"><div class="h-4 w-8 bg-text/5 rounded"></div></td>
               <td class="px-6 py-4"><div class="h-4 w-8 bg-text/5 rounded"></div></td>
               <td class="px-6 py-4"><div class="h-4 w-20 bg-text/5 rounded"></div></td>
               <td class="px-6 py-4"></td>
            </tr>
          </tbody>
          <tbody v-else-if="recentOfferings.length" class="divide-y divide-border">
            <tr v-for="offering in recentOfferings" :key="offering.id" class="hover:bg-bg/50 transition-colors group">
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded border border-border bg-surface flex items-center justify-center shrink-0">
                    <BookOpen class="w-4 h-4 text-text/40" />
                  </div>
                  <div>
                    <p class="font-medium text-text">{{ offering.course?.name }}</p>
                    <p class="text-[11px] font-mono text-text/50 mt-0.5">{{ offering.course?.code }}</p>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                <BaseBadge
                  :variant="
                    offering.status === 'approved' ? 'success' :
                    offering.status === 'rejected' ? 'danger' :
                    offering.status === 'draft' ? 'info' : 'neutral'
                  ">
                  {{ offering.status }}
                </BaseBadge>
              </td>
              <td class="px-6 py-4 text-text/70 tabular-nums">
                {{ offering.sections?.length ?? 0 }}
              </td>
              <td class="px-6 py-4 text-text/70 tabular-nums">
                {{ offering.instructor_assignments?.length ?? 0 }}
              </td>
              <td class="px-6 py-4 text-xs text-text/50">
                {{ formatRelativeDate(offering.updated_at) }}
              </td>
              <td class="px-6 py-4 text-right">
                <router-link to="/admin/course-offerings" class="inline-flex p-1.5 rounded hover:bg-border/50 text-text/40 hover:text-accent transition-colors opacity-0 group-hover:opacity-100 focus:opacity-100">
                  <ChevronRight class="w-4 h-4" />
                </router-link>
              </td>
            </tr>
          </tbody>
          <tbody v-else>
            <tr>
              <td colspan="6" class="px-6 py-12 text-center text-sm text-text/50">
                No course offerings recorded yet.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </div>
</template>
