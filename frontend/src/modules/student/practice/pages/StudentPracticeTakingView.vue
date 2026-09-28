<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import {
  GraduationCap,
  Clock,
  ArrowLeft,
  ArrowRight,
  Check,
  X,
  Sparkles,
  Lightbulb,
  ChevronUp,
  ChevronDown,
  RotateCcw,
  CheckCircle2,
  XCircle,
  HelpCircle,
  AlertCircle,
  Loader2,
  BookOpen,
} from 'lucide-vue-next';

import BaseButton from '@/shared/components/ui/BaseButton.vue';
import BaseBadge from '@/shared/components/ui/BaseBadge.vue';
import ConfirmModal from '@/shared/components/ConfirmModal.vue';
import PracticeScoreSummaryModal from '../components/PracticeScoreSummaryModal.vue';
import QuestionInlineGuidance from '../components/QuestionInlineGuidance.vue';

import { useUiStore } from '@/stores/ui';
import { handleApiError } from '@/shared/utils/apiError';
import {
  getPracticeExam,
  getPracticeExamQuestions,
  submitPracticeAnswer,
  completePracticeExam,
  retakePracticeExam,
} from '../api/practice';
import type { PracticeExam, PracticeQuestion, PracticeQuestionOption } from '../types/practice';

const route = useRoute();
const router = useRouter();
const uiStore = useUiStore();

const examId = Number(route.params.practiceExamId);

const exam = ref<PracticeExam | null>(null);
const questions = ref<PracticeQuestion[]>([]);
const currentIndex = ref(0);
const loading = ref(true);
const submittingAnswer = ref(false);
const isExplanationOpen = ref(true);
const showSummaryModal = ref(false);
const showRetakeConfirm = ref(false);
const retaking = ref(false);

// Filter tab in question palette: 'all' | 'unanswered' | 'answered'
const sidebarFilter = ref<'all' | 'unanswered' | 'answered'>('all');

// Answer tracking: key is questionId
const userAnswers = ref<Record<number, { option_id: number; is_correct: boolean; correct_option_id?: number }>>({});

// Timer tracking
const timeRemainingSeconds = ref(0);
const timerInterval = ref<any>(null);
const timeSpentSeconds = ref(0);
const spentInterval = ref<any>(null);

const currentQuestion = computed(() => {
  return questions.value[currentIndex.value] || null;
});

const currentAnswer = computed(() => {
  if (!currentQuestion.value) return null;
  return userAnswers.value[currentQuestion.value.id] || null;
});

const isCurrentAnswered = computed(() => {
  return !!currentAnswer.value;
});

const totalQuestions = computed(() => questions.value.length);

const answeredCount = computed(() => Object.keys(userAnswers.value).length);

const correctCount = computed(() => {
  return Object.values(userAnswers.value).filter((a) => a.is_correct).length;
});

const incorrectCount = computed(() => {
  return Object.values(userAnswers.value).filter((a) => !a.is_correct).length;
});

const progressPercentage = computed(() => {
  if (totalQuestions.value === 0) return 0;
  return Math.round((answeredCount.value / totalQuestions.value) * 100);
});

const formattedTimeRemaining = computed(() => {
  const mins = Math.floor(timeRemainingSeconds.value / 60);
  const secs = timeRemainingSeconds.value % 60;
  return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
});

const filteredPaletteQuestions = computed(() => {
  return questions.value.filter((q) => {
    const isAns = !!userAnswers.value[q.id];
    if (sidebarFilter.value === 'answered') return isAns;
    if (sidebarFilter.value === 'unanswered') return !isAns;
    return true;
  });
});

const correctOption = computed(() => {
  if (!currentQuestion.value) return null;
  return currentQuestion.value.options.find((o) => o.is_correct) || null;
});

async function loadExamData() {
  loading.value = true;
  try {
    const [examRes, questionsRes] = await Promise.all([
      getPracticeExam(examId),
      getPracticeExamQuestions(examId, 1, 100),
    ]);

    exam.value = examRes.data;
    questions.value = questionsRes.data;

    // Restore any previously answered questions for this user
    const restored: Record<number, { option_id: number; is_correct: boolean; correct_option_id?: number }> = {};
    questions.value.forEach((q) => {
      if (q.user_answer) {
        const correctOpt = q.options.find((o) => o.is_correct);
        restored[q.id] = {
          option_id: q.user_answer.selected_option_id,
          is_correct: q.user_answer.is_correct,
          correct_option_id: correctOpt?.id,
        };
      }
    });
    userAnswers.value = restored;

    // Set countdown timer based on duration
    const duration = exam.value.duration_minutes || 15;
    timeRemainingSeconds.value = duration * 60;

    startTimers();
  } catch (error) {
    handleApiError(error, uiStore, undefined, 'Failed to load practice questions');
    router.push({ name: 'student.practice.list' });
  } finally {
    loading.value = false;
  }
}

function startTimers() {
  clearInterval(timerInterval.value);
  clearInterval(spentInterval.value);

  timerInterval.value = setInterval(() => {
    if (timeRemainingSeconds.value > 0) {
      timeRemainingSeconds.value--;
    } else {
      clearInterval(timerInterval.value);
      handleFinishPractice();
    }
  }, 1000);

  spentInterval.value = setInterval(() => {
    timeSpentSeconds.value++;
  }, 1000);
}

function stopTimers() {
  clearInterval(timerInterval.value);
  clearInterval(spentInterval.value);
}

async function handleSelectOption(option: PracticeQuestionOption) {
  if (!currentQuestion.value || isCurrentAnswered.value || submittingAnswer.value) return;

  submittingAnswer.value = true;
  const qId = currentQuestion.value.id;

  try {
    const res = await submitPracticeAnswer(examId, qId, {
      option_id: option.id,
    });

    userAnswers.value[qId] = {
      option_id: option.id,
      is_correct: res.data.is_correct,
      correct_option_id: res.data.correct_option_id,
    };
    isExplanationOpen.value = true;
  } catch (error) {
    // If offline or fallback, resolve from option
    const isCorrect = option.is_correct ?? false;
    const correctOpt = currentQuestion.value.options.find((o) => o.is_correct);

    userAnswers.value[qId] = {
      option_id: option.id,
      is_correct: isCorrect,
      correct_option_id: correctOpt?.id,
    };
    isExplanationOpen.value = true;
  } finally {
    submittingAnswer.value = false;
  }
}

function jumpToQuestion(index: number) {
  if (index >= 0 && index < totalQuestions.value) {
    currentIndex.value = index;
    isExplanationOpen.value = true;
  }
}

function handlePrevious() {
  if (currentIndex.value > 0) {
    currentIndex.value--;
    isExplanationOpen.value = true;
  }
}

function handleNext() {
  if (currentIndex.value < totalQuestions.value - 1) {
    currentIndex.value++;
    isExplanationOpen.value = true;
  } else {
    handleFinishPractice();
  }
}

async function handleFinishPractice() {
  stopTimers();
  try {
    if (exam.value && exam.value.status !== 'completed') {
      await completePracticeExam(examId);
    }
  } catch (e) {
    // Ignore if already completed
  }
  showSummaryModal.value = true;
}

async function handleRetake() {
  if (retaking.value) return;
  retaking.value = true;
  try {
    await retakePracticeExam(examId);
    if (exam.value) {
      exam.value.status = 'active';
    }
    userAnswers.value = {};
    questions.value.forEach((q) => {
      delete q.user_answer;
    });
    currentIndex.value = 0;
    timeSpentSeconds.value = 0;
    const duration = exam.value?.duration_minutes || 15;
    timeRemainingSeconds.value = duration * 60;
    showSummaryModal.value = false;
    showRetakeConfirm.value = false;
    startTimers();
    uiStore.showToast('Practice exam reset! Starting fresh.', 'success');
  } catch (error) {
    handleApiError(error, uiStore, undefined, 'Failed to reset practice exam');
  } finally {
    retaking.value = false;
  }
}

function handleExit() {
  stopTimers();
  showSummaryModal.value = false;
  router.push({ name: 'student.practice.list' });
}

onMounted(() => {
  loadExamData();
});

onUnmounted(() => {
  stopTimers();
});
</script>

<template>
  <!-- Full Viewport Exam Sitting Canvas -->
  <div class="min-h-screen bg-bg text-text font-sans flex flex-col select-none antialiased">
    <!-- Top Bar Header -->
    <header class="h-16 px-4 sm:px-6 lg:px-8 flex items-center justify-between border-b border-border bg-surface sticky top-0 z-30 shadow-xs shrink-0">
      <!-- Left: Back Button & Exam Info -->
      <div class="flex items-center gap-3">
        <button
          type="button"
          class="w-9 h-9 rounded-xl border border-border bg-surface hover:bg-bg hover:border-accent/40 shadow-2xs flex items-center justify-center text-text/70 hover:text-text transition-colors cursor-pointer"
          title="Return to Practice Hub"
          @click="handleExit">
          <ArrowLeft class="w-4 h-4" />
        </button>

        <div class="overflow-hidden leading-tight">
          <div class="flex items-center gap-2">
            <h1 class="text-sm font-bold text-text truncate max-w-xs sm:max-w-md font-display">
              {{ exam?.title || 'Practice Exam' }}
            </h1>
            <span class="hidden sm:inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-accent/10 text-accent text-[11px] font-mono font-semibold">
              <Sparkles class="w-3 h-3" />
              Practice Mode
            </span>
          </div>
          <p class="text-[11px] text-text/50 font-mono">
            Question {{ currentIndex + 1 }} of {{ totalQuestions }} · Instant Evaluation
          </p>
        </div>
      </div>

      <!-- Center: Progress Bar -->
      <div class="hidden md:flex flex-col items-center gap-1 w-48 lg:w-64">
        <div class="flex items-center justify-between w-full text-[10px] font-mono text-text/50">
          <span>Completion</span>
          <span>{{ answeredCount }} / {{ totalQuestions }} Answered</span>
        </div>
        <div class="h-1.5 w-full bg-bg rounded-full overflow-hidden border border-border">
          <div
            class="h-full bg-accent rounded-full transition-all duration-300"
            :style="{ width: `${progressPercentage}%` }" />
        </div>
      </div>

      <!-- Right: Timer & Finish CTA -->
      <div class="flex items-center gap-3">
        <div
          class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-border bg-surface shadow-2xs font-mono text-xs font-semibold"
          :class="timeRemainingSeconds <= 300 ? 'text-error border-error/30 bg-error/5 animate-pulse' : 'text-text'">
          <Clock class="w-3.5 h-3.5 text-text/50" />
          <span>{{ formattedTimeRemaining }}</span>
        </div>

        <button
          type="button"
          class="hidden sm:flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-border bg-surface hover:bg-bg text-text/70 hover:text-text text-xs font-semibold shadow-2xs transition-colors cursor-pointer"
          title="Reset and retake practice exam"
          @click="showRetakeConfirm = true">
          <RotateCcw class="w-3.5 h-3.5 text-text/50" />
          <span>Retake</span>
        </button>

        <BaseButton
          variant="primary"
          class="text-xs font-semibold py-1.5 px-3 shadow-xs"
          @click="handleFinishPractice">
          <span>Finish Exam</span>
        </BaseButton>
      </div>
    </header>

    <!-- Loading State -->
    <div v-if="loading" class="flex-1 flex items-center justify-center p-12">
      <div class="flex flex-col items-center gap-3 text-center">
        <Loader2 class="w-8 h-8 animate-spin text-accent" />
        <p class="text-xs font-mono text-text/60">Loading practice questions...</p>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="questions.length === 0" class="flex-1 flex items-center justify-center p-12">
      <div class="text-center space-y-3">
        <AlertCircle class="w-10 h-10 mx-auto text-warning" />
        <h3 class="text-base font-semibold text-text">No questions available in this practice exam</h3>
        <BaseButton variant="secondary" @click="handleExit">Back to Practice Hub</BaseButton>
      </div>
    </div>

    <!-- Main Question View -->
    <div v-else class="flex-1 flex flex-col md:flex-row overflow-hidden">
      <!-- Left Sidebar: Question Palette & Navigation (w-72) -->
      <aside class="w-full md:w-72 lg:w-80 border-b md:border-b-0 md:border-r border-border bg-surface p-4 flex flex-col shrink-0 overflow-y-auto">
        <!-- Real-time Score Tally -->
        <div class="bg-bg border border-border rounded-xl p-3.5 space-y-2 mb-4">
          <div class="flex items-center justify-between text-xs font-mono">
            <span class="text-text/60">Session Accuracy</span>
            <span class="font-bold text-text">{{ progressPercentage }}% Completed</span>
          </div>

          <div class="grid grid-cols-2 gap-2 text-xs font-mono">
            <div class="bg-surface border border-border/80 rounded-lg p-2 flex items-center gap-2">
              <CheckCircle2 class="w-3.5 h-3.5 text-success shrink-0" />
              <div>
                <span class="text-[10px] text-text/50 block">Correct</span>
                <span class="font-bold text-success text-sm">{{ correctCount }}</span>
              </div>
            </div>

            <div class="bg-surface border border-border/80 rounded-lg p-2 flex items-center gap-2">
              <XCircle class="w-3.5 h-3.5 text-error shrink-0" />
              <div>
                <span class="text-[10px] text-text/50 block">Incorrect</span>
                <span class="font-bold text-error text-sm">{{ incorrectCount }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Palette Filter Tabs -->
        <div class="flex items-center gap-1 bg-bg p-1 rounded-lg border border-border text-[11px] font-mono mb-3">
          <button
            v-for="tab in [
              { key: 'all', label: 'All' },
              { key: 'unanswered', label: 'Unanswered' },
              { key: 'answered', label: 'Answered' },
            ]"
            :key="tab.key"
            type="button"
            class="flex-1 py-1 rounded text-center transition-colors cursor-pointer"
            :class="sidebarFilter === tab.key ? 'bg-surface text-accent font-semibold shadow-2xs' : 'text-text/60 hover:text-text'"
            @click="sidebarFilter = tab.key as any">
            {{ tab.label }}
          </button>
        </div>

        <!-- Question Jump Palette Grid (Zero Jitter, Solid Borders) -->
        <div class="grid grid-cols-4 sm:grid-cols-5 md:grid-cols-4 gap-2 overflow-y-auto max-h-64 md:max-h-none flex-1">
          <button
            v-for="(q, idx) in questions"
            :key="q.id"
            type="button"
            class="h-10 rounded-xl border-2 text-xs font-mono font-bold flex items-center justify-center transition-colors relative cursor-pointer"
            :class="[
              currentIndex === idx
                ? 'border-accent text-accent bg-accent/5'
                : userAnswers[q.id]?.is_correct
                  ? 'bg-success/15 border-success/50 text-success'
                  : userAnswers[q.id]
                    ? 'bg-error/15 border-error/50 text-error'
                    : 'bg-surface border-border text-text/60 hover:border-text/40 hover:bg-bg',
            ]"
            @click="jumpToQuestion(idx)">
            <span>Q{{ idx + 1 }}</span>
            <span
              v-if="userAnswers[q.id]?.is_correct"
              class="absolute top-1 right-1 w-1.5 h-1.5 rounded-full bg-success" />
            <span
              v-else-if="userAnswers[q.id]"
              class="absolute top-1 right-1 w-1.5 h-1.5 rounded-full bg-error" />
          </button>
        </div>

        <!-- Bottom Legend -->
        <div class="mt-4 pt-3 border-t border-border/60 flex items-center justify-between text-[10px] font-mono text-text/50">
          <span class="flex items-center gap-1">
            <span class="w-2 h-2 rounded-full bg-success" /> Correct
          </span>
          <span class="flex items-center gap-1">
            <span class="w-2 h-2 rounded-full bg-error" /> Incorrect
          </span>
          <span class="flex items-center gap-1">
            <span class="w-2 h-2 rounded-full border border-border" /> Open
          </span>
        </div>
      </aside>

      <!-- Center Main Question Canvas -->
      <main class="flex-1 flex flex-col justify-between overflow-y-auto bg-bg p-4 sm:p-6 lg:p-10">
        <div class="max-w-3xl w-full mx-auto space-y-6">
          <!-- Question Header Row -->
          <div class="flex items-center justify-between text-xs font-mono border-b border-border pb-3">
            <div class="flex items-center gap-2">
              <span class="px-2.5 py-0.5 rounded-md bg-surface border border-border font-bold text-accent">
                Question {{ currentIndex + 1 }}
              </span>
              <span class="text-border">·</span>
              <span class="uppercase text-text/50">{{ currentQuestion?.type }}</span>
              <span class="text-border">·</span>
              <span class="capitalize text-text/50">{{ currentQuestion?.difficulty }}</span>
            </div>

            <div class="text-text/60">
              <span>1 Mark</span>
            </div>
          </div>

          <!-- Question Content Stem -->
          <div class="space-y-3">
            <h2 class="text-lg sm:text-xl md:text-2xl font-semibold font-display text-text leading-snug">
              {{ currentQuestion?.content }}
            </h2>
          </div>

          <!-- Option Cards (Solid 2px border, zero layout shift on hover or selection) -->
          <div class="space-y-3 pt-2">
            <div
              v-for="(option, optIdx) in currentQuestion?.options || []"
              :key="option.id"
              class="relative p-4 rounded-xl border-2 transition-colors cursor-pointer select-none flex items-center justify-between"
              :class="[
                // Unanswered state
                !isCurrentAnswered
                  ? 'bg-surface border-border hover:border-accent/40 hover:bg-surface/90'
                  // User selected this and it is CORRECT
                  : currentAnswer?.option_id === option.id && currentAnswer?.is_correct
                    ? 'border-success bg-success/10 text-success font-semibold shadow-2xs'
                    // User selected this and it is WRONG
                    : currentAnswer?.option_id === option.id && !currentAnswer?.is_correct
                      ? 'border-error bg-error/10 text-error font-semibold shadow-2xs'
                      // Correct answer revealed
                      : option.is_correct || currentAnswer?.correct_option_id === option.id
                        ? 'border-success/60 bg-success/5 text-success font-semibold'
                        // Other distractors
                        : 'bg-surface/50 border-border/40 opacity-40 pointer-events-none'
              ]"
              @click="handleSelectOption(option)">
              <!-- Option Left: Badge & Text -->
              <div class="flex items-center gap-3.5 pr-4">
                <span
                  class="w-7 h-7 rounded-lg border flex items-center justify-center text-xs font-bold font-mono shrink-0 transition-colors"
                  :class="[
                    !isCurrentAnswered
                      ? 'bg-bg border-border text-text/70'
                      : currentAnswer?.option_id === option.id && currentAnswer?.is_correct
                        ? 'bg-success text-white border-success'
                        : currentAnswer?.option_id === option.id && !currentAnswer?.is_correct
                          ? 'bg-error text-white border-error'
                          : option.is_correct || currentAnswer?.correct_option_id === option.id
                            ? 'bg-success text-white border-success'
                            : 'bg-bg border-border text-text/40'
                  ]">
                  {{ String.fromCharCode(65 + optIdx) }}
                </span>

                <span class="text-sm font-medium leading-relaxed">
                  {{ option.option_text }}
                </span>
              </div>

              <!-- Option Right: Status Icon -->
              <div class="shrink-0 flex items-center gap-2">
                <div
                  v-if="currentAnswer?.option_id === option.id && currentAnswer?.is_correct"
                  class="flex items-center gap-1.5 text-xs font-mono font-bold text-success">
                  <CheckCircle2 class="w-4 h-4 text-success" />
                  <span class="hidden sm:inline">Correct</span>
                </div>

                <div
                  v-else-if="currentAnswer?.option_id === option.id && !currentAnswer?.is_correct"
                  class="flex items-center gap-1.5 text-xs font-mono font-bold text-error">
                  <XCircle class="w-4 h-4 text-error" />
                  <span class="hidden sm:inline">Your Selection</span>
                </div>

                <div
                  v-else-if="isCurrentAnswered && (option.is_correct || currentAnswer?.correct_option_id === option.id)"
                  class="flex items-center gap-1.5 text-xs font-mono font-bold text-success">
                  <Check class="w-4 h-4 text-success" />
                  <span class="hidden sm:inline">Correct Answer</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Clean Feedback & Pedagogical Note (No fake static text!) -->
          <div
            v-if="isCurrentAnswered"
            class="rounded-xl border p-4 transition-all duration-200 mt-6 space-y-3"
            :class="currentAnswer?.is_correct ? 'border-success/30 bg-success/5' : 'border-error/30 bg-error/5'">
            <!-- Status Row -->
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2.5">
                <div
                  class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0"
                  :class="currentAnswer?.is_correct ? 'bg-success/20 text-success' : 'bg-error/20 text-error'">
                  <CheckCircle2 v-if="currentAnswer?.is_correct" class="w-4 h-4" />
                  <XCircle v-else class="w-4 h-4" />
                </div>
                <div>
                  <h4 class="text-sm font-bold text-text">
                    {{ currentAnswer?.is_correct ? 'Correct! Well done.' : 'Incorrect answer.' }}
                  </h4>
                  <p class="text-xs text-text/60">
                    {{ currentAnswer?.is_correct ? 'You selected the right answer.' : `The correct answer is "${correctOption?.option_text}".` }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Authentic Explanation (Rendered ONLY if genuine explanation exists from curriculum data) -->
            <div
              v-if="currentQuestion?.explanation"
              class="p-3 bg-surface/90 rounded-lg border border-border/60 text-xs text-text/80 leading-relaxed space-y-1">
              <div class="flex items-center gap-1.5 text-accent font-semibold font-mono text-[11px]">
                <Lightbulb class="w-3.5 h-3.5" />
                <span>Explanation</span>
              </div>
              <p>{{ currentQuestion.explanation }}</p>
            </div>
          </div>

          <!-- Inline AI Guidance & Follow-up (at the bottom of each question) -->
          <QuestionInlineGuidance
            v-if="currentQuestion"
            :exam-id="examId"
            :question="currentQuestion"
            :user-answer="currentAnswer"
            :is-answered="isCurrentAnswered" />
        </div>

        <!-- Sticky Bottom Navigation Bar -->
        <div class="max-w-3xl w-full mx-auto pt-6 border-t border-border mt-8 flex items-center justify-between shrink-0">
          <BaseButton
            variant="secondary"
            class="text-xs font-semibold flex items-center gap-1.5"
            :disabled="currentIndex === 0"
            @click="handlePrevious">
            <ArrowLeft class="w-3.5 h-3.5" />
            <span>Previous</span>
          </BaseButton>

          <BaseButton
            variant="primary"
            class="text-xs font-semibold flex items-center gap-1.5 shadow-xs"
            @click="handleNext">
            <span>{{ currentIndex === totalQuestions - 1 ? 'Finish & Review Results' : 'Next Question' }}</span>
            <ArrowRight class="w-3.5 h-3.5" />
          </BaseButton>
        </div>
      </main>
    </div>

    <!-- Score Summary Modal -->
    <PracticeScoreSummaryModal
      v-model="showSummaryModal"
      :exam="exam"
      :questions="questions"
      :user-answers="userAnswers"
      :time-spent-seconds="timeSpentSeconds"
      @retake="handleRetake"
      @exit="handleExit" />

    <!-- Retake Confirmation Modal -->
    <ConfirmModal
      :show="showRetakeConfirm"
      title="Retake Practice Exam"
      description="Are you sure you want to retake this practice exam? All your answered questions will be cleared so you can take it again from scratch."
      confirm-text="Retake Now"
      :loading="retaking"
      @close="showRetakeConfirm = false"
      @confirm="handleRetake" />
  </div>
</template>
