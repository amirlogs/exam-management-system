<script setup lang="ts">
import { ArrowLeft } from 'lucide-vue-next';
import { useRoute, useRouter } from 'vue-router';
import { ref } from 'vue';

import ExamForm from '../components/ExamForm.vue';
import { createExam } from '../api/exams';

const router = useRouter();
const route = useRoute();

const saving = ref(false);

const courseOfferingId = Number(route.query.courseOfferingId);

function goBack() {
  router.push({
    name: 'instructor.exams.list',
  });
}

async function handleSubmit(payload: { title: string; type: string; duration_minutes: number; composition: Record<string, { marks_each?: number }> }) {
  if (!courseOfferingId) {
    return;
  }

  saving.value = true;

  try {
    const response = await createExam(courseOfferingId, payload);

    router.replace({
      name: 'instructor.exams.detail',
      params: {
        examId: response.data.id,
      },
    });
  } finally {
    saving.value = false;
  }
}
</script>

<template>
  <div class="mx-auto w-full max-w-[1000px] px-6 py-6">
    <button type="button" class="mb-5 inline-flex items-center gap-2 text-sm font-medium text-text/55 hover:text-text" @click="goBack">
      <ArrowLeft class="h-4 w-4" />
      Back to Exams
    </button>

    <div class="mb-6">
      <h1 class="text-2xl font-semibold tracking-tight text-text">Create exam</h1>

      <p class="mt-1 text-sm leading-6 text-text/55">Create a draft exam for your selected course offering.</p>
    </div>

    <div v-if="!courseOfferingId" class="rounded-md border border-error/20 bg-error/5 p-5">
      <p class="text-sm text-error">No course offering was selected.</p>
    </div>

    <ExamForm v-else :loading="saving" @submit="handleSubmit" @cancel="goBack" />
  </div>
</template>
