<script setup lang="ts">
import { ArrowLeft, FileSpreadsheet, Plus, RefreshCw, Upload } from 'lucide-vue-next';

import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import BaseButton from '@/shared/components/ui/BaseButton.vue';

import ExamQuestionsTable from '../components/ExamQuestionsTable.vue';
import ExamQuestionPicker from '../components/ExamQuestionPicker.vue';

import { addExamQuestion, getExam, listExamQuestions, removeExamQuestion } from '../api/exams';

import type { Exam, ExamQuestion } from '../types/exam';

const route = useRoute();
const router = useRouter();

const examId = Number(route.params.examId);

const exam = ref<Exam | null>(null);
const questions = ref<ExamQuestion[]>([]);

const loading = ref(true);
const actionLoading = ref(false);
const error = ref<string | null>(null);

const showPicker = ref(false);
const removingId = ref<number | null>(null);

const canEdit = computed(() => exam.value?.status === 'draft');

const questionIds = computed(() => questions.value.map((item) => item.question.id));

async function load() {
  loading.value = true;
  error.value = null;

  try {
    const [examResponse, questionResponse] = await Promise.all([getExam(examId), listExamQuestions(examId, 1, 100)]);

    exam.value = examResponse.data;

    questions.value = questionResponse.data ?? [];
  } catch {
    error.value = 'Unable to load exam questions.';
  } finally {
    loading.value = false;
  }
}

function goBack() {
  router.push({
    name: 'instructor.exams.detail',
    params: {
      examId,
    },
  });
}

function openImport() {
  router.push({
    name: 'instructor.exams.questions.import',
    params: {
      examId,
    },
  });
}

async function addQuestion(payload: { question_id: number; marks?: number }) {
  actionLoading.value = true;
  error.value = null;

  try {
    await addExamQuestion(examId, payload);

    showPicker.value = false;

    await load();
  } catch (requestError: any) {
    error.value = requestError?.response?.data?.message || 'Unable to add this question.';
  } finally {
    actionLoading.value = false;
  }
}

async function removeQuestion(question: ExamQuestion) {
  removingId.value = question.id;
  error.value = null;

  try {
    await removeExamQuestion(examId, question.id);

    await load();
  } catch (requestError: any) {
    error.value = requestError?.response?.data?.message || 'Unable to remove this question.';
  } finally {
    removingId.value = null;
  }
}

onMounted(load);
</script>

<template>
  <div class="mx-auto w-full max-w-[1200px] px-6 py-6">
    <button type="button" class="mb-5 inline-flex items-center gap-2 text-sm font-medium text-text/55 hover:text-text" @click="goBack">
      <ArrowLeft class="h-4 w-4" />
      Back to Exam
    </button>

    <div v-if="exam" class="mb-6 flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
      <div>
        <p class="text-xs font-bold uppercase tracking-wide text-text/45">{{ exam.type }} · {{ exam.status.replaceAll('_', ' ') }}</p>

        <h1 class="mt-1 text-2xl font-semibold tracking-tight text-text">
          {{ exam.title }}
        </h1>

        <p class="mt-1 text-sm text-text/55">{{ exam.total_questions }} questions · {{ exam.total_marks }} marks</p>
      </div>

      <div v-if="canEdit" class="flex flex-wrap gap-2">
        <BaseButton variant="secondary" @click="openPicker = true">
          <template #icon>
            <Plus class="h-4 w-4" />
          </template>
          Add question
        </BaseButton>

        <BaseButton variant="secondary" @click="openImport">
          <template #icon>
            <Upload class="h-4 w-4" />
          </template>
          Import CSV
        </BaseButton>
      </div>
    </div>

    <div v-if="error" class="mb-5 rounded-md border border-error/20 bg-error/5 p-4">
      <p class="text-sm text-error">
        {{ error }}
      </p>
    </div>

    <div class="mb-5 rounded-md border border-border bg-surface p-4">
      <div class="flex items-center justify-between gap-4">
        <div>
          <p class="text-xs font-bold uppercase tracking-wide text-text/45">Exam questions</p>

          <p class="mt-1 text-sm text-text/55">Questions currently attached to this exam.</p>
        </div>

        <button
          type="button"
          class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-border text-text/55 hover:border-accent/40 hover:text-accent"
          title="Refresh"
          @click="load">
          <RefreshCw class="h-4 w-4" :class="{ 'animate-spin': loading }" />
        </button>
      </div>
    </div>

    <ExamQuestionsTable :questions="questions" :loading="loading" :can-remove="canEdit" @remove="removeQuestion" />

    <div class="mt-4 flex items-center gap-2 text-xs text-text/45">
      <FileSpreadsheet class="h-4 w-4" />

      <span> CSV import follows the same review → fix → confirm workflow used by Question Bank. </span>
    </div>

    <ExamQuestionPicker v-if="showPicker" :exam="exam!" :existing-question-ids="questionIds" :loading="actionLoading" @add="addQuestion" @close="showPicker = false" />
  </div>
</template>
