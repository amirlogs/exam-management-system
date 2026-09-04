<script setup lang="ts">
import { ArrowLeft, FileSpreadsheet } from 'lucide-vue-next';

import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import ImportUploadStep from '@/modules/admin/imports/components/ImportUploadStep.vue';

import { getExam } from '../api/exams';

import type { Exam } from '../types/exam';

import { getTeaching } from '@/modules/instructor/teaching/api/teaching';

const route = useRoute();
const router = useRouter();

const examId = Number(route.params.examId);

const exam = ref<Exam | null>(null);
const courseOptions = ref<
  {
    value: string;
    label: string;
  }[]
>([]);

const loading = ref(true);
const error = ref<string | null>(null);

async function load() {
  loading.value = true;
  error.value = null;

  try {
    const [examResponse, teachingResponse] = await Promise.all([getExam(examId), getTeaching(1, 1000)]);

    exam.value = examResponse.data;

    const teaching = teachingResponse.data?.find((item: any) => item?.id === exam.value?.course_offering_id);

    if (teaching?.course) {
      courseOptions.value = [
        {
          value: String(teaching.course.id),
          label: `${teaching.course.code} — ${teaching.course.name}`,
        },
      ];
    }
  } catch {
    error.value = 'Unable to load the exam import context.';
  } finally {
    loading.value = false;
  }
}

function goBack() {
  router.push({
    name: 'instructor.exams.questions',
    params: {
      examId,
    },
  });
}

onMounted(load);
</script>

<template>
  <div class="mx-auto w-full max-w-[1100px] px-6 py-6">
    <button type="button" class="mb-5 inline-flex items-center gap-2 text-sm font-medium text-text/55 hover:text-text" @click="goBack">
      <ArrowLeft class="h-4 w-4" />
      Back to Exam Questions
    </button>

    <div class="mb-6 flex items-start gap-3">
      <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-text/5 text-text/50">
        <FileSpreadsheet class="h-5 w-5" />
      </div>

      <div>
        <p class="text-xs font-bold uppercase tracking-wide text-text/45">Exam question import</p>

        <h1 class="mt-1 text-2xl font-semibold tracking-tight text-text">Import Questions</h1>

        <p class="mt-1 max-w-2xl text-sm leading-6 text-text/55">
          Upload questions directly into this exam. They will go through the same validation and review process as Question Bank imports.
        </p>
      </div>
    </div>

    <div v-if="loading" class="rounded-md border border-border bg-surface px-6 py-14 text-center">
      <p class="text-sm text-text/45">Loading exam…</p>
    </div>

    <div v-else-if="error" class="rounded-md border border-error/20 bg-error/5 p-5">
      <p class="text-sm text-error">
        {{ error }}
      </p>

      <button type="button" class="mt-2 text-sm font-medium text-error underline" @click="load">Try again</button>
    </div>

    <template v-else-if="exam && courseOptions.length">
      <div class="mb-5 rounded-md border border-border bg-bg p-4">
        <p class="text-xs font-bold uppercase tracking-wide text-text/45">Importing into</p>

        <p class="mt-1 text-sm font-medium text-text">
          {{ exam.title }}
        </p>

        <p class="mt-1 text-xs text-text/45">The selected course is fixed to this exam.</p>
      </div>

      <ImportUploadStep type="questions" :course-options="courseOptions" />
    </template>
  </div>
</template>
