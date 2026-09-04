<script setup lang="ts">
import { CalendarDays, ChevronRight, ClipboardCheck, Plus, RefreshCw, Search } from 'lucide-vue-next';

import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';

import BaseButton from '@/shared/components/ui/BaseButton.vue';
import BaseSelect from '@/shared/components/ui/BaseSelect.vue';

import ExamStatusBadge from '../components/ExamStatusBadge.vue';
import { listExams } from '../api/exams';

import { getTeaching } from '@/modules/instructor/teaching/api/teaching';

import type { Exam } from '../types/exam';

const router = useRouter();

const loading = ref(false);
const error = ref<string | null>(null);

const teachings = ref<any[]>([]);
const exams = ref<Exam[]>([]);

const selectedTeachingId = ref<number | null>(null);
const search = ref('');
const status = ref('');

const statusOptions = [
  { value: '', label: 'All statuses' },
  { value: 'draft', label: 'Draft' },
  { value: 'pending_approval', label: 'Pending approval' },
  { value: 'approved', label: 'Approved' },
  { value: 'rejected', label: 'Rejected' },
  { value: 'scheduled', label: 'Scheduled' },
  { value: 'active', label: 'Active' },
  { value: 'completed', label: 'Completed' },
];

const teachingOptions = computed(() =>
  teachings.value
    .filter((item) => item?.id && item?.course)
    .map((item) => ({
      value: String(item.id),
      label: `${item.course.code} — ${item.course.name}`,
    })),
);

const filteredExams = computed(() => {
  const query = search.value.trim().toLowerCase();

  return exams.value.filter((exam) => {
    if (query && !exam.title.toLowerCase().includes(query) && !exam.type.toLowerCase().includes(query)) {
      return false;
    }

    if (status.value && exam.status !== status.value) {
      return false;
    }

    return true;
  });
});

const selectedTeaching = computed(() => teachings.value.find((teaching) => teaching.id === selectedTeachingId.value));

async function loadTeaching() {
  const response = await getTeaching(1, 1000);

  teachings.value = response.data ?? [];

  if (!selectedTeachingId.value && teachings.value.length) {
    selectedTeachingId.value = teachings.value[0].id;
  }
}

async function loadExams() {
  if (!selectedTeachingId.value) {
    exams.value = [];
    return;
  }

  loading.value = true;
  error.value = null;

  try {
    const response = await listExams(
      selectedTeachingId.value,
      1,
      100,
      status.value
        ? {
            status: status.value,
          }
        : {},
    );

    exams.value = response.data ?? [];
  } catch {
    exams.value = [];
    error.value = 'Unable to load your exams.';
  } finally {
    loading.value = false;
  }
}

function openExam(exam: Exam) {
  router.push({
    name: 'instructor.exams.detail',
    params: {
      examId: exam.id,
    },
  });
}

function createExam() {
  if (!selectedTeachingId.value) {
    return;
  }

  router.push({
    name: 'instructor.exams.create',
    query: {
      courseOfferingId: String(selectedTeachingId.value),
    },
  });
}

async function changeTeaching() {
  await loadExams();
}

onMounted(async () => {
  try {
    await loadTeaching();
    await loadExams();
  } catch {
    error.value = 'Unable to load your teaching assignments.';
  }
});
</script>

<template>
  <div class="mx-auto w-full max-w-[1200px] px-6 py-6">
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
      <div>
        <p class="text-xs font-bold uppercase tracking-wide text-text/45">Instructor workspace</p>

        <h1 class="mt-1 text-2xl font-semibold tracking-tight text-text">Exams</h1>

        <p class="mt-1 max-w-2xl text-sm leading-6 text-text/55">Create, prepare, submit, schedule, and manage exams for your assigned course offerings.</p>
      </div>

      <BaseButton :disabled="!selectedTeachingId" @click="createExam">
        <template #icon>
          <Plus class="h-4 w-4" />
        </template>
        Create exam
      </BaseButton>
    </div>

    <div class="mb-5 rounded-md border border-border bg-surface p-4">
      <div class="grid grid-cols-1 gap-3 lg:grid-cols-[1fr_180px_auto]">
        <div>
          <BaseSelect v-model="selectedTeachingId" label="Course" :options="teachingOptions" placeholder="Select course" @update:model-value="changeTeaching" />
        </div>

        <BaseSelect v-model="status" label="Status" :options="statusOptions" placeholder="Status" @update:model-value="loadExams" />

        <div class="flex items-end">
          <button
            type="button"
            class="inline-flex h-10 w-10 items-center justify-center rounded-md border border-border text-text/55 hover:border-accent/40 hover:text-accent"
            title="Refresh"
            @click="loadExams">
            <RefreshCw class="h-4 w-4" :class="{ 'animate-spin': loading }" />
          </button>
        </div>
      </div>
    </div>

    <div v-if="selectedTeaching" class="mb-5 flex items-center gap-2 rounded-md border border-border bg-bg px-4 py-3">
      <ClipboardCheck class="h-4 w-4 text-accent" />

      <div class="min-w-0">
        <p class="truncate text-sm font-medium text-text">
          {{ selectedTeaching.course.code }} —
          {{ selectedTeaching.course.name }}
        </p>

        <p class="text-xs text-text/45">{{ selectedTeaching.assignments?.length || 0 }} teaching assignment(s)</p>
      </div>
    </div>

    <div v-if="error" class="mb-5 rounded-md border border-error/20 bg-error/5 p-4">
      <p class="text-sm text-error">
        {{ error }}
      </p>

      <button type="button" class="mt-2 text-xs font-medium text-error underline" @click="loadExams">Try again</button>
    </div>

    <div class="rounded-md border border-border bg-surface">
      <div class="border-b border-border p-4">
        <div class="relative max-w-sm">
          <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-text/30" />

          <input
            v-model="search"
            type="text"
            placeholder="Search exams…"
            class="w-full rounded-md border border-border bg-bg py-2 pl-9 pr-3 text-sm text-text outline-none focus:border-accent" />
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full min-w-[820px]">
          <thead>
            <tr class="border-b border-border bg-bg text-left">
              <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-text/50">Exam</th>

              <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-text/50">Type</th>

              <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-text/50">Questions</th>

              <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-text/50">Marks</th>

              <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-text/50">Status</th>

              <th class="w-12 px-4 py-3" />
            </tr>
          </thead>

          <tbody v-if="loading" class="divide-y divide-border">
            <tr v-for="item in 5" :key="item">
              <td class="px-4 py-4">
                <div class="h-4 w-56 animate-pulse rounded bg-text/5" />
              </td>

              <td class="px-4 py-4">
                <div class="h-4 w-20 animate-pulse rounded bg-text/5" />
              </td>

              <td class="px-4 py-4">
                <div class="h-4 w-8 animate-pulse rounded bg-text/5" />
              </td>

              <td class="px-4 py-4">
                <div class="h-4 w-8 animate-pulse rounded bg-text/5" />
              </td>

              <td class="px-4 py-4">
                <div class="h-6 w-20 animate-pulse rounded-full bg-text/5" />
              </td>

              <td />
            </tr>
          </tbody>

          <tbody v-else-if="filteredExams.length" class="divide-y divide-border">
            <tr v-for="exam in filteredExams" :key="exam.id" class="group cursor-pointer transition-colors hover:bg-text/2" @click="openExam(exam)">
              <td class="px-4 py-4">
                <div>
                  <p class="text-sm font-medium text-text">
                    {{ exam.title }}
                  </p>

                  <p class="mt-1 text-xs text-text/45">Created {{ exam.created_at || '—' }}</p>
                </div>
              </td>

              <td class="px-4 py-4">
                <span class="font-mono text-xs uppercase text-text/60">
                  {{ exam.type }}
                </span>
              </td>

              <td class="px-4 py-4">
                <span class="font-mono text-sm tabular-nums text-text/70">
                  {{ exam.total_questions }}
                </span>
              </td>

              <td class="px-4 py-4">
                <span class="font-mono text-sm tabular-nums text-text/70">
                  {{ exam.total_marks }}
                </span>
              </td>

              <td class="px-4 py-4">
                <ExamStatusBadge :status="exam.status" />
              </td>

              <td class="px-4 py-4 text-right">
                <ChevronRight class="ml-auto h-4 w-4 text-text/25 transition group-hover:text-accent" />
              </td>
            </tr>
          </tbody>

          <tbody v-else>
            <tr>
              <td colspan="6" class="px-6 py-16 text-center">
                <div class="mx-auto max-w-sm">
                  <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-md border border-border bg-bg text-text/40">
                    <CalendarDays class="h-5 w-5" />
                  </div>

                  <p class="mt-3 text-sm font-medium text-text">No exams found</p>

                  <p class="mt-1 text-sm text-text/45">Create an exam for this course offering to get started.</p>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
