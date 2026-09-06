<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import {
  Clock,
  Flag,
  Bookmark,
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
  GraduationCap,
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

function isExamEndedError(err: any): boolean {
  const msg = (err?.response?.data?.message || err?.message || '').toLowerCase();
  const status = err?.response?.status;
  return (
    status === 403 &&
    (msg.includes('completed') ||
      msg.includes('not in progress') ||
      msg.includes('already submitted') ||
      msg.includes('ended') ||
      msg.includes('closed'))
  );
}

function handleExamEnded() {
  if (timerInterval) clearInterval(timerInterval);
  uiStore.showToast('This examination has concluded. Your answers have been submitted.', 'info');
  router.push({ name: 'student.exams.overview', params: { examId } });
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
    if (isExamEndedError(err)) {
      handleExamEnded();
      return;
    }
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
    if (isExamEndedError(err)) {
      handleExamEnded();
      return;
    }
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
    if (isExamEndedError(err)) {
      handleExamEnded();
      return;
    }
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
    <!-- ── Minimal Academic Exam Top Bar ─────────────────────────────── -->
    <header class="w-full bg-surface border-b border-border px-6 lg:px-8 py-3.5 flex items-center justify-between shadow-2xs sticky top-0 z-40">
      <!-- Left: University Exam Management Portal -->
      <div class="flex items-center gap-3 min-w-0">
        <div class="w-8 h-8 rounded-lg bg-accent/10 flex items-center justify-center shrink-0">
          <GraduationCap class="w-4.5 h-4.5 text-accent" />
        </div>
        <div class="min-w-0">
          <h1 class="text-sm font-bold text-text truncate font-display">
            University Exam Management Portal
          </h1>
          <p v-if="exam?.title" class="text-[11px] font-mono text-text/50 truncate">
            {{ exam?.course?.code ? `${exam.course.code} · ` : '' }}{{ exam.title }}
          </p>
        </div>
      </div>

      <!-- Right: Network Status, Single Calm Timer, Fullscreen -->
      <div class="flex items-center gap-3 shrink-0">
        <!-- Connectivity status (only when offline) -->
        <div
          v-if="!isOnline"
          class="flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-mono font-semibold bg-error/15 text-error border border-error/30 animate-pulse"
          title="Network disconnected. Answers saved to local memory.">
          <WifiOff class="w-3.5 h-3.5" />
          <span class="hidden sm:inline">Offline</span>
        </div>

        <!-- Single Calm Timer Indicator -->
        <div
          class="flex items-center gap-2 px-3.5 py-1 rounded-full text-xs font-mono font-semibold transition-colors border shadow-2xs"
          :class="isTimeCritical ? 'bg-error/15 text-error border-error/30 animate-pulse' : 'bg-bg text-text border-border'">
          <Clock class="w-3.5 h-3.5 text-accent" :class="{ 'text-error': isTimeCritical }" />
          <span>{{ formattedTimeRemaining }} remaining</span>
        </div>

        <!-- Fullscreen toggle -->
        <button
          type="button"
          class="p-1.5 rounded-md border border-border bg-bg text-text/70 hover:text-text hover:bg-surface transition-colors cursor-pointer hidden sm:inline-flex"
          :title="isFullscreen ? 'Exit Fullscreen' : 'Enter Fullscreen Examination Mode'"
          @click="toggleFullscreen">
          <Minimize2 v-if="isFullscreen" class="w-4 h-4" />
          <Maximize2 v-else class="w-4 h-4" />
        </button>
      </div>
    </header>

    <!-- ── Loading State ──────────────────────────────────────────── -->
    <div v-if="loading" class="flex-1 flex items-center justify-center p-12">
      <div class="flex flex-col items-center gap-4">
        <Loader2 class="w-8 h-8 animate-spin text-accent" />
        <p class="text-sm font-medium text-text/60">Loading examination questions...</p>
      </div>
    </div>

    <!-- ── Main Exam Body ─────────────────────────────────────────── -->
    <div v-else class="flex-1 flex flex-col md:flex-row w-full min-h-[calc(100vh-3.5rem)]">
      <!-- Left Sidebar: Question Navigation & Progress -->
      <aside class="w-full md:w-72 shrink-0 bg-surface border-b md:border-b-0 md:border-r border-border flex flex-col justify-between select-none">
        <div class="p-5 space-y-5 flex-1 overflow-y-auto">
          <!-- Progress Summary -->
          <div>
            <div class="flex items-center justify-between text-xs text-text/70 mb-2">
              <span class="font-medium text-text">Progress</span>
              <span class="font-mono font-semibold text-text">{{ answeredCount }} of {{ totalQuestions }} answered</span>
            </div>
            <div class="w-full bg-bg border border-border rounded-full h-1.5 overflow-hidden">
              <div
                class="bg-accent h-full transition-all duration-300 rounded-full"
                :style="{ width: `${completionPercent}%` }" />
            </div>
          </div>

          <!-- Filter Underline Tabs -->
          <div class="flex items-center gap-4 border-b border-border pb-2 text-xs font-mono">
            <button
              type="button"
              class="relative pb-1 transition-colors cursor-pointer"
              :class="paletteFilter === 'all' ? 'text-text font-bold after:absolute after:-bottom-[9px] after:left-0 after:right-0 after:h-[2px] after:bg-text' : 'text-text/50 hover:text-text'"
              @click="paletteFilter = 'all'">
              All ({{ totalQuestions }})
            </button>
            <button
              type="button"
              class="relative pb-1 transition-colors cursor-pointer"
              :class="paletteFilter === 'unanswered' ? 'text-text font-bold after:absolute after:-bottom-[9px] after:left-0 after:right-0 after:h-[2px] after:bg-text' : 'text-text/50 hover:text-text'"
              @click="paletteFilter = 'unanswered'">
              Unanswered ({{ unansweredCount }})
            </button>
            <button
              type="button"
              class="relative pb-1 transition-colors cursor-pointer"
              :class="paletteFilter === 'flagged' ? 'text-text font-bold after:absolute after:-bottom-[9px] after:left-0 after:right-0 after:h-[2px] after:bg-text' : 'text-text/50 hover:text-text'"
              @click="paletteFilter = 'flagged'">
              Flagged ({{ flaggedQuestions.size }})
            </button>
          </div>

          <!-- Question Grid (4 Columns) -->
          <div class="grid grid-cols-4 gap-2">
            <template v-for="(q, idx) in questions" :key="q.id">
              <button
                v-if="
                  paletteFilter === 'all' ||
                  (paletteFilter === 'unanswered' && !isQuestionAnswered(q.exam_question_id)) ||
                  (paletteFilter === 'flagged' && flaggedQuestions.has(q.exam_question_id))
                "
                type="button"
                class="relative h-10 rounded-md font-mono text-xs flex items-center justify-center transition-all cursor-pointer select-none"
                :class="[
                  currentIndex === idx
                    ? 'bg-surface text-accent font-bold ring-2 ring-accent shadow-xs'
                    : isQuestionAnswered(q.exam_question_id)
                      ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 font-semibold hover:bg-emerald-500/25'
                      : 'bg-surface text-text/70 border border-border hover:border-text/40',
                  flaggedQuestions.has(q.exam_question_id) && currentIndex !== idx ? 'ring-1 ring-amber-500/60' : '',
                ]"
                @click="goToQuestion(idx)">
                {{ idx + 1 }}
                <!-- Flagged Dot Indicator -->
                <span
                  v-if="flaggedQuestions.has(q.exam_question_id)"
                  class="absolute top-1.5 right-1.5 w-1.5 h-1.5 rounded-full bg-amber-500" />
              </button>
            </template>
          </div>

          <!-- Filter empty state -->
          <div
            v-if="paletteFilter === 'unanswered' && unansweredCount === 0"
            class="py-3 px-2 text-center text-xs text-text/60 bg-bg border border-border rounded-md font-mono">
            All questions answered
          </div>
          <div
            v-else-if="paletteFilter === 'flagged' && flaggedQuestions.size === 0"
            class="py-3 px-2 text-center text-xs text-text/50 bg-bg border border-border rounded-md font-mono">
            No flagged questions
          </div>
        </div>

        <!-- Left Rail Bottom Submit CTA -->
        <div class="p-5 border-t border-border bg-surface">
          <BaseButton variant="primary" class="w-full font-semibold shadow-xs" @click="showSubmitModal = true">
            <Send class="w-4 h-4 mr-1.5" />
            <span>Submit Exam</span>
          </BaseButton>
        </div>
      </aside>

      <!-- Main Question Workspace -->
      <section class="flex-1 flex flex-col min-h-0 bg-bg overflow-y-auto">
        <div v-if="currentQuestion" class="flex-1 px-6 sm:px-12 lg:px-16 py-8 max-w-3xl mx-auto w-full space-y-6">
          <!-- Metadata Bar: Question Index • Type & Points / Flag Toggle -->
          <div class="flex items-center justify-between pb-3 border-b border-border">
            <div class="flex items-center gap-2 text-xs font-mono text-text/60">
              <span class="font-bold text-text text-sm">Question {{ currentIndex + 1 }}</span>
              <span>·</span>
              <span class="capitalize">{{ currentQuestion.type.replace('_', ' ') }}</span>
              <span>({{ currentQuestion.marks }} pt{{ currentQuestion.marks !== 1 ? 's' : '' }})</span>
            </div>

            <!-- Flag for Review -->
            <button
              type="button"
              class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-md border transition-all cursor-pointer"
              :class="
                flaggedQuestions.has(currentQuestion.exam_question_id)
                  ? 'text-amber-600 dark:text-amber-400 bg-amber-500/10 border-amber-500/40 font-semibold'
                  : 'text-text/60 hover:text-text hover:bg-surface border-border'
              "
              @click="toggleFlag(currentQuestion.exam_question_id)">
              <Bookmark class="w-3.5 h-3.5" :class="{ 'fill-current': flaggedQuestions.has(currentQuestion.exam_question_id) }" />
              <span>{{ flaggedQuestions.has(currentQuestion.exam_question_id) ? 'Flagged for review' : 'Flag for review' }}</span>
            </button>
          </div>

          <!-- Question Prompt / Content -->
          <div class="py-2">
            <h2 class="text-xl lg:text-2xl font-semibold text-text leading-snug tracking-tight font-display whitespace-pre-line">
              {{ currentQuestion.content }}
            </h2>
          </div>

          <!-- MCQ / True-False Options -->
          <fieldset v-if="currentQuestion.type === 'mcq' || currentQuestion.type === 'true_false'" class="space-y-3">
            <legend class="sr-only">Available Answers</legend>
            <div
              v-for="(option, optIdx) in currentQuestion.options"
              :key="option.id"
              role="button"
              tabindex="0"
              class="group relative flex items-center justify-between p-4 sm:p-5 rounded-xl border transition-all cursor-pointer select-none overflow-hidden"
              :class="
                answersState[currentQuestion.exam_question_id]?.selected_option_id === option.id
                  ? 'border-accent/40 border-l-4 border-l-accent bg-accent/5 shadow-2xs'
                  : 'border-border bg-surface hover:border-text/30 hover:bg-bg/40'
              "
              @click="selectOption(option.id)"
              @keydown.enter="selectOption(option.id)"
              @keydown.space.prevent="selectOption(option.id)">
              <div class="flex items-center gap-3.5 min-w-0">
                <!-- Option Letter Badge -->
                <span
                  class="w-7 h-7 rounded-md flex items-center justify-center font-mono text-xs font-semibold transition-colors shrink-0"
                  :class="
                    answersState[currentQuestion.exam_question_id]?.selected_option_id === option.id
                      ? 'bg-accent text-white shadow-2xs'
                      : 'border border-border bg-bg text-text/70 group-hover:border-text/30'
                  ">
                  {{ String.fromCharCode(65 + optIdx) }}
                </span>
                <!-- Option Text -->
                <span
                  class="text-sm sm:text-base leading-relaxed text-text font-normal"
                  :class="{ 'font-semibold': answersState[currentQuestion.exam_question_id]?.selected_option_id === option.id }">
                  {{ option.option_text }}
                </span>
              </div>

              <!-- Radio Indicator -->
              <span
                class="w-5 h-5 rounded-full flex items-center justify-center transition-all shrink-0 ml-4"
                :class="
                  answersState[currentQuestion.exam_question_id]?.selected_option_id === option.id
                    ? 'border-2 border-accent'
                    : 'border-2 border-border'
                ">
                <span
                  v-if="answersState[currentQuestion.exam_question_id]?.selected_option_id === option.id"
                  class="w-2.5 h-2.5 rounded-full bg-accent" />
              </span>
            </div>
          </fieldset>

          <!-- Written Response (Short Answer / Essay) -->
          <div v-else-if="currentQuestion.type === 'short_answer' || currentQuestion.type === 'essay'" class="space-y-4">
            <div class="flex justify-between items-center text-xs text-text/60 font-mono">
              <span class="font-sans">Enter your response:</span>
              <span>Words: {{ currentWordCount }} · Characters: {{ currentAnswerEntry?.answer_text?.length || 0 }}</span>
            </div>

            <textarea
              v-if="currentQuestion.type === 'essay'"
              v-model="answersState[currentQuestion.exam_question_id].answer_text"
              rows="10"
              placeholder="Type your essay response here..."
              class="w-full bg-surface border border-border rounded-xl p-5 text-sm sm:text-base text-text focus:outline-none focus:border-accent focus:ring-2 focus:ring-accent/20 resize-y leading-relaxed font-sans shadow-2xs transition-colors"
              @input="onTextAnswerInput" />
            <input
              v-else
              v-model="answersState[currentQuestion.exam_question_id].answer_text"
              type="text"
              placeholder="Type your concise response here..."
              class="w-full bg-surface border border-border rounded-xl px-5 py-3.5 text-sm sm:text-base text-text focus:outline-none focus:border-accent focus:ring-2 focus:ring-accent/20 shadow-2xs transition-colors"
              @input="onTextAnswerInput" />

            <!-- Save Status Action Bar -->
            <div class="flex items-center justify-between bg-surface border border-border p-4 rounded-xl shadow-2xs">
              <div class="text-xs text-text/60 flex items-center gap-2 font-mono">
                <span v-if="answersState[currentQuestion.exam_question_id]?.isDirty" class="text-amber-500 font-semibold flex items-center gap-1.5">
                  <AlertTriangle class="w-4 h-4" />
                  Unsaved edits
                </span>
                <span v-else-if="answersState[currentQuestion.exam_question_id]?.lastSavedAt" class="text-emerald-500 font-medium flex items-center gap-1.5">
                  <Check class="w-4 h-4" />
                  Saved at {{ answersState[currentQuestion.exam_question_id].lastSavedAt }}
                </span>
                <span v-else class="text-text/50">
                  Saved locally
                </span>
              </div>

              <BaseButton variant="primary" class="font-semibold shadow-xs" @click="showSubmitModal = true">
                <Send class="w-4 h-4 mr-1.5" />
                Submit Exam
              </BaseButton>
            </div>
          </div>
        </div>

        <!-- Sticky Footer Navigation Bar: Exactly Two Actions -->
        <div class="sticky bottom-0 bg-surface/95 backdrop-blur-md border-t border-border px-6 sm:px-12 lg:px-16 py-3.5 shadow-xs">
          <div class="max-w-3xl mx-auto flex items-center justify-between gap-4">
            <BaseButton
              variant="secondary"
              :disabled="currentIndex === 0"
              class="flex items-center gap-1.5"
              @click="prevQuestion">
              <ArrowLeft class="w-4 h-4" />
              <span>Previous</span>
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
              <span>Submit Exam</span>
              <Send class="w-4 h-4" />
            </BaseButton>
          </div>
        </div>
      </section>
    </div>

    <!-- ── Interactive Review & Submit Modal ──────────────────────── -->
    <div v-if="showSubmitModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-xs p-4">
      <div class="w-full max-w-lg bg-surface p-6 sm:p-8 rounded-2xl shadow-2xl border border-border flex flex-col items-center text-center space-y-6">
        <!-- Header & Summary Pills -->
        <div class="space-y-3">
          <h2 class="text-xl sm:text-2xl font-bold font-display text-text">Ready to submit your exam?</h2>
          <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-bg border border-border font-mono text-xs">
            <span class="font-semibold text-text">{{ answeredCount }} answered</span>
            <span class="text-text/30">·</span>
            <span class="font-semibold" :class="unansweredCount > 0 ? 'text-amber-500' : 'text-text/60'">
              {{ unansweredCount }} unanswered
            </span>
            <span class="text-text/30">·</span>
            <span class="font-semibold text-text/70">{{ flaggedQuestions.size }} flagged</span>
          </div>
        </div>

        <!-- Attention Needed Box with Interactive Jump Links -->
        <div v-if="unansweredCount > 0" class="bg-amber-500/10 border border-amber-500/20 rounded-xl p-4 text-left w-full space-y-2 text-xs">
          <div class="flex items-center gap-1.5 text-amber-600 dark:text-amber-400 font-semibold">
            <AlertTriangle class="w-4 h-4" />
            <span>Unanswered questions remaining</span>
          </div>
          <p class="text-text/70 leading-relaxed">
            Click any question number below to jump directly to it:
          </p>
          <div class="flex flex-wrap gap-1.5 pt-1">
            <button
              v-for="qNum in unansweredQuestionNumbers.slice(0, 16)"
              :key="qNum"
              type="button"
              class="px-2.5 py-1 rounded bg-surface border border-border font-mono font-bold text-text hover:bg-accent hover:text-white transition-colors cursor-pointer"
              @click="jumpFromModalToQuestion(qNum - 1)">
              Q{{ qNum }}
            </button>
            <span v-if="unansweredQuestionNumbers.length > 16" class="text-text/50 font-mono text-xs self-center">
              +{{ unansweredQuestionNumbers.length - 16 }} more
            </span>
          </div>
        </div>

        <!-- All Answered Reassurance Box -->
        <div v-else class="bg-emerald-500/10 border border-emerald-500/20 rounded-xl p-4 text-left w-full space-y-1 text-xs">
          <div class="flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400 font-semibold">
            <CheckCircle2 class="w-4 h-4" />
            <span>All questions completed</span>
          </div>
          <p class="text-text/70 leading-relaxed">
            You have responded to all {{ totalQuestions }} questions. Once submitted, your answers cannot be modified.
          </p>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center gap-3 w-full pt-2">
          <BaseButton variant="secondary" class="flex-1" @click="showSubmitModal = false">
            Return to Exam
          </BaseButton>

          <BaseButton variant="primary" :loading="submitting" class="flex-1 font-semibold shadow-xs" @click="submitFinalExam">
            Submit Exam
          </BaseButton>
        </div>
      </div>
    </div>
  </div>
</template>
