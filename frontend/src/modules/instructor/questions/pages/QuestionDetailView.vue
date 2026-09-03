<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { Archive, ArrowLeft, Pencil, RotateCcw } from 'lucide-vue-next';
import { useRoute, useRouter } from 'vue-router';

import ResourceToolbar from '@/shared/components/ResourceToolbar.vue';
import BaseButton from '@/shared/components/ui/BaseButton.vue';
import BaseBadge from '@/shared/components/ui/BaseBadge.vue';
import ConfirmModal from '@/shared/components/ConfirmModal.vue';

import { handleApiError } from '@/shared/utils/apiError';

import { useUiStore } from '@/stores/ui';

import * as api from '../api/questions';

import type { Question } from '../types/question';

const route = useRoute();
const router = useRouter();
const uiStore = useUiStore();

const question = ref<Question | null>(null);

const loading = ref(true);
const actionLoading = ref(false);

const showArchiveModal = ref(false);

const showRestoreModal = ref(false);

const questionId = computed(() => Number(route.params.questionId));

const difficultyVariant: Record<string, 'neutral' | 'info' | 'danger' | 'warning' | 'success' | 'dark'> = {
  easy: 'success',
  medium: 'warning',
  hard: 'danger',
};

function formatType(type: string) {
  const labels: Record<string, string> = {
    mcq: 'Multiple choice',
    true_false: 'True / False',
    short_answer: 'Short answer',
    essay: 'Essay',
  };

  return labels[type] || type;
}

function formatDifficulty(difficulty: string) {
  return difficulty.charAt(0).toUpperCase() + difficulty.slice(1);
}

async function load() {
  loading.value = true;

  try {
    question.value = await api.getQuestion(questionId.value);
  } catch (error) {
    handleApiError(error, uiStore, undefined, 'Failed to load question.');

    router.push({
      name: 'instructor.questions.list',
    });
  } finally {
    loading.value = false;
  }
}

function back() {
  router.push({
    name: 'instructor.questions.list',
  });
}

function edit() {
  router.push({
    name: 'instructor.questions.edit',
    params: {
      questionId: questionId.value,
    },
  });
}

async function archive() {
  actionLoading.value = true;

  try {
    await api.archiveQuestion(questionId.value);

    uiStore.showToast('Question archived successfully.', 'success');

    showArchiveModal.value = false;

    await load();
  } catch (error) {
    handleApiError(error, uiStore, undefined, 'Failed to archive question.');
  } finally {
    actionLoading.value = false;
  }
}

async function restore() {
  actionLoading.value = true;

  try {
    question.value = await api.restoreQuestion(questionId.value);

    uiStore.showToast('Question restored successfully.', 'success');

    showRestoreModal.value = false;
  } catch (error) {
    handleApiError(error, uiStore, undefined, 'Failed to restore question.');
  } finally {
    actionLoading.value = false;
  }
}

onMounted(load);
</script>

<template>
  <div class="mx-auto w-full max-w-300 space-y-6 px-6 py-6">
    <div v-if="loading" class="space-y-5">
      <div class="h-5 w-32 animate-pulse rounded bg-bg" />

      <div class="rounded-md border border-border bg-surface p-6">
        <div class="space-y-3">
          <div class="h-7 w-96 animate-pulse rounded bg-bg" />

          <div class="h-4 w-48 animate-pulse rounded bg-bg" />
        </div>
      </div>
    </div>

    <template v-else-if="question">
      <ResourceToolbar
        :title="question.course?.name || 'Question'"
        :description="`${question.course?.code || '—'} · Question #${question.id}`"
        :show-search="false"
        :show-refresh="true"
        :refreshing="loading"
        :show-fullscreen="true"
        @refresh="load">
        <template #actions>
          <BaseButton variant="secondary" @click="back">
            <template #icon>
              <ArrowLeft class="h-4 w-4" />
            </template>

            Back
          </BaseButton>

          <BaseButton v-if="question.status === 'active'" v-can="'question.update'" variant="secondary" @click="edit">
            <template #icon>
              <Pencil class="h-4 w-4" />
            </template>

            Edit
          </BaseButton>

          <BaseButton v-if="question.status === 'active'" v-can="'question.archive'" variant="secondary" @click="showArchiveModal = true">
            <template #icon>
              <Archive class="h-4 w-4" />
            </template>

            Archive
          </BaseButton>

          <BaseButton v-else v-can="'question.restore'" @click="showRestoreModal = true">
            <template #icon>
              <RotateCcw class="h-4 w-4" />
            </template>

            Restore
          </BaseButton>
        </template>
      </ResourceToolbar>

      <div class="rounded-md border border-border bg-surface shadow-sm">
        <div class="border-b border-border p-6">
          <div class="flex flex-wrap items-center gap-2">
            <BaseBadge variant="neutral">
              {{ formatType(question.type) }}
            </BaseBadge>

            <BaseBadge :variant="difficultyVariant[question.difficulty] || 'neutral'">
              {{ formatDifficulty(question.difficulty) }}
            </BaseBadge>

            <BaseBadge :variant="question.status === 'active' ? 'success' : 'dark'">
              {{ question.status === 'active' ? 'Active' : 'Archived' }}
            </BaseBadge>
          </div>

          <h1 class="mt-5 text-xl font-semibold leading-relaxed text-text">
            {{ question.content }}
          </h1>

          <div class="mt-4 flex flex-wrap gap-x-6 gap-y-2 text-sm text-text/50">
            <span>
              Chapter:
              <strong class="font-medium text-text/70">
                {{ question.chapter || '—' }}
              </strong>
            </span>

            <span>
              Created:
              <strong class="font-medium text-text/70">
                {{ question.created_at || '—' }}
              </strong>
            </span>

            <span>
              Updated:
              <strong class="font-medium text-text/70">
                {{ question.updated_at || '—' }}
              </strong>
            </span>
          </div>
        </div>

        <div v-if="question.type === 'mcq' || question.type === 'true_false'" class="p-6">
          <h2 class="mb-3 text-xs font-bold uppercase tracking-wide text-text/45">Answer choices</h2>

          <div class="space-y-2">
            <div
              v-for="(option, index) in question.options"
              :key="option.id"
              class="flex items-center justify-between gap-4 rounded-md border p-3"
              :class="option.is_correct ? 'border-success/30 bg-success/5' : 'border-border bg-surface'">
              <div class="flex items-center gap-3">
                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-bg text-xs font-bold text-text/60">
                  {{ String.fromCharCode(65 + index) }}
                </span>

                <span class="text-sm text-text">
                  {{ option.option_text }}
                </span>
              </div>

              <BaseBadge v-if="option.is_correct" variant="success"> Correct </BaseBadge>
            </div>
          </div>
        </div>

        <div v-else class="p-6">
          <h2 class="mb-2 text-xs font-bold uppercase tracking-wide text-text/45">Answer</h2>

          <p class="text-sm leading-relaxed text-text/60">The current question API does not persist a separate expected answer for essay and short-answer questions.</p>
        </div>
      </div>
    </template>
  </div>

  <ConfirmModal
    :show="showArchiveModal"
    title="Archive this question?"
    description="This question will be removed from the active question bank but can be restored later."
    confirm-text="Archive question"
    variant="danger"
    :loading="actionLoading"
    @close="showArchiveModal = false"
    @confirm="archive" />

  <ConfirmModal
    :show="showRestoreModal"
    title="Restore this question?"
    description="This question will become active again and return to the question bank."
    confirm-text="Restore question"
    :loading="actionLoading"
    @close="showRestoreModal = false"
    @confirm="restore" />
</template>
