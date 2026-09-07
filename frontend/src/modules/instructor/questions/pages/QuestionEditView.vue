<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { ArrowLeft } from 'lucide-vue-next';
import { useRoute, useRouter } from 'vue-router';

import ResourceToolbar from '@/shared/components/ResourceToolbar.vue';
import BaseButton from '@/shared/components/ui/BaseButton.vue';

import { handleApiError } from '@/shared/utils/apiError';
import { useUiStore } from '@/stores/ui';

import QuestionFormView from '../components/QuestionForm.vue';
import * as api from '../api/questions';

import type { Question, QuestionDifficulty, QuestionType } from '../types/question';

const route = useRoute();
const router = useRouter();
const uiStore = useUiStore();

const questionId = computed(() => Number(route.params.questionId));

const question = ref<Question | null>(null);
const loading = ref(true);
const saving = ref(false);

async function load() {
  loading.value = true;

  try {
    question.value = await api.getQuestion(questionId.value);
  } catch (error) {
    handleApiError(error, uiStore, undefined, 'Failed to load question.');

    router.push({ name: 'instructor.questions.list' });
  } finally {
    loading.value = false;
  }
}

async function handleSubmit(payload: { type: QuestionType; chapter: string; content: string; difficulty: QuestionDifficulty; options: string[]; correctAnswer: string }) {
  saving.value = true;

  try {
    await api.updateQuestion(questionId.value, {
      type: payload.type,
      chapter: payload.chapter || null,
      content: payload.content,
      difficulty: payload.difficulty,
      options: payload.options,
      correct_answer: payload.correctAnswer || null,
    });

    uiStore.showToast('Question updated successfully.', 'success');

    router.push({ name: 'instructor.questions.detail', params: { questionId: questionId.value } });
  } catch (error) {
    handleApiError(error, uiStore, undefined, 'Failed to update question.');
  } finally {
    saving.value = false;
  }
}

function cancel() {
  router.push({ name: 'instructor.questions.detail', params: { questionId: questionId.value } });
}

onMounted(load);
</script>

<template>
  <div class="mx-auto w-full max-w-220 space-y-6 px-6 py-6">
    <ResourceToolbar title="Edit Question" description="Update this question's content and answers." :show-search="false" :show-refresh="false" :show-fullscreen="false">
      <template #actions>
        <BaseButton variant="secondary" @click="cancel">
          <template #icon>
            <ArrowLeft class="h-4 w-4" />
          </template>
          Back
        </BaseButton>
      </template>
    </ResourceToolbar>

    <div v-if="loading" class="rounded-md border border-border bg-surface p-6 text-sm text-text/50">Loading question…</div>

    <QuestionFormView v-else-if="question" :question="question" :saving="saving" @submit="handleSubmit" @cancel="cancel" />
  </div>
</template>
