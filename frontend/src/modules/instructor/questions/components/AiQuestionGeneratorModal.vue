<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import {
  Sparkles,
  FileText,
  CheckCircle2,
  X,
  RotateCcw,
  Clock,
  Trash2,
  Check,
  ChevronRight,
  History,
  Minus,
  Plus,
  Loader2,
  BookOpen,
} from 'lucide-vue-next';

import BaseButton from '@/shared/components/ui/BaseButton.vue';
import BaseBadge from '@/shared/components/ui/BaseBadge.vue';
import claudeLoadingGif from '@/assets/claude-loading.gif';
import { handleApiError } from '@/shared/utils/apiError';
import { useUiStore } from '@/stores/ui';

import {
  generateQuestions,
  getGenerationSession,
  confirmGeneratedQuestions,
  cancelGeneratedQuestions,
  getGenerationHistory,
  deleteGeneratedQuestion,
} from '../api/aiQuestions';
import { getTeaching } from '@/modules/instructor/teaching/api/teaching';
import { getCourses } from '@/modules/admin/courses/api/courses';

import type {
  AiDifficulty,
  AiQuestionType,
  GeneratedQuestionsHistory,
} from '../types/aiQuestion';

const props = withDefaults(
  defineProps<{
    modelValue: boolean;
    courseId?: number | null;
    examId?: number | null;
    courseName?: string;
    scope?: 'instructor' | 'admin' | 'exam';
  }>(),
  {
    modelValue: false,
    courseId: null,
    examId: null,
    courseName: '',
    scope: 'instructor',
  }
);

const emit = defineEmits<{
  (e: 'update:modelValue', val: boolean): void;
  (e: 'confirmed', session: GeneratedQuestionsHistory): void;
}>();

const uiStore = useUiStore();

// View navigation: 'form' | 'generating' | 'review' | 'history'
const currentStep = ref<'form' | 'generating' | 'review' | 'history'>('form');

// Form configuration state
const isNoteMode = ref(false); // false = Topic/Concepts, true = Lecture Notes
const topicInput = ref('');
const noteContent = ref('');
const selectedCourseId = ref<number | null>(props.courseId || null);
const selectedType = ref<AiQuestionType>('mcq');
const selectedDifficulty = ref<AiDifficulty>('medium');
const questionCount = ref<number>(7);

// Loading & Generation tracking
const isSubmitting = ref(false);
const deletingIdx = ref<number | null>(null);
const activeSession = ref<GeneratedQuestionsHistory | null>(null);
const pollTimer = ref<any>(null);
const elapsedSeconds = ref(0);
const elapsedTimer = ref<any>(null);

// History drawer
const historyItems = ref<GeneratedQuestionsHistory[]>([]);
const loadingHistory = ref(false);

// Courses list
const courses = ref<{ id: number; label: string }[]>([]);
const loadingCourses = ref(false);

const typeOptions: { value: AiQuestionType; label: string }[] = [
  { value: 'mcq', label: 'Multiple Choice (MCQ)' },
  { value: 'true_false', label: 'True / False' },
  { value: 'short_answer', label: 'Short Answer' },
  { value: 'essay', label: 'Essay / Long Answer' },
];

const difficultyOptions: { value: AiDifficulty; label: string }[] = [
  { value: 'easy', label: 'Easy' },
  { value: 'medium', label: 'Medium' },
  { value: 'hard', label: 'Hard' },
];

// Review selection
const selectedQuestionIndexes = ref<Set<number>>(new Set());

// Normalizes question items from history response
const parsedQuestions = computed(() => {
  if (!activeSession.value || !activeSession.value.questions) return [];
  return activeSession.value.questions.map((item: any, idx: number) => {
    const data = item.data ?? item;
    return {
      index: idx + 1,
      backendIndex: idx,
      type: data.type || activeSession.value?.type || 'mcq',
      content: data.content || '',
      chapter: data.chapter || 'General',
      options: data.options || [],
      correct_answer: data.correct_answer,
      difficulty: data.difficulty || activeSession.value?.difficulty || 'medium',
    };
  });
});

const isAllSelected = computed(() => {
  return parsedQuestions.value.length > 0 && selectedQuestionIndexes.value.size === parsedQuestions.value.length;
});

function toggleSelectAll() {
  if (isAllSelected.value) {
    selectedQuestionIndexes.value.clear();
  } else {
    selectedQuestionIndexes.value = new Set(parsedQuestions.value.map((_, i) => i));
  }
}

async function loadCourses() {
  loadingCourses.value = true;
  try {
    if (props.scope === 'admin') {
      const res = await getCourses(1, 200);
      courses.value = (res.data || []).map((c: any) => ({
        id: c.id,
        label: `${c.code} — ${c.title || c.name}`,
      }));
    } else {
      const res = await getTeaching(1, 200);
      courses.value = (res.data || [])
        .filter((t: any) => t.course)
        .map((t: any) => ({
          id: t.course.id,
          label: `${t.course.code} — ${t.course.name || t.course.title}`,
        }));
    }

    if (props.courseId) {
      selectedCourseId.value = props.courseId;
    } else if (courses.value.length === 1) {
      selectedCourseId.value = courses.value[0].id;
    }
  } catch (err) {
    handleApiError(err, uiStore, undefined, 'Failed to load courses.');
  } finally {
    loadingCourses.value = false;
  }
}

// Claude-style thinking stages
const thinkingStages = [
  'Analyzing course curriculum & depth...',
  'Synthesizing question stems & correct answers...',
  'Formulating plausible distractors & rubric...',
  'Structuring interactive review deck...',
];
const currentStageIdx = ref(0);
let stageInterval: any = null;

function startElapsedCounter() {
  elapsedSeconds.value = 0;
  clearInterval(elapsedTimer.value);
  elapsedTimer.value = setInterval(() => {
    elapsedSeconds.value++;
  }, 1000);

  currentStageIdx.value = 0;
  clearInterval(stageInterval);
  stageInterval = setInterval(() => {
    currentStageIdx.value = (currentStageIdx.value + 1) % thinkingStages.length;
  }, 3000);
}

function stopElapsedCounter() {
  clearInterval(elapsedTimer.value);
  clearInterval(stageInterval);
}

const formattedElapsed = computed(() => {
  const mins = Math.floor(elapsedSeconds.value / 60);
  const secs = elapsedSeconds.value % 60;
  return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
});

function incrementCount() {
  if (questionCount.value < 99) {
    questionCount.value++;
  }
}

function decrementCount() {
  if (questionCount.value > 1) {
    questionCount.value--;
  }
}

function onCountInput(event: Event) {
  const target = event.target as HTMLInputElement;
  let val = parseInt(target.value, 10);
  if (isNaN(val)) return;
  if (val < 1) val = 1;
  if (val > 99) val = 99; // Allow realistic any number.
  questionCount.value = val;
}

async function handleGenerate() {
  const input = isNoteMode.value ? noteContent.value.trim() : topicInput.value.trim();
  if (!input || input.length < 3) {
    uiStore.showToast('Please enter a topic or lecture notes of at least 3 characters.', 'error');
    return;
  }

  if (!selectedCourseId.value) {
    uiStore.showToast('Please select a target course for the questions.', 'error');
    return;
  }

  isSubmitting.value = true;
  try {
    const payload: any = {
      input,
      count: questionCount.value,
      is_note: isNoteMode.value,
      type: selectedType.value,
      difficulty: selectedDifficulty.value,
      context: {
        course_id: selectedCourseId.value,
      },
    };

    if (props.examId) {
      payload.context.exam_id = props.examId;
    }

    const res = await generateQuestions(payload);
    activeSession.value = res.data;
    currentStep.value = 'generating';
    startElapsedCounter();
    startPolling(res.data.id);
  } catch (err) {
    handleApiError(err, uiStore, undefined, 'Failed to start AI question generation.');
  } finally {
    isSubmitting.value = false;
  }
}

function startPolling(id: number) {
  stopPolling();
  pollTimer.value = setInterval(async () => {
    try {
      const res = await getGenerationSession(id);
      const session = res.data;
      activeSession.value = session;

      if (session.status === 'ready_for_review') {
        stopPolling();
        stopElapsedCounter();
        selectedQuestionIndexes.value = new Set(parsedQuestions.value.map((_, i) => i));
        currentStep.value = 'review';
        uiStore.showToast('Questions generated successfully!', 'success');
      } else if (session.status === 'failed') {
        stopPolling();
        stopElapsedCounter();
        currentStep.value = 'form';
        uiStore.showToast('AI generation failed. Please try again with a clearer prompt.', 'error');
      }
    } catch {
      // Continue polling
    }
  }, 2500);
}

function stopPolling() {
  if (pollTimer.value) {
    clearInterval(pollTimer.value);
    pollTimer.value = null;
  }
}

async function handleCancel() {
  if (!activeSession.value) {
    currentStep.value = 'form';
    return;
  }

  try {
    await cancelGeneratedQuestions(activeSession.value.id);
    uiStore.showToast('Generation cancelled.', 'info');
  } catch {
    // Non-blocking
  } finally {
    stopPolling();
    stopElapsedCounter();
    activeSession.value = null;
    currentStep.value = 'form';
  }
}

async function handleConfirm() {
  if (!activeSession.value) return;

  isSubmitting.value = true;
  try {
    const res = await confirmGeneratedQuestions(activeSession.value.id);
    uiStore.showToast(
      props.examId
        ? 'Questions attached to exam.'
        : 'Questions saved to bank.',
      'success'
    );
    emit('confirmed', res.data);
    closeModal();
  } catch (err) {
    handleApiError(err, uiStore, undefined, 'Failed to confirm questions.');
  } finally {
    isSubmitting.value = false;
  }
}

// Backend deletion of a single question
async function handleDeleteQuestion(backendIndex: number) {
  if (!activeSession.value) return;

  deletingIdx.value = backendIndex;
  try {
    const res = await deleteGeneratedQuestion(activeSession.value.id, backendIndex);
    activeSession.value = res.data;
    selectedQuestionIndexes.value = new Set(parsedQuestions.value.map((_, i) => i));
    uiStore.showToast('Question deleted.', 'success');
  } catch (err) {
    handleApiError(err, uiStore, undefined, 'Failed to delete question.');
  } finally {
    deletingIdx.value = null;
  }
}

function toggleSelectQuestion(idx: number) {
  if (selectedQuestionIndexes.value.has(idx)) {
    if (selectedQuestionIndexes.value.size === 1) {
      uiStore.showToast('At least one question must remain selected to save.', 'warning');
      return;
    }
    selectedQuestionIndexes.value.delete(idx);
  } else {
    selectedQuestionIndexes.value.add(idx);
  }
}

async function loadHistory() {
  loadingHistory.value = true;
  try {
    const res = await getGenerationHistory(1, 20);
    historyItems.value = res.data || [];
  } catch {
    // Non-blocking
  } finally {
    loadingHistory.value = false;
  }
}

function resumeHistorySession(session: GeneratedQuestionsHistory) {
  activeSession.value = session;
  if (session.status === 'ready_for_review') {
    selectedQuestionIndexes.value = new Set(parsedQuestions.value.map((_, i) => i));
    currentStep.value = 'review';
  } else if (session.status === 'pending') {
    currentStep.value = 'generating';
    startElapsedCounter();
    startPolling(session.id);
  } else {
    currentStep.value = 'review';
  }
}

function closeModal() {
  stopPolling();
  stopElapsedCounter();
  emit('update:modelValue', false);
}

async function resetToForm() {
  const sessionId = activeSession.value?.id;
  stopPolling();
  stopElapsedCounter();
  activeSession.value = null;
  selectedQuestionIndexes.value.clear();
  currentStep.value = 'form';

  if (sessionId) {
    try {
      await cancelGeneratedQuestions(sessionId);
    } catch {
      // Non-blocking
    }
  }
}

watch(
  () => props.modelValue,
  (val) => {
    if (val) {
      loadCourses();
      if (props.courseId) {
        selectedCourseId.value = props.courseId;
      }
    } else {
      stopPolling();
      stopElapsedCounter();
    }
  }
);

onMounted(() => {
  if (props.modelValue) {
    loadCourses();
  }
});

onBeforeUnmount(() => {
  stopPolling();
  stopElapsedCounter();
});
</script>

<template>
  <div
    v-if="modelValue"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm transition-opacity"
    @click.self="closeModal"
  >
    <div
      class="relative flex flex-col w-full max-w-3xl max-h-[90vh] rounded-2xl bg-surface shadow-2xl overflow-hidden ring-1 ring-border"
    >
      <!-- HEADER -->
      <div class="flex items-center justify-between border-b border-border px-6 py-4 shrink-0 bg-surface">
        <div>
          <h2 class="text-base font-semibold text-text">Generate Questions with AI</h2>
          <p v-if="courseName || examId" class="text-sm text-text/60 mt-0.5 flex items-center gap-1.5">
            <BookOpen class="h-4 w-4 text-text/40" />
            {{ courseName || 'Exam Paper' }}
          </p>
        </div>
        <div class="flex items-center gap-3">
          <button
            v-if="currentStep === 'form'"
            type="button"
            class="text-sm font-medium text-text/60 hover:text-text transition-colors flex items-center gap-1.5"
            @click="() => { currentStep = 'history'; loadHistory(); }"
          >
            <History class="h-4 w-4" />
            History
          </button>
          <button
            v-else-if="currentStep === 'history'"
            type="button"
            class="text-sm font-medium text-text/60 hover:text-text transition-colors flex items-center gap-1.5"
            @click="currentStep = 'form'"
          >
            <RotateCcw class="h-4 w-4" />
            Back
          </button>
          <button
            type="button"
            class="p-1 rounded-md text-text/40 hover:bg-bg hover:text-text transition-colors cursor-pointer"
            @click="closeModal"
          >
            <X class="h-5 w-5" />
          </button>
        </div>
      </div>

      <!-- BODY -->
      <div class="flex-1 overflow-y-auto bg-surface">
        
        <!-- STEP 1: FORM -->
        <div v-if="currentStep === 'form'" class="p-6 space-y-8">
          
          <!-- Mode Switcher -->
          <div class="flex p-1 bg-bg rounded-lg border border-border w-fit">
            <button
              type="button"
              class="px-4 py-1.5 text-sm font-medium rounded-md transition-colors cursor-pointer"
              :class="!isNoteMode ? 'bg-surface shadow-sm text-text' : 'text-text/60 hover:text-text'"
              @click="isNoteMode = false"
            >
              Topic / Objective
            </button>
            <button
              type="button"
              class="px-4 py-1.5 text-sm font-medium rounded-md transition-colors cursor-pointer"
              :class="isNoteMode ? 'bg-surface shadow-sm text-text' : 'text-text/60 hover:text-text'"
              @click="isNoteMode = true"
            >
              Lecture Notes / Text
            </button>
          </div>

          <!-- Prompt Area -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-text">
              {{ isNoteMode ? 'Source text' : 'Topic description' }}
            </label>
            <textarea
              v-if="!isNoteMode"
              v-model="topicInput"
              rows="4"
              class="w-full rounded-xl border border-border bg-surface px-4 py-3 text-sm text-text placeholder:text-text/40 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent resize-none transition-shadow"
              placeholder="e.g. Acid properties in database management systems..."
              @keydown.enter.ctrl="handleGenerate"
            />
            <textarea
              v-else
              v-model="noteContent"
              rows="6"
              class="w-full rounded-xl border border-border bg-surface px-4 py-3 text-sm text-text placeholder:text-text/40 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent resize-none transition-shadow"
              placeholder="Paste your lecture notes here..."
            />
          </div>

          <!-- Settings Grid -->
          <div
            class="grid grid-cols-1 gap-6"
            :class="courseId ? 'sm:grid-cols-3' : 'sm:grid-cols-2 lg:grid-cols-4'"
          >
            <!-- Course (if needed) -->
            <div v-if="!courseId" class="space-y-2">
              <label class="block text-sm font-medium text-text">Course</label>
              <select
                v-model="selectedCourseId"
                class="w-full h-10 rounded-lg border border-border bg-surface px-3 text-sm text-text focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent"
              >
                <option :value="null" disabled>Select course</option>
                <option v-for="c in courses" :key="c.id" :value="c.id">{{ c.label }}</option>
              </select>
            </div>

            <!-- Question Type -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-text">Question Type</label>
              <select
                v-model="selectedType"
                class="w-full h-10 rounded-lg border border-border bg-surface px-3 text-sm text-text focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent"
              >
                <option v-for="opt in typeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
              </select>
            </div>

            <!-- Difficulty -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-text">Difficulty</label>
              <div class="flex h-10 rounded-lg border border-border bg-bg p-1">
                <button
                  v-for="diff in difficultyOptions"
                  :key="diff.value"
                  type="button"
                  class="flex-1 rounded-md text-sm font-medium transition-colors cursor-pointer"
                  :class="selectedDifficulty === diff.value ? 'bg-surface shadow-sm text-text' : 'text-text/60 hover:text-text'"
                  @click="selectedDifficulty = diff.value"
                >
                  {{ diff.label }}
                </button>
              </div>
            </div>

            <!-- Count -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-text">Count</label>
              <div class="flex h-10 items-center rounded-lg border border-border bg-surface overflow-hidden">
                <button
                  type="button"
                  class="px-3 h-full text-text/60 hover:bg-bg hover:text-text transition-colors cursor-pointer"
                  @click="decrementCount"
                >
                  <Minus class="h-4 w-4" />
                </button>
                <input
                  type="number"
                  v-model="questionCount"
                  min="1"
                  class="w-full text-center text-sm font-medium text-text border-none focus:outline-none [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none bg-transparent"
                  @input="onCountInput"
                />
                <button
                  type="button"
                  class="px-3 h-full text-text/60 hover:bg-bg hover:text-text transition-colors cursor-pointer"
                  @click="incrementCount"
                >
                  <Plus class="h-4 w-4" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- STEP 2: CLAUDE-STYLE AI GENERATING ANIMATION -->
        <div v-else-if="currentStep === 'generating'" class="flex flex-col items-center justify-center py-20 px-6 space-y-7 text-center">
          
          <!-- Authentic Claude pulsing/morphing flower animation -->
          <div class="relative flex items-center justify-center">
            <!-- Radial ambient warm aura behind the icon -->
            <div class="claude-ambient-glow absolute h-24 w-24 rounded-full bg-accent/20 blur-xl pointer-events-none" />
            <div class="claude-ambient-glow-outer absolute h-36 w-36 rounded-full bg-accent/10 blur-2xl pointer-events-none" />

            <!-- Claude animated loading icon from IconScout asset -->
            <img
              :src="claudeLoadingGif"
              alt="Claude AI Loading"
              class="relative z-10 h-20 w-20 object-contain drop-shadow-md select-none pointer-events-none"
            />
          </div>

          <!-- Claude-style Thinking Pill & Step Text -->
          <div class="space-y-3 max-w-sm">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full border border-border/80 bg-bg/80 text-xs font-mono text-text/70 shadow-2xs">
              <span class="h-2 w-2 rounded-full bg-accent animate-ping opacity-75" />
              <span>Thinking • {{ formattedElapsed }}</span>
            </div>

            <div class="min-h-[1.5rem] flex items-center justify-center">
              <p class="text-sm font-medium text-text/80 transition-opacity duration-300">
                {{ thinkingStages[currentStageIdx] }}
              </p>
            </div>
          </div>

          <!-- Claude-style Streaming Shimmer Lines -->
          <div class="w-full max-w-xs space-y-2 pt-1">
            <div class="h-1.5 w-full rounded-full bg-border/40 overflow-hidden relative">
              <div class="claude-shimmer h-full w-full rounded-full bg-gradient-to-r from-transparent via-accent/50 to-transparent" />
            </div>
            <div class="h-1.5 w-4/5 mx-auto rounded-full bg-border/40 overflow-hidden relative">
              <div class="claude-shimmer h-full w-full rounded-full bg-gradient-to-r from-transparent via-accent/40 to-transparent" style="animation-delay: 0.35s;" />
            </div>
            <div class="h-1.5 w-3/5 mx-auto rounded-full bg-border/40 overflow-hidden relative">
              <div class="claude-shimmer h-full w-full rounded-full bg-gradient-to-r from-transparent via-accent/30 to-transparent" style="animation-delay: 0.7s;" />
            </div>
          </div>

          <!-- Cancel generation -->
          <div class="pt-1">
            <button
              type="button"
              class="text-xs text-text/45 hover:text-rose-500 transition-colors cursor-pointer"
              @click="handleCancel"
            >
              Cancel generation
            </button>
          </div>
        </div>

        <!-- STEP 3: REVIEW -->
        <div v-else-if="currentStep === 'review'" class="p-6 space-y-6">
          <div class="flex items-center justify-between">
            <h3 class="text-base font-semibold text-text">Review {{ parsedQuestions.length }} Questions</h3>
            <button
              type="button"
              class="text-sm font-medium text-accent hover:underline cursor-pointer"
              @click="toggleSelectAll"
            >
              {{ isAllSelected ? 'Deselect All' : 'Select All' }}
            </button>
          </div>

          <div class="space-y-4">
            <div
              v-for="(q, idx) in parsedQuestions"
              :key="idx"
              class="p-5 rounded-xl border transition-colors space-y-4"
              :class="selectedQuestionIndexes.has(idx) ? 'border-border bg-surface shadow-sm' : 'border-border/50 bg-bg opacity-70'"
            >
              <div class="flex items-start justify-between gap-4">
                <div class="flex items-start gap-3">
                  <button
                    type="button"
                    class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded border transition-colors cursor-pointer"
                    :class="selectedQuestionIndexes.has(idx) ? 'border-accent bg-accent text-white' : 'border-border bg-surface'"
                    @click="toggleSelectQuestion(idx)"
                  >
                    <Check v-if="selectedQuestionIndexes.has(idx)" class="h-3 w-3" />
                  </button>
                  <div class="space-y-1">
                    <div class="flex items-center gap-2">
                      <span class="text-xs font-medium text-text/60 uppercase">{{ q.type.replace('_', ' ') }}</span>
                      <span class="text-text/30">•</span>
                      <span class="text-xs font-medium text-text/60 uppercase">{{ q.difficulty }}</span>
                    </div>
                    <p class="text-sm font-medium text-text leading-relaxed">{{ q.content }}</p>
                  </div>
                </div>
                
                <button
                  type="button"
                  class="p-1.5 text-text/40 hover:text-rose-500 hover:bg-rose-50 rounded-md transition-colors shrink-0 cursor-pointer"
                  :disabled="deletingIdx === q.backendIndex"
                  @click="handleDeleteQuestion(q.backendIndex)"
                >
                  <Loader2 v-if="deletingIdx === q.backendIndex" class="h-4 w-4 animate-spin text-rose-500" />
                  <Trash2 v-else class="h-4 w-4" />
                </button>
              </div>

              <!-- Options -->
              <div v-if="q.options && q.options.length > 0" class="pl-8 grid grid-cols-1 sm:grid-cols-2 gap-2">
                <div
                  v-for="(opt, optIdx) in q.options"
                  :key="optIdx"
                  class="px-3 py-2 rounded-lg text-sm border transition-colors flex items-center justify-between"
                  :class="String(opt).trim().toLowerCase() === String(q.correct_answer).trim().toLowerCase()
                    ? 'border-emerald-200 bg-emerald-50 text-emerald-900 font-medium'
                    : 'border-border/60 bg-surface text-text/80'"
                >
                  <span>{{ opt }}</span>
                  <Check v-if="String(opt).trim().toLowerCase() === String(q.correct_answer).trim().toLowerCase()" class="h-4 w-4 text-emerald-600" />
                </div>
              </div>

              <!-- Short Answer -->
              <div v-else-if="q.correct_answer" class="pl-8">
                <div class="px-4 py-3 rounded-lg border border-border/60 bg-bg text-sm text-text">
                  <span class="block text-xs font-medium text-text/50 uppercase mb-1">Answer Key</span>
                  {{ q.correct_answer }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- STEP 4: HISTORY -->
        <div v-else-if="currentStep === 'history'" class="p-6">
          <div v-if="loadingHistory" class="flex justify-center py-12">
            <Loader2 class="h-6 w-6 text-text/40 animate-spin" />
          </div>
          <div v-else-if="historyItems.length === 0" class="text-center py-12 text-sm text-text/60">
            No past generations found.
          </div>
          <div v-else class="space-y-3">
            <button
              v-for="item in historyItems"
              :key="item.id"
              class="w-full flex items-center justify-between p-4 rounded-xl border border-border bg-surface hover:bg-bg transition-colors text-left cursor-pointer"
              @click="resumeHistorySession(item)"
            >
              <div class="space-y-1 pr-4 truncate">
                <p class="text-sm font-medium text-text truncate">{{ item.input }}</p>
                <div class="flex items-center gap-2 text-xs text-text/60">
                  <span>{{ item.created_at }}</span>
                  <span>•</span>
                  <span class="uppercase">{{ item.type }}</span>
                  <span>•</span>
                  <span>{{ item.total_rows }} questions</span>
                </div>
              </div>
              <div class="flex items-center gap-3 shrink-0">
                <BaseBadge
                  :variant="item.status === 'confirmed' ? 'success' : item.status === 'ready_for_review' ? 'info' : 'warning'"
                >
                  {{ item.status }}
                </BaseBadge>
                <ChevronRight class="h-4 w-4 text-text/40" />
              </div>
            </button>
          </div>
        </div>

      </div>

      <!-- FOOTER -->
      <div v-if="currentStep !== 'generating' && currentStep !== 'history'" class="border-t border-border px-6 py-4 bg-surface shrink-0 flex items-center justify-between">
        <div v-if="currentStep === 'review'">
          <button
            type="button"
            class="text-sm font-medium text-text/60 hover:text-text transition-colors cursor-pointer"
            @click="handleCancel"
          >
            Discard Batch
          </button>
        </div>
        <div v-else></div> <!-- Spacer for flex-between -->

        <div class="flex items-center gap-3">
          <BaseButton v-if="currentStep === 'review'" variant="secondary" @click="resetToForm">
            Adjust Prompt
          </BaseButton>
          <BaseButton v-else variant="secondary" @click="closeModal">
            Cancel
          </BaseButton>

          <BaseButton
            v-if="currentStep === 'form'"
            variant="primary"
            :loading="isSubmitting"
            :disabled="loadingCourses || (!topicInput.trim() && !noteContent.trim())"
            @click="handleGenerate"
          >
            Generate {{ questionCount }} Questions
          </BaseButton>
          
          <BaseButton
            v-else-if="currentStep === 'review'"
            variant="primary"
            :loading="isSubmitting"
            :disabled="selectedQuestionIndexes.size === 0"
            @click="handleConfirm"
          >
            {{ examId
              ? `Confirm & Attach (${selectedQuestionIndexes.size}) to Exam`
              : `Confirm & Save (${selectedQuestionIndexes.size}) Questions` }}
          </BaseButton>
        </div>
      </div>

    </div>
  </div>
</template>

<style scoped>
@keyframes claudePulse {
  0%, 100% {
    transform: scale(0.95) rotate(0deg);
  }
  50% {
    transform: scale(1.08) rotate(12deg);
  }
}

@keyframes claudeGlow {
  0%, 100% {
    transform: scale(0.85);
    opacity: 0.25;
    filter: blur(14px);
  }
  50% {
    transform: scale(1.2);
    opacity: 0.55;
    filter: blur(22px);
  }
}

@keyframes claudeGlowOuter {
  0%, 100% {
    transform: scale(0.9);
    opacity: 0.1;
    filter: blur(20px);
  }
  50% {
    transform: scale(1.3);
    opacity: 0.25;
    filter: blur(30px);
  }
}

@keyframes claudeShimmer {
  0% {
    transform: translateX(-100%);
  }
  100% {
    transform: translateX(100%);
  }
}

.claude-star {
  animation: claudePulse 3.6s ease-in-out infinite;
}

.claude-ambient-glow {
  animation: claudeGlow 3.6s ease-in-out infinite;
}

.claude-ambient-glow-outer {
  animation: claudeGlowOuter 3.6s ease-in-out infinite;
}

.claude-shimmer {
  animation: claudeShimmer 2.2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
