<script setup lang="ts">
import { HelpCircle, Trash2 } from 'lucide-vue-next';
import BaseBadge from '@/shared/components/ui/BaseBadge.vue';

defineProps<{
  questions: any[];
  loading?: boolean;
  canRemove: boolean;
}>();

const emit = defineEmits<{
  remove: [any];
}>();

function getContent(item: any): string {
  return item?.question?.content || item?.content || '—';
}

function getChapter(item: any): string {
  return item?.question?.chapter || item?.chapter || '';
}

function getType(item: any): string {
  return item?.question?.type || item?.type || '—';
}

function getDifficulty(item: any): string {
  return item?.question?.difficulty || item?.difficulty || '';
}

function getMarks(item: any): number | string {
  if (item?.marks !== undefined && item?.marks !== null && item?.marks !== '') {
    return item.marks;
  }
  if (item?.pivot?.marks !== undefined && item?.pivot?.marks !== null && item?.pivot?.marks !== '') {
    return item.pivot.marks;
  }
  return '—';
}

function getDifficultyVariant(difficulty: string | null | undefined): 'neutral' | 'info' | 'danger' | 'warning' | 'success' | 'dark' {
  if (!difficulty) return 'neutral';
  const val = difficulty.toLowerCase();
  if (val === 'easy') return 'success';
  if (val === 'medium') return 'warning';
  if (val === 'hard') return 'danger';
  return 'neutral';
}
</script>

<template>
  <div class="overflow-hidden rounded-xl border border-border bg-surface shadow-sm">
    <div class="overflow-x-auto">
      <table class="w-full border-collapse min-w-[760px]">
        <thead>
          <tr class="border-b border-border bg-bg/40">
            <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-text/45">Question</th>
            <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-text/45">Type</th>
            <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-text/45">Difficulty</th>
            <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-text/45">Marks</th>
            <th class="w-16 px-6 py-3.5 text-right text-xs font-bold uppercase tracking-wide text-text/45">Action</th>
          </tr>
        </thead>

        <tbody v-if="loading" class="divide-y divide-border">
          <tr v-for="item in 5" :key="item">
            <td colspan="5" class="px-6 py-4">
              <div class="h-4 w-full animate-pulse rounded bg-bg" />
            </td>
          </tr>
        </tbody>

        <tbody v-else-if="questions.length" class="divide-y divide-border">
          <tr v-for="examQuestion in questions" :key="examQuestion.pivot?.id || examQuestion.id" class="transition-colors hover:bg-text/[0.02]">
            <td class="px-6 py-4">
              <div class="max-w-xl">
                <p class="text-sm font-medium leading-6 text-text">
                  {{ getContent(examQuestion) }}
                </p>
                <p v-if="getChapter(examQuestion)" class="mt-1 text-xs text-text/45">Chapter: {{ getChapter(examQuestion) }}</p>
              </div>
            </td>

            <td class="px-6 py-4">
              <span class="inline-flex rounded-md bg-bg border border-border px-2.5 py-1 font-mono text-xs font-medium uppercase text-text/70">
                {{ getType(examQuestion) }}
              </span>
            </td>

            <td class="px-6 py-4">
              <BaseBadge :variant="getDifficultyVariant(getDifficulty(examQuestion))">
                {{ getDifficulty(examQuestion) || '—' }}
              </BaseBadge>
            </td>

            <td class="px-6 py-4">
              <span class="font-mono text-sm tabular-nums font-semibold text-text">
                {{ getMarks(examQuestion) }}
              </span>
            </td>

            <td class="px-6 py-4 text-right">
              <button
                v-if="canRemove"
                type="button"
                class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-text/40 transition hover:bg-error/10 hover:text-error"
                title="Remove question"
                @click="emit('remove', examQuestion)">
                <Trash2 class="h-4 w-4" />
              </button>
              <span v-else class="text-xs text-text/30">—</span>
            </td>
          </tr>
        </tbody>

        <tbody v-else>
          <tr>
            <td colspan="5" class="px-6 py-14 text-center">
              <div class="mx-auto flex max-w-sm flex-col items-center">
                <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-bg">
                  <HelpCircle class="h-5 w-5 text-text/30" />
                </div>
                <p class="text-sm font-medium text-text">No questions added yet</p>
                <p class="mt-1 text-xs text-text/45">Add questions from the question bank or import them from CSV.</p>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
