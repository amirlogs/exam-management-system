<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { ArrowLeft, FileQuestion, FileSpreadsheet, Plus, RefreshCw, Sparkles, Upload } from 'lucide-vue-next';

import BaseButton from '@/shared/components/ui/BaseButton.vue';
import ExamStatusBadge from '../components/ExamStatusBadge.vue';
import ExamQuestionsTable from '../components/ExamQuestionsTable.vue';
import ExamQuestionPicker from '../components/ExamQuestionPicker.vue';
import AiQuestionGeneratorModal from '@/modules/instructor/questions/components/AiQuestionGeneratorModal.vue';

import { addExamQuestion, addExamQuestionsBulk, getExam, listExamQuestions, removeExamQuestion } from '../api/exams';
import { getTeaching } from '@/modules/instructor/teaching/api/teaching';
import { useUiStore } from '@/stores/ui';
import { handleApiError } from '@/shared/utils/apiError';

import type { Exam, ExamQuestionItem } from '../types/exam';

const route = useRoute();
const router = useRouter();
const uiStore = useUiStore();

const examId = computed(() => Number(route.params.examId));

const exam = ref<Exam | null>(null);
const questions = ref<ExamQuestionItem[]>([]);
const examCourseId = ref<number | null>(null);
const examCourseLabel = ref<string>('');

const loading = ref(true);
const actionLoading = ref(false);

const showPicker = ref(false);
const showAiModal = ref(false);
const removingId = ref<number | null>(null);

const canEdit = computed(() => exam.value?.status === 'draft');
const questionIds = computed(() => questions.value.map((item) => item.question?.id || item.id));

async function load() {
  loading.value = true;
  try {
    const [examResponse, questionResponse, teachingResponse] = await Promise.all([
      getExam(examId.value),
      listExamQuestions(examId.value, 1, 100),
      getTeaching(1, 1000).catch(() => ({ data: [] })),
    ]);

    exam.value = examResponse.data;
    questions.value = questionResponse.data ?? [];

    const teaching = (teachingResponse.data || []).find(
      (item: any) => item?.id === exam.value?.course_offering_id
    );
    if (teaching?.course) {
      examCourseId.value = teaching.course.id;
      examCourseLabel.value = `${teaching.course.code} — ${teaching.course.name || teaching.course.title}`;
    }
  } catch (error) {
    handleApiError(error, uiStore, undefined, 'Unable to load exam questions.');
  } finally {
    loading.value = false;
  }
}

function goBack() {
  router.push({
    name: 'instructor.exams.detail',
    params: {
      examId: examId.value,
    },
  });
}

function openImport() {
  router.push({
    name: 'instructor.exams.questions.import',
    params: {
      examId: examId.value,
    },
  });
}

const pickerErrorMessage = ref<string | null>(null);

function openPicker() {
  pickerErrorMessage.value = null;
  showPicker.value = true;
}

async function handleAddBulk(payload: { questions: { question_id: number; marks?: number }[] }) {
  actionLoading.value = true;
  pickerErrorMessage.value = null;
  try {
    const res = await addExamQuestionsBulk(examId.value, payload);
    const createdCount = res.data?.created?.length ?? payload.questions.length;
    const errors = res.data?.errors || res.errors;

    if (errors && Object.keys(errors).length > 0) {
      const messages = Object.values(errors).flat().join(' · ');
      uiStore.showToast(messages || 'Some questions could not be added.', 'error');
    } else {
      uiStore.showToast(`${createdCount} question${createdCount === 1 ? '' : 's'} added to exam.`, 'success');
    }

    showPicker.value = false;
    await load();
  } catch (requestError: any) {
    const errData = requestError?.response?.data;
    let message = 'Unable to add selected questions.';

    if (errData?.errors) {
      if (Array.isArray(errData.errors)) {
        const flat = errData.errors.flat();
        const unique = Array.from(new Set(flat.map((m: any) => (typeof m === 'string' ? m : JSON.stringify(m)))));
        message = unique.join(' · ');
      } else if (typeof errData.errors === 'object') {
        const flat = Object.values(errData.errors).flat();
        const unique = Array.from(new Set(flat.map((m: any) => (typeof m === 'string' ? m : JSON.stringify(m)))));
        message = unique.join(' · ');
      } else if (typeof errData.errors === 'string') {
        message = errData.errors;
      }
    } else if (errData?.message) {
      message = errData.message;
    }

    pickerErrorMessage.value = message;
    uiStore.showToast(message, 'error');
  } finally {
    actionLoading.value = false;
  }
}

async function removeQuestion(question: any) {
  const pivotId = question?.pivot?.id || question?.exam_question_id || question?.id;
  if (!pivotId) return;

  removingId.value = pivotId;
  try {
    await removeExamQuestion(examId.value, pivotId);
    uiStore.showToast('Question removed from exam.', 'success');
    await load();
  } catch (requestError: any) {
    handleApiError(requestError, uiStore, undefined, 'Unable to remove this question.');
  } finally {
    removingId.value = null;
  }
}

onMounted(load);
</script>

<template>
  <div class="mx-auto w-full max-w-360 space-y-6 px-6 py-6 min-h-[calc(100vh-68px)]">
    <!-- Top Navigation -->
    <div class="flex items-center gap-3">
      <button
        type="button"
        class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-border bg-surface text-text/60 transition-colors hover:border-accent/40 hover:text-accent hover:bg-accent/5"
        title="Back to exam details"
        @click="goBack">
        <ArrowLeft class="h-4 w-4" />
      </button>

      <div>
        <h1 class="font-display text-xl font-bold text-text">Exam Question Paper</h1>
        <p class="text-xs text-text/45">Manage questions attached to this exam paper.</p>
      </div>
    </div>

    <!-- Header Card -->
    <div v-if="exam" class="rounded-xl border border-border bg-surface p-6 shadow-sm">
      <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex min-w-0 items-start gap-4">
          <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-accent/10">
            <FileQuestion class="h-6 w-6 text-accent" />
          </div>

          <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2.5">
              <h2 class="font-display text-2xl font-bold text-text">
                {{ exam.title }}
              </h2>
              <ExamStatusBadge :status="exam.status" />
              <span class="inline-flex rounded-md bg-bg border border-border px-2.5 py-0.5 font-mono text-xs uppercase font-medium text-text/70">
                {{ exam.type }}
              </span>
            </div>

            <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs text-text/55">
              <span
                >Attached: <span class="font-medium text-text/80">{{ exam.total_questions || 0 }} question(s)</span></span
              >
              <span>•</span>
              <span
                >Total Marks: <span class="font-medium text-text/80">{{ exam.total_marks || 0 }}</span></span
              >
              <span>•</span>
              <span
                >Duration: <span class="font-medium text-text/80">{{ exam.duration_minutes }} min</span></span
              >
            </div>
          </div>
        </div>

        <div v-if="canEdit" class="flex items-center gap-2 shrink-0 flex-wrap sm:flex-nowrap">
          <BaseButton v-can="'exam.update'" @click="openPicker">
            <template #icon>
              <Plus class="h-4 w-4" />
            </template>
            Add questions
          </BaseButton>

          <BaseButton v-can="'exam.update'" variant="secondary" @click="openImport">
            <template #icon>
              <Upload class="h-4 w-4" />
            </template>
            Import CSV
          </BaseButton>

          <BaseButton v-can="'exam.update'" variant="secondary" @click="showAiModal = true">
            <template #icon>
              <Sparkles class="h-4 w-4 text-accent" />
            </template>
            Generate with AI
          </BaseButton>

          <button
            type="button"
            class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-border bg-surface text-text/60 transition-colors hover:border-accent/40 hover:text-accent disabled:opacity-50 cursor-pointer"
            title="Refresh"
            @click="load">
            <RefreshCw class="h-4 w-4" :class="{ 'animate-spin': loading }" />
          </button>
        </div>
      </div>
    </div>

    <!-- Questions Table -->
    <ExamQuestionsTable :questions="questions" :loading="loading" :can-remove="canEdit" @remove="removeQuestion" />

    <!-- Helper note -->
    <div class="flex items-center gap-2 text-xs text-text/45">
      <FileSpreadsheet class="h-4 w-4" />
      <span>CSV import follows the same review → fix → confirm workflow used by Question Bank.</span>
    </div>

    <!-- Question Picker Modal -->
    <ExamQuestionPicker
      v-if="showPicker"
      :exam="exam!"
      :existing-question-ids="questionIds"
      :loading="actionLoading"
      :error-message="pickerErrorMessage"
      @add-bulk="handleAddBulk"
      @close="showPicker = false" />

    <!-- AI Question Generator Modal -->
    <AiQuestionGeneratorModal
      v-model="showAiModal"
      :exam-id="examId"
      :course-id="examCourseId"
      :course-name="examCourseLabel || exam?.title"
      scope="exam"
      @confirmed="load"
    />
  </div>
</template>
