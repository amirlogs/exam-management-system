<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Plus, Trash2 } from 'lucide-vue-next';

import BaseButton from '@/shared/components/ui/BaseButton.vue';
import type { ExamComposition } from '../types/exam';

const props = defineProps<{
  composition: ExamComposition;
  loading?: boolean;
  disabled?: boolean;
}>();

const emit = defineEmits<{
  save: [ExamComposition];
}>();

const localComposition = ref<ExamComposition>({});

const allTypes = [
  { key: 'MCQ', label: 'Multiple Choice (MCQ)' },
  { key: 'TRUE_FALSE', label: 'True / False' },
  { key: 'SHORT_ANSWER', label: 'Short Answer' },
  { key: 'ESSAY', label: 'Essay' },
];

function formatTypeName(typeStr: string) {
  const map: Record<string, string> = {
    MCQ: 'Multiple Choice (MCQ)',
    TRUE_FALSE: 'True / False',
    SHORT_ANSWER: 'Short Answer',
    ESSAY: 'Essay',
  };
  return map[typeStr.toUpperCase()] || typeStr.replaceAll('_', ' ');
}

watch(
  () => props.composition,
  (value) => {
    const raw = value ?? {};
    const normalized: ExamComposition = {};
    for (const [k, v] of Object.entries(raw)) {
      const upper = k.toUpperCase();
      if (!normalized[upper]) {
        normalized[upper] = { ...v };
      }
    }
    localComposition.value = normalized;
  },
  {
    immediate: true,
    deep: true,
  },
);

const entries = computed(() => Object.entries(localComposition.value));

const availableToAddTypes = computed(() => {
  return allTypes.filter((t) => !localComposition.value[t.key]);
});

function addType(type: string) {
  const upper = type.toUpperCase();
  if (localComposition.value[upper]) {
    return;
  }

  if (upper === 'MCQ' || upper === 'TRUE_FALSE') {
    localComposition.value[upper] = {
      marks_each: 1,
    };
  } else {
    localComposition.value[upper] = {};
  }
}

function removeType(type: string) {
  delete localComposition.value[type.toUpperCase()];
}

function save() {
  const payload: ExamComposition = {};
  for (const [key, val] of Object.entries(localComposition.value)) {
    const upper = key.toUpperCase();
    const lower = key.toLowerCase();
    const conf =
      upper === 'MCQ' || upper === 'TRUE_FALSE'
        ? { marks_each: val.marks_each ? Number(val.marks_each) : 1 }
        : {};
    payload[upper] = conf;
    payload[lower] = conf;
  }

  emit('save', payload);
}
</script>

<template>
  <div class="rounded-xl border border-border bg-surface p-6 shadow-sm">
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
      <div>
        <h2 class="text-base font-semibold text-text">Question Composition</h2>
        <p class="mt-0.5 text-xs text-text/50">
          Configure allowed question types and marks per question for this exam.
        </p>
      </div>

      <BaseButton :disabled="disabled" :loading="loading" @click="save">
        Save composition
      </BaseButton>
    </div>

    <div class="space-y-3">
      <div
        v-for="[type, config] in entries"
        :key="type"
        class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-border bg-bg/50 p-4 transition-colors">
        <div class="flex items-center gap-3">
          <span class="inline-flex rounded-md bg-accent/10 px-2 py-0.5 font-mono text-[11px] font-bold uppercase text-accent">
            {{ type }}
          </span>
          <div>
            <p class="text-sm font-semibold text-text">
              {{ formatTypeName(type) }}
            </p>
            <p class="text-xs text-text/50">
              {{ type === 'MCQ' || type === 'TRUE_FALSE' ? 'Auto-graded question type' : 'Manually graded question type' }}
            </p>
          </div>
        </div>

        <div class="flex items-center gap-4">
          <div v-if="type === 'MCQ' || type === 'TRUE_FALSE'" class="flex items-center gap-2">
            <span class="text-xs font-medium text-text/55">Marks each:</span>
            <input
              v-model.number="config.marks_each"
              type="number"
              min="0.5"
              step="0.5"
              :disabled="disabled"
              class="h-8 w-20 rounded-lg border border-border bg-surface px-2.5 text-center font-mono text-xs font-semibold text-text outline-none focus:border-accent disabled:opacity-50" />
          </div>
          <span v-else class="text-xs text-text/45">Custom marks allocated per question</span>

          <button
            type="button"
            :disabled="disabled"
            class="rounded-lg p-1.5 text-text/40 transition hover:bg-error/10 hover:text-error disabled:opacity-40"
            title="Remove question type"
            @click="removeType(type)">
            <Trash2 class="h-4 w-4" />
          </button>
        </div>
      </div>

      <div v-if="!entries.length" class="rounded-xl border border-dashed border-border p-8 text-center text-xs text-text/45">
        No question types configured. Add a type below to allow questions in this exam.
      </div>
    </div>

    <!-- Add Question Type Bar -->
    <div class="mt-5 flex flex-wrap items-center gap-2 border-t border-border pt-5">
      <span class="mr-1 text-xs font-medium text-text/50">Add question type:</span>
      <template v-if="availableToAddTypes.length">
        <button
          v-for="t in availableToAddTypes"
          :key="t.key"
          type="button"
          :disabled="disabled"
          class="inline-flex items-center gap-1.5 rounded-lg border border-border bg-bg px-3 py-1.5 text-xs font-medium text-text/70 transition-colors hover:border-accent/40 hover:text-accent disabled:opacity-40"
          @click="addType(t.key)">
          <Plus class="h-3.5 w-3.5" />
          {{ t.label }}
        </button>
      </template>
      <span v-else class="text-xs text-text/40">All question types are configured.</span>
    </div>
  </div>
</template>
