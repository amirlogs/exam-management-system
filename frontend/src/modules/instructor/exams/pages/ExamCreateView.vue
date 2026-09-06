<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { ArrowLeft, FileText } from 'lucide-vue-next';
import { useRoute, useRouter } from 'vue-router';

import BaseSelect from '@/shared/components/ui/BaseSelect.vue';
import ExamForm from '../components/ExamForm.vue';
import { createExam } from '../api/exams';
import { getTeaching } from '@/modules/instructor/teaching/api/teaching';
import { useUiStore } from '@/stores/ui';
import { handleApiError } from '@/shared/utils/apiError';

const router = useRouter();
const route = useRoute();
const uiStore = useUiStore();

const saving = ref(false);
const loadingTeachings = ref(true);
const selectedOfferingId = ref<string>('');
const teachingOptions = ref<{ value: string; label: string }[]>([]);

async function loadTeachings() {
  loadingTeachings.value = true;
  try {
    const res = await getTeaching(1, 1000);
    const teachings = res.data ?? [];
    teachingOptions.value = teachings
      .filter((item: any) => item?.id && item?.course)
      .map((item: any) => ({
        value: String(item.id),
        label: `${item.course.code} — ${item.course.name} (${item.semester?.name || 'Current'})`,
      }));

    if (route.query.courseOfferingId) {
      selectedOfferingId.value = String(route.query.courseOfferingId);
    } else if (teachingOptions.value.length) {
      selectedOfferingId.value = teachingOptions.value[0].value;
    }
  } catch (error) {
    handleApiError(error, uiStore, undefined, 'Failed to load your teaching assignments.');
  } finally {
    loadingTeachings.value = false;
  }
}

function goBack() {
  router.push({
    name: 'instructor.exams.list',
  });
}

async function handleSubmit(payload: { title: string; type: string; duration_minutes: number; composition: Record<string, { marks_each?: number }> }) {
  const courseOfferingId = Number(selectedOfferingId.value);
  if (!courseOfferingId) {
    uiStore.showToast('Please select a course offering for this exam.', 'error');
    return;
  }

  saving.value = true;
  try {
    const response = await createExam(courseOfferingId, payload);
    uiStore.showToast('Exam created successfully.', 'success');

    router.replace({
      name: 'instructor.exams.detail',
      params: {
        examId: response.data.id,
      },
    });
  } catch (error: any) {
    handleApiError(error, uiStore, undefined, 'Failed to create exam.');
  } finally {
    saving.value = false;
  }
}

onMounted(loadTeachings);
</script>

<template>
  <div class="mx-auto w-full max-w-360 space-y-6 px-6 py-6 min-h-[calc(100vh-68px)]">
    <!-- Top Navigation -->
    <div class="flex items-center gap-3">
      <button
        type="button"
        class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-border bg-surface text-text/60 transition-colors hover:border-accent/40 hover:text-accent hover:bg-accent/5"
        title="Back to exams"
        @click="goBack">
        <ArrowLeft class="h-4 w-4" />
      </button>

      <div>
        <h1 class="font-display text-xl font-bold text-text">Create New Exam</h1>
        <p class="text-xs text-text/45">Create a draft exam paper for one of your assigned course offerings.</p>
      </div>
    </div>

    <div class="rounded-xl border border-border bg-surface p-6 shadow-sm">
      <div class="flex items-start gap-4">
        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-accent/10">
          <FileText class="h-6 w-6 text-accent" />
        </div>
        <div class="min-w-0 flex-1">
          <h2 class="font-display text-lg font-bold text-text">Course Offering Assignment</h2>
          <p class="mt-0.5 text-xs text-text/50">Select which course offering this exam belongs to.</p>

          <div class="mt-4 max-w-md">
            <BaseSelect v-model="selectedOfferingId" label="Course Offering" :options="teachingOptions" placeholder="Select a course offering" />
          </div>
        </div>
      </div>
    </div>

    <ExamForm :loading="saving" @submit="handleSubmit" @cancel="goBack" />
  </div>
</template>
