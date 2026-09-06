<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import {
  Clock,
  Flag,
  Check,
  ArrowLeft,
  ArrowRight,
  AlertTriangle,
  CheckCircle2,
  Send,
  Loader2,
  Lock,
  Stars,
  ListChecks,
  Save,
  Info,
  Maximize2,
  Minimize2,
  Wifi,
  WifiOff,
} from 'lucide-vue-next';

import BaseButton from '@/shared/components/ui/BaseButton.vue';
import BaseBadge from '@/shared/components/ui/BaseBadge.vue';
import { useUiStore } from '@/stores/ui';
import { handleApiError } from '@/shared/utils/apiError';

import * as api from '../api/studentExams';
import type { StudentExam, StudentExamQuestion } from '../types/studentExam';

const route = useRoute();
const router = useRouter();
const uiStore = useUiStore();

const examId = Number(route.params.examId);
const exam = ref<StudentExam | null>(null);
const attemptId = ref<number | null>(null);
const questions = ref<StudentExamQuestion[]>([]);
const currentIndex = ref(0);

const loading = ref(true);
const submitting = ref(false);
const savingAnswer = ref(false);
const showSubmitModal = ref(false);
const isFullscreen = ref(false);
const isOnline = ref(typeof navigator !== 'undefined' ? navigator.onLine : true);

const paletteFilter = ref<'all' | 'unanswered' | 'flagged'>('all');
const flaggedQuestions = ref<Set<number>>(new Set());

// Track local answer values and their saved status per question
interface AnswerEntry {
  selected_option_id: number | null;
  answer_text: string;
  isDirty: boolean;
  lastSavedAt?: string;
}

const answersState = ref<Record<number, AnswerEntry>>({});

// Timer tracking (synced from backend duration and started_at)
const remainingSeconds = ref<number>(0);
let timerInterval: any = null;

const currentQuestion = computed(() => {
  return questions.value[currentIndex.value] || null;
});

const totalQuestions = computed(() => questions.value.length);

const currentAnswerEntry = computed(() => {
  if (!currentQuestion.value) return null;
  return answersState.value[currentQuestion.value.exam_question_id] || null;
});

const currentWordCount = computed(() => {
  const text = currentAnswerEntry.value?.answer_text?.trim() || '';
  if (!text) return 0;
  return text.split(/\s+/).filter(Boolean).length;
});

const isQuestionAnswered = (examQuestionId: number): boolean => {
  const ans = answersState.value[examQuestionId];
  if (!ans) return false;
  if (ans.selected_option_id !== null && ans.selected_option_id !== undefined) return true;
  if (ans.answer_text && ans.answer_text.trim().length > 0) return true;
  return false;
};

const answeredCount = computed(() => {
  let count = 0;
  for (const q of questions.value) {
    if (isQuestionAnswered(q.exam_question_id)) {
      count++;
    }
  }
  return count;
});

const unansweredCount = computed(() => {
  return Math.max(0, totalQuestions.value - answeredCount.value);
});

const completionPercent = computed(() => {
  if (totalQuestions.value === 0) return 0;
  return Math.round((answeredCount.value / totalQuestions.value) * 100);
});

const unansweredQuestionNumbers = computed(() => {
  const numbers: number[] = [];
  questions.value.forEach((q, idx) => {
    if (!isQuestionAnswered(q.exam_question_id)) {
      numbers.push(idx + 1);
    }
  });
  return numbers;
});

const formattedTimeRemaining = computed(() => {
  const total = remainingSeconds.value;
  if (total <= 0) return '00:00:00';
  const hours = Math.floor(total / 3600);
  const minutes = Math.floor((total % 3600) / 60);
  const seconds = total % 60;

  if (hours > 0) {
    return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
  }
  return `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
});

const isTimeCritical = computed(() => {
  return remainingSeconds.value < 300 && remainingSeconds.value > 0; // Less than 5 mins
});

function toggleFlag(qId: number) {
  if (flaggedQuestions.value.has(qId)) {
    flaggedQuestions.value.delete(qId);
  } else {
    flaggedQuestions.value.add(qId);
  }
}

function toggleFullscreen() {
  if (!document.fullscreenElement) {
    document.documentElement.requestFullscreen().then(() => {
      isFullscreen.value = true;
    }).catch(() => {});
  } else {
    if (document.exitFullscreen) {
      document.exitFullscreen().then(() => {
        isFullscreen.value = false;
      }).catch(() => {});
    }
  }
}

function onFullscreenChange() {
  isFullscreen.value = !!document.fullscreenElement;
}

function handleOnline() {
  isOnline.value = true;
  uiStore.showToast('Network connection restored.', 'success');
}

function handleOffline() {
  isOnline.value = false;
  uiStore.showToast('Network connection lost! Your work is preserved locally until connection returns.', 'error');
}

function handleBeforeUnload(e: BeforeUnloadEvent) {
  e.preventDefault();
  e.returnValue = '';
}

async function initializeExam() {
  loading.value = true;
  try {
    // 1. Fetch Exam Details
    const examRes = await api.getStudentExam(examId);
    exam.value = examRes.data;

    // 2. Start or get current attempt from backend (source of truth)
    const attemptRes = await api.startStudentExam(examId);
    attemptId.value = attemptRes.data.id;

    if (attemptRes.data.status === 'completed' || attemptRes.data.status === 'graded') {
      uiStore.showToast('This examination has already been completed.', 'info');
      router.push({ name: 'student.exams.overview', params: { examId } });
      return;
    }

    // 3. Fetch Exam Questions
    const qRes = await api.getStudentExamQuestions(examId);
    questions.value = qRes.data || [];

    // 4. Initialize Local Answers State
    const map: Record<number, AnswerEntry> = {};
    for (const q of questions.value) {
      map[q.exam_question_id] = {
        selected_option_id: q.selected_answer_id ?? null,
        answer_text: q.answer_text ?? '',
        isDirty: false,
        lastSavedAt: q.selected_answer_id || q.answer_text ? 'Saved' : undefined,
      };
    }
    answersState.value = map;

    // 5. Compute Remaining Time based on backend started_at & duration_minutes
    const durationMinutes = exam.value.duration_minutes || 60;
    const totalDurationSeconds = durationMinutes * 60;
    const startedAtMs = attemptRes.data.started_at ? new Date(attemptRes.data.started_at).getTime() : Date.now();
    const elapsedSeconds = Math.max(0, Math.floor((Date.now() - startedAtMs) / 1000));

    remainingSeconds.value = Math.max(0, totalDurationSeconds - elapsedSeconds);

    startTimer();
  } catch (err: any) {
    handleApiError(err, uiStore, undefined, 'Failed to initialize examination session.');
    router.push({ name: 'student.exams.overview', params: { examId } });
  } finally {
    loading.value = false;
  }
}

function startTimer() {
  if (timerInterval) clearInterval(timerInterval);
  timerInterval = setInterval(() => {
    if (remainingSeconds.value > 0) {
      remainingSeconds.value--;
    } else {
      clearInterval(timerInterval);
      handleTimeoutSubmission();
    }
  }, 1000);
}

async function handleTimeoutSubmission() {
  uiStore.showToast('Time has expired! Submitting your exam automatically...', 'info');
  await submitFinalExam();
}

async function selectOption(optionId: number) {
  if (!currentQuestion.value || !attemptId.value) return;
  const qId = currentQuestion.value.exam_question_id;

  answersState.value[qId] = {
    selected_option_id: optionId,
    answer_text: '',
    isDirty: false,
    lastSavedAt: 'Saving...',
  };

  try {
    savingAnswer.value = true;
    await api.saveStudentAnswer(attemptId.value, {
      exam_question_id: qId,
      selected_option_id: optionId,
      answer_text: null,
    });
    answersState.value[qId].lastSavedAt = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  } catch (err: any) {
    console.error('Error saving option', err);
    uiStore.showToast('Failed to save answer choice. Please retry.', 'error');
  } finally {
    savingAnswer.value = false;
  }
}

function onTextAnswerInput() {
  if (!currentQuestion.value) return;
  const qId = currentQuestion.value.exam_question_id;
  if (answersState.value[qId]) {
    answersState.value[qId].isDirty = true;
  }
}

async function saveWrittenAnswer(showToast = true) {
  if (!currentQuestion.value || !attemptId.value) return;
  const qId = currentQuestion.value.exam_question_id;
  const entry = answersState.value[qId];
  if (!entry) return;

  savingAnswer.value = true;
  try {
    await api.saveStudentAnswer(attemptId.value, {
      exam_question_id: qId,
      selected_option_id: null,
      answer_text: entry.answer_text,
    });
    entry.isDirty = false;
    entry.lastSavedAt = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    if (showToast) {
      uiStore.showToast('Answer response saved successfully.', 'success');
    }
  } catch (err: any) {
    handleApiError(err, uiStore, undefined, 'Failed to save written answer.');
  } finally {
    savingAnswer.value = false;
  }
}

async function autoSaveIfDirty() {
  if (!currentQuestion.value || !attemptId.value) return;
  const qId = currentQuestion.value.exam_question_id;
  const entry = answersState.value[qId];
  if (entry && entry.isDirty && (currentQuestion.value.type === 'short_answer' || currentQuestion.value.type === 'essay')) {
    await saveWrittenAnswer(false);
  }
}

async function goToQuestion(idx: number) {
  if (idx >= 0 && idx < totalQuestions.value) {
    await autoSaveIfDirty();
    currentIndex.value = idx;
  }
}

async function nextQuestion() {
  if (currentIndex.value < totalQuestions.value - 1) {
    await autoSaveIfDirty();
    currentIndex.value++;
  } else {
    await autoSaveIfDirty();
    showSubmitModal.value = true;
  }
}

async function prevQuestion() {
  if (currentIndex.value > 0) {
    await autoSaveIfDirty();
    currentIndex.value--;
  }
}

function jumpFromModalToQuestion(idx: number) {
  showSubmitModal.value = false;
  goToQuestion(idx);
}

async function submitFinalExam() {
  if (!attemptId.value) return;
  submitting.value = true;
  try {
    await autoSaveIfDirty();
    await api.submitStudentExam(attemptId.value);
    uiStore.showToast('Examination submitted successfully!', 'success');
    router.push({ name: 'student.exams.overview', params: { examId } });
  } catch (err: any) {
    handleApiError(err, uiStore, undefined, 'Failed to submit exam attempt.');
  } finally {
    submitting.value = false;
    showSubmitModal.value = false;
  }
}

onMounted(() => {
  initializeExam();
  window.addEventListener('beforeunload', handleBeforeUnload);
  document.addEventListener('fullscreenchange', onFullscreenChange);
  window.addEventListener('online', handleOnline);
  window.addEventListener('offline', handleOffline);
});

onUnmounted(() => {
  if (timerInterval) clearInterval(timerInterval);
  window.removeEventListener('beforeunload', handleBeforeUnload);
  document.removeEventListener('fullscreenchange', onFullscreenChange);
  window.removeEventListener('online', handleOnline);
  window.removeEventListener('offline', handleOffline);
});
</script>

<template>
  <div class="min-h-screen bg-bg flex flex-col font-sans select-none">
    <!-- ── Sticky Academic Exam Header ─────────────────────────────── -->
    <header class="w-full bg-surface border-b border-border px-4 sm:px-6 py-3 flex items-center justify-between shadow-2xs sticky top-0 z-40">
      <!-- Left: Title and Course Identity -->
      <div class="flex items-center gap-3 min-w-0">
        <span class="w-2.5 h-2.5 rounded-full bg-accent animate-pulse shrink-0" />
        <h1 class="text-sm sm:text-base font-bold text-text tracking-tight truncate font-display">
          {{ exam?.course?.code }}: {{ exam?.title }}
        </h1>
        <span class="text-xs text-text/50 font-mono hidden md:inline-block truncate max-w-[220px]">
          / {{ exam?.course?.name }}
        </span>
      </div>

      <!-- Right: Invigilation Controls, Timer & Status -->
      <div class="flex items-center gap-3 shrink-0">
        <!-- Connectivity status -->
        <div
          v-if="!isOnline"
          class="flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-mono font-semibold bg-error/15 text-error border border-error/30 animate-pulse"
          title="Network disconnected. Answers saved to local memory.">
          <WifiOff class="w-3.5 h-3.5" />
          <span class="hidden sm:inline">Offline Mode</span>
        </div>

        <!-- Session active badge -->
        <div class="hidden sm:flex items-center gap-1.5 bg-success/10 text-success px-2.5 py-1 rounded-full font-mono text-[11px] font-semibold border border-success/20">
          <Lock class="w-3 h-3" />
          <span>SESSION ACTIVE</span>
        </div>

        <!-- High-contrast countdown timer -->
        <div
          class="flex items-center gap-1.5 px-3.5 py-1 rounded-full text-xs font-mono font-bold transition-colors border shadow-2xs"
          :class="isTimeCritical ? 'bg-error/15 text-error border-error/30 animate-pulse' : 'bg-bg text-text border-border'">
          <Clock class="w-3.5 h-3.5 text-accent" :class="{ 'text-error': isTimeCritical }" />
          <span>{{ formattedTimeRemaining }}</span>
        </div>

        <!-- Fullscreen toggle -->
        <button
          type="button"
          class="p-1.5 rounded-lg border border-border bg-bg text-text/70 hover:text-text hover:bg-surface transition-colors cursor-pointer hidden sm:inline-flex"
          :title="isFullscreen ? 'Exit Fullscreen' : 'Enter Fullscreen Examination Mode'"
          @click="toggleFullscreen">
          <Minimize2 v-if="isFullscreen" class="w-4 h-4" />
          <Maximize2 v-else class="w-4 h-4" />
        </button>

        <div class="h-4 w-px bg-border hidden sm:block" />
        <span class="font-mono text-xs text-text/50 hidden lg:inline-block">ID: {{ examId }}</span>
      </div>
    </header>

    <!-- ── Loading State ──────────────────────────────────────────── -->
    <div v-if="loading" class="flex-1 flex items-center justify-center p-12">
      <div class="flex flex-col items-center gap-4">
        <Loader2 class="w-8 h-8 animate-spin text-accent" />
        <p class="text-sm font-medium text-text/60">Initializing secure examination chamber...</p>
      </div>
    </div>

    <!-- ── Main Exam Body ─────────────────────────────────────────── -->
    <div v-else class="flex-1 flex flex-col md:flex-row w-full min-h-[calc(100vh-3.75rem)]">
      <!-- Left Question Palette Rail -->
      <aside class="w-full md:w-72 shrink-0 bg-surface border-b md:border-b-0 md:border-r border-border flex flex-col justify-between select-none">
        <div class="p-4 sm:p-5 space-y-4 flex-1 overflow-y-auto">
          <!-- Rail title & item counter -->
          <div class="flex items-center justify-between pb-2 border-b border-border">
            <span class="font-mono text-xs uppercase text-text/60 tracking-wider font-semibold">Question Index</span>
            <span class="font-mono text-[11px] bg-bg border border-border px-2 py-0.5 rounded text-text font-bold">
              {{ totalQuestions }} Items
            </span>
          </div>

          <!-- Overall completion progress bar -->
          <div class="space-y-1.5 bg-bg/60 border border-border/80 rounded-xl p-3">
            <div class="flex items-center justify-between text-[11px] font-mono">
              <span class="text-text/70">Completion Progress</span>
              <span class="font-bold text-accent">{{ completionPercent }}%</span>
            </div>
            <div class="w-full bg-surface border border-border rounded-full h-2 overflow-hidden">
              <div
                class="bg-accent h-full transition-all duration-300 rounded-full"
                :style="{ width: `${completionPercent}%` }" />
            </div>
            <div class="text-[10px] font-mono text-text/50 flex justify-between pt-0.5">
              <span>{{ answeredCount }} Answered</span>
              <span>{{ unansweredCount }} Pending</span>
            </div>
          </div>

          <!-- Palette Filter Pills -->
          <div class="flex items-center gap-1.5 bg-bg p-1 rounded-lg border border-border text-[11px] font-mono">
            <button
              type="button"
              class="flex-1 py-1 rounded transition-colors text-center cursor-pointer font-medium"
              :class="paletteFilter === 'all' ? 'bg-surface text-text shadow-2xs font-bold' : 'text-text/60 hover:text-text'"
              @click="paletteFilter = 'all'">
              All ({{ totalQuestions }})
            </button>
            <button
              type="button"
              class="flex-1 py-1 rounded transition-colors text-center cursor-pointer font-medium"
              :class="paletteFilter === 'unanswered' ? 'bg-surface text-text shadow-2xs font-bold' : 'text-text/60 hover:text-text'"
              @click="paletteFilter = 'unanswered'">
              Blank ({{ unansweredCount }})
            </button>
            <button
              type="button"
              class="flex-1 py-1 rounded transition-colors text-center cursor-pointer font-medium"
              :class="paletteFilter === 'flagged' ? 'bg-surface text-warning shadow-2xs font-bold' : 'text-text/60 hover:text-text'"
              @click="paletteFilter = 'flagged'">
              Flag ({{ flaggedQuestions.size }})
            </button>
          </div>

          <!-- Question Grid (5 columns) -->
          <div class="grid grid-cols-5 gap-2">
            <template v-for="(q, idx) in questions" :key="q.id">
              <button
                v-if="
                  paletteFilter === 'all' ||
                  (paletteFilter === 'unanswered' && !isQuestionAnswered(q.exam_question_id)) ||
                  (paletteFilter === 'flagged' && flaggedQuestions.has(q.exam_question_id))
                "
                type="button"
                class="relative h-10 rounded-lg font-mono text-xs flex items-center justify-center transition-all cursor-pointer select-none"
                :class="[
                  currentIndex === idx
                    ? 'bg-accent text-white font-bold shadow-md scale-105 ring-2 ring-accent ring-offset-1 ring-offset-surface'
                    : isQuestionAnswered(q.exam_question_id)
                      ? 'bg-success/15 text-success font-semibold border border-success/30 hover:bg-success/25'
                      : 'bg-bg text-text/70 hover:bg-bg/80 border border-border',
                  flaggedQuestions.has(q.exam_question_id) && currentIndex !== idx ? '!border-warning ring-1 ring-warning/60' : '',
                ]"
                @click="goToQuestion(idx)">
                {{ idx + 1 }}
                <span
                  v-if="flaggedQuestions.has(q.exam_question_id)"
                  class="absolute -top-1 -right-1 w-2.5 h-2.5 rounded-full bg-warning ring-2 ring-surface" />
              </button>
            </template>
          </div>

          <!-- Empty filter feedback -->
          <div
            v-if="paletteFilter === 'unanswered' && unansweredCount === 0"
            class="py-3 px-2 text-center text-xs text-success font-medium bg-success/10 border border-success/20 rounded-lg">
            All questions answered
          </div>
          <div
            v-else-if="paletteFilter === 'flagged' && flaggedQuestions.size === 0"
            class="py-3 px-2 text-center text-xs text-text/50 bg-bg border border-border rounded-lg font-mono text-[11px]">
            No questions flagged
          </div>

          <!-- Legend -->
          <div class="bg-bg border border-border/80 rounded-xl p-3 space-y-2 text-xs text-text/60">
            <div class="flex items-center gap-2">
              <span class="w-3 h-3 rounded bg-accent inline-block shadow-2xs" />
              <span class="font-medium text-text">Current Focus (Q{{ currentIndex + 1 }})</span>
            </div>
            <div class="flex items-center gap-2">
              <span class="w-3 h-3 rounded bg-success/30 border border-success/50 inline-block" />
              <span>Answered ({{ answeredCount }})</span>
            </div>
            <div class="flex items-center gap-2">
              <span class="w-3 h-3 rounded bg-bg border border-border inline-block" />
              <span>Unanswered ({{ unansweredCount }})</span>
            </div>
            <div v-if="flaggedQuestions.size > 0" class="flex items-center gap-2">
              <span class="w-3 h-3 rounded bg-warning/40 border border-warning inline-block" />
              <span class="text-warning font-medium">Flagged for Review ({{ flaggedQuestions.size }})</span>
            </div>
          </div>
        </div>

        <!-- Submit Examination CTA at Bottom of Rail -->
        <div class="p-4 sm:p-5 border-t border-border bg-surface">
          <BaseButton variant="primary" class="w-full font-semibold shadow-sm" @click="showSubmitModal = true">
            <ListChecks class="w-4 h-4" />
            <span>Finish &amp; Submit</span>
          </BaseButton>
          <p class="text-[10px] font-mono text-text/50 text-center mt-2">
            {{ answeredCount }} of {{ totalQuestions }} questions finalized
          </p>
        </div>
      </aside>

      <!-- Main Question Workspace -->
      <section class="flex-1 flex flex-col min-h-0 bg-bg overflow-y-auto">
        <div v-if="currentQuestion" class="flex-1 px-6 sm:px-12 lg:px-16 py-8 max-w-3xl mx-auto w-full space-y-6">
          <!-- Question metadata card -->
          <div class="flex items-center justify-between bg-surface px-6 py-3.5 rounded-xl border border-border shadow-2xs">
            <div class="flex items-center gap-3">
              <span class="text-base sm:text-lg font-bold text-text font-display">
                Question {{ currentIndex + 1 }}
                <span class="text-text/50 font-normal text-sm">of {{ totalQuestions }}</span>
              </span>
              <span class="text-xs font-mono bg-bg border border-border text-text/70 px-2.5 py-0.5 rounded capitalize">
                {{ currentQuestion.type.replace('_', ' ') }}
              </span>
            </div>

            <div class="flex items-center gap-3">
              <!-- Flag toggle -->
              <button
                type="button"
                class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-lg border transition-all cursor-pointer"
                :class="
                  flaggedQuestions.has(currentQuestion.exam_question_id)
                    ? 'text-warning font-semibold bg-warning/10 border-warning/40'
                    : 'text-text/60 hover:text-text hover:bg-bg border-border'
                "
                @click="toggleFlag(currentQuestion.exam_question_id)">
                <Flag class="w-3.5 h-3.5" :class="{ 'fill-current': flaggedQuestions.has(currentQuestion.exam_question_id) }" />
                <span>{{ flaggedQuestions.has(currentQuestion.exam_question_id) ? 'Flagged' : 'Flag' }}</span>
              </button>

              <!-- Points badge -->
              <div class="flex items-center gap-1.5 bg-bg border border-border px-3 py-1 rounded-lg text-xs font-mono font-bold text-text">
                <Stars class="w-3.5 h-3.5 text-accent" />
                <span>{{ currentQuestion.marks }} pt{{ currentQuestion.marks !== 1 ? 's' : '' }}</span>
              </div>
            </div>
          </div>

          <!-- Question Prompt / Stimulus -->
          <div class="py-2 space-y-2">
            <h2 class="text-xl sm:text-2xl font-semibold text-text leading-relaxed font-display whitespace-pre-line">
              {{ currentQuestion.content }}
            </h2>
          </div>

          <!-- MCQ / True-False Options (Stitch Ergonomic Card Design) -->
          <fieldset v-if="currentQuestion.type === 'mcq' || currentQuestion.type === 'true_false'" class="space-y-3.5">
            <legend class="sr-only">Available Answers</legend>
            <div
              v-for="(option, optIdx) in currentQuestion.options"
              :key="option.id"
              role="button"
              tabindex="0"
              class="group relative flex items-center justify-between p-5 rounded-xl border-2 transition-all cursor-pointer select-none overflow-hidden"
              :class="
                answersState[currentQuestion.exam_question_id]?.selected_option_id === option.id
                  ? 'border-accent bg-accent/10 shadow-sm ring-1 ring-accent'
                  : 'border-border bg-surface hover:border-accent/40 hover:bg-bg/60 shadow-2xs'
              "
              @click="selectOption(option.id)"
              @keydown.enter="selectOption(option.id)"
              @keydown.space.prevent="selectOption(option.id)">
              <!-- Left accent bar for selected -->
              <div
                v-if="answersState[currentQuestion.exam_question_id]?.selected_option_id === option.id"
                class="absolute left-0 top-0 bottom-0 w-1.5 bg-accent" />

              <div class="flex items-center gap-4 min-w-0">
                <span
                  class="w-8 h-8 rounded-lg flex items-center justify-center font-mono text-xs font-bold transition-colors shrink-0"
                  :class="
                    answersState[currentQuestion.exam_question_id]?.selected_option_id === option.id
                      ? 'bg-accent text-white shadow-2xs'
                      : 'bg-bg border border-border text-text/70 group-hover:border-accent/50'
                  ">
                  {{ String.fromCharCode(65 + optIdx) }}
                </span>
                <span
                  class="text-sm sm:text-base leading-relaxed text-text font-medium"
                  :class="{ 'font-semibold': answersState[currentQuestion.exam_question_id]?.selected_option_id === option.id }">
                  {{ option.option_text }}
                </span>
              </div>

              <!-- Radio Indicator with Checkmark -->
              <span
                class="w-6 h-6 rounded-full flex items-center justify-center transition-all shrink-0 ml-4 shadow-2xs"
                :class="
                  answersState[currentQuestion.exam_question_id]?.selected_option_id === option.id
                    ? 'bg-accent text-white'
                    : 'bg-bg border border-border text-transparent'
                ">
                <Check class="w-3.5 h-3.5 stroke-[3]" />
              </span>
            </div>
          </fieldset>

          <!-- Short Answer / Essay Written Response -->
          <div v-else-if="currentQuestion.type === 'short_answer' || currentQuestion.type === 'essay'" class="space-y-4">
            <div class="flex justify-between items-center text-xs text-text/60 font-mono">
              <span class="font-sans">Enter your academic response below:</span>
              <span>Words: {{ currentWordCount }} · Characters: {{ currentAnswerEntry?.answer_text?.length || 0 }}</span>
            </div>

            <textarea
              v-if="currentQuestion.type === 'essay'"
              v-model="answersState[currentQuestion.exam_question_id].answer_text"
              rows="11"
              placeholder="Type your comprehensive essay response here..."
              class="w-full bg-surface border border-border rounded-xl p-5 text-sm sm:text-base text-text focus:outline-none focus:border-accent focus:ring-2 focus:ring-accent/20 resize-y leading-relaxed font-sans shadow-2xs transition-colors"
              @input="onTextAnswerInput" />
            <input
              v-else
              v-model="answersState[currentQuestion.exam_question_id].answer_text"
              type="text"
              placeholder="Type your concise response here..."
              class="w-full bg-surface border border-border rounded-xl px-5 py-3.5 text-sm sm:text-base text-text focus:outline-none focus:border-accent focus:ring-2 focus:ring-accent/20 shadow-2xs transition-colors"
              @input="onTextAnswerInput" />

            <!-- Explicit Save Status Action Bar -->
            <div class="flex items-center justify-between bg-surface border border-border p-4 rounded-xl shadow-2xs">
              <div class="text-xs text-text/60 flex items-center gap-2">
                <span v-if="answersState[currentQuestion.exam_question_id]?.isDirty" class="text-warning font-semibold flex items-center gap-1.5">
                  <AlertTriangle class="w-4 h-4" />
                  Unsaved edits in response — click Save to synchronize
                </span>
                <span v-else-if="answersState[currentQuestion.exam_question_id]?.lastSavedAt" class="text-success font-medium flex items-center gap-1.5">
                  <Check class="w-4 h-4" />
                  Synchronized with server at {{ answersState[currentQuestion.exam_question_id].lastSavedAt }}
                </span>
                <span v-else class="text-text/50">
                  Draft saved in local session memory.
                </span>
              </div>

              <BaseButton variant="primary" :loading="savingAnswer" class="font-semibold shadow-xs" @click="saveWrittenAnswer(true)">
                <Save class="w-4 h-4 mr-1.5" />
                Save Answer
              </BaseButton>
            </div>
          </div>
        </div>

        <!-- Sticky Footer Navigation Bar -->
        <div class="sticky bottom-0 bg-surface/95 backdrop-blur-md border-t border-border px-6 sm:px-12 lg:px-16 py-3.5 shadow-md">
          <div class="max-w-3xl mx-auto flex items-center justify-between gap-3">
            <BaseButton variant="secondary" :disabled="currentIndex === 0" class="flex items-center gap-1.5" @click="prevQuestion">
              <ArrowLeft class="w-4 h-4" />
              <span>Previous</span>
            </BaseButton>

            <BaseButton variant="secondary" class="flex items-center gap-1.5 hidden sm:inline-flex" @click="showSubmitModal = true">
              <ListChecks class="w-4 h-4" />
              <span>Review ({{ unansweredCount }} pending)</span>
            </BaseButton>

            <BaseButton
              v-if="currentIndex < totalQuestions - 1"
              variant="primary"
              class="flex items-center gap-1.5 shadow-xs"
              @click="nextQuestion">
              <span>Next Question</span>
              <ArrowRight class="w-4 h-4" />
            </BaseButton>

            <BaseButton
              v-else
              variant="primary"
              class="flex items-center gap-1.5 shadow-xs"
              @click="showSubmitModal = true">
              <span>Finish Examination</span>
              <CheckCircle2 class="w-4 h-4" />
            </BaseButton>
          </div>
        </div>
      </section>
    </div>

    <!-- ── Interactive Review & Submit Modal ──────────────────────── -->
    <div v-if="showSubmitModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
      <div class="w-full max-w-lg bg-surface p-6 sm:p-8 rounded-2xl shadow-2xl border border-border flex flex-col items-center text-center space-y-6">
        <!-- Icon Badge -->
        <div class="w-14 h-14 rounded-2xl bg-accent/10 flex items-center justify-center text-accent shadow-xs">
          <Send class="w-7 h-7" />
        </div>

        <!-- Header & Counter breakdown pills -->
        <div class="space-y-3">
          <h2 class="text-xl sm:text-2xl font-bold font-display text-text">Ready to submit your examination?</h2>
          <div class="inline-flex items-center gap-2.5 px-4 py-1.5 rounded-full bg-bg border border-border font-mono text-xs">
            <span class="font-bold text-success">{{ answeredCount }} Answered</span>
            <span class="text-text/30">·</span>
            <span class="font-bold" :class="unansweredCount > 0 ? 'text-error' : 'text-text/60'">
              {{ unansweredCount }} Unanswered
            </span>
            <span class="text-text/30">·</span>
            <span class="font-bold text-warning">{{ flaggedQuestions.size }} Flagged</span>
          </div>
        </div>

        <!-- Attention Needed Box with Interactive Jump Links -->
        <div v-if="unansweredCount > 0" class="bg-error/10 border border-error/25 rounded-xl p-4 text-left w-full space-y-2 text-xs">
          <div class="flex items-center gap-1.5 text-error font-semibold">
            <AlertTriangle class="w-4 h-4" />
            <span>Unanswered Questions Detected</span>
          </div>
          <p class="text-text/70 leading-relaxed">
            Click any question number below to jump directly to it:
          </p>
          <div class="flex flex-wrap gap-1.5 pt-1">
            <button
              v-for="qNum in unansweredQuestionNumbers.slice(0, 15)"
              :key="qNum"
              type="button"
              class="px-2 py-1 rounded bg-surface border border-border font-mono font-bold text-text hover:bg-accent hover:text-white transition-colors cursor-pointer"
              @click="jumpFromModalToQuestion(qNum - 1)">
              Q{{ qNum }}
            </button>
            <span v-if="unansweredQuestionNumbers.length > 15" class="text-text/50 font-mono text-xs self-center">
              +{{ unansweredQuestionNumbers.length - 15 }} more
            </span>
          </div>
        </div>

        <!-- All Answered Reassurance Box -->
        <div v-else class="bg-success/10 border border-success/20 rounded-xl p-4 text-left w-full space-y-1 text-xs">
          <div class="flex items-center gap-1.5 text-success font-semibold">
            <CheckCircle2 class="w-4 h-4" />
            <span>All Questions Completed</span>
          </div>
          <p class="text-text/70 leading-relaxed">
            You have responded to all {{ totalQuestions }} questions in this assessment. Once submitted, your answers cannot be modified.
          </p>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center gap-3 w-full pt-2">
          <BaseButton variant="secondary" class="flex-1" @click="showSubmitModal = false">
            Return to Exam
          </BaseButton>

          <BaseButton variant="primary" :loading="submitting" class="flex-1 font-semibold shadow-xs" @click="submitFinalExam">
            Confirm &amp; Submit
          </BaseButton>
        </div>
      </div>
    </div>
  </div>
</template>
