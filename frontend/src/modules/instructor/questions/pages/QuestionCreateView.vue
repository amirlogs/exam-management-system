<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { ArrowLeft } from 'lucide-vue-next';
import { useRouter } from 'vue-router';

import ResourceToolbar from '@/shared/components/ResourceToolbar.vue';
import BaseButton from '@/shared/components/ui/BaseButton.vue';

import { handleApiError } from '@/shared/utils/apiError';
import { useUiStore } from '@/stores/ui';

import { getTeaching } from '@/modules/instructor/teaching/api/teaching';

import QuestionFormView from '../components/QuestionForm.vue';
import * as api from '../api/questions';

import type { QuestionDifficulty, QuestionType } from '../types/question';

const router = useRouter();
const uiStore = useUiStore();

const loadingCourses = ref(true);
const saving = ref(false);

const courseOptions = ref<{ value: string; label: string }[]>([]);

async function loadCourses() {
  loadingCourses.value = true;

  try {
    const res = await getTeaching(1, 1000);

    const unique = new Map<number, { value: string; label: string }>();

    for (const teaching of res.data) {
      if (!teaching.course) {
        continue;
      }

      if (!unique.has(teaching.course.id)) {
        unique.set(teaching.course.id, {
          value: String(teaching.course.id),
          label: `${teaching.course.code} - ${teaching.course.name}`,
        });
      }
    }

    courseOptions.value = Array.from(unique.values());
  } catch (error) {
    handleApiError(error, uiStore, undefined, 'Failed to load your teaching courses.');
  } finally {
    loadingCourses.value = false;
  }
}

async function handleSubmit(payload: {
  courseId: number;
  type: QuestionType;
  chapter: string;
  content: string;
  difficulty: QuestionDifficulty;
  options: string[];
  correctAnswer: string;
}) {
  saving.value = true;

  try {
    const question = await api.createQuestion(payload.courseId, {
      type: payload.type,
      chapter: payload.chapter || null,
      content: payload.content,
      difficulty: payload.difficulty,
      options: payload.options,
      correct_answer: payload.correctAnswer || null,
    });

    uiStore.showToast('Question created successfully.', 'success');

    router.push({
      name: 'instructor.questions.detail',
      params: { questionId: question.id },
    });
  } catch (error) {
    handleApiError(error, uiStore, undefined, 'Failed to create question.');
  } finally {
    saving.value = false;
  }
}

function cancel() {
  router.push({ name: 'instructor.questions.add' });
}

onMounted(loadCourses);
</script>

<template>
  <div class="mx-auto w-full max-w-220 space-y-6 px-6 py-6">
    <ResourceToolbar title="Create Question" description="Add a single question to your question bank." :show-search="false" :show-refresh="false" :show-fullscreen="false">
      <template #actions>
        <BaseButton variant="secondary" @click="cancel">
          <template #icon>
            <ArrowLeft class="h-4 w-4" />
          </template>
          Back
        </BaseButton>
      </template>
    </ResourceToolbar>

    <div v-if="loadingCourses" class="rounded-md border border-border bg-surface p-6 text-sm text-text/50">Loading your teaching courses…</div>

    <div v-else-if="!courseOptions.length" class="rounded-md border border-border bg-surface p-6 text-sm text-text/50">
      You aren't assigned to any course offerings yet, so there's no course to attach a question to.
    </div>

    <QuestionFormView v-else :courses="courseOptions" :saving="saving" @submit="handleSubmit" @cancel="cancel" />
  </div>
</template>
