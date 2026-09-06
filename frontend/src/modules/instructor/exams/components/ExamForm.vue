<script setup lang="ts">
import { computed, ref } from 'vue';
import BaseButton from '@/shared/components/ui/BaseButton.vue';
import BaseInput from '@/shared/components/ui/BaseInput.vue';
import BaseSelect from '@/shared/components/ui/BaseSelect.vue';
import DurationSelector from './DurationSelector.vue';

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

  return mcqEnabled.value || trueFalseEnabled.value;
});

function submit() {
  if (!canSubmit.value) {
    return;
  }

  const composition: ExamComposition = {};

  if (mcqEnabled.value) {
    const marks = Number(mcqMarks.value);
    composition.mcq = { marks_each: marks };
    composition.MCQ = { marks_each: marks };
  }

  if (trueFalseEnabled.value) {
    const marks = Number(trueFalseMarks.value);
    composition.true_false = { marks_each: marks };
    composition.TRUE_FALSE = { marks_each: marks };
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
  <form class="space-y-6" @submit.prevent="submit">
    <div class="rounded-xl border border-border bg-surface p-6 shadow-sm">
      <div class="mb-5">
        <h2 class="text-base font-semibold text-text">Exam Details</h2>
        <p class="mt-0.5 text-sm text-text/50">Set the basic information for this exam paper.</p>
      </div>

      <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
        <div class="md:col-span-2">
          <BaseInput v-model="title" label="Exam title" placeholder="e.g. Midterm Examination — Spring 2026" />
        </div>

        <div class="md:col-span-2">
          <BaseSelect v-model="type" label="Exam type" :options="typeOptions" />
        </div>

        <div class="md:col-span-2 pt-1">
          <DurationSelector v-model="duration" :min="30" />
        </div>
      </div>
    </div>

    <div class="rounded-xl border border-border bg-surface p-6 shadow-sm">
      <div class="mb-5">
        <h2 class="text-base font-semibold text-text">Question Composition</h2>
        <p class="mt-0.5 text-sm text-text/50">Select the question types allowed in this exam and configure marks for auto-graded types.</p>
      </div>

      <div class="space-y-3">
        <label class="flex items-center justify-between rounded-xl border border-border bg-bg/50 p-4 transition hover:bg-bg">
          <div class="flex items-center gap-3">
            <input v-model="mcqEnabled" type="checkbox" class="h-4 w-4 rounded accent-accent" />
            <div>
              <p class="text-sm font-medium text-text">Multiple Choice (MCQ)</p>
              <p class="text-xs text-text/50">Marks each question receives upon correct answer.</p>
            </div>
          </div>
          <BaseInput v-if="mcqEnabled" v-model="mcqMarks" class="w-28" type="number" min="1" />
        </label>

        <label class="flex items-center justify-between rounded-xl border border-border bg-bg/50 p-4 transition hover:bg-bg">
          <div class="flex items-center gap-3">
            <input v-model="trueFalseEnabled" type="checkbox" class="h-4 w-4 rounded accent-accent" />
            <div>
              <p class="text-sm font-medium text-text">True / False</p>
              <p class="text-xs text-text/50">Marks each question receives upon correct answer.</p>
            </div>
          </div>
          <BaseInput v-if="trueFalseEnabled" v-model="trueFalseMarks" class="w-28" type="number" min="1" />
        </label>
      </div>
    </div>

    <div class="flex justify-end gap-3 pt-2">
      <BaseButton type="button" variant="secondary" @click="emit('cancel')"> Cancel </BaseButton>
      <BaseButton type="submit" :loading="props.loading" :disabled="!canSubmit"> Create exam </BaseButton>
    </div>
  </form>
</template>
