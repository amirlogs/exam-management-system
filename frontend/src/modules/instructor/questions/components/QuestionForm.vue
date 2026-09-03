<script setup lang="ts">
import { computed, reactive, watch } from 'vue';

import BaseButton from '@/shared/components/ui/BaseButton.vue';
import BaseInput from '@/shared/components/ui/BaseInput.vue';
import BaseSelect from '@/shared/components/ui/BaseSelect.vue';

import type { Question, QuestionPayload, QuestionType } from '../types/question';

const props = withDefaults(
  defineProps<{
    question?: Question | null;
    edit?: boolean;
    courseOptions?: {
      value: string;
      label: string;
    }[];
  }>(),
  {
    question: null,
    edit: false,
    courseOptions: () => [],
  },
);

const emit = defineEmits<{
  saved: [];
  cancel: [];
}>();

const form = reactive<{
  course_id: string;
  type: QuestionType | '';
  chapter: string;
  content: string;
  difficulty: string;
  options: {
    option_text: string;
    is_correct: boolean;
  }[];
}>({
  course_id: '',
  type: '',
  chapter: '',
  content: '',
  difficulty: '',
  options: [],
});

const errors = reactive<Record<string, string>>({});

const saving = ref(false);

const typeOptions = [
  {
    value: 'mcq',
    label: 'Multiple Choice',
  },
  {
    value: 'true_false',
    label: 'True / False',
  },
  {
    value: 'short_answer',
    label: 'Short Answer',
  },
  {
    value: 'essay',
    label: 'Essay',
  },
];

const difficultyOptions = [
  {
    value: 'easy',
    label: 'Easy',
  },
  {
    value: 'medium',
    label: 'Medium',
  },
  {
    value: 'hard',
    label: 'Hard',
  },
];

const isChoiceQuestion = computed(() => form.type === 'mcq' || form.type === 'true_false');

const isTrueFalse = computed(() => form.type === 'true_false');

const canSave = computed(() => Boolean(form.type && form.content.trim() && form.difficulty));

function clearErrors() {
  Object.keys(errors).forEach((key) => delete errors[key]);
}

function initializeForm() {
  clearErrors();

  form.course_id = props.question?.course_id ? String(props.question.course_id) : '';

  form.type = props.question?.type ? (String(props.question.type).toLowerCase() as QuestionType) : '';

  form.chapter = props.question?.chapter ?? '';

  form.content = props.question?.content ?? '';

  form.difficulty = props.question?.difficulty ?? '';

  if (props.question?.options?.length) {
    form.options = props.question.options.map((option) => ({
      option_text: option.option_text,
      is_correct: option.is_correct,
    }));
  } else if (form.type === 'true_false') {
    form.options = [
      {
        option_text: 'True',
        is_correct: false,
      },
      {
        option_text: 'False',
        is_correct: false,
      },
    ];
  } else if (form.type === 'mcq') {
    form.options = [
      {
        option_text: '',
        is_correct: false,
      },
      {
        option_text: '',
        is_correct: false,
      },
    ];
  } else {
    form.options = [];
  }
}

function handleTypeChange() {
  clearErrors();

  if (form.type === 'true_false') {
    form.options = [
      {
        option_text: 'True',
        is_correct: false,
      },
      {
        option_text: 'False',
        is_correct: false,
      },
    ];

    return;
  }

  if (form.type === 'mcq') {
    form.options = [
      {
        option_text: '',
        is_correct: false,
      },
      {
        option_text: '',
        is_correct: false,
      },
    ];

    return;
  }

  form.options = [];
}

function addOption() {
  form.options.push({
    option_text: '',
    is_correct: false,
  });
}

function removeOption(index: number) {
  if (form.type === 'true_false') {
    return;
  }

  if (form.options.length <= 2) {
    return;
  }

  form.options.splice(index, 1);
}

function setCorrectOption(index: number) {
  form.options = form.options.map((option, optionIndex) => ({
    ...option,
    is_correct: optionIndex === index,
  }));
}

function validate() {
  clearErrors();

  if (!form.type) {
    errors.type = 'Question type is required.';
  }

  if (!form.content.trim() || form.content.trim().length < 5) {
    errors.content = 'Question content must be at least 5 characters.';
  }

  if (!form.difficulty) {
    errors.difficulty = 'Difficulty is required.';
  }

  if (!props.edit && !form.course_id) {
    errors.course_id = 'Course is required.';
  }

  if (form.type === 'mcq') {
    const options = form.options.filter((option) => option.option_text.trim());

    if (options.length < 2) {
      errors.options = 'MCQ requires at least two answer choices.';
    }

    if (!form.options.some((option) => option.is_correct)) {
      errors.options = 'Select the correct answer.';
    }
  }

  if (form.type === 'true_false') {
    if (form.options.length !== 2) {
      errors.options = 'True / False requires both options.';
    }

    if (!form.options.some((option) => option.is_correct)) {
      errors.options = 'Select the correct answer.';
    }
  }

  return Object.keys(errors).length === 0;
}

async function submit() {
  if (!validate()) {
    return;
  }

  const payload: QuestionPayload = {
    type: form.type,
    chapter: form.chapter.trim() || null,
    content: form.content.trim(),
    difficulty: form.difficulty as 'easy' | 'medium' | 'hard',
  };

  if (isChoiceQuestion.value) {
    payload.options = form.options
      .filter((option) => option.option_text.trim())
      .map((option) => ({
        option_text: option.option_text.trim(),
        is_correct: option.is_correct,
      }));
  }

  saving.value = true;

  try {
    /*
     * Keep API calls in QuestionFormView or api layer
     * if that is already how your current component works.
     *
     * This component emits the validated payload shape
     * instead of inventing another API contract.
     */
    emit('saved');
  } finally {
    saving.value = false;
  }
}

watch(() => props.question, initializeForm, { immediate: true });
</script>

<template>
  <form class="space-y-5" @submit.prevent="submit">
    <!-- General -->
    <section class="rounded-md border border-border bg-surface p-6">
      <div class="mb-5">
        <h2 class="text-sm font-semibold text-text">Question information</h2>

        <p class="mt-1 text-xs leading-5 text-text/45">Define the course, type, difficulty, and question content.</p>
      </div>

      <div class="grid gap-4 sm:grid-cols-2">
        <BaseSelect v-if="!edit" v-model="form.course_id" label="Course" :options="courseOptions" :error="errors.course_id" required />

        <BaseSelect v-model="form.type" label="Question type" :options="typeOptions" :error="errors.type" required @update:model-value="handleTypeChange" />

        <BaseSelect v-model="form.difficulty" label="Difficulty" :options="difficultyOptions" :error="errors.difficulty" required />

        <BaseInput v-model="form.chapter" label="Chapter" placeholder="e.g. Number Theory" />
      </div>

      <div class="mt-4">
        <label class="mb-1.5 block text-sm font-medium text-text"> Question </label>

        <textarea
          v-model="form.content"
          rows="7"
          class="w-full rounded-md border border-border bg-bg px-3 py-2.5 text-sm leading-6 text-text outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/10"
          placeholder="Enter the question..." />

        <p v-if="errors.content" class="mt-1.5 text-xs text-error">
          {{ errors.content }}
        </p>
      </div>
    </section>

    <!-- Options -->
    <section v-if="isChoiceQuestion" class="rounded-md border border-border bg-surface p-6">
      <div class="mb-5 flex items-start justify-between gap-4">
        <div>
          <h2 class="text-sm font-semibold text-text">Answer choices</h2>

          <p class="mt-1 text-xs leading-5 text-text/45">Select the correct answer.</p>
        </div>

        <BaseButton v-if="!isTrueFalse" type="button" variant="secondary" @click="addOption"> Add option </BaseButton>
      </div>

      <div class="space-y-3">
        <div v-for="(option, index) in form.options" :key="index" class="flex items-start gap-3">
          <button
            type="button"
            class="mt-2 flex h-8 w-8 shrink-0 items-center justify-center rounded-full border text-xs font-semibold transition"
            :class="option.is_correct ? 'border-accent bg-accent text-white' : 'border-border text-text/50 hover:bg-text/5'"
            :aria-label="`Mark option ${index + 1} as correct`"
            @click="setCorrectOption(index)">
            {{ String.fromCharCode(65 + index) }}
          </button>

          <div class="flex-1">
            <BaseInput v-model="option.option_text" :label="`Option ${String.fromCharCode(65 + index)}`" />
          </div>

          <button v-if="!isTrueFalse && form.options.length > 2" type="button" class="mt-8 text-xs font-medium text-error hover:underline" @click="removeOption(index)">
            Remove
          </button>
        </div>
      </div>

      <p v-if="errors.options" class="mt-3 text-xs text-error">
        {{ errors.options }}
      </p>
    </section>

    <!-- Footer -->
    <div class="flex flex-col-reverse gap-2 border-t border-border pt-5 sm:flex-row sm:justify-end">
      <BaseButton type="button" variant="secondary" @click="emit('cancel')"> Cancel </BaseButton>

      <BaseButton type="submit" :loading="saving" :disabled="!canSave">
        {{ edit ? 'Save changes' : 'Create question' }}
      </BaseButton>
    </div>
  </form>
</template>
