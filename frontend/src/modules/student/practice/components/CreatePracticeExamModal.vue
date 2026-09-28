<script setup lang="ts">
import { ref, computed, watch, onBeforeUnmount } from 'vue';
import { useRouter } from 'vue-router';
import {
  Sparkles,
  X,
  Plus,
  Minus,
  ArrowRight,
  RotateCcw,
  Clock,
  Layers,
  HelpCircle,
  Check,
  Loader2,
  Trash2,
  CheckCircle2,
  BookOpen,
} from 'lucide-vue-next';

import BaseButton from '@/shared/components/ui/BaseButton.vue';
import BaseBadge from '@/shared/components/ui/BaseBadge.vue';
import claudeLoadingGif from '@/assets/claude-loading.gif';
import { useUiStore } from '@/stores/ui';
import { handleApiError } from '@/shared/utils/apiError';

import {
  createPracticeExam,
  generatePracticeQuestions,
  getPracticeHistory,
  confirmPracticeQuestions,
  deleteGeneratedPracticeQuestion,
  startPracticeExam,
} from '../api/practice';
import type {
  PracticeDifficulty,
  PracticeQuestionType,
  PracticeQuestionHistory,
  PracticeExam,
} from '../types/practice';

const props = defineProps<{
  modelValue: boolean;
}>();

const emit = defineEmits<{
  (e: 'update:modelValue', val: boolean): void;
  (e: 'created', exam: PracticeExam): void;
}>();

const router = useRouter();
const uiStore = useUiStore();

// Navigation steps inside modal: 'form' | 'generating' | 'review'
const currentStep = ref<'form' | 'generating' | 'review'>('form');

// Form configuration
const examTitle = ref('');
const topicMode = ref<'topic' | 'notes'>('topic');
const topicInput = ref('');
const notesContent = ref('');
const questionCount = ref(8);
const selectedType = ref<PracticeQuestionType>('mcq');
const selectedDifficulty = ref<PracticeDifficulty>('medium');
const durationMinutes = ref(15);

// Loading & Generation tracking
const isSubmitting = ref(false);
const activeExam = ref<PracticeExam | null>(null);
const activeHistory = ref<PracticeQuestionHistory | null>(null);
const pollTimer = ref<any>(null);
const elapsedSeconds = ref(0);
const elapsedTimer = ref<any>(null);
const deletingIdx = ref<number | null>(null);

const stages = [
  'Analyzing syllabus concepts & learning objectives...',
  'Generating realistic scenario-based question stems...',
  'Calibrating distractor options & conceptual explanations...',
  'Formatting and assembling interactive practice test...',
];
const currentStage = ref(0);

const formattedElapsed = computed(() => {
  const mins = Math.floor(elapsedSeconds.value / 60);
  const secs = elapsedSeconds.value % 60;
  return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
});

const isFormValid = computed(() => {
  const hasPrompt = topicMode.value === 'topic' ? topicInput.value.trim().length >= 3 : notesContent.value.trim().length >= 10;
  return examTitle.value.trim().length >= 3 && hasPrompt && questionCount.value >= 1;
});

function adjustCount(delta: number) {
  const next = questionCount.value + delta;
  if (next >= 1 && next <= 25) {
    questionCount.value = next;
  }
}

function adjustDuration(delta: number) {
  const next = durationMinutes.value + delta;
  if (next >= 5 && next <= 180) {
    durationMinutes.value = next;
  }
}

function startTimers() {
  elapsedSeconds.value = 0;
  currentStage.value = 0;
  clearInterval(elapsedTimer.value);
  elapsedTimer.value = setInterval(() => {
    elapsedSeconds.value++;
    if (elapsedSeconds.value % 7 === 0 && currentStage.value < stages.length - 1) {
      currentStage.value++;
    }
  }, 1000);
}

function stopTimers() {
  clearInterval(elapsedTimer.value);
  clearInterval(pollTimer.value);
  elapsedTimer.value = null;
  pollTimer.value = null;
}

async function handleStartGeneration() {
  if (!isFormValid.value) return;

  isSubmitting.value = true;
  try {
    // 1. Create Practice Exam container
    const countType = selectedType.value === 'true_false' ? 'TRUE_FALSE' : 'MCQ';
    const examRes = await createPracticeExam({
      title: examTitle.value.trim(),
      duration_minutes: durationMinutes.value,
      composition: {
        [countType]: {
          count: questionCount.value,
          marks_each: 1,
        },
      },
    });

    activeExam.value = examRes.data;

    // 2. Submit AI Question Generation
    currentStep.value = 'generating';
    startTimers();

    const genRes = await generatePracticeQuestions(activeExam.value.id, {
      input: topicMode.value === 'topic' ? topicInput.value.trim() : notesContent.value.trim(),
      count: questionCount.value,
      is_note: topicMode.value === 'notes',
      type: selectedType.value,
      difficulty: selectedDifficulty.value,
    });

    activeHistory.value = genRes.data;

    // 3. Poll for ready_for_review
    pollTimer.value = setInterval(async () => {
      try {
        if (!activeHistory.value) return;
        const checkRes = await getPracticeHistory(activeHistory.value.id);
        activeHistory.value = checkRes.data;

        if (activeHistory.value.status === 'ready_for_review') {
          stopTimers();
          currentStep.value = 'review';
        } else if (activeHistory.value.status === 'failed') {
          stopTimers();
          uiStore.showToast('Question generation failed. Please try a different topic prompt.', 'error');
          currentStep.value = 'form';
        }
      } catch (err) {
        stopTimers();
        handleApiError(err, uiStore, undefined, 'Error checking generation status');
        currentStep.value = 'form';
      }
    }, 2000);
  } catch (error) {
    stopTimers();
    currentStep.value = 'form';
    handleApiError(error, uiStore, undefined, 'Failed to initialize practice exam');
  } finally {
    isSubmitting.value = false;
  }
}

async function handleDeleteQuestion(idx: number) {
  if (!activeHistory.value) return;
  deletingIdx.value = idx;
  try {
    const res = await deleteGeneratedPracticeQuestion(activeHistory.value.id, idx);
    activeHistory.value = res.data;
    uiStore.showToast('Question removed from batch', 'success');
  } catch (error) {
    handleApiError(error, uiStore, undefined, 'Failed to remove question');
  } finally {
    deletingIdx.value = null;
  }
}

async function handleConfirmAndStart() {
  if (!activeHistory.value || !activeExam.value) return;
  isSubmitting.value = true;
  try {
    await confirmPracticeQuestions(activeHistory.value.id);
    await startPracticeExam(activeExam.value.id);

    uiStore.showToast('Practice Exam ready! Starting session...', 'success');
    emit('created', activeExam.value);
    emit('update:modelValue', false);

    router.push({
      name: 'student.practice.take',
      params: { practiceExamId: activeExam.value.id },
    });
  } catch (error) {
    handleApiError(error, uiStore, undefined, 'Failed to confirm questions');
  } finally {
    isSubmitting.value = false;
  }
}

function closeModal() {
  stopTimers();
  emit('update:modelValue', false);
}

watch(
  () => props.modelValue,
  (val) => {
    if (val) {
      currentStep.value = 'form';
      examTitle.value = '';
      topicInput.value = '';
      notesContent.value = '';
      questionCount.value = 8;
      selectedType.value = 'mcq';
      selectedDifficulty.value = 'medium';
      durationMinutes.value = 15;
    } else {
      stopTimers();
    }
  }
);

onBeforeUnmount(() => {
  stopTimers();
});
</script>

<template>
  <div v-if="modelValue" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs">
    <div class="relative w-full max-w-2xl bg-surface border border-border rounded-2xl shadow-xl overflow-hidden flex flex-col max-h-[92vh] animate-in fade-in zoom-in-95 duration-200">
      <!-- Modal Header -->
      <div class="flex items-center justify-between px-6 py-4 border-b border-border bg-surface shrink-0">
        <div class="flex items-center gap-2.5">
          <div class="w-8 h-8 rounded-xl bg-accent/10 flex items-center justify-center text-accent">
            <Sparkles class="w-4 h-4" />
          </div>
          <div>
            <h2 class="text-base font-bold text-text font-display">
              {{ currentStep === 'form' ? 'Create Practice Exam' : currentStep === 'generating' ? 'Generating Questions' : 'Review Question Deck' }}
            </h2>
            <p class="text-xs text-text/50">
              {{ currentStep === 'form' ? 'Configure topic, question types, and target duration' : currentStep === 'generating' ? 'AI Curriculum Tutor is synthesizing questions' : 'Review and prune questions before taking the exam' }}
            </p>
          </div>
        </div>

        <button
          type="button"
          class="w-8 h-8 rounded-lg border border-border text-text/60 hover:text-text hover:bg-bg flex items-center justify-center transition-colors cursor-pointer"
          @click="closeModal">
          <X class="w-4 h-4" />
        </button>
      </div>

      <!-- Modal Body (Step 1: Form) -->
      <div v-if="currentStep === 'form'" class="p-6 overflow-y-auto space-y-5">
        <!-- Exam Title -->
        <div class="space-y-1.5">
          <label class="block text-xs font-semibold text-text uppercase tracking-wider font-mono">Exam Title</label>
          <input
            v-model="examTitle"
            type="text"
            placeholder="e.g., Relational Database Normalization & Indexing"
            class="w-full bg-surface border border-border rounded-xl px-3.5 py-2.5 text-sm text-text placeholder:text-text/40 focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent transition-colors" />
        </div>

        <!-- Mode Toggle (Topic vs Lecture Notes) -->
        <div class="space-y-1.5">
          <label class="block text-xs font-semibold text-text uppercase tracking-wider font-mono">Input Source</label>
          <div class="grid grid-cols-2 gap-2 bg-bg p-1 rounded-xl border border-border">
            <button
              type="button"
              class="py-1.5 text-xs font-medium rounded-lg transition-all cursor-pointer"
              :class="topicMode === 'topic' ? 'bg-surface text-accent font-semibold shadow-2xs border border-border' : 'text-text/60 hover:text-text'"
              @click="topicMode = 'topic'">
              Conceptual Topic &amp; Keywords
            </button>
            <button
              type="button"
              class="py-1.5 text-xs font-medium rounded-lg transition-all cursor-pointer"
              :class="topicMode === 'notes' ? 'bg-surface text-accent font-semibold shadow-2xs border border-border' : 'text-text/60 hover:text-text'"
              @click="topicMode = 'notes'">
              Paste Lecture / Text Notes
            </button>
          </div>
        </div>

        <!-- Topic or Notes Textarea -->
        <div v-if="topicMode === 'topic'" class="space-y-1.5">
          <div class="flex items-center justify-between text-xs text-text/60 font-mono">
            <span>Topic Prompt / Key Concepts</span>
            <span :class="topicInput.length < 3 ? 'text-text/40' : 'text-success'">{{ topicInput.length }} chars</span>
          </div>
          <textarea
            v-model="topicInput"
            rows="3"
            placeholder="e.g., B-Trees vs B+ Trees, 3NF vs BCNF anomalies, ACID properties and isolation levels in PostgreSQL"
            class="w-full bg-surface border border-border rounded-xl p-3 text-sm text-text placeholder:text-text/40 focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent transition-colors resize-none" />
        </div>
        <div v-else class="space-y-1.5">
          <div class="flex items-center justify-between text-xs text-text/60 font-mono">
            <span>Lecture Notes / Text Snippet</span>
            <span :class="notesContent.length < 10 ? 'text-text/40' : 'text-success'">{{ notesContent.length }} chars</span>
          </div>
          <textarea
            v-model="notesContent"
            rows="5"
            placeholder="Paste excerpts from course slides, textbook summaries, or lecture notes..."
            class="w-full bg-surface border border-border rounded-xl p-3 text-sm text-text placeholder:text-text/40 focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent transition-colors resize-none" />
        </div>

        <!-- Parameters Grid (Questions Count, Type, Difficulty, Duration) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
          <!-- Question Count Stepper -->
          <div class="bg-surface border border-border rounded-xl p-3.5 space-y-2">
            <span class="text-xs font-semibold text-text uppercase tracking-wider font-mono">Number of Questions</span>
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <button
                  type="button"
                  class="w-8 h-8 rounded-lg border border-border bg-bg hover:bg-surface flex items-center justify-center text-text/70 hover:text-text cursor-pointer disabled:opacity-30"
                  :disabled="questionCount <= 1"
                  @click="adjustCount(-1)">
                  <Minus class="w-3.5 h-3.5" />
                </button>
                <span class="font-mono text-base font-bold text-text w-8 text-center">{{ questionCount }}</span>
                <button
                  type="button"
                  class="w-8 h-8 rounded-lg border border-border bg-bg hover:bg-surface flex items-center justify-center text-text/70 hover:text-text cursor-pointer disabled:opacity-30"
                  :disabled="questionCount >= 25"
                  @click="adjustCount(1)">
                  <Plus class="w-3.5 h-3.5" />
                </button>
              </div>

              <div class="flex gap-1">
                <button
                  v-for="q in [5, 10, 15]"
                  :key="q"
                  type="button"
                  class="px-2 py-0.5 rounded text-[11px] font-mono border transition-colors cursor-pointer"
                  :class="questionCount === q ? 'bg-accent text-white border-accent' : 'bg-bg border-border text-text/60 hover:text-text'"
                  @click="questionCount = q">
                  {{ q }}
                </button>
              </div>
            </div>
          </div>

          <!-- Duration Stepper -->
          <div class="bg-surface border border-border rounded-xl p-3.5 space-y-2">
            <span class="text-xs font-semibold text-text uppercase tracking-wider font-mono">Duration (Minutes)</span>
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <button
                  type="button"
                  class="w-8 h-8 rounded-lg border border-border bg-bg hover:bg-surface flex items-center justify-center text-text/70 hover:text-text cursor-pointer disabled:opacity-30"
                  :disabled="durationMinutes <= 5"
                  @click="adjustDuration(-5)">
                  <Minus class="w-3.5 h-3.5" />
                </button>
                <span class="font-mono text-base font-bold text-text w-12 text-center">{{ durationMinutes }}m</span>
                <button
                  type="button"
                  class="w-8 h-8 rounded-lg border border-border bg-bg hover:bg-surface flex items-center justify-center text-text/70 hover:text-text cursor-pointer disabled:opacity-30"
                  :disabled="durationMinutes >= 180"
                  @click="adjustDuration(5)">
                  <Plus class="w-3.5 h-3.5" />
                </button>
              </div>

              <div class="flex gap-1">
                <button
                  v-for="d in [10, 15, 30]"
                  :key="d"
                  type="button"
                  class="px-2 py-0.5 rounded text-[11px] font-mono border transition-colors cursor-pointer"
                  :class="durationMinutes === d ? 'bg-accent text-white border-accent' : 'bg-bg border-border text-text/60 hover:text-text'"
                  @click="durationMinutes = d">
                  {{ d }}m
                </button>
              </div>
            </div>
          </div>

          <!-- Question Type Pills -->
          <div class="bg-surface border border-border rounded-xl p-3.5 space-y-2">
            <span class="text-xs font-semibold text-text uppercase tracking-wider font-mono">Format</span>
            <div class="grid grid-cols-2 gap-2">
              <button
                type="button"
                class="px-3 py-1.5 rounded-lg text-xs font-medium border text-center transition-all cursor-pointer"
                :class="selectedType === 'mcq' ? 'bg-accent text-white border-accent shadow-2xs font-semibold' : 'bg-bg border-border text-text/70 hover:text-text'"
                @click="selectedType = 'mcq'">
                Multiple Choice
              </button>
              <button
                type="button"
                class="px-3 py-1.5 rounded-lg text-xs font-medium border text-center transition-all cursor-pointer"
                :class="selectedType === 'true_false' ? 'bg-accent text-white border-accent shadow-2xs font-semibold' : 'bg-bg border-border text-text/70 hover:text-text'"
                @click="selectedType = 'true_false'">
                True / False
              </button>
            </div>
          </div>

          <!-- Difficulty Pills -->
          <div class="bg-surface border border-border rounded-xl p-3.5 space-y-2">
            <span class="text-xs font-semibold text-text uppercase tracking-wider font-mono">Difficulty</span>
            <div class="grid grid-cols-3 gap-1.5">
              <button
                type="button"
                class="py-1.5 rounded-lg text-xs font-medium border text-center transition-all cursor-pointer"
                :class="selectedDifficulty === 'easy' ? 'bg-success text-white border-success font-semibold shadow-2xs' : 'bg-bg border-border text-text/70 hover:text-text'"
                @click="selectedDifficulty = 'easy'">
                Easy
              </button>
              <button
                type="button"
                class="py-1.5 rounded-lg text-xs font-medium border text-center transition-all cursor-pointer"
                :class="selectedDifficulty === 'medium' ? 'bg-warning text-white border-warning font-semibold shadow-2xs' : 'bg-bg border-border text-text/70 hover:text-text'"
                @click="selectedDifficulty = 'medium'">
                Medium
              </button>
              <button
                type="button"
                class="py-1.5 rounded-lg text-xs font-medium border text-center transition-all cursor-pointer"
                :class="selectedDifficulty === 'hard' ? 'bg-error text-white border-error font-semibold shadow-2xs' : 'bg-bg border-border text-text/70 hover:text-text'"
                @click="selectedDifficulty = 'hard'">
                Hard
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal Body (Step 2: AI Generating Screen) -->
      <div v-else-if="currentStep === 'generating'" class="flex flex-col items-center justify-center py-16 px-6 text-center space-y-6">
        <div class="relative flex items-center justify-center">
          <div class="absolute h-28 w-28 rounded-full bg-accent/20 blur-xl pointer-events-none" />
          <video
            autoplay
            loop
            muted
            playsinline
            class="relative z-10 h-24 w-24 object-contain rounded-full drop-shadow-md">
            <source src="@/assets/claude-loading.mp4" type="video/mp4" />
            <img :src="claudeLoadingGif" alt="AI Generating" class="h-24 w-24 object-contain" />
          </video>
        </div>

        <div class="space-y-2 max-w-sm">
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full border border-border bg-bg text-xs font-mono text-text/70 shadow-2xs">
            <span class="h-2 w-2 rounded-full bg-accent animate-ping opacity-75" />
            <span>AI Curriculum Engine • {{ formattedElapsed }}</span>
          </div>
          <p class="text-sm font-medium text-text transition-opacity duration-300">
            {{ stages[currentStage] }}
          </p>
        </div>

        <div class="w-full max-w-xs space-y-2 pt-2">
          <div class="h-1.5 w-full rounded-full bg-border/40 overflow-hidden relative">
            <div class="h-full w-full rounded-full bg-gradient-to-r from-transparent via-accent/60 to-transparent animate-pulse" />
          </div>
        </div>
      </div>

      <!-- Modal Body (Step 3: Review Questions) -->
      <div v-else-if="currentStep === 'review'" class="p-6 overflow-y-auto space-y-4 max-h-[60vh]">
        <div class="flex items-center justify-between border-b border-border pb-3">
          <div class="flex items-center gap-2 text-xs font-mono text-text/70">
            <CheckCircle2 class="w-4 h-4 text-success" />
            <span>{{ activeHistory?.validated_question?.length || 0 }} Questions Generated</span>
          </div>
          <span class="text-xs text-text/50">Discard any questions you prefer not to practice</span>
        </div>

        <div class="space-y-3">
          <article
            v-for="(q, idx) in activeHistory?.validated_question || []"
            :key="idx"
            class="bg-surface border border-border rounded-xl p-4 shadow-2xs space-y-2.5 relative group">
            <div class="flex items-start justify-between gap-3">
              <div class="flex items-center gap-2 text-xs font-mono">
                <span class="font-bold text-accent">Q{{ idx + 1 }}</span>
                <span class="text-border">·</span>
                <span class="uppercase text-text/50">{{ q.type || selectedType }}</span>
                <span class="text-border">·</span>
                <span class="capitalize text-text/50">{{ q.difficulty || selectedDifficulty }}</span>
              </div>

              <button
                type="button"
                class="text-text/40 hover:text-error transition-colors p-1 rounded-md hover:bg-error/10 cursor-pointer"
                :disabled="deletingIdx === idx"
                title="Remove question"
                @click="handleDeleteQuestion(idx)">
                <Loader2 v-if="deletingIdx === idx" class="w-3.5 h-3.5 animate-spin text-error" />
                <Trash2 v-else class="w-3.5 h-3.5" />
              </button>
            </div>

            <p class="text-sm font-medium text-text font-display leading-snug">
              {{ q.content }}
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1 text-xs">
              <div
                v-for="(opt, optIdx) in q.options || []"
                :key="optIdx"
                class="p-2 rounded-lg border flex items-center gap-2 font-mono"
                :class="opt.is_correct ? 'border-success/40 bg-success/10 text-success font-semibold' : 'border-border bg-bg text-text/70'">
                <span class="w-5 h-5 rounded-full bg-surface border border-border flex items-center justify-center text-[10px] shrink-0 font-bold">
                  {{ String.fromCharCode(65 + optIdx) }}
                </span>
                <span class="truncate">{{ opt.option_text }}</span>
                <Check v-if="opt.is_correct" class="w-3.5 h-3.5 text-success ml-auto shrink-0" />
              </div>
            </div>
          </article>
        </div>
      </div>

      <!-- Modal Footer Actions -->
      <div class="px-6 py-4 border-t border-border bg-surface flex items-center justify-between shrink-0">
        <BaseButton variant="secondary" @click="closeModal">
          Cancel
        </BaseButton>

        <div v-if="currentStep === 'form'">
          <BaseButton
            variant="primary"
            class="font-semibold shadow-xs flex items-center gap-1.5"
            :disabled="!isFormValid || isSubmitting"
            :loading="isSubmitting"
            @click="handleStartGeneration">
            <Sparkles class="w-4 h-4" />
            <span>Generate Practice Test</span>
          </BaseButton>
        </div>

        <div v-else-if="currentStep === 'review'">
          <BaseButton
            variant="primary"
            class="font-semibold shadow-xs flex items-center gap-1.5"
            :loading="isSubmitting"
            @click="handleConfirmAndStart">
            <span>Begin Practice Exam</span>
            <ArrowRight class="w-4 h-4" />
          </BaseButton>
        </div>
      </div>
    </div>
  </div>
</template>
