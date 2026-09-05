<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { ArrowLeft, BookOpen, CalendarDays, ClipboardList, FileText, Users } from 'lucide-vue-next';

import BaseBadge from '@/shared/components/ui/BaseBadge.vue';
import { useUiStore } from '@/stores/ui';

import * as api from '../api/teaching';
import type { TeachingDetail, TeachingExam } from '../types/teaching';

const route = useRoute();
const router = useRouter();
const uiStore = useUiStore();

const courseOfferingId = computed(() => Number(route.params.courseOfferingId));

const teaching = ref<TeachingDetail | null>(null);
const loading = ref(true);

const statusVariant: Record<string, 'neutral' | 'info' | 'danger' | 'warning' | 'success' | 'dark'> = {
  approved: 'success',
  pending_approval: 'warning',
  draft: 'neutral',
  rejected: 'danger',
  cancelled: 'danger',
};

async function load() {
  loading.value = true;

  try {
    teaching.value = await api.getTeachingDetail(courseOfferingId.value);
  } catch (error: any) {
    uiStore.showToast(error?.response?.data?.message || 'Failed to load the teaching assignment.', 'error');

    router.push({
      name: 'instructor.teaching.list',
    });
  } finally {
    loading.value = false;
  }
}

function goBack() {
  router.push({
    name: 'instructor.teaching.list',
  });
}

function formatStatus(status: string) {
  return status
    .split('_')
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ');
}

function formatRole(role: string) {
  return role
    .split('_')
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ');
}

function formatDate(value: string | null) {
  if (!value) {
    return '—';
  }

  const date = new Date(value);

  if (Number.isNaN(date.getTime())) {
    return value;
  }

  return new Intl.DateTimeFormat('en-US', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(date);
}

function getExamStatusVariant(status: string): 'neutral' | 'info' | 'danger' | 'warning' | 'success' | 'dark' {
  const variants: Record<string, 'neutral' | 'info' | 'danger' | 'warning' | 'success' | 'dark'> = {
    approved: 'success',
    submitted: 'warning',
    draft: 'neutral',
    scheduled: 'info',
    active: 'success',
    completed: 'dark',
    grading: 'warning',
    published: 'success',
    archived: 'dark',
    rejected: 'danger',
  };

  return variants[status] || 'neutral';
}

function goToQuestions() {
  router.push({
    name: 'instructor.questions.list',
    query: teaching.value?.course?.id ? { course_id: String(teaching.value.course.id) } : undefined,
  });
}

function goToExams() {
  router.push({
    name: 'instructor.exams.list',
    query: {
      courseOfferingId: String(courseOfferingId.value),
    },
  });
}

function openExam(exam: TeachingExam) {
  router.push({
    name: 'instructor.exams.detail',
    params: {
      examId: exam.id,
    },
  });
}

function examLabel(exam: TeachingExam) {
  return exam.title || `${formatStatus(exam.type)} Exam`;
}

onMounted(() => {
  load();
});
</script>

<template>
  <div class="mx-auto min-h-[calc(100vh-68px)] w-full max-w-360 space-y-6 px-6 py-8">
    <div v-if="loading" class="space-y-6">
      <div class="h-5 w-28 animate-pulse rounded bg-bg" />

      <div class="rounded-xl border border-border bg-surface p-6">
        <div class="space-y-3">
          <div class="h-7 w-72 animate-pulse rounded bg-bg" />
          <div class="h-4 w-48 animate-pulse rounded bg-bg" />
        </div>
      </div>

      <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div v-for="i in 3" :key="i" class="h-24 animate-pulse rounded-xl border border-border bg-surface" />
      </div>
    </div>

    <template v-else-if="teaching">
      <button type="button" class="inline-flex items-center gap-2 text-sm font-medium text-text/60 transition-colors hover:text-accent" @click="goBack">
        <ArrowLeft class="h-4 w-4" />
        <span>Back to My Teaching</span>
      </button>

      <div class="rounded-xl border border-border bg-surface p-6 shadow-sm">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
          <div class="flex min-w-0 items-start gap-4">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-accent/10">
              <BookOpen class="h-6 w-6 text-accent" />
            </div>

            <div class="min-w-0">
              <div class="flex flex-wrap items-center gap-2">
                <h1 class="font-display text-2xl font-bold text-text">
                  {{ teaching.course?.name || '—' }}
                </h1>

                <BaseBadge :variant="statusVariant[teaching.status] || 'neutral'">
                  {{ formatStatus(teaching.status) }}
                </BaseBadge>
              </div>

              <p class="mt-1 font-mono text-sm text-text/50">
                {{ teaching.course?.code || '—' }}
              </p>

              <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-text/60">
                <span class="inline-flex items-center gap-1.5">
                  <CalendarDays class="h-4 w-4 text-text/35" />
                  {{ teaching.semester?.name || '—' }}
                </span>

                <span>
                  {{ teaching.semester?.academic_year || '—' }}
                </span>

                <span v-if="teaching.course?.credit_hours"> {{ teaching.course.credit_hours }} credit hours </span>
              </div>
            </div>
          </div>

          <div class="flex shrink-0 items-center gap-2">
            <button
              type="button"
              class="inline-flex h-9 items-center gap-2 rounded-lg border border-border bg-surface px-3 text-sm font-medium text-text/70 transition-colors hover:border-accent/40 hover:text-accent"
              @click="goToQuestions">
              <BookOpen class="h-4 w-4" />
              <span>Question Bank</span>
            </button>

            <button
              type="button"
              class="inline-flex h-9 items-center gap-2 rounded-lg bg-accent px-3 text-sm font-medium text-white transition-colors hover:bg-accent/90"
              @click="goToExams">
              <FileText class="h-4 w-4" />
              <span>Exams</span>
            </button>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-border bg-surface p-5 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs font-medium uppercase tracking-wide text-text/45">Sections</p>

              <p class="mt-2 text-2xl font-bold tabular-nums text-text">
                {{ teaching.assignments.length }}
              </p>
            </div>

            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-accent/10">
              <Users class="h-5 w-5 text-accent" />
            </div>
          </div>
        </div>

        <div class="rounded-xl border border-border bg-surface p-5 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs font-medium uppercase tracking-wide text-text/45">Students</p>

              <p class="mt-2 text-2xl font-bold tabular-nums text-text">
                {{ teaching.students.length }}
              </p>
            </div>

            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-accent/10">
              <Users class="h-5 w-5 text-accent" />
            </div>
          </div>
        </div>

        <div class="rounded-xl border border-border bg-surface p-5 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs font-medium uppercase tracking-wide text-text/45">Exams</p>

              <p class="mt-2 text-2xl font-bold tabular-nums text-text">
                {{ teaching.exams.length }}
              </p>
            </div>

            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-accent/10">
              <ClipboardList class="h-5 w-5 text-accent" />
            </div>
          </div>
        </div>
      </div>

      <section class="space-y-3">
        <div>
          <h2 class="text-base font-semibold text-text">My Sections</h2>

          <p class="mt-1 text-sm text-text/50">Sections you are assigned to for this course offering.</p>
        </div>

        <div class="overflow-hidden rounded-xl border border-border bg-surface shadow-sm">
          <table class="w-full border-collapse">
            <thead>
              <tr class="border-b border-border bg-bg/40">
                <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-text/45">Section</th>

                <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-text/45">Program</th>

                <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-text/45">Year</th>

                <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-text/45">Role</th>

                <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-text/45">Assigned</th>
              </tr>
            </thead>

            <tbody v-if="teaching.assignments.length" class="divide-y divide-border">
              <tr v-for="assignment in teaching.assignments" :key="assignment.id">
                <td class="px-6 py-5">
                  <p class="text-sm font-medium text-text">
                    {{ assignment.section?.name || '—' }}
                  </p>
                </td>

                <td class="px-6 py-5">
                  <p class="text-sm text-text/70">
                    {{ assignment.section?.program?.name || '—' }}
                  </p>

                  <p v-if="assignment.section?.program?.code" class="mt-0.5 font-mono text-xs text-text/45">
                    {{ assignment.section.program.code }}
                  </p>
                </td>

                <td class="px-6 py-5 text-sm text-text/70">
                  {{ assignment.section?.year_level ? `Year ${assignment.section.year_level}` : '—' }}
                </td>

                <td class="px-6 py-5">
                  <span class="text-sm text-text/70">
                    {{ formatRole(assignment.type) }}
                  </span>
                </td>

                <td class="px-6 py-5 text-sm text-text/60">
                  {{ assignment.assigned_at || '—' }}
                </td>
              </tr>
            </tbody>

            <tbody v-else>
              <tr>
                <td colspan="5" class="px-6 py-12 text-center text-sm text-text/45">No section assignments found.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section class="space-y-3">
        <div>
          <h2 class="text-base font-semibold text-text">Students</h2>

          <p class="mt-1 text-sm text-text/50">Students belonging to your assigned sections.</p>
        </div>

        <div class="overflow-hidden rounded-xl border border-border bg-surface shadow-sm">
          <table class="w-full border-collapse">
            <thead>
              <tr class="border-b border-border bg-bg/40">
                <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-text/45">Student</th>

                <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-text/45">Student Number</th>

                <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-text/45">Section</th>

                <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-text/45">Status</th>
              </tr>
            </thead>

            <tbody v-if="teaching.students.length" class="divide-y divide-border">
              <tr v-for="student in teaching.students" :key="student.id">
                <td class="px-6 py-5">
                  <p class="text-sm font-medium text-text">
                    {{ student.user ? `${student.user.first_name} ${student.user.last_name}` : '—' }}
                  </p>

                  <p v-if="student.user?.email" class="mt-0.5 text-xs text-text/45">
                    {{ student.user.email }}
                  </p>
                </td>

                <td class="px-6 py-5">
                  <span class="font-mono text-sm text-text/70">
                    {{ student.student_number }}
                  </span>
                </td>

                <td class="px-6 py-5 text-sm text-text/70">
                  {{ student.section ? `${student.section.name} · Year ${student.section.year_level}` : '—' }}
                </td>

                <td class="px-6 py-5">
                  <BaseBadge :variant="student.status === 'active' ? 'success' : 'neutral'">
                    {{ formatStatus(student.status) }}
                  </BaseBadge>
                </td>
              </tr>
            </tbody>

            <tbody v-else>
              <tr>
                <td colspan="4" class="px-6 py-12 text-center">
                  <div class="mx-auto flex max-w-sm flex-col items-center">
                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-bg">
                      <Users class="h-6 w-6 text-text/30" />
                    </div>

                    <p class="text-sm font-medium text-text">No students found</p>

                    <p class="mt-1 text-sm text-text/45">Students from your assigned sections will appear here.</p>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section class="space-y-3">
        <div>
          <h2 class="text-base font-semibold text-text">Exams</h2>

          <p class="mt-1 text-sm text-text/50">Exams created for this course offering.</p>
        </div>

        <div v-if="teaching.exams.length" class="grid grid-cols-1 gap-4 lg:grid-cols-2">
          <button
            v-for="exam in teaching.exams"
            :key="exam.id"
            type="button"
            class="rounded-xl border border-border bg-surface p-5 text-left shadow-sm transition-all hover:border-accent/40 hover:shadow-md"
            @click="openExam(exam)">
            <div class="flex items-start justify-between gap-4">
              <div class="flex min-w-0 items-start gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-accent/10">
                  <FileText class="h-5 w-5 text-accent" />
                </div>

                <div class="min-w-0">
                  <h3 class="truncate text-sm font-semibold text-text">
                    {{ examLabel(exam) }}
                  </h3>

                  <p class="mt-0.5 text-xs text-text/45">
                    {{ formatStatus(exam.type) }}
                  </p>
                </div>
              </div>

              <BaseBadge :variant="getExamStatusVariant(exam.status)">
                {{ formatStatus(exam.status) }}
              </BaseBadge>
            </div>

            <div class="mt-5 grid grid-cols-2 gap-4">
              <div>
                <p class="text-xs text-text/40">Questions</p>

                <p class="mt-1 text-sm font-medium tabular-nums text-text">
                  {{ exam.total_questions }}
                </p>
              </div>

              <div>
                <p class="text-xs text-text/40">Total Marks</p>

                <p class="mt-1 text-sm font-medium tabular-nums text-text">
                  {{ exam.total_marks }}
                </p>
              </div>

              <div>
                <p class="text-xs text-text/40">Duration</p>

                <p class="mt-1 text-sm font-medium text-text">{{ exam.duration_minutes }} min</p>
              </div>

              <div>
                <p class="text-xs text-text/40">Schedule</p>

                <p class="mt-1 text-sm font-medium text-text">
                  {{ formatDate(exam.scheduled_start) }}
                </p>
              </div>
            </div>
          </button>
        </div>

        <div v-else class="rounded-xl border border-border bg-surface px-6 py-12 text-center shadow-sm">
          <div class="mx-auto flex max-w-sm flex-col items-center">
            <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-bg">
              <FileText class="h-6 w-6 text-text/30" />
            </div>

            <p class="text-sm font-medium text-text">No exams yet</p>

            <p class="mt-1 text-sm text-text/45">Exams for this course offering will appear here.</p>

            <button
              type="button"
              class="mt-4 inline-flex h-9 items-center gap-2 rounded-lg bg-accent px-3 text-sm font-medium text-white transition-colors hover:bg-accent/90"
              @click="goToExams">
              <FileText class="h-4 w-4" />
              Manage Exams
            </button>
          </div>
        </div>
      </section>
    </template>
  </div>
</template>
