<script setup lang="ts">
import { computed, ref, watch } from 'vue';

import BaseButton from '@/shared/components/ui/BaseButton.vue';
import BaseInput from '@/shared/components/ui/BaseInput.vue';

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

watch(
  () => props.composition,
  (value) => {
    localComposition.value = JSON.parse(JSON.stringify(value ?? {}));
  },
  {
    immediate: true,
    deep: true,
  },
);

const entries = computed(() => Object.entries(localComposition.value));

function addType(type: string) {
  if (localComposition.value[type]) {
    return;
  }

  localComposition.value[type] = {};
}

function removeType(type: string) {
  delete localComposition.value[type];
}

function save() {
  emit('save', JSON.parse(JSON.stringify(localComposition.value)));
}
</script>

<template>
  <div class="rounded-md border border-border bg-surface p-6">
    <div class="mb-5 flex items-start justify-between gap-4">
      <div>
        <h2 class="text-base font-semibold text-text">Composition</h2>

        <p class="mt-1 text-sm text-text/55">Change the exam's question-type configuration while it is still in draft.</p>
      </div>

      <BaseButton :disabled="disabled" :loading="loading" @click="save"> Save composition </BaseButton>
    </div>

    <div class="space-y-3">
      <div v-for="[type, config] in entries" :key="type" class="flex items-center gap-3 rounded-md border border-border bg-bg p-4">
        <div class="min-w-0 flex-1">
          <p class="text-sm font-medium text-text">
            {{ type.replaceAll('_', ' ') }}
          </p>

          <p class="mt-0.5 text-xs text-text/50">Question type</p>
        </div>

        <BaseInput v-if="type === 'MCQ' || type === 'TRUE_FALSE'" v-model="config.marks_each" type="number" min="1" class="w-28" />

        <span v-else class="text-xs text-text/50"> Marks per question </span>

        <button type="button" :disabled="disabled" class="rounded-md px-2 py-1 text-xs text-error hover:bg-error/5 disabled:opacity-40" @click="removeType(type)">Remove</button>
      </div>
    </div>

    <div class="mt-5 flex flex-wrap gap-2 border-t border-border pt-5">
      <button
        type="button"
        class="rounded-md border border-border px-3 py-2 text-xs font-medium text-text/70 hover:border-accent/40 hover:text-text"
        :disabled="disabled"
        @click="addType('MCQ')">
        + MCQ
      </button>

      <button
        type="button"
        class="rounded-md border border-border px-3 py-2 text-xs font-medium text-text/70 hover:border-accent/40 hover:text-text"
        :disabled="disabled"
        @click="addType('TRUE_FALSE')">
        + True / False
      </button>

      <button
        type="button"
        class="rounded-md border border-border px-3 py-2 text-xs font-medium text-text/70 hover:border-accent/40 hover:text-text"
        :disabled="disabled"
        @click="addType('SHORT_ANSWER')">
        + Short Answer
      </button>

      <button
        type="button"
        class="rounded-md border border-border px-3 py-2 text-xs font-medium text-text/70 hover:border-accent/40 hover:text-text"
        :disabled="disabled"
        @click="addType('ESSAY')">
        + Essay
      </button>
    </div>
  </div>
</template>
