<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Plus, Trash2, Check } from 'lucide-vue-next';

import BaseButton from '@/shared/components/ui/BaseButton.vue';
import BaseInput from '@/shared/components/ui/BaseInput.vue';
import BaseSelect from '@/shared/components/ui/BaseSelect.vue';

import type { Question, QuestionDifficulty, QuestionType } from '../types/question';

const props = withDefaults(
  defineProps<{
    question?: Question | null;
    courses?: {
      value: string;
      label: string;
    }[];
    saving?: boolean;
  }>(),
  {
    question: null,
    courses: () => [],
    saving: false,
  },
);

const emit = defineEmits<{
  submit: [
    {
      courseId: number;
      type: QuestionType;
      chapter: string;
      content: string;
      difficulty: QuestionDifficulty;
      options: string[];
      correctAnswer: string;
    },
  ];

  cancel: [];
}>();

const courseId = ref('');
const type = ref<QuestionType>('mcq');
const content = ref('');
const chapter = ref('');
const difficulty = ref<QuestionDifficulty>('easy');

const options = ref<string[]>(['', '']);

const correctAnswer = ref('');

const typeOptions = [
  {
    value: 'mcq',
    label: 'Multiple choice',
  },
  {
    value: 'true_false',
    label: 'True / False',
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

const isEditing = computed(() => !!props.question);

const requiresOptions = computed(() => {
  return type.value === 'mcq' || type.value === 'true_false';
});

const canSubmit = computed(() => {
  if (!content.value.trim()) {
    return false;
  }

  if (!difficulty.value) {
    return false;
  }

  if (!isEditing.value && !courseId.value) {
    return false;
  }

  if (!requiresOptions.value) {
    return true;
  }

  const validOptions = options.value.filter((option) => option.trim());

  if (validOptions.length < 2) {
    return false;
  }

  if (!correctAnswer.value.trim()) {
    return false;
  }

  return validOptions.includes(correctAnswer.value);
});

function loadQuestion(question: Question) {
  courseId.value = String(question.course_id);
  type.value = question.type;
  content.value = question.content;
  chapter.value = question.chapter || '';
  difficulty.value = question.difficulty;

  const questionOptions = question.options?.map((option) => option.option_text) ?? [];

  if (type.value === 'true_false') {
    options.value = ['True', 'False'];
  } else if (questionOptions.length) {
    options.value = questionOptions;
  } else {
    options.value = ['', ''];
  }

  const correct = question.options?.find((option) => option.is_correct);

  correctAnswer.value = correct?.option_text || '';
}

watch(
  () => props.question,
  (question) => {
    if (question) {
      loadQuestion(question);
    }
  },
  {
    immediate: true,
  },
);

watch(type, (value) => {
  if (value === 'true_false') {
    options.value = ['True', 'False'];

    if (correctAnswer.value !== 'True' && correctAnswer.value !== 'False') {
      correctAnswer.value = '';
    }

    return;
  }

  if (value === 'mcq') {
    if (options.value.length < 2) {
      options.value = ['', ''];
    }

    return;
  }

  options.value = [];
  correctAnswer.value = '';
});

function addOption() {
  options.value.push('');
}

function removeOption(index: number) {
  if (options.value.length <= 2) {
    return;
  }

  const removed = options.value.splice(index, 1)[0];

  if (removed === correctAnswer.value) {
    correctAnswer.value = '';
  }
}

function submit() {
  if (!canSubmit.value) {
    return;
  }

  emit('submit', {
    courseId: Number(courseId.value),
    type: type.value,
    chapter: chapter.value.trim(),
    content: content.value.trim(),
    difficulty: difficulty.value,

    options: requiresOptions.value ? options.value.map((option) => option.trim()).filter(Boolean) : [],

    correctAnswer: requiresOptions.value ? correctAnswer.value.trim() : '',
  });
}
</script>

<template>
  <div class="space-y-6 rounded-md border border-border bg-surface p-6">
    <div class="space-y-5">
      <div v-if="!isEditing">
        <BaseSelect v-model="courseId" label="Course" :options="courses" placeholder="Select course" />

        <p class="mt-1.5 text-xs text-text/45">You can only select courses from your teaching assignments.</p>
      </div>

      <div v-else class="rounded-md border border-border bg-bg p-4">
        <p class="text-xs font-bold uppercase tracking-wide text-text/45">Course</p>

        <p class="mt-1 text-sm font-semibold text-text">
          {{ question?.course?.name || '—' }}
        </p>

        <p class="mt-0.5 font-mono text-xs text-text/40">
          {{ question?.course?.code || '—' }}
        </p>
      </div>

      <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
        <BaseSelect v-model="type" label="Question type" :options="typeOptions" />

        <BaseSelect v-model="difficulty" label="Difficulty" :options="difficultyOptions" />
      </div>

      <BaseInput v-model="chapter" label="Chapter" placeholder="e.g. Database Fundamentals" />

      <div>
        <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-text/60"> Question </label>

        <textarea
          v-model="content"
          rows="5"
          placeholder="Enter the question..."
          class="w-full rounded-md border border-border bg-surface px-3 py-2.5 text-sm text-text outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/10" />
      </div>
    </div>

    <div v-if="requiresOptions" class="space-y-4 rounded-md border border-border bg-bg/40 p-5">
      <div class="flex items-center justify-between gap-4">
        <div>
          <p class="text-sm font-semibold text-text">
            {{ type === 'mcq' ? 'Answer choices' : 'Correct answer' }}
          </p>

          <p class="mt-0.5 text-xs text-text/45">Select the correct answer.</p>
        </div>

        <button v-if="type === 'mcq'" type="button" class="inline-flex items-center gap-1.5 text-xs font-semibold text-accent" @click="addOption">
          <Plus class="h-3.5 w-3.5" />
          Add choice
        </button>
      </div>

      <div class="space-y-2">
        <div v-for="(option, index) in options" :key="index" class="flex items-center gap-2">
          <input
            type="radio"
            :name="`question-correct-${type}`"
            :checked="correctAnswer === option"
            :disabled="!option.trim()"
            class="h-4 w-4 accent-accent"
            @change="correctAnswer = option" />

          <input
            v-model="options[index]"
            type="text"
            :disabled="type === 'true_false'"
            :placeholder="`Option ${index + 1}`"
            class="flex-1 rounded-md border border-border bg-surface px-3 py-2 text-sm text-text outline-none focus:border-accent" />

          <button v-if="type === 'mcq' && options.length > 2" type="button" class="rounded-md p-2 text-text/40 hover:bg-error/5 hover:text-error" @click="removeOption(index)">
            <Trash2 class="h-4 w-4" />
          </button>
        </div>
      </div>
    </div>

    <div v-else class="space-y-2">
      <label class="block text-xs font-bold uppercase tracking-wide text-text/60"> Expected answer </label>

      <textarea
        v-model="correctAnswer"
        rows="4"
        placeholder="Enter the expected answer..."
        class="w-full rounded-md border border-border bg-surface px-3 py-2.5 text-sm text-text outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/10" />

      <p class="text-xs text-text/40">This is optional for essay and short-answer questions with the current backend model.</p>
    </div>

    <div class="flex justify-end gap-2 border-t border-border pt-5">
      <BaseButton variant="secondary" @click="emit('cancel')"> Cancel </BaseButton>

      <BaseButton :loading="saving" :disabled="!canSubmit" @click="submit">
        <template #icon>
          <Check class="h-4 w-4" />
        </template>

        {{ isEditing ? 'Save changes' : 'Create question' }}
      </BaseButton>
    </div>
  </div>
</template>
