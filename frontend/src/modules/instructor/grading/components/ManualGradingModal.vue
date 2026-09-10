<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { X, CheckCircle2, AlertCircle, Save, Award, FileText, Check } from 'lucide-vue-next';
import BaseButton from '@/shared/components/ui/BaseButton.vue';
import BaseBadge from '@/shared/components/ui/BaseBadge.vue';
import { useUiStore } from '@/stores/ui';
import { handleApiError } from '@/shared/utils/apiError';
import { getSubmissionDetail, gradeAnswer } from '../api/grading';
import type { Exam } from '../../exams/types/exam';
import type { AnswerItem, ExamAttemptSubmission } from '../types/grading';

const props = defineProps<{
  open: boolean;
  exam: Exam | null;
  submission: ExamAttemptSubmission | null;
}>();

const emit = defineEmits<{
  (e: 'close'): void;
  (e: 'graded'): void;
}>();

const uiStore = useUiStore();
const loading = ref(false);
const detail = ref<ExamAttemptSubmission | null>(null);
const savingAnswers = ref<Record<number, boolean>>({});
const inputMarks = ref<Record<number, number | ''>>({});

watch(
  () => [props.open, props.submission?.id],
  async ([isOpen, subId]) => {
    if (isOpen && props.exam && subId) {
      await loadSubmissionDetail();
    } else {
      detail.value = null;
      inputMarks.value = {};
    }
  },
  { immediate: true },
);

async function loadSubmissionDetail() {
  if (!props.exam || !props.submission) return;
  loading.value = true;
  try {
    const res = await getSubmissionDetail(props.exam.id, props.submission.id);
    detail.value = res.data;

    // Initialize inputs
    if (detail.value?.answers) {
      const marksMap: Record<number, number | ''> = {};
      detail.value.answers.forEach((ans) => {
        marksMap[ans.id] = ans.marks_awarded !== null ? ans.marks_awarded : '';
      });
      inputMarks.value = marksMap;
    }
  } catch (err: any) {
    handleApiError(err, uiStore, undefined, 'Failed to load submission details.');
  } finally {
    loading.value = false;
  }
}

const currentScore = computed(() => {
  if (!detail.value?.answers) return detail.value?.score ?? 0;
  return detail.value.answers.reduce((acc, ans) => acc + (ans.marks_awarded ?? 0), 0);
});

const totalMarks = computed(() => props.exam?.total_marks ?? 100);

async function handleSaveMark(answer: AnswerItem) {
  const entered = inputMarks.value[answer.id];
  if (entered === '' || entered === null || entered === undefined) {
    uiStore.showToast('Please enter a valid mark.', 'error');
    return;
  }

  const markNum = Number(entered);
  const maxMark = answer.exam_question?.marks ?? 0;

  if (isNaN(markNum) || markNum < 0) {
    uiStore.showToast('Marks must be 0 or higher.', 'error');
    return;
  }

  if (markNum > maxMark) {
    uiStore.showToast(`Marks cannot exceed the maximum of ${maxMark}.`, 'error');
    return;
  }

  savingAnswers.value[answer.id] = true;
  try {
    await gradeAnswer(answer.id, { marks_awarded: markNum });
    uiStore.showToast('Answer graded successfully.', 'success');

    // Update local state
    answer.marks_awarded = markNum;
    answer.is_correct = markNum > 0;
    if (detail.value) {
      detail.value.score = currentScore.value;
    }
    emit('graded');
  } catch (err: any) {
    handleApiError(err, uiStore, undefined, 'Failed to save marks.');
  } finally {
    savingAnswers.value[answer.id] = false;
  }
}
</script>

<template>
  <Teleport to="body">
    <div
      v-if="open"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
      role="dialog"
      aria-modal="true"
      @click.self="$emit('close')"
    >
      <!-- Modal Card -->
      <div
        class="relative flex max-h-[90vh] w-full max-w-4xl flex-col overflow-hidden rounded-xl border border-border bg-surface shadow-xl"
        @click.stop
      >
        <!-- Header -->
        <div class="flex shrink-0 items-center justify-between border-b border-border bg-bg/40 px-6 py-4">
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-accent/10 text-accent">
              <Award class="h-5 w-5" />
            </div>
            <div>
              <h2 class="font-display text-base font-bold text-text sm:text-lg">
                Grade Submission: {{ submission?.student?.user?.full_name || submission?.student?.user?.first_name || submission?.student?.student_number || 'Student' }}
              </h2>
              <div class="mt-0.5 flex flex-wrap items-center gap-2.5 font-mono text-xs text-text/50">
                <span>ID: {{ submission?.student?.student_number || '—' }}</span>
                <span>•</span>
                <span>Exam: {{ exam?.title }}</span>
                <span>•</span>
                <span class="font-semibold text-text">
                  Score: {{ currentScore }} / {{ totalMarks }} pts
                </span>
              </div>
            </div>
          </div>

          <button
            type="button"
            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-text/50 transition-colors hover:bg-bg hover:text-text focus:outline-none focus:ring-2 focus:ring-accent/30 cursor-pointer"
            aria-label="Close dialog"
            @click="$emit('close')"
          >
            <X class="h-4 w-4" />
          </button>
        </div>

        <!-- Body -->
        <div class="flex-1 min-h-0 overflow-y-auto p-6 space-y-5 bg-bg/20">
          <div v-if="loading" class="flex flex-col items-center justify-center py-16 text-text/50">
            <div class="mb-3 h-8 w-8 animate-spin rounded-full border-2 border-accent border-t-transparent" />
            <p class="text-sm">Loading submission questions and answers...</p>
          </div>

          <template v-else-if="detail && detail.answers && detail.answers.length > 0">
            <div
              v-for="(answer, index) in detail.answers"
              :key="answer.id"
              class="rounded-xl border border-border bg-surface p-5 space-y-4 shadow-2xs"
            >
              <!-- Question Header -->
              <div class="flex items-start justify-between gap-4">
                <div class="flex items-center gap-2">
                  <span class="flex h-7 w-7 items-center justify-center rounded-md bg-bg border border-border text-xs font-mono font-bold text-text">
                    Q{{ answer.exam_question?.order_number || index + 1 }}
                  </span>
                  <BaseBadge variant="neutral" class="uppercase text-[10px] tracking-wider font-mono">
                    {{ answer.question?.type?.replace('_', ' ') }}
                  </BaseBadge>
                  <span class="text-xs font-mono text-text/50">
                    ({{ answer.exam_question?.marks }} pt{{ answer.exam_question?.marks !== 1 ? 's' : '' }})
                  </span>
                </div>

                <!-- Marks Status / Award Badge -->
                <div class="flex items-center gap-2">
                  <span
                    v-if="answer.marks_awarded !== null"
                    class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 font-mono text-xs font-semibold"
                    :class="answer.marks_awarded > 0 ? 'bg-success/15 text-success' : 'bg-bg border border-border text-text/60'"
                  >
                    <Check v-if="answer.marks_awarded > 0" class="h-3.5 w-3.5" />
                    {{ answer.marks_awarded }} / {{ answer.exam_question?.marks }} pts
                  </span>
                  <span
                    v-else
                    class="inline-flex items-center gap-1 rounded-full bg-warning/15 px-2.5 py-0.5 font-mono text-xs font-semibold text-warning"
                  >
                    <AlertCircle class="h-3.5 w-3.5" />
                    Needs Grading
                  </span>
                </div>
              </div>

              <!-- Question Content -->
              <p class="text-sm font-medium text-text leading-relaxed whitespace-pre-line">
                {{ answer.question?.content }}
              </p>

              <!-- Objective Question Options (MCQ / TRUE_FALSE) -->
              <div
                v-if="answer.question?.options && answer.question.options.length > 0"
                class="space-y-2 pt-1"
              >
                <div
                  v-for="opt in answer.question.options"
                  :key="opt.id"
                  class="flex items-center justify-between rounded-lg border p-3 text-sm transition-colors"
                  :class="[
                    opt.id === answer.selected_option_id
                      ? opt.is_correct
                        ? 'border-success bg-success/10 text-text font-medium'
                        : 'border-error bg-error/10 text-text font-medium'
                      : opt.is_correct
                        ? 'border-success/40 bg-success/5 text-text/70'
                        : 'border-border bg-surface text-text/70'
                  ]"
                >
                  <div class="flex items-center gap-2.5">
                    <span
                      class="flex h-5 w-5 items-center justify-center rounded-full text-[10px] font-bold"
                      :class="opt.id === answer.selected_option_id ? 'bg-accent text-white' : 'bg-bg border border-border text-text/50'"
                    >
                      {{ opt.id === answer.selected_option_id ? '✓' : '' }}
                    </span>
                    <span>{{ opt.option_text }}</span>
                  </div>

                  <span v-if="opt.is_correct" class="flex items-center gap-1 text-xs font-semibold text-success">
                    Correct Choice
                  </span>
                  <span v-else-if="opt.id === answer.selected_option_id" class="flex items-center gap-1 text-xs font-semibold text-error">
                    Student Choice (Incorrect)
                  </span>
                </div>
              </div>

              <!-- Written Question Answer Text -->
              <div v-else class="space-y-3 pt-1">
                <div>
                  <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-text/50">
                    Student's Answer:
                  </span>
                  <div class="min-h-[70px] rounded-lg border border-border bg-bg p-4 font-sans text-sm text-text whitespace-pre-wrap">
                    {{ answer.answer_text || 'No response provided by student.' }}
                  </div>
                </div>

                <!-- Grading Input Form -->
                <div class="flex items-center justify-between gap-4 rounded-lg border border-border bg-bg/50 p-3">
                  <div class="flex items-center gap-3">
                    <span class="text-xs font-semibold text-text">Award Marks:</span>
                    <input
                      v-model="inputMarks[answer.id]"
                      type="number"
                      :min="0"
                      :max="answer.exam_question?.marks || 100"
                      step="0.5"
                      class="w-24 rounded-md border border-border bg-surface px-3 py-1.5 font-mono text-sm font-semibold text-text focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20"
                      placeholder="0.0"
                    />
                    <span class="font-mono text-xs text-text/50">
                      / {{ answer.exam_question?.marks }} max
                    </span>
                  </div>

                  <BaseButton
                    variant="primary"
                    class="text-xs px-3 py-1.5"
                    :loading="savingAnswers[answer.id]"
                    @click="handleSaveMark(answer)"
                  >
                    <template #icon>
                      <Save class="h-3.5 w-3.5" />
                    </template>
                    Save Marks
                  </BaseButton>
                </div>
              </div>
            </div>
          </template>

          <div v-else class="py-16 text-center text-text/50">
            <FileText class="mx-auto mb-2 h-10 w-10 opacity-40" />
            <p class="text-sm font-medium">No answers recorded for this submission.</p>
          </div>
        </div>

        <!-- Footer -->
        <div class="flex shrink-0 items-center justify-between border-t border-border bg-surface px-6 py-4">
          <div class="font-mono text-xs text-text/60">
            Total Score: <span class="font-bold text-text text-sm">{{ currentScore }} / {{ totalMarks }} pts</span>
          </div>
          <BaseButton variant="secondary" @click="$emit('close')">
            Done
          </BaseButton>
        </div>
      </div>
    </div>
  </Teleport>
</template>
