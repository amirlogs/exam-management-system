<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { Check, CheckSquare, Layers, Search, Square, X } from 'lucide-vue-next';

import BaseButton from '@/shared/components/ui/BaseButton.vue';
import BaseInput from '@/shared/components/ui/BaseInput.vue';
import BaseSelect from '@/shared/components/ui/BaseSelect.vue';
import BaseBadge from '@/shared/components/ui/BaseBadge.vue';
import type { Exam } from '../types/exam';
import { listQuestions } from '@/modules/instructor/questions/api/questions';

const props = defineProps<{
  exam: Exam;
  existingQuestionIds: number[];
  loading?: boolean;
  errorMessage?: string | null;
}>();

const emit = defineEmits<{
  'add-bulk': [
    {
      questions: {
        question_id: number;
        marks?: number;
      }[];
    },
  ];
  close: [];
}>();

const questions = ref<any[]>([]);
const search = ref('');
const type = ref('');
const difficulty = ref('');
const loading = ref(false);

// Map of question_id -> { selected: boolean, marks: number }
const selectedMap = ref<Record<number, { marks: number }>>({});

const typeOptions = [
  { value: '', label: 'All types' },
  { value: 'MCQ', label: 'MCQ' },
  { value: 'TRUE_FALSE', label: 'True / False' },
];

const difficultyOptions = [
  { value: '', label: 'All difficulty' },
  { value: 'easy', label: 'Easy' },
  { value: 'medium', label: 'Medium' },
  { value: 'hard', label: 'Hard' },
];

function getDifficultyVariant(diff: string | null | undefined): 'neutral' | 'info' | 'danger' | 'warning' | 'success' | 'dark' {
  if (!diff) return 'neutral';
  const val = diff.toLowerCase();
  if (val === 'easy') return 'success';
  if (val === 'medium') return 'warning';
  if (val === 'hard') return 'danger';
  return 'neutral';
}

function getCompositionMarkForType(typeStr: string) {
  const normalized = String(typeStr).toLowerCase();
  const upper = String(typeStr).toUpperCase();
  const comp = props.exam.composition?.[normalized] || props.exam.composition?.[upper];
  if (comp && comp.marks_each && Number(comp.marks_each) > 0) {
    return Number(comp.marks_each);
  }
  return 1;
}

const existingIdsSet = computed(() => {
  return new Set((props.existingQuestionIds || []).map(Number));
});

function isAlreadyAdded(questionId: number) {
  return existingIdsSet.value.has(Number(questionId));
}

const filteredQuestions = computed(() => {
  const query = search.value.trim().toLowerCase();

  return questions.value.filter((question) => {
    if (query && !question.content?.toLowerCase().includes(query) && !question.chapter?.toLowerCase().includes(query)) {
      return false;
    }

    if (type.value && String(question.type).toUpperCase() !== type.value.toUpperCase()) {
      return false;
    }

    if (difficulty.value && String(question.difficulty).toLowerCase() !== difficulty.value.toLowerCase()) {
      return false;
    }

    return true;
  });
});

const selectableFilteredQuestions = computed(() => {
  return filteredQuestions.value.filter((q) => !isAlreadyAdded(q.id));
});

const selectedQuestionList = computed(() => {
  const ids = Object.keys(selectedMap.value).map(Number);
  return questions.value.filter((q) => ids.includes(q.id) && !isAlreadyAdded(q.id));
});

const selectedCount = computed(() => selectedQuestionList.value.length);

const isAllFilteredSelected = computed(() => {
  if (!selectableFilteredQuestions.value.length) return false;
  return selectableFilteredQuestions.value.every((q) => !!selectedMap.value[q.id]);
});

function toggleSelectAll() {
  if (isAllFilteredSelected.value) {
    for (const q of selectableFilteredQuestions.value) {
      delete selectedMap.value[q.id];
    }
  } else {
    for (const q of selectableFilteredQuestions.value) {
      if (!selectedMap.value[q.id]) {
        selectedMap.value[q.id] = {
          marks: getCompositionMarkForType(q.type),
        };
      }
    }
  }
}

function toggleQuestion(question: any) {
  if (isAlreadyAdded(question.id)) return;

  if (selectedMap.value[question.id]) {
    delete selectedMap.value[question.id];
  } else {
    selectedMap.value[question.id] = {
      marks: getCompositionMarkForType(question.type),
    };
  }
}

function isSelected(id: number) {
  return !isAlreadyAdded(id) && !!selectedMap.value[id];
}

const totalCalculatedMarks = computed(() => {
  let sum = 0;
  for (const q of selectedQuestionList.value) {
    const item = selectedMap.value[q.id];
    const mark = item?.marks ? Number(item.marks) : getCompositionMarkForType(q.type);
    sum += mark;
  }
  return sum;
});

async function loadQuestions() {
  loading.value = true;
  try {
    const response = await listQuestions(1, 200);
    questions.value = response.data ?? [];
  } finally {
    loading.value = false;
  }
}

function submitBulk() {
  const validQuestions = selectedQuestionList.value.filter((q) => !isAlreadyAdded(q.id));
  if (!validQuestions.length) return;

  const payloadQuestions = validQuestions.map((q) => {
    const item = selectedMap.value[q.id];
    const finalMark = item?.marks && Number(item.marks) > 0 ? Number(item.marks) : getCompositionMarkForType(q.type);
    return {
      question_id: Number(q.id),
      marks: finalMark,
    };
  });

  emit('add-bulk', {
    questions: payloadQuestions,
  });
}

onMounted(loadQuestions);
</script>

<template>
  <div class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 backdrop-blur-xs p-4">
    <div class="flex max-h-[90vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl border border-border bg-surface shadow-2xl">
      <!-- Modal Header -->
      <div class="flex items-start justify-between gap-4 border-b border-border px-6 py-5">
        <div>
          <div class="flex items-center gap-2">
            <h2 class="font-display text-lg font-bold text-text">Add Questions to Exam</h2>
            <span class="rounded-full bg-accent/10 px-2.5 py-0.5 text-xs font-semibold text-accent"> Bulk Selection </span>
          </div>
          <p class="mt-0.5 text-xs text-text/50">Select one or multiple questions from your question bank to attach to {{ exam.title }}.</p>
        </div>

        <button type="button" class="rounded-lg p-2 text-text/45 transition hover:bg-bg hover:text-text" @click="emit('close')">
          <X class="h-4 w-4" />
        </button>
      </div>

      <!-- Error banner if server returned an error -->
      <div v-if="props.errorMessage" class="flex items-center gap-2 border-b border-error/20 bg-error/10 px-6 py-3 text-xs font-medium text-error">
        <span>{{ props.errorMessage }}</span>
      </div>

      <!-- Modal Body (2-pane) -->
      <div class="grid min-h-0 flex-1 grid-cols-1 md:grid-cols-[1fr_360px]">
        <!-- Left Pane: Searchable Question List -->
        <div class="flex min-h-0 flex-col border-b border-border md:border-b-0 md:border-r">
          <!-- Filter Toolbar -->
          <div class="border-b border-border bg-surface p-4">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-[1fr_140px_140px]">
              <div class="relative">
                <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-text/30" />
                <input
                  v-model="search"
                  type="text"
                  placeholder="Search questions…"
                  class="w-full rounded-lg border border-border bg-bg py-2 pl-9 pr-3 text-sm text-text outline-none focus:border-accent" />
              </div>

              <BaseSelect v-model="type" :options="typeOptions" placeholder="All types" />
              <BaseSelect v-model="difficulty" :options="difficultyOptions" placeholder="All difficulty" />
            </div>

            <!-- Select all bar -->
            <div class="mt-3 flex items-center justify-between border-t border-border/60 pt-2.5 text-xs">
              <button type="button" class="inline-flex items-center gap-1.5 font-medium text-accent hover:underline" @click="toggleSelectAll">
                <CheckSquare v-if="isAllFilteredSelected" class="h-3.5 w-3.5" />
                <Square v-else class="h-3.5 w-3.5" />
                <span>{{ isAllFilteredSelected ? 'Deselect all filtered' : 'Select all filtered' }}</span>
              </button>

              <span class="text-text/45"> Showing {{ filteredQuestions.length }} ({{ selectableFilteredQuestions.length }} available to add) </span>
            </div>
          </div>

          <!-- Question Items Scrollable List -->
          <div class="min-h-0 flex-1 overflow-y-auto divide-y divide-border">
            <div
              v-for="question in filteredQuestions"
              :key="question.id"
              class="flex items-start gap-3 p-4 transition-colors"
              :class="[
                isAlreadyAdded(question.id) ? 'opacity-60 cursor-not-allowed bg-text/[0.02]' : 'cursor-pointer hover:bg-text/[0.02]',
                isSelected(question.id) ? 'bg-accent/5' : '',
              ]"
              @click="toggleQuestion(question)">
              <div class="mt-1 flex shrink-0 items-center justify-center">
                <input
                  type="checkbox"
                  class="h-4 w-4 rounded accent-accent pointer-events-none"
                  :disabled="isAlreadyAdded(question.id)"
                  :checked="isAlreadyAdded(question.id) || isSelected(question.id)" />
              </div>

              <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                  <span class="font-mono text-xs font-semibold text-text/50">#{{ question.id }}</span>
                  <span class="inline-flex rounded bg-bg border border-border px-2 py-0.5 font-mono text-[10px] uppercase font-medium text-text/60">
                    {{ question.type }}
                  </span>
                  <BaseBadge :variant="getDifficultyVariant(question.difficulty)">
                    {{ question.difficulty || '—' }}
                  </BaseBadge>
                  <span v-if="isAlreadyAdded(question.id)" class="inline-flex items-center rounded-md bg-accent/10 px-2 py-0.5 text-[10px] font-semibold text-accent">
                    Already in Exam
                  </span>
                </div>

                <p class="mt-1.5 text-sm leading-relaxed text-text">
                  {{ question.content }}
                </p>
              </div>
            </div>

            <div v-if="!loading && !filteredQuestions.length" class="px-6 py-14 text-center">
              <p class="text-sm font-medium text-text">No matching questions found</p>
              <p class="mt-1 text-xs text-text/45">Try adjusting your search query or filters.</p>
            </div>

            <div v-if="loading" class="px-6 py-14 text-center text-sm text-text/45">Loading questions…</div>
          </div>
        </div>

        <!-- Right Pane: Selection Summary & Marks Config -->
        <div class="flex flex-col bg-bg/20">
          <div class="border-b border-border bg-surface px-5 py-4">
            <div class="flex items-center justify-between">
              <h3 class="text-xs font-bold uppercase tracking-wider text-text/45">Selected Questions ({{ selectedCount }})</h3>
              <span class="font-mono text-xs font-semibold text-accent"> {{ totalCalculatedMarks }} Total Marks </span>
            </div>
          </div>

          <div class="min-h-0 flex-1 overflow-y-auto p-4 space-y-3">
            <template v-if="selectedCount > 0">
              <div v-for="q in selectedQuestionList" :key="q.id" class="rounded-xl border border-border bg-surface p-3 shadow-xs">
                <div class="flex items-start justify-between gap-2">
                  <p class="line-clamp-2 text-xs font-medium text-text">
                    {{ q.content }}
                  </p>
                  <button type="button" class="shrink-0 text-text/40 hover:text-error" title="Remove" @click.stop="toggleQuestion(q)">
                    <X class="h-3.5 w-3.5" />
                  </button>
                </div>

                <div class="mt-2.5 flex items-center justify-between gap-2">
                  <span class="inline-flex rounded bg-bg border border-border px-1.5 py-0.5 font-mono text-[10px] uppercase text-text/60">
                    {{ q.type }}
                  </span>

                  <!-- Marks allocation input -->
                  <div class="flex items-center gap-1.5">
                    <span class="text-[11px] text-text/50">Marks:</span>
                    <input
                      v-model.number="selectedMap[q.id].marks"
                      type="number"
                      min="0.5"
                      step="0.5"
                      class="h-7 w-16 rounded border border-border bg-bg px-2 text-center text-xs font-mono font-semibold text-text outline-none focus:border-accent" />
                  </div>
                </div>
              </div>
            </template>

            <div v-else class="flex flex-col items-center justify-center py-16 text-center text-text/45">
              <Layers class="mb-2 h-8 w-8 text-text/25" />
              <p class="text-xs font-medium">No questions selected</p>
              <p class="mt-0.5 text-[11px] text-text/40">Check questions on the left to add them to this exam in bulk.</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Persistent Modal Footer (Always visible) -->
      <div class="flex flex-wrap items-center justify-between gap-3 border-t border-border bg-surface px-6 py-4">
        <div class="flex items-center gap-3">
          <div class="flex items-center gap-2">
            <span class="text-xs font-semibold uppercase tracking-wider text-text/50">Selected:</span>
            <span class="rounded-md bg-accent/10 px-2.5 py-0.5 font-mono text-xs font-bold text-accent"> {{ selectedCount }} question{{ selectedCount === 1 ? '' : 's' }} </span>
          </div>
          <span class="text-text/30">•</span>
          <span class="font-mono text-xs font-semibold text-text/70"> {{ totalCalculatedMarks }} total marks </span>
        </div>

        <div class="flex items-center gap-2.5">
          <BaseButton variant="secondary" @click="emit('close')"> Cancel </BaseButton>

          <BaseButton v-can="'exam.update'" :disabled="selectedCount === 0" :loading="props.loading" @click="submitBulk">
            Add {{ selectedCount > 0 ? selectedCount : '' }} Question{{ selectedCount === 1 ? '' : 's' }}
          </BaseButton>
        </div>
      </div>
    </div>
  </div>
</template>
