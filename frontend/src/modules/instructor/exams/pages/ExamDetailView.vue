<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { ArrowLeft, CalendarClock, ChevronLeft, ChevronRight, ClipboardList, Clock3, ExternalLink, FileQuestion, FileText, HelpCircle, Lock } from 'lucide-vue-next';

import BaseBadge from '@/shared/components/ui/BaseBadge.vue';
import BaseButton from '@/shared/components/ui/BaseButton.vue';
import ExamStatusBadge from '../components/ExamStatusBadge.vue';
import ExamCompositionEditor from '../components/ExamCompositionEditor.vue';
import ExamLifecycleActions from '../components/ExamLifecycleActions.vue';

import {
  approveExam,
  archiveExam,
  cancelExam,
  endExam,
  extendExamTime,
  getExam,
  listExamQuestions,
  publishExam,
  rejectExam,
  revertExamToDraft,
  scheduleExam,
  submitExamForApproval,
  updateExamComposition,
  updateExamSchedule,
} from '../api/exams';

import { useUiStore } from '@/stores/ui';
import { handleApiError } from '@/shared/utils/apiError';
import type { Exam } from '../types/exam';

const router = useRouter();
const route = useRoute();
const uiStore = useUiStore();

const exam = ref<Exam | null>(null);
const attachedQuestions = ref<any[]>([]);
const questionPage = ref(1);
const questionPerPage = 5;
const questionPagination = ref<any>(null);

const loading = ref(true);
const loadingQuestions = ref(false);
const actionLoading = ref(false);

const examId = computed(() => Number(route.params.examId));
const showComposition = computed(() => exam.value?.status === 'draft');

function formatDate(value: string | null | undefined) {
  if (!value) return 'Not scheduled';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  return new Intl.DateTimeFormat('en-US', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(date);
}

function getDifficultyVariant(difficulty: string | null | undefined): 'neutral' | 'info' | 'danger' | 'warning' | 'success' | 'dark' {
  if (!difficulty) return 'neutral';
  const val = difficulty.toLowerCase();
  if (val === 'easy') return 'success';
  if (val === 'medium') return 'warning';
  if (val === 'hard') return 'danger';
  return 'neutral';
}

function getMarks(item: any): number | string {
  if (item?.marks !== undefined && item?.marks !== null && item?.marks !== '') {
    return item.marks;
  }
  if (item?.pivot?.marks !== undefined && item?.pivot?.marks !== null && item?.pivot?.marks !== '') {
    return item.pivot.marks;
  }
  return 1;
}

async function loadExam() {
  loading.value = true;
  try {
    const [response] = await Promise.all([getExam(examId.value), loadQuestions(1)]);
    exam.value = response.data;
  } catch (error) {
    handleApiError(error, uiStore, undefined, 'Unable to load this exam.');
    router.push({ name: 'instructor.exams.list' });
  } finally {
    loading.value = false;
  }
}

async function loadQuestions(page = 1) {
  loadingQuestions.value = true;
  try {
    const qResponse = await listExamQuestions(examId.value, page, questionPerPage);
    attachedQuestions.value = qResponse.data ?? [];
    questionPagination.value = qResponse.pagination ?? null;
    questionPage.value = page;
  } catch (error) {
    // Non-blocking
  } finally {
    loadingQuestions.value = false;
  }
}

function goBack() {
  router.push({
    name: 'instructor.exams.list',
  });
}

function openQuestions() {
  router.push({
    name: 'instructor.exams.questions',
    params: {
      examId: examId.value,
    },
  });
}

async function runAction(action: () => Promise<unknown>, successMessage: string) {
  actionLoading.value = true;
  try {
    await action();
    uiStore.showToast(successMessage, 'success');
    await loadExam();
  } catch (requestError: any) {
    handleApiError(requestError, uiStore, undefined, 'The exam action could not be completed.');
  } finally {
    actionLoading.value = false;
  }
}

async function handleSchedule(payload: { scheduled_start: string; duration_minutes?: number }) {
  if (!exam.value) return;

  if (exam.value.status === 'scheduled') {
    await runAction(() => updateExamSchedule(exam.value!.id, payload), 'Exam schedule updated successfully.');
  } else {
    await runAction(() => scheduleExam(exam.value!.id, { scheduled_start: payload.scheduled_start }), 'Exam scheduled successfully.');
  }
}

async function handleExtendTime(durationMinutes: number) {
  if (!exam.value) return;
  await runAction(() => extendExamTime(exam.value!.id, durationMinutes), 'Exam time extended successfully.');
}

onMounted(loadExam);
</script>

<template>
  <div class="mx-auto min-h-[calc(100vh-68px)] w-full max-w-360 space-y-6 px-6 py-8">
    <!-- Skeleton Loading -->
    <div v-if="loading" class="space-y-6">
      <div class="h-5 w-28 animate-pulse rounded bg-bg" />

      <div class="rounded-xl border border-border bg-surface p-6 shadow-sm">
        <div class="space-y-3">
          <div class="h-7 w-72 animate-pulse rounded bg-bg" />
          <div class="h-4 w-48 animate-pulse rounded bg-bg" />
        </div>
      </div>

      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div v-for="i in 4" :key="i" class="h-24 animate-pulse rounded-xl border border-border bg-surface" />
      </div>
    </div>

    <!-- Exam Content -->
    <template v-else-if="exam">
      <!-- Top Action Bar (Back button on left, Lifecycle Actions on right) -->
      <div class="flex flex-wrap items-center justify-between gap-4">
        <button type="button" class="inline-flex items-center gap-2 text-sm font-medium text-text/60 transition-colors hover:text-accent" @click="goBack">
          <ArrowLeft class="h-4 w-4" />
          <span>Back to Exams</span>
        </button>

        <div class="flex flex-wrap items-center gap-2.5">
          <BaseButton v-can:any="['exam.view', 'exam.update']" variant="secondary" @click="openQuestions">
            <template #icon>
              <FileQuestion class="h-4 w-4" />
            </template>
            Manage Question Paper
          </BaseButton>

          <ExamLifecycleActions
            :exam="exam"
            :loading="actionLoading"
            @submit="runAction(() => submitExamForApproval(exam!.id), 'Exam submitted for approval.')"
            @revert="runAction(() => revertExamToDraft(exam!.id), 'Exam reverted to draft.')"
            @approve="runAction(() => approveExam(exam!.id), 'Exam approved.')"
            @reject="(reason) => runAction(() => rejectExam(exam!.id, reason), 'Exam rejected.')"
            @schedule="handleSchedule"
            @publish="runAction(() => publishExam(exam!.id), 'Exam published and activated.')"
            @end="runAction(() => endExam(exam!.id), 'Exam ended.')"
            @cancel="runAction(() => cancelExam(exam!.id), 'Exam cancelled.')"
            @archive="runAction(() => archiveExam(exam!.id), 'Exam archived.')"
            @extend-time="handleExtendTime" />
        </div>
      </div>

      <!-- Main Overview Card -->
      <div class="rounded-xl border border-border bg-surface p-6 shadow-sm">
        <div class="flex min-w-0 items-start gap-4">
          <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-accent/10">
            <FileText class="h-6 w-6 text-accent" />
          </div>

          <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2.5">
              <h1 class="font-display text-2xl font-bold text-text truncate">
                {{ exam.title }}
              </h1>
              <ExamStatusBadge :status="exam.status" />
              <span class="inline-flex rounded-md bg-bg border border-border px-2.5 py-0.5 font-mono text-xs uppercase font-medium text-text/70">
                {{ exam.type }}
              </span>
            </div>

            <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-text/60">
              <span class="font-mono text-text/50">#{{ exam.id }}</span>
              <span>•</span>
              <span class="inline-flex items-center gap-1.5">
                <Clock3 class="h-4 w-4 text-text/35" />
                {{ exam.duration_minutes }} min
              </span>
              <span>•</span>
              <span class="inline-flex items-center gap-1.5">
                <CalendarClock class="h-4 w-4 text-text/35" />
                Created {{ formatDate(exam.created_at) }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Stat / Metrics 4-Column Grid -->
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-border bg-surface p-5 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs font-medium uppercase tracking-wide text-text/45">Questions</p>
              <p class="mt-2 text-2xl font-bold tabular-nums text-text">
                {{ exam.total_questions }}
              </p>
            </div>
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-accent/10">
              <ClipboardList class="h-5 w-5 text-accent" />
            </div>
          </div>
        </div>

        <div class="rounded-xl border border-border bg-surface p-5 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs font-medium uppercase tracking-wide text-text/45">Total Marks</p>
              <p class="mt-2 text-2xl font-bold tabular-nums text-text">
                {{ exam.total_marks }}
              </p>
            </div>
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-accent/10">
              <FileText class="h-5 w-5 text-accent" />
            </div>
          </div>
        </div>

        <div class="rounded-xl border border-border bg-surface p-5 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs font-medium uppercase tracking-wide text-text/45">Duration</p>
              <p class="mt-2 text-2xl font-bold tabular-nums text-text">{{ exam.duration_minutes }} min</p>
            </div>
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-accent/10">
              <Clock3 class="h-5 w-5 text-accent" />
            </div>
          </div>
        </div>

        <div class="rounded-xl border border-border bg-surface p-5 shadow-sm">
          <div class="flex items-center justify-between">
            <div class="min-w-0">
              <p class="text-xs font-medium uppercase tracking-wide text-text/45">Schedule</p>
              <p class="mt-2 text-sm font-semibold text-text truncate">
                {{ formatDate(exam.scheduled_start) }}
              </p>
            </div>
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-accent/10">
              <CalendarClock class="h-5 w-5 text-accent" />
            </div>
          </div>
        </div>
      </div>

      <!-- Question Paper Preview Section (Compact 5-items preview with pagination) -->
      <section class="space-y-3">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h2 class="text-base font-semibold text-text">Question Paper Preview</h2>
            <p class="mt-0.5 text-xs text-text/50">{{ exam.total_questions }} question(s) attached totaling {{ exam.total_marks }} mark(s).</p>
          </div>

          <button type="button" class="inline-flex items-center gap-1.5 text-xs font-medium text-accent hover:underline" @click="openQuestions">
            <span>Open Full Question Paper</span>
            <ExternalLink class="h-3.5 w-3.5" />
          </button>
        </div>

        <!-- Compact Table Container -->
        <div class="overflow-hidden rounded-xl border border-border bg-surface shadow-sm">
          <div class="overflow-x-auto">
            <table class="w-full border-collapse min-w-[640px]">
              <thead>
                <tr class="border-b border-border bg-bg/40">
                  <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-text/45">Question</th>
                  <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-text/45">Type</th>
                  <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-text/45">Difficulty</th>
                  <th class="px-5 py-3 text-right text-xs font-bold uppercase tracking-wide text-text/45">Marks</th>
                </tr>
              </thead>

              <tbody v-if="loadingQuestions" class="divide-y divide-border">
                <tr v-for="i in 3" :key="i">
                  <td colspan="4" class="px-5 py-4">
                    <div class="h-4 w-full animate-pulse rounded bg-bg" />
                  </td>
                </tr>
              </tbody>

              <tbody v-else-if="attachedQuestions.length" class="divide-y divide-border">
                <tr v-for="item in attachedQuestions" :key="item.pivot?.id || item.id" class="transition-colors hover:bg-text/[0.02]">
                  <td class="px-5 py-3.5">
                    <p class="line-clamp-2 text-sm font-medium text-text">
                      {{ item.question?.content || item.content || '—' }}
                    </p>
                  </td>

                  <td class="px-5 py-3.5">
                    <span class="inline-flex rounded bg-bg border border-border px-2 py-0.5 font-mono text-[10px] uppercase font-medium text-text/70">
                      {{ item.question?.type || item.type || '—' }}
                    </span>
                  </td>

                  <td class="px-5 py-3.5">
                    <BaseBadge :variant="getDifficultyVariant(item.question?.difficulty || item.difficulty)">
                      {{ item.question?.difficulty || item.difficulty || '—' }}
                    </BaseBadge>
                  </td>

                  <td class="px-5 py-3.5 text-right font-mono text-sm font-semibold tabular-nums text-text">
                    {{ getMarks(item) }}
                  </td>
                </tr>
              </tbody>

              <tbody v-else>
                <tr>
                  <td colspan="4" class="px-5 py-10 text-center">
                    <div class="mx-auto flex max-w-sm flex-col items-center">
                      <div class="mb-2 flex h-9 w-9 items-center justify-center rounded-lg bg-bg">
                        <HelpCircle class="h-5 w-5 text-text/30" />
                      </div>
                      <p class="text-sm font-medium text-text">No questions attached yet</p>
                      <p class="mt-0.5 text-xs text-text/45">Use "Manage Question Paper" to add or import questions.</p>
                      <BaseButton v-can="'exam.update'" size="sm" class="mt-3" @click="openQuestions">
                        <template #icon>
                          <FileQuestion class="h-3.5 w-3.5" />
                        </template>
                        Add Questions
                      </BaseButton>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination Bar for 5-item preview -->
          <div
            v-if="questionPagination && questionPagination.total > questionPerPage"
            class="flex items-center justify-between border-t border-border bg-surface px-5 py-3 text-xs">
            <span class="text-text/50">
              Showing {{ questionPagination.from || 1 }}–{{ questionPagination.to || attachedQuestions.length }} of {{ questionPagination.total }} questions
            </span>

            <div class="flex items-center gap-1.5">
              <button
                type="button"
                :disabled="questionPage <= 1"
                class="inline-flex items-center gap-1 rounded-md border border-border bg-bg px-2.5 py-1 text-xs font-medium text-text/70 transition-colors hover:border-accent/40 hover:text-accent disabled:opacity-40"
                @click="loadQuestions(questionPage - 1)">
                <ChevronLeft class="h-3.5 w-3.5" /> Prev
              </button>

              <span class="px-2 font-mono text-xs text-text/60"> {{ questionPage }} / {{ questionPagination.last_page }} </span>

              <button
                type="button"
                :disabled="questionPage >= questionPagination.last_page"
                class="inline-flex items-center gap-1 rounded-md border border-border bg-bg px-2.5 py-1 text-xs font-medium text-text/70 transition-colors hover:border-accent/40 hover:text-accent disabled:opacity-40"
                @click="loadQuestions(questionPage + 1)">
                Next <ChevronRight class="h-3.5 w-3.5" />
              </button>
            </div>
          </div>
        </div>
      </section>

      <!-- Composition Section -->
      <section class="space-y-3">
        <ExamCompositionEditor
          v-if="showComposition"
          :composition="exam.composition"
          :loading="actionLoading"
          @save="(composition) => runAction(() => updateExamComposition(exam!.id, composition), 'Composition updated successfully.')" />

        <div v-else class="rounded-xl border border-border bg-surface p-6 shadow-sm">
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-text/5">
              <Lock class="h-5 w-5 text-text/40" />
            </div>

            <div>
              <h3 class="text-sm font-semibold text-text">Composition is locked</h3>
              <p class="mt-0.5 text-xs text-text/50">The exam is in {{ exam.status.replaceAll('_', ' ') }} state, so its question composition cannot be modified.</p>
            </div>
          </div>
        </div>
      </section>
    </template>
  </div>
</template>
