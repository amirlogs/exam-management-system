<script setup lang="ts">
import { Search, X } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

import BaseButton from '@/shared/components/ui/BaseButton.vue';
import BaseInput from '@/shared/components/ui/BaseInput.vue';
import BaseSelect from '@/shared/components/ui/BaseSelect.vue';
import { listExamQuestions } from '../api/exams';
import type { Exam, ExamQuestion } from '../types/exam';
import { listQuestions } from '@/modules/instructor/questions/api/questions';

const props = defineProps<{
  exam: Exam;
  existingQuestionIds: number[];
  loading?: boolean;
}>();

const emit = defineEmits<{
  add: [
    {
      question_id: number;
      marks?: number;
    },
  ];
  close: [];
}>();

const questions = ref<any[]>([]);
const search = ref('');
const type = ref('');
const difficulty = ref('');
const loading = ref(false);

const selected = ref<any | null>(null);
const marks = ref<number | null>(null);

const typeOptions = [
  { value: '', label: 'All types' },
  { value: 'mcq', label: 'MCQ' },
  { value: 'true_false', label: 'True / False' },
  { value: 'short_answer', label: 'Short Answer' },
  { value: 'essay', label: 'Essay' },
];

const difficultyOptions = [
  { value: '', label: 'All difficulty' },
  { value: 'easy', label: 'Easy' },
  { value: 'medium', label: 'Medium' },
  { value: 'hard', label: 'Hard' },
];

const filteredQuestions = computed(() => {
  const query = search.value.trim().toLowerCase();

  return questions.value.filter((question) => {
    if (props.existingQuestionIds.includes(question.id)) {
      return false;
    }

    if (query && !question.content?.toLowerCase().includes(query) && !question.chapter?.toLowerCase().includes(query)) {
      return false;
    }

    if (type.value && question.type !== type.value) {
      return false;
    }

    if (difficulty.value && question.difficulty !== difficulty.value) {
      return false;
    }

    return true;
  });
});

async function loadQuestions() {
  loading.value = true;

  try {
    const response = await listQuestions(1, 100);

    questions.value = response.data ?? [];
  } finally {
    loading.value = false;
  }
}

function choose(question: any) {
  selected.value = question;

  const normalizedType = String(question.type).toLowerCase();

  if (normalizedType === 'mcq' || normalizedType === 'true_false') {
    marks.value = null;
  } else {
    marks.value = 1;
  }
}

function addSelected() {
  if (!selected.value) {
    return;
  }

  const normalizedType = String(selected.value.type).toLowerCase();

  const payload: {
    question_id: number;
    marks?: number;
  } = {
    question_id: selected.value.id,
  };

  if (normalizedType !== 'mcq' && normalizedType !== 'true_false') {
    if (!marks.value || marks.value < 1) {
      return;
    }

    payload.marks = Number(marks.value);
  }

  emit('add', payload);

  selected.value = null;
  marks.value = null;
}

onMounted(loadQuestions);
</script>

<template>
  <div class="fixed inset-0 z-[100] flex items-center justify-center bg-black/40 p-4">
    <div class="flex max-h-[90vh] w-full max-w-5xl flex-col overflow-hidden rounded-lg border border-border bg-surface shadow-2xl">
      <div class="flex items-start justify-between gap-4 border-b border-border px-6 py-5">
        <div>
          <h2 class="text-base font-semibold text-text">Add question</h2>

          <p class="mt-1 text-sm text-text/55">Select a question from your question bank.</p>
        </div>

        <button type="button" class="rounded-md p-2 text-text/45 hover:bg-text/5 hover:text-text" @click="emit('close')">
          <X class="h-4 w-4" />
        </button>
      </div>

      <div class="grid min-h-0 flex-1 grid-cols-1 md:grid-cols-[1fr_320px]">
        <div class="min-h-0 overflow-y-auto border-b border-border md:border-b-0 md:border-r">
          <div class="sticky top-0 z-10 border-b border-border bg-surface p-4">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-[1fr_170px_170px]">
              <div class="relative">
                <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-text/30" />

                <input
                  v-model="search"
                  type="text"
                  placeholder="Search questions…"
                  class="w-full rounded-md border border-border bg-bg py-2 pl-9 pr-3 text-sm text-text outline-none focus:border-accent" />
              </div>

              <BaseSelect v-model="type" :options="typeOptions" placeholder="Type" />

              <BaseSelect v-model="difficulty" :options="difficultyOptions" placeholder="Difficulty" />
            </div>
          </div>

          <div class="divide-y divide-border">
            <button
              v-for="question in filteredQuestions"
              :key="question.id"
              type="button"
              class="block w-full p-4 text-left transition hover:bg-text/3"
              :class="selected?.id === question.id ? 'bg-accent/5' : ''"
              @click="choose(question)">
              <div class="flex items-start gap-3">
                <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-text/5 text-[10px] font-semibold text-text/50">
                  {{ question.id }}
                </div>

                <div class="min-w-0 flex-1">
                  <p class="text-sm leading-6 text-text">
                    {{ question.content }}
                  </p>

                  <div class="mt-2 flex flex-wrap items-center gap-2 text-[11px]">
                    <span class="rounded bg-text/5 px-2 py-1 font-mono uppercase text-text/55">
                      {{ question.type }}
                    </span>

                    <span class="text-text/40">
                      {{ question.difficulty || '—' }}
                    </span>

                    <span v-if="question.chapter" class="text-text/40">
                      {{ question.chapter }}
                    </span>
                  </div>
                </div>
              </div>
            </button>

            <div v-if="!loading && !filteredQuestions.length" class="px-6 py-14 text-center">
              <p class="text-sm font-medium text-text">No questions available</p>

              <p class="mt-1 text-xs text-text/45">Try a different search or filter.</p>
            </div>

            <div v-if="loading" class="px-6 py-14 text-center text-sm text-text/45">Loading questions…</div>
          </div>
        </div>

        <div class="flex flex-col p-5">
          <div v-if="selected" class="flex-1">
            <p class="text-xs font-bold uppercase tracking-wide text-text/45">Selected question</p>

            <p class="mt-3 text-sm leading-6 text-text">
              {{ selected.content }}
            </p>

            <div class="mt-5 rounded-md border border-border bg-bg p-4">
              <p class="text-xs text-text/45">Type</p>

              <p class="mt-1 text-sm font-medium uppercase text-text">
                {{ selected.type }}
              </p>
            </div>

            <div v-if="selected.type !== 'mcq' && selected.type !== 'true_false'" class="mt-4">
              <BaseInput v-model="marks" type="number" min="1" label="Marks" />
            </div>
          </div>

          <div v-else class="flex flex-1 items-center justify-center text-center">
            <div>
              <p class="text-sm font-medium text-text">Select a question</p>

              <p class="mt-1 text-xs leading-5 text-text/45">Choose a question from the list to add it to this exam.</p>
            </div>
          </div>

          <div class="flex justify-end gap-2 border-t border-border pt-4">
            <BaseButton variant="secondary" @click="emit('close')"> Cancel </BaseButton>

            <BaseButton :disabled="!selected" @click="addSelected"> Add question </BaseButton>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
