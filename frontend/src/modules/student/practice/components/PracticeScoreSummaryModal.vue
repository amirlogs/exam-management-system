<script setup lang="ts">
import { computed, ref } from 'vue';
import {
  Award,
  CheckCircle2,
  XCircle,
  Clock,
  RotateCcw,
  ArrowRight,
  ChevronDown,
  ChevronUp,
  Sparkles,
  Lightbulb,
} from 'lucide-vue-next';
import BaseButton from '@/shared/components/ui/BaseButton.vue';
import type { PracticeExam, PracticeQuestion } from '../types/practice';

const props = defineProps<{
  modelValue: boolean;
  exam: PracticeExam | null;
  questions: PracticeQuestion[];
  userAnswers: Record<number, { option_id: number; is_correct: boolean }>;
  timeSpentSeconds: number;
}>();

const emit = defineEmits<{
  (e: 'update:modelValue', val: boolean): void;
  (e: 'retake'): void;
  (e: 'exit'): void;
}>();

const expandedQuestionId = ref<number | null>(null);

const totalQuestions = computed(() => props.questions.length);

const correctCount = computed(() => {
  return Object.values(props.userAnswers).filter((a) => a.is_correct).length;
});

const incorrectCount = computed(() => {
  return totalQuestions.value - correctCount.value;
});

const accuracyPercentage = computed(() => {
  if (totalQuestions.value === 0) return 0;
  return Math.round((correctCount.value / totalQuestions.value) * 100);
});

const formattedTimeSpent = computed(() => {
  const mins = Math.floor(props.timeSpentSeconds / 60);
  const secs = props.timeSpentSeconds % 60;
  return `${mins}m ${secs}s`;
});

function toggleExpand(id: number) {
  expandedQuestionId.value = expandedQuestionId.value === id ? null : id;
}

function getOption(question: PracticeQuestion, optionId: number) {
  return question.options.find((o) => o.id === optionId);
}

function getCorrectOption(question: PracticeQuestion) {
  return question.options.find((o) => o.is_correct);
}
</script>

<template>
  <div v-if="modelValue" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs">
    <div class="relative w-full max-w-2xl bg-surface border border-border rounded-2xl shadow-xl overflow-hidden flex flex-col max-h-[92vh] animate-in fade-in zoom-in-95 duration-200">
      <!-- Header -->
      <div class="p-6 text-center border-b border-border space-y-4 bg-surface shrink-0">
        <!-- SVG Accuracy Ring -->
        <div class="relative w-24 h-24 mx-auto flex items-center justify-center">
          <svg class="w-full h-full -rotate-90" viewBox="0 0 36 36">
            <path
              class="text-border"
              stroke-width="3.5"
              stroke="currentColor"
              fill="none"
              d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
            <path
              class="transition-all duration-1000 ease-out"
              :class="accuracyPercentage >= 70 ? 'text-success' : accuracyPercentage >= 50 ? 'text-warning' : 'text-error'"
              stroke-dasharray="100, 100"
              :stroke-dashoffset="100 - accuracyPercentage"
              stroke-width="3.5"
              stroke-linecap="round"
              stroke="currentColor"
              fill="none"
              d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
          </svg>
          <div class="absolute inset-0 flex flex-col items-center justify-center">
            <span class="text-xl font-bold font-mono text-text">{{ accuracyPercentage }}%</span>
            <span class="text-[10px] font-mono uppercase text-text/50">Score</span>
          </div>
        </div>

        <div class="space-y-1">
          <h2 class="text-xl font-bold text-text font-display">
            {{ accuracyPercentage >= 80 ? 'Mastery Demonstrated!' : accuracyPercentage >= 60 ? 'Solid Practice Session!' : 'Good Effort — Target Weak Spots!' }}
          </h2>
          <p class="text-xs text-text/60">
            Completed {{ exam?.title || 'Practice Exam' }} in {{ formattedTimeSpent }}
          </p>
        </div>

        <!-- 3 Key Metric Stats -->
        <div class="grid grid-cols-3 gap-3 pt-2">
          <div class="bg-bg border border-border rounded-xl p-2.5">
            <span class="text-[11px] font-mono text-text/50 block">Total Questions</span>
            <span class="text-base font-bold font-mono text-text">{{ totalQuestions }}</span>
          </div>
          <div class="bg-success/10 border border-success/20 rounded-xl p-2.5">
            <span class="text-[11px] font-mono text-success block">Correct</span>
            <span class="text-base font-bold font-mono text-success">{{ correctCount }}</span>
          </div>
          <div class="bg-error/10 border border-error/20 rounded-xl p-2.5">
            <span class="text-[11px] font-mono text-error block">Incorrect</span>
            <span class="text-base font-bold font-mono text-error">{{ incorrectCount }}</span>
          </div>
        </div>
      </div>

      <!-- Question-by-Question Breakdown -->
      <div class="p-6 overflow-y-auto space-y-3 flex-1">
        <h3 class="text-xs font-semibold text-text uppercase tracking-wider font-mono">Question Breakdown &amp; Analysis</h3>

        <div
          v-for="(q, idx) in questions"
          :key="q.id"
          class="border rounded-xl transition-all overflow-hidden bg-surface"
          :class="userAnswers[q.id]?.is_correct ? 'border-success/30' : 'border-error/30'">
          <!-- Accordion Title Bar -->
          <button
            type="button"
            class="w-full p-3.5 flex items-center justify-between text-left cursor-pointer hover:bg-bg/40 transition-colors"
            @click="toggleExpand(q.id)">
            <div class="flex items-center gap-3">
              <span
                class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold font-mono shrink-0"
                :class="userAnswers[q.id]?.is_correct ? 'bg-success text-white' : 'bg-error text-white'">
                {{ idx + 1 }}
              </span>
              <p class="text-xs font-medium text-text line-clamp-1 font-display">
                {{ q.content }}
              </p>
            </div>

            <div class="flex items-center gap-2 shrink-0">
              <span
                class="text-[11px] font-mono font-semibold px-2 py-0.5 rounded-md"
                :class="userAnswers[q.id]?.is_correct ? 'bg-success/10 text-success' : 'bg-error/10 text-error'">
                {{ userAnswers[q.id]?.is_correct ? 'Correct' : 'Incorrect' }}
              </span>
              <ChevronUp v-if="expandedQuestionId === q.id" class="w-3.5 h-3.5 text-text/40" />
              <ChevronDown v-else class="w-3.5 h-3.5 text-text/40" />
            </div>
          </button>

          <!-- Expanded Details -->
          <div v-if="expandedQuestionId === q.id" class="p-4 border-t border-border/60 space-y-3 bg-bg/40 text-xs">
            <p class="font-medium text-text text-sm font-display">{{ q.content }}</p>

            <div class="space-y-1.5">
              <div
                v-for="(opt, optIdx) in q.options"
                :key="opt.id"
                class="p-2 rounded-lg border flex items-center justify-between font-mono"
                :class="
                  opt.is_correct
                    ? 'border-success bg-success/15 text-success font-semibold'
                    : userAnswers[q.id]?.option_id === opt.id
                      ? 'border-error bg-error/15 text-error line-through'
                      : 'border-border bg-surface text-text/60'
                ">
                <span class="flex items-center gap-2">
                  <span class="font-bold">{{ String.fromCharCode(65 + optIdx) }}.</span>
                  <span>{{ opt.option_text }}</span>
                </span>
                <span v-if="opt.is_correct" class="text-[10px] font-bold uppercase tracking-wider">Correct Answer</span>
                <span v-else-if="userAnswers[q.id]?.option_id === opt.id" class="text-[10px] font-bold uppercase tracking-wider">Your Selection</span>
              </div>
            </div>

            <!-- Explanation if present -->
            <div v-if="q.explanation" class="p-3 bg-surface rounded-lg border border-border flex items-start gap-2 text-text/80 leading-relaxed">
              <Lightbulb class="w-4 h-4 text-accent shrink-0 mt-0.5" />
              <div>
                <strong class="text-text font-semibold">Explanation: </strong>
                <span>{{ q.explanation }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer Buttons -->
      <div class="px-6 py-4 border-t border-border bg-surface flex items-center justify-between shrink-0">
        <BaseButton variant="secondary" class="flex items-center gap-1.5" @click="emit('retake')">
          <RotateCcw class="w-3.5 h-3.5" />
          <span>Retake Exam</span>
        </BaseButton>

        <BaseButton variant="primary" class="font-semibold flex items-center gap-1.5" @click="emit('exit')">
          <span>Return to Practice Hub</span>
          <ArrowRight class="w-3.5 h-3.5" />
        </BaseButton>
      </div>
    </div>
  </div>
</template>
