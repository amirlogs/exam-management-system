<script setup lang="ts">
import { ref, computed, watch, onUnmounted } from 'vue';
import {
  Sparkles,
  Send,
  Loader2,
  Copy,
  Check,
  RotateCcw,
  CornerDownRight,
  MessageSquare,
} from 'lucide-vue-next';
import { marked } from 'marked';
import { getQuestionGuidance, askQuestionGuidance } from '../api/practice';
import type { PracticeQuestion, QuestionGuidance } from '../types/practice';
import { useUiStore } from '@/stores/ui';
import { handleApiError } from '@/shared/utils/apiError';

// Configure marked options for clean line-breaks and GitHub flavored markdown
marked.setOptions({
  breaks: true,
  gfm: true,
});

const props = defineProps<{
  examId: number;
  question: PracticeQuestion;
  userAnswer?: {
    option_id: number;
    is_correct: boolean;
    correct_option_id?: number;
  } | null;
  isAnswered: boolean;
}>();

const uiStore = useUiStore();

const guidances = ref<QuestionGuidance[]>([]);
const loading = ref(false);
const submitting = ref(false);
const initialCustomPrompt = ref('');
const followUpInput = ref('');
const copiedId = ref<number | null>(null);

let pollInterval: any = null;

// Initial guidance (first interaction)
const initialGuidance = computed(() => {
  return guidances.value[0] || null;
});

// Follow-up Q&A items
const followUpGuidances = computed(() => {
  return guidances.value.slice(1);
});

const isGeneratingAny = computed(() => {
  return guidances.value.some((g) => g.response === null);
});

async function fetchGuidance(silent = false) {
  if (!props.question?.id) return;
  if (!silent) loading.value = true;
  try {
    const res = await getQuestionGuidance(props.examId, props.question.id);
    guidances.value = res.data || [];

    if (isGeneratingAny.value) {
      startPolling();
    } else {
      stopPolling();
    }
  } catch (error) {
    if (!silent) {
      handleApiError(error, uiStore, undefined, 'Failed to load question explanation');
    }
  } finally {
    if (!silent) loading.value = false;
  }
}

function startPolling() {
  if (pollInterval) return;
  let attempts = 0;
  const maxAttempts = 25; // ~35s

  pollInterval = setInterval(async () => {
    attempts++;
    if (!props.question?.id) {
      stopPolling();
      return;
    }
    try {
      const res = await getQuestionGuidance(props.examId, props.question.id);
      guidances.value = res.data || [];

      const stillWaiting = guidances.value.some((g) => g.response === null);
      if (!stillWaiting || attempts >= maxAttempts) {
        stopPolling();
        if (attempts >= maxAttempts && stillWaiting) {
          uiStore.showToast('Explanation is taking longer than usual. Feel free to refresh.', 'warning');
        }
      }
    } catch {
      if (attempts >= maxAttempts) {
        stopPolling();
      }
    }
  }, 1500);
}

function stopPolling() {
  if (pollInterval) {
    clearInterval(pollInterval);
    pollInterval = null;
  }
}

// 1. Initial "Explain Why" button click
async function handleExplainWhy() {
  if (submitting.value || isGeneratingAny.value) return;

  const correctOpt = props.question.options.find((o) => o.is_correct);
  const selectedOpt = props.question.options.find((o) => o.id === props.userAnswer?.option_id);

  let prompt = '';
  if (props.userAnswer?.is_correct) {
    prompt = `Explain why "${correctOpt?.option_text || 'the selected answer'}" is the correct answer for this question and break down the underlying concepts in detail.`;
  } else if (selectedOpt && correctOpt) {
    prompt = `I selected "${selectedOpt.option_text}", but the correct answer is "${correctOpt.option_text}". Please explain why my choice is incorrect, why "${correctOpt.option_text}" is correct, and explain the key concept.`;
  } else {
    prompt = `Please explain the correct answer for this question: "${props.question.content}" and break down the underlying concept.`;
  }

  await submitGuidancePrompt(prompt);
}

// 2. Initial custom question submission
async function handleInitialCustomPrompt() {
  const text = initialCustomPrompt.value.trim();
  if (!text || submitting.value || isGeneratingAny.value) return;

  initialCustomPrompt.value = '';
  await submitGuidancePrompt(text);
}

// 3. Follow-up question submission
async function handleSendFollowUp() {
  const text = followUpInput.value.trim();
  if (!text || submitting.value || isGeneratingAny.value) return;

  followUpInput.value = '';
  await submitGuidancePrompt(text);
}

// Common submit helper
async function submitGuidancePrompt(promptText: string) {
  submitting.value = true;
  const tempId = Date.now();
  const tempItem: QuestionGuidance = {
    id: tempId,
    user_id: 0,
    student_id: 0,
    practice_exam_id: props.examId,
    practice_question_id: props.question.id,
    prompt: promptText,
    response: null,
    created_at: 'Just now',
  };
  guidances.value.push(tempItem);

  try {
    const res = await askQuestionGuidance(props.examId, props.question.id, { prompt: promptText });
    const idx = guidances.value.findIndex((g) => g.id === tempId);
    if (idx !== -1) {
      guidances.value[idx] = res.data;
    }
    startPolling();
  } catch (error) {
    guidances.value = guidances.value.filter((g) => g.id !== tempId);
    handleApiError(error, uiStore, undefined, 'Failed to generate guidance');
  } finally {
    submitting.value = false;
  }
}

function copyText(text: string, id: number) {
  navigator.clipboard.writeText(text);
  copiedId.value = id;
  setTimeout(() => {
    copiedId.value = null;
  }, 2000);
}

function renderMarkdown(text: string | null) {
  if (!text) return '';
  return marked.parse(text);
}

watch(
  () => props.question?.id,
  () => {
    stopPolling();
    guidances.value = [];
    initialCustomPrompt.value = '';
    followUpInput.value = '';
    if (props.isAnswered) {
      fetchGuidance();
    }
  },
  { immediate: true }
);

watch(
  () => props.isAnswered,
  (val) => {
    if (val && guidances.value.length === 0) {
      fetchGuidance();
    }
  }
);

onUnmounted(() => {
  stopPolling();
});
</script>

<template>
  <div v-if="isAnswered" class="mt-4 pt-1">
    <!-- State 1: Before any explanation is generated -> Offer both "Explain Why" and Custom Question -->
    <div
      v-if="!loading && guidances.length === 0"
      class="rounded-xl border border-accent/25 bg-surface p-4.5 space-y-3.5 shadow-2xs transition-colors">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center gap-2.5">
          <div class="w-8 h-8 rounded-lg bg-accent/10 text-accent flex items-center justify-center shrink-0">
            <Sparkles class="w-4 h-4" />
          </div>
          <div>
            <h5 class="text-xs font-bold text-text font-display">
              {{ userAnswer?.is_correct ? 'Want to understand why this is correct?' : 'Understand why this is the answer' }}
            </h5>
            <p class="text-[11px] text-text/60">
              Get an instant conceptual explanation or ask a specific question.
            </p>
          </div>
        </div>

        <button
          type="button"
          class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-lg bg-accent text-white hover:bg-accent-hover text-xs font-semibold shadow-2xs transition-colors cursor-pointer shrink-0 disabled:opacity-50"
          :disabled="submitting"
          @click="handleExplainWhy">
          <Loader2 v-if="submitting" class="w-3.5 h-3.5 animate-spin" />
          <Sparkles v-else class="w-3.5 h-3.5" />
          <span>Explain Why</span>
        </button>
      </div>

      <!-- Or Ask Custom Question inline -->
      <div class="pt-3 border-t border-border/70 flex items-center gap-2">
        <input
          v-model="initialCustomPrompt"
          type="text"
          placeholder="Or ask a specific question about this problem..."
          class="flex-1 bg-bg border border-border rounded-lg px-3 py-1.5 text-xs text-text placeholder:text-text/40 focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent transition-colors"
          :disabled="submitting"
          @keyup.enter="handleInitialCustomPrompt" />

        <button
          type="button"
          class="px-3 py-1.5 rounded-lg border border-border bg-surface hover:border-accent/50 hover:text-accent text-xs font-semibold transition-colors cursor-pointer shrink-0 disabled:opacity-40 flex items-center gap-1"
          :disabled="!initialCustomPrompt.trim() || submitting"
          @click="handleInitialCustomPrompt">
          <Loader2 v-if="submitting" class="w-3.5 h-3.5 animate-spin" />
          <Send v-else class="w-3.5 h-3.5" />
          <span class="hidden sm:inline">Ask</span>
        </button>
      </div>
    </div>

    <!-- State 2: Initial loading -->
    <div
      v-else-if="loading && guidances.length === 0"
      class="rounded-xl border border-border bg-surface p-6 text-center space-y-2">
      <Loader2 class="w-6 h-6 animate-spin text-accent mx-auto" />
      <p class="text-xs font-mono text-text/50">Loading question explanation...</p>
    </div>

    <!-- State 3: Rendered Explanation Card & Follow-up Q&A -->
    <div
      v-else
      class="rounded-xl border border-border bg-surface shadow-2xs overflow-hidden transition-all duration-200">
      <!-- Card Header -->
      <div class="px-5 py-3 border-b border-border bg-bg/50 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <div class="w-6 h-6 rounded-md bg-accent/10 text-accent flex items-center justify-center shrink-0">
            <Sparkles class="w-3.5 h-3.5" />
          </div>
          <span class="text-xs font-bold text-text font-display">AI Conceptual Explanation</span>
        </div>

        <div class="flex items-center gap-1.5">
          <button
            type="button"
            class="p-1 rounded text-text/40 hover:text-text transition-colors cursor-pointer"
            title="Refresh explanation"
            @click="fetchGuidance(false)">
            <RotateCcw class="w-3.5 h-3.5" :class="{ 'animate-spin': loading }" />
          </button>
          <button
            v-if="initialGuidance?.response"
            type="button"
            class="p-1 rounded text-text/40 hover:text-text transition-colors cursor-pointer"
            title="Copy explanation text"
            @click="copyText(initialGuidance.response, initialGuidance.id)">
            <Check v-if="copiedId === initialGuidance.id" class="w-3.5 h-3.5 text-success" />
            <Copy v-else class="w-3.5 h-3.5" />
          </button>
        </div>
      </div>

      <!-- Card Content -->
      <div class="p-5 space-y-4">
        <!-- Initial Guidance Response -->
        <div v-if="initialGuidance">
          <!-- Rendered Markdown Explanation -->
          <div
            v-if="initialGuidance.response"
            class="guidance-markdown text-xs sm:text-sm select-text"
            v-html="renderMarkdown(initialGuidance.response)" />

          <!-- Generating shimmer -->
          <div v-else class="space-y-2.5 py-1 animate-pulse">
            <div class="flex items-center gap-2 text-accent text-xs font-mono font-medium">
              <Loader2 class="w-3.5 h-3.5 animate-spin" />
              <span>Analyzing question &amp; preparing explanation...</span>
            </div>
            <div class="h-3 bg-text/10 rounded w-full" />
            <div class="h-3 bg-text/10 rounded w-5/6" />
            <div class="h-3 bg-text/10 rounded w-3/4" />
          </div>
        </div>

        <!-- Follow-up Q&A Thread (if any) -->
        <div v-if="followUpGuidances.length > 0" class="pt-3 border-t border-border/70 space-y-3">
          <div class="flex items-center gap-1.5 text-xs font-mono font-semibold text-text/60">
            <MessageSquare class="w-3.5 h-3.5 text-accent" />
            <span>Follow-up Q&amp;A</span>
          </div>

          <div
            v-for="item in followUpGuidances"
            :key="item.id"
            class="rounded-lg bg-bg/80 border border-border/80 p-3.5 space-y-2 text-xs">
            <!-- Student Prompt -->
            <div class="flex items-start gap-2 font-medium text-text">
              <CornerDownRight class="w-3.5 h-3.5 mt-0.5 text-accent shrink-0" />
              <span>{{ item.prompt }}</span>
            </div>

            <!-- Follow-up Response -->
            <div class="pl-5 pt-1 border-l-2 border-accent/30 select-text">
              <div
                v-if="item.response"
                class="guidance-markdown text-xs"
                v-html="renderMarkdown(item.response)" />
              <div v-else class="flex items-center gap-2 text-accent text-xs font-mono animate-pulse">
                <Loader2 class="w-3 h-3 animate-spin" />
                <span>Formulating response...</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Follow-up Input Bar -->
        <div v-if="initialGuidance?.response" class="pt-3 border-t border-border/70">
          <div class="flex items-center gap-2 bg-bg border border-border rounded-lg p-1.5 focus-within:ring-2 focus-within:ring-accent/20 focus-within:border-accent transition-colors">
            <input
              v-model="followUpInput"
              type="text"
              placeholder="Ask a follow-up question..."
              class="flex-1 bg-transparent border-0 px-2 py-1 text-xs text-text placeholder:text-text/40 focus:outline-none"
              :disabled="submitting || isGeneratingAny"
              @keyup.enter="handleSendFollowUp" />

            <button
              type="button"
              class="px-3 py-1 rounded-md bg-accent text-white hover:bg-accent-hover text-xs font-semibold disabled:opacity-40 disabled:pointer-events-none transition-colors cursor-pointer flex items-center gap-1 shrink-0"
              :disabled="!followUpInput.trim() || submitting || isGeneratingAny"
              @click="handleSendFollowUp">
              <Loader2 v-if="submitting" class="w-3 h-3 animate-spin" />
              <Send v-else class="w-3 h-3" />
              <span class="hidden sm:inline">Ask</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
:deep(.guidance-markdown) {
  color: var(--text);
  line-height: 1.65;
}

:deep(.guidance-markdown h1),
:deep(.guidance-markdown h2),
:deep(.guidance-markdown h3),
:deep(.guidance-markdown h4) {
  font-family: var(--font-display);
  font-weight: 700;
  color: var(--text);
  margin-top: 1.2rem;
  margin-bottom: 0.5rem;
}

:deep(.guidance-markdown h1) {
  font-size: 1.15rem;
}

:deep(.guidance-markdown h2) {
  font-size: 1.05rem;
}

:deep(.guidance-markdown h3) {
  font-size: 0.95rem;
}

:deep(.guidance-markdown h4) {
  font-size: 0.875rem;
}

:deep(.guidance-markdown p) {
  margin-bottom: 0.75rem;
}

:deep(.guidance-markdown p:last-child) {
  margin-bottom: 0;
}

:deep(.guidance-markdown ul) {
  list-style-type: disc;
  padding-left: 1.25rem;
  margin-top: 0.5rem;
  margin-bottom: 0.75rem;
}

:deep(.guidance-markdown ol) {
  list-style-type: decimal;
  padding-left: 1.25rem;
  margin-top: 0.5rem;
  margin-bottom: 0.75rem;
}

:deep(.guidance-markdown li) {
  margin-bottom: 0.35rem;
  line-height: 1.55;
}

:deep(.guidance-markdown strong) {
  font-weight: 700;
  color: var(--text);
}

:deep(.guidance-markdown em) {
  font-style: italic;
}

:deep(.guidance-markdown pre) {
  background-color: var(--bg);
  border: 1px solid var(--border);
  border-radius: 0.5rem;
  padding: 0.75rem 1rem;
  overflow-x: auto;
  font-family: var(--font-mono);
  font-size: 0.8rem;
  margin: 0.75rem 0;
  line-height: 1.5;
}

:deep(.guidance-markdown code:not(pre code)) {
  background-color: var(--bg);
  border: 1px solid var(--border);
  border-radius: 0.25rem;
  padding: 0.125rem 0.35rem;
  font-family: var(--font-mono);
  font-size: 0.775rem;
  color: var(--accent);
  font-weight: 600;
}

:deep(.guidance-markdown blockquote) {
  border-left: 3px solid var(--accent);
  padding-left: 0.875rem;
  margin: 0.75rem 0;
  color: var(--text);
  opacity: 0.85;
}
</style>
