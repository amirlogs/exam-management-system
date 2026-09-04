<script setup lang="ts">
import { ArrowLeft, CalendarClock, ClipboardList, Clock3, FileText, Settings2 } from 'lucide-vue-next';

import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import BaseButton from '@/shared/components/ui/BaseButton.vue';

import ExamStatusBadge from '../components/ExamStatusBadge.vue';
import ExamCompositionEditor from '../components/ExamCompositionEditor.vue';
import ExamLifecycleActions from '../components/ExamLifecycleActions.vue';

import {
  approveExam,
  archiveExam,
  cancelExam,
  endExam,
  getExam,
  publishExam,
  rejectExam,
  revertExamToDraft,
  scheduleExam,
  submitExamForApproval,
  updateExamComposition,
} from '../api/exams';

import type { Exam } from '../types/exam';

const router = useRouter();
const route = useRoute();

const exam = ref<Exam | null>(null);
const loading = ref(true);
const actionLoading = ref(false);
const error = ref<string | null>(null);

const showComposition = computed(() => exam.value?.status === 'draft');

const examId = Number(route.params.examId);

async function loadExam() {
  loading.value = true;
  error.value = null;

  try {
    const response = await getExam(examId);

    exam.value = response.data;
  } catch {
    error.value = 'Unable to load this exam.';
  } finally {
    loading.value = false;
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
      examId,
    },
  });
}

async function runAction(action: () => Promise<unknown>) {
  actionLoading.value = true;
  error.value = null;

  try {
    await action();
    await loadExam();
  } catch (requestError: any) {
    error.value = requestError?.response?.data?.message || 'The exam action could not be completed.';
  } finally {
    actionLoading.value = false;
  }
}

async function handleSchedule() {
  router.push({
    name: 'instructor.exams.questions',
    params: {
      examId,
    },
    query: {
      action: 'schedule',
    },
  });
}

onMounted(loadExam);
</script>

<template>
  <div class="mx-auto w-full max-w-[1100px] px-6 py-6">
    <button type="button" class="mb-5 inline-flex items-center gap-2 text-sm font-medium text-text/55 hover:text-text" @click="goBack">
      <ArrowLeft class="h-4 w-4" />
      Back to Exams
    </button>

    <div v-if="loading" class="space-y-5">
      <div class="h-8 w-72 animate-pulse rounded bg-text/5" />
      <div class="h-32 animate-pulse rounded-md bg-text/5" />
      <div class="h-56 animate-pulse rounded-md bg-text/5" />
    </div>

    <div v-else-if="error" class="rounded-md border border-error/20 bg-error/5 p-5">
      <p class="text-sm text-error">
        {{ error }}
      </p>

      <button type="button" class="mt-2 text-sm font-medium text-error underline" @click="loadExam">Try again</button>
    </div>

    <template v-else-if="exam">
      <div class="mb-6 flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <div class="flex flex-wrap items-center gap-2">
            <h1 class="text-2xl font-semibold tracking-tight text-text">
              {{ exam.title }}
            </h1>

            <ExamStatusBadge :status="exam.status" />
          </div>

          <p class="mt-2 text-sm text-text/55">{{ exam.type }} · {{ exam.duration_minutes }} minutes</p>
        </div>

        <ExamLifecycleActions
          :exam="exam"
          :loading="actionLoading"
          @submit="runAction(() => submitExamForApproval(exam.id))"
          @revert="runAction(() => revertExamToDraft(exam.id))"
          @approve="runAction(() => approveExam(exam.id))"
          @reject="(reason) => runAction(() => rejectExam(exam.id, reason))"
          @schedule="handleSchedule"
          @publish="runAction(() => publishExam(exam.id))"
          @end="runAction(() => endExam(exam.id))"
          @cancel="runAction(() => cancelExam(exam.id))"
          @archive="runAction(() => archiveExam(exam.id))" />
      </div>

      <div class="mb-5 grid grid-cols-2 gap-3 md:grid-cols-4">
        <div class="rounded-md border border-border bg-surface p-4">
          <div class="flex items-center gap-2 text-text/45">
            <ClipboardList class="h-4 w-4" />
            <span class="text-xs">Questions</span>
          </div>

          <p class="mt-2 font-mono text-xl font-semibold text-text">
            {{ exam.total_questions }}
          </p>
        </div>

        <div class="rounded-md border border-border bg-surface p-4">
          <div class="flex items-center gap-2 text-text/45">
            <FileText class="h-4 w-4" />
            <span class="text-xs">Total marks</span>
          </div>

          <p class="mt-2 font-mono text-xl font-semibold text-text">
            {{ exam.total_marks }}
          </p>
        </div>

        <div class="rounded-md border border-border bg-surface p-4">
          <div class="flex items-center gap-2 text-text/45">
            <Clock3 class="h-4 w-4" />
            <span class="text-xs">Duration</span>
          </div>

          <p class="mt-2 font-mono text-xl font-semibold text-text">{{ exam.duration_minutes }}m</p>
        </div>

        <div class="rounded-md border border-border bg-surface p-4">
          <div class="flex items-center gap-2 text-text/45">
            <CalendarClock class="h-4 w-4" />
            <span class="text-xs">Schedule</span>
          </div>

          <p class="mt-2 text-sm font-medium text-text">
            {{ exam.scheduled_start || 'Not scheduled' }}
          </p>
        </div>
      </div>

      <div class="mb-5 rounded-md border border-border bg-surface p-6">
        <div class="flex items-start justify-between gap-4">
          <div>
            <h2 class="text-base font-semibold text-text">Questions</h2>

            <p class="mt-1 text-sm text-text/55">Build the exam paper from your question bank.</p>
          </div>

          <BaseButton @click="openQuestions"> Manage questions </BaseButton>
        </div>
      </div>

      <ExamCompositionEditor
        v-if="showComposition"
        :composition="exam.composition"
        :loading="actionLoading"
        @save="(composition) => runAction(() => updateExamComposition(exam.id, composition))" />

      <div v-else class="rounded-md border border-border bg-surface p-6">
        <div class="flex items-center gap-3">
          <Settings2 class="h-5 w-5 text-text/45" />

          <div>
            <h2 class="text-sm font-semibold text-text">Composition is locked</h2>

            <p class="mt-1 text-xs leading-5 text-text/45">The exam is no longer in draft, so its composition cannot be changed.</p>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
