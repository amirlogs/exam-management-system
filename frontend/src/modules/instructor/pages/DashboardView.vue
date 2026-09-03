<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { ArrowRight, BookOpen, CalendarDays, CheckCircle2, Clock3, GraduationCap, Users2, RefreshCw, AlertCircle } from 'lucide-vue-next';
import axios from 'axios';

const router = useRouter();

interface Program {
  id: number;
  name: string;
  code: string;
}

interface Section {
  id: number;
  name: string;
  year_level: number;
  program: Program | null;
}

interface Assignment {
  id: number;
  type: string;
  assigned_at: string | null;
  section: Section | null;
}

interface Course {
  id: number;
  code: string;
  name: string;
  credit_hours: number;
}

interface Semester {
  id: number;
  name: string;
  academic_year: string;
  status: string;
}

interface Teaching {
  id: number;
  course: Course;
  semester: Semester;
  status: string;
  assignments: Assignment[];
}

interface Pagination {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number | null;
  to: number | null;
}

interface ApiResponse {
  success: boolean;
  message: string;
  data: Teaching[];
  pagination: Pagination;
  errors: unknown;
}

const teaching = ref<Teaching[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);

const fetchTeaching = async () => {
  loading.value = true;
  error.value = null;

  try {
    const response = await axios.get<ApiResponse>('/me/teaching', {
      params: {
        per_page: 100,
      },
    });

    teaching.value = response.data.data ?? [];
  } catch (err: unknown) {
    if (axios.isAxiosError(err)) {
      error.value = err.response?.data?.message ?? 'Unable to load your teaching information.';
    } else {
      error.value = 'Unable to load your teaching information.';
    }
  } finally {
    loading.value = false;
  }
};

onMounted(fetchTeaching);

const totalCourses = computed(() => teaching.value.length);

const totalSections = computed(() => {
  return new Set(teaching.value.flatMap((item) => item.assignments.map((assignment) => assignment.section?.id).filter((id): id is number => id !== undefined))).size;
});

const draftCourses = computed(() => {
  return teaching.value.filter((item) => item.status === 'draft').length;
});

const approvedCourses = computed(() => {
  return teaching.value.filter((item) => item.status === 'approved').length;
});

const recentTeaching = computed(() => {
  return teaching.value.slice(0, 5);
});

const uniquePrograms = computed(() => {
  const programs = new Map<number, Program>();

  teaching.value.forEach((item) => {
    item.assignments.forEach((assignment) => {
      const program = assignment.section?.program;

      if (program) {
        programs.set(program.id, program);
      }
    });
  });

  return Array.from(programs.values());
});

const goToTeaching = () => {
  router.push('/instructor/teaching');
};

const goToTeachingDetail = (id: number) => {
  router.push(`/instructor/teaching/${id}`);
};
</script>

<template>
  <div class="space-y-8">
    <!-- Header -->
    <section class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <p class="text-sm font-medium text-accent">Instructor Workspace</p>

        <h1 class="mt-1 text-2xl font-bold tracking-tight text-text sm:text-3xl">Dashboard</h1>

        <p class="mt-2 max-w-2xl text-sm text-text/55">A quick overview of your teaching assignments and academic workload.</p>
      </div>

      <button
        type="button"
        class="inline-flex items-center justify-center gap-2 rounded-xl border border-border bg-surface px-4 py-2.5 text-sm font-medium text-text transition hover:bg-bg active:scale-[0.98]"
        :disabled="loading"
        @click="fetchTeaching">
        <RefreshCw class="h-4 w-4" :class="{ 'animate-spin': loading }" />
        Refresh
      </button>
    </section>

    <!-- Error -->
    <div v-if="error" class="flex items-start gap-3 rounded-2xl border border-border bg-surface p-4">
      <AlertCircle class="mt-0.5 h-5 w-5 shrink-0 text-accent" />

      <div class="min-w-0 flex-1">
        <p class="font-medium text-text">Unable to load dashboard data</p>

        <p class="mt-1 text-sm text-text/55">
          {{ error }}
        </p>
      </div>

      <button type="button" class="shrink-0 text-sm font-semibold text-accent hover:underline" @click="fetchTeaching">Retry</button>
    </div>

    <!-- Stats -->
    <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <!-- Courses -->
      <div class="rounded-2xl border border-border bg-surface p-5">
        <div class="flex items-start justify-between">
          <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-accent/10">
            <BookOpen class="h-5 w-5 text-accent" />
          </div>

          <span class="text-xs font-medium text-text/40"> Teaching </span>
        </div>

        <div class="mt-5">
          <div v-if="loading" class="h-8 w-16 animate-pulse rounded-lg bg-bg" />
          <p v-else class="text-3xl font-bold tracking-tight text-text">
            {{ totalCourses }}
          </p>

          <p class="mt-1 text-sm text-text/50">Course offerings</p>
        </div>
      </div>

      <!-- Sections -->
      <div class="rounded-2xl border border-border bg-surface p-5">
        <div class="flex items-start justify-between">
          <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-accent/10">
            <Users2 class="h-5 w-5 text-accent" />
          </div>

          <span class="text-xs font-medium text-text/40"> Assigned </span>
        </div>

        <div class="mt-5">
          <div v-if="loading" class="h-8 w-16 animate-pulse rounded-lg bg-bg" />
          <p v-else class="text-3xl font-bold tracking-tight text-text">
            {{ totalSections }}
          </p>

          <p class="mt-1 text-sm text-text/50">Sections</p>
        </div>
      </div>

      <!-- Draft -->
      <div class="rounded-2xl border border-border bg-surface p-5">
        <div class="flex items-start justify-between">
          <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-accent/10">
            <Clock3 class="h-5 w-5 text-accent" />
          </div>

          <span class="text-xs font-medium text-text/40"> Status </span>
        </div>

        <div class="mt-5">
          <div v-if="loading" class="h-8 w-16 animate-pulse rounded-lg bg-bg" />
          <p v-else class="text-3xl font-bold tracking-tight text-text">
            {{ draftCourses }}
          </p>

          <p class="mt-1 text-sm text-text/50">Draft assignments</p>
        </div>
      </div>

      <!-- Approved -->
      <div class="rounded-2xl border border-border bg-surface p-5">
        <div class="flex items-start justify-between">
          <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-accent/10">
            <CheckCircle2 class="h-5 w-5 text-accent" />
          </div>

          <span class="text-xs font-medium text-text/40"> Status </span>
        </div>

        <div class="mt-5">
          <div v-if="loading" class="h-8 w-16 animate-pulse rounded-lg bg-bg" />
          <p v-else class="text-3xl font-bold tracking-tight text-text">
            {{ approvedCourses }}
          </p>

          <p class="mt-1 text-sm text-text/50">Approved assignments</p>
        </div>
      </div>
    </section>

    <!-- Main content -->
    <section class="grid grid-cols-1 gap-6 xl:grid-cols-3">
      <!-- Teaching -->
      <div class="xl:col-span-2 rounded-2xl border border-border bg-surface">
        <div class="flex items-center justify-between border-b border-border px-5 py-4">
          <div>
            <h2 class="font-semibold text-text">My Teaching</h2>

            <p class="mt-1 text-xs text-text/45">Your current course assignments</p>
          </div>

          <button type="button" class="inline-flex items-center gap-1.5 text-sm font-semibold text-accent hover:opacity-80" @click="goToTeaching">
            View all
            <ArrowRight class="h-4 w-4" />
          </button>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="divide-y divide-border">
          <div v-for="item in 3" :key="item" class="flex items-center gap-4 px-5 py-5">
            <div class="h-11 w-11 animate-pulse rounded-xl bg-bg" />

            <div class="min-w-0 flex-1 space-y-2">
              <div class="h-4 w-40 animate-pulse rounded bg-bg" />
              <div class="h-3 w-56 animate-pulse rounded bg-bg" />
            </div>
          </div>
        </div>

        <!-- Empty -->
        <div v-else-if="recentTeaching.length === 0" class="px-6 py-14 text-center">
          <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-bg">
            <BookOpen class="h-6 w-6 text-text/30" />
          </div>

          <h3 class="mt-4 font-semibold text-text">No teaching assignments yet</h3>

          <p class="mx-auto mt-1 max-w-sm text-sm text-text/50">Your assigned course offerings will appear here.</p>
        </div>

        <!-- Teaching items -->
        <div v-else class="divide-y divide-border">
          <button
            v-for="item in recentTeaching"
            :key="item.id"
            type="button"
            class="flex w-full items-center gap-4 px-5 py-4 text-left transition hover:bg-bg/60"
            @click="goToTeachingDetail(item.id)">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-accent/10">
              <BookOpen class="h-5 w-5 text-accent" />
            </div>

            <div class="min-w-0 flex-1">
              <div class="flex flex-wrap items-center gap-2">
                <h3 class="truncate font-semibold text-text">
                  {{ item.course.code }}
                </h3>

                <span class="rounded-full bg-bg px-2 py-0.5 text-[11px] font-medium capitalize text-text/55">
                  {{ item.status.replaceAll('_', ' ') }}
                </span>
              </div>

              <p class="mt-1 truncate text-sm text-text/55">
                {{ item.course.name }}
              </p>

              <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-text/40">
                <span class="inline-flex items-center gap-1">
                  <CalendarDays class="h-3.5 w-3.5" />
                  {{ item.semester.name }}
                  {{ item.semester.academic_year }}
                </span>

                <span class="inline-flex items-center gap-1">
                  <Users2 class="h-3.5 w-3.5" />
                  {{ item.assignments.length }}
                  {{ item.assignments.length === 1 ? 'section' : 'sections' }}
                </span>
              </div>
            </div>

            <ArrowRight class="h-4 w-4 shrink-0 text-text/25" />
          </button>
        </div>
      </div>

      <!-- Academic snapshot -->
      <div class="rounded-2xl border border-border bg-surface">
        <div class="border-b border-border px-5 py-4">
          <h2 class="font-semibold text-text">Academic Snapshot</h2>

          <p class="mt-1 text-xs text-text/45">Programs represented in your assignments</p>
        </div>

        <div class="p-5">
          <div v-if="loading" class="space-y-3">
            <div v-for="item in 4" :key="item" class="h-10 animate-pulse rounded-xl bg-bg" />
          </div>

          <div v-else-if="uniquePrograms.length === 0" class="py-8 text-center">
            <GraduationCap class="mx-auto h-8 w-8 text-text/25" />

            <p class="mt-3 text-sm text-text/50">No program information available.</p>
          </div>

          <div v-else class="space-y-2">
            <div v-for="program in uniquePrograms" :key="program.id" class="flex items-center justify-between rounded-xl border border-border px-4 py-3">
              <div class="min-w-0">
                <p class="truncate text-sm font-medium text-text">
                  {{ program.name }}
                </p>

                <p class="mt-0.5 text-xs text-text/40">
                  {{ program.code }}
                </p>
              </div>

              <GraduationCap class="h-4 w-4 shrink-0 text-text/25" />
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Quick action -->
    <section class="rounded-2xl border border-border bg-surface p-5">
      <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h2 class="font-semibold text-text">Continue your work</h2>

          <p class="mt-1 text-sm text-text/50">Open your teaching assignments to manage sections, students, and exams.</p>
        </div>

        <button
          type="button"
          class="inline-flex items-center justify-center gap-2 rounded-xl bg-accent px-4 py-2.5 text-sm font-semibold text-white transition hover:opacity-90 active:scale-[0.98]"
          @click="goToTeaching">
          Open My Teaching
          <ArrowRight class="h-4 w-4" />
        </button>
      </div>
    </section>
  </div>
</template>
