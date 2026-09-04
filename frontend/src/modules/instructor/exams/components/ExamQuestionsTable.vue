<script setup lang="ts">
import { MoreVertical, Trash2 } from 'lucide-vue-next';

import ExamStatusBadge from './ExamStatusBadge.vue';

import type { ExamQuestion } from '../types/exam';

defineProps<{
  questions: ExamQuestion[];
  loading?: boolean;
  canRemove: boolean;
}>();

const emit = defineEmits<{
  remove: [ExamQuestion];
}>();
</script>

<template>
  <div class="overflow-hidden rounded-md border border-border bg-surface">
    <div class="overflow-x-auto">
      <table class="w-full min-w-[760px]">
        <thead>
          <tr class="border-b border-border bg-bg text-left">
            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-text/50">Question</th>

            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-text/50">Type</th>

            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-text/50">Difficulty</th>

            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-text/50">Marks</th>

            <th class="w-14 px-4 py-3" />
          </tr>
        </thead>

        <tbody v-if="loading" class="divide-y divide-border">
          <tr v-for="item in 5" :key="item">
            <td class="px-4 py-4">
              <div class="h-4 w-72 animate-pulse rounded bg-text/5" />
            </td>

            <td class="px-4 py-4">
              <div class="h-4 w-20 animate-pulse rounded bg-text/5" />
            </td>

            <td class="px-4 py-4">
              <div class="h-4 w-16 animate-pulse rounded bg-text/5" />
            </td>

            <td class="px-4 py-4">
              <div class="h-4 w-10 animate-pulse rounded bg-text/5" />
            </td>

            <td />
          </tr>
        </tbody>

        <tbody v-else-if="questions.length" class="divide-y divide-border">
          <tr v-for="examQuestion in questions" :key="examQuestion.id" class="transition-colors hover:bg-text/2">
            <td class="px-4 py-4">
              <div class="max-w-xl">
                <p class="text-sm font-medium leading-6 text-text">
                  {{ examQuestion.question.content }}
                </p>

                <p v-if="examQuestion.question.chapter" class="mt-1 text-xs text-text/45">
                  {{ examQuestion.question.chapter }}
                </p>
              </div>
            </td>

            <td class="px-4 py-4">
              <span class="font-mono text-xs uppercase text-text/60">
                {{ examQuestion.question.type }}
              </span>
            </td>

            <td class="px-4 py-4">
              <span class="text-sm text-text/60">
                {{ examQuestion.question.difficulty || '—' }}
              </span>
            </td>

            <td class="px-4 py-4">
              <span class="font-mono text-sm tabular-nums text-text">
                {{ examQuestion.marks }}
              </span>
            </td>

            <td class="px-4 py-4 text-right">
              <button
                v-if="canRemove"
                type="button"
                class="inline-flex h-8 w-8 items-center justify-center rounded-md text-text/45 transition hover:bg-error/5 hover:text-error"
                :aria-label="`Remove question ${examQuestion.id}`"
                @click="emit('remove', examQuestion)">
                <Trash2 class="h-4 w-4" />
              </button>

              <MoreVertical v-else class="ml-auto h-4 w-4 text-text/25" />
            </td>
          </tr>
        </tbody>

        <tbody v-else>
          <tr>
            <td colspan="5" class="px-6 py-14 text-center">
              <p class="text-sm font-medium text-text">No questions added yet</p>

              <p class="mt-1 text-xs text-text/45">Add questions from the question bank or import them from CSV.</p>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
