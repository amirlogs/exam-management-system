<script setup lang="ts">
import { computed, ref } from 'vue';
import BaseButton from '@/shared/components/ui/BaseButton.vue';
import BaseInput from '@/shared/components/ui/BaseInput.vue';
import BaseSelect from '@/shared/components/ui/BaseSelect.vue';

import type { ExamComposition, ExamType } from '../types/exam';

const props = defineProps<{
  loading?: boolean;
}>();

const emit = defineEmits<{
  submit: [
    {
      title: string;
      type: ExamType;
      duration_minutes: number;
      composition: ExamComposition;
    },
  ];
  cancel: [];
}>();

const title = ref('');
const type = ref<ExamType>('MIDTERM');
const duration = ref<number>(90);

const mcqEnabled = ref(true);
const mcqMarks = ref(1);

const trueFalseEnabled = ref(true);
const trueFalseMarks = ref(2);

const essayEnabled = ref(false);
const shortAnswerEnabled = ref(false);

const typeOptions = [
  {
    value: 'MIDTERM',
    label: 'Midterm',
  },
  {
    value: 'FINAL',
    label: 'Final',
  },
];

const canSubmit = computed(() => {
  if (!title.value.trim()) {
    return false;
  }

  if (!duration.value || duration.value < 30) {
    return false;
  }

  if (mcqEnabled.value && mcqMarks.value < 1) {
    return false;
  }

  if (trueFalseEnabled.value && trueFalseMarks.value < 1) {
    return false;
  }

  return mcqEnabled.value || trueFalseEnabled.value || essayEnabled.value || shortAnswerEnabled.value;
});

function submit() {
  if (!canSubmit.value) {
    return;
  }

  const composition: ExamComposition = {};

  if (mcqEnabled.value) {
    composition.MCQ = {
      marks_each: Number(mcqMarks.value),
    };
  }

  if (trueFalseEnabled.value) {
    composition.TRUE_FALSE = {
      marks_each: Number(trueFalseMarks.value),
    };
  }

  if (shortAnswerEnabled.value) {
    composition.SHORT_ANSWER = {};
  }

  if (essayEnabled.value) {
    composition.ESSAY = {};
  }

  emit('submit', {
    title: title.value.trim(),
    type: type.value,
    duration_minutes: Number(duration.value),
    composition,
  });
}
</script>

<template>
  <form class="space-y-8" @submit.prevent="submit">
    <div class="rounded-md border border-border bg-surface p-6">
      <div class="mb-5">
        <h2 class="text-base font-semibold text-text">Exam details</h2>

        <p class="mt-1 text-sm text-text/55">Set the basic information for this exam.</p>
      </div>

      <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
        <div class="md:col-span-2">
          <BaseInput v-model="title" label="Exam title" placeholder="e.g. Discrete Mathematics final" />
        </div>

        <BaseSelect v-model="type" label="Exam type" :options="typeOptions" />

        <BaseInput v-model="duration" label="Duration (minutes)" type="number" min="30" />
      </div>
    </div>

    <div class="rounded-md border border-border bg-surface p-6">
      <div class="mb-5">
        <h2 class="text-base font-semibold text-text">Question composition</h2>

        <p class="mt-1 text-sm text-text/55">Select the question types that will be used in this exam.</p>
      </div>

      <div class="space-y-3">
        <label class="flex items-center justify-between rounded-md border border-border bg-bg p-4">
          <div class="flex items-center gap-3">
            <input v-model="mcqEnabled" type="checkbox" class="h-4 w-4 accent-accent" />

            <div>
              <p class="text-sm font-medium text-text">Multiple Choice</p>

              <p class="text-xs text-text/50">Configure marks for each MCQ.</p>
            </div>
          </div>

          <BaseInput v-if="mcqEnabled" v-model="mcqMarks" class="w-28" type="number" min="1" />
        </label>

        <label class="flex items-center justify-between rounded-md border border-border bg-bg p-4">
          <div class="flex items-center gap-3">
            <input v-model="trueFalseEnabled" type="checkbox" class="h-4 w-4 accent-accent" />

            <div>
              <p class="text-sm font-medium text-text">True / False</p>

              <p class="text-xs text-text/50">Configure marks for each question.</p>
            </div>
          </div>

          <BaseInput v-if="trueFalseEnabled" v-model="trueFalseMarks" class="w-28" type="number" min="1" />
        </label>

        <label class="flex items-center gap-3 rounded-md border border-border bg-bg p-4">
          <input v-model="shortAnswerEnabled" type="checkbox" class="h-4 w-4 accent-accent" />

          <div>
            <p class="text-sm font-medium text-text">Short Answer</p>

            <p class="text-xs text-text/50">Marks are supplied when questions are added.</p>
          </div>
        </label>

        <label class="flex items-center gap-3 rounded-md border border-border bg-bg p-4">
          <input v-model="essayEnabled" type="checkbox" class="h-4 w-4 accent-accent" />

          <div>
            <p class="text-sm font-medium text-text">Essay</p>

            <p class="text-xs text-text/50">Marks are supplied when questions are added.</p>
          </div>
        </label>
      </div>
    </div>

    <div class="flex justify-end gap-2 border-t border-border pt-6">
      <BaseButton type="button" variant="secondary" @click="emit('cancel')"> Cancel </BaseButton>

      <BaseButton type="submit" :loading="props.loading" :disabled="!canSubmit"> Create exam </BaseButton>
    </div>
  </form>
</template>
