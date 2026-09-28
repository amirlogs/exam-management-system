<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import {
  Sparkles,
  BrainCircuit,
  CheckCircle2,
  Target,
  Flame,
  Search,
  Clock,
  Layers,
  ArrowRight,
  Play,
  Trash2,
  RotateCcw,
  BookOpen,
} from 'lucide-vue-next';

import BaseButton from '@/shared/components/ui/BaseButton.vue';
import BaseBadge from '@/shared/components/ui/BaseBadge.vue';
import AppPagination from '@/shared/components/AppPagination.vue';
import ConfirmModal from '@/shared/components/ConfirmModal.vue';
import CreatePracticeExamModal from '../components/CreatePracticeExamModal.vue';

import { useUiStore } from '@/stores/ui';
import { handleApiError } from '@/shared/utils/apiError';
import { getPracticeExams, deletePracticeExam, startPracticeExam, retakePracticeExam } from '../api/practice';
import type { PracticeExam } from '../types/practice';

const router = useRouter();
const uiStore = useUiStore();

const exams = ref<PracticeExam[]>([]);
const loading = ref(true);
const search = ref('');
const activeTab = ref<'all' | 'active' | 'draft' | 'completed'>('all');
const showCreateModal = ref(false);

const deletingExamId = ref<number | null>(null);
const showDeleteConfirm = ref(false);

const examToRetake = ref<PracticeExam | null>(null);
const showRetakeConfirm = ref(false);
const retaking = ref(false);

const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 12,
  total: 0,
  from: 0,
  to: 0,
});

const stats = computed(() => {
  const totalSets = pagination.value.total;
  const completedSets = exams.value.filter((e) => e.status === 'completed').length;
  const activeSets = exams.value.filter((e) => e.status === 'active').length;
  const totalQuestions = exams.value.reduce((acc, curr) => acc + (curr.total_questions || 0), 0);

  return {
    totalSets,
    completedSets,
    activeSets,
    totalQuestions,
  };
});

const filteredExams = computed(() => {
  return exams.value.filter((e) => {
    if (activeTab.value !== 'all' && e.status !== activeTab.value) {
      return false;
    }
    if (search.value.trim()) {
      const q = search.value.toLowerCase().trim();
      return e.title.toLowerCase().includes(q);
    }
    return true;
  });
});

async function fetchExams(page = 1) {
  loading.value = true;
  try {
    const res = await getPracticeExams(page, pagination.value.per_page, {
      status: activeTab.value === 'all' ? undefined : activeTab.value,
      search: search.value.trim() || undefined,
    });
    exams.value = res.data;
    pagination.value = {
      current_page: res.pagination.current_page,
      last_page: res.pagination.last_page,
      per_page: res.pagination.per_page,
      total: res.pagination.total,
      from: res.pagination.from || 0,
      to: res.pagination.to || 0,
    };
  } catch (error) {
    handleApiError(error, uiStore, undefined, 'Failed to load practice exams');
  } finally {
    loading.value = false;
  }
}

function handleTabChange(tab: 'all' | 'active' | 'draft' | 'completed') {
  activeTab.value = tab;
  fetchExams(1);
}

function handleSearch() {
  fetchExams(1);
}

function handlePageChange(page: number) {
  fetchExams(page);
}

async function handleStartOrResume(exam: PracticeExam) {
  try {
    if (exam.status === 'draft') {
      await startPracticeExam(exam.id);
    }
    router.push({
      name: 'student.practice.take',
      params: { practiceExamId: exam.id },
    });
  } catch (error) {
    handleApiError(error, uiStore, undefined, 'Failed to enter practice session');
  }
}

function promptDelete(examId: number) {
  deletingExamId.value = examId;
  showDeleteConfirm.value = true;
}

async function confirmDelete() {
  if (!deletingExamId.value) return;
  try {
    await deletePracticeExam(deletingExamId.value);
    uiStore.showToast('Practice exam removed successfully', 'success');
    showDeleteConfirm.value = false;
    deletingExamId.value = null;
    fetchExams(pagination.value.current_page);
  } catch (error) {
    handleApiError(error, uiStore, undefined, 'Failed to delete practice exam');
  }
}

function promptRetake(exam: PracticeExam) {
  examToRetake.value = exam;
  showRetakeConfirm.value = true;
}

async function confirmRetake() {
  if (!examToRetake.value || retaking.value) return;
  retaking.value = true;
  try {
    const targetId = examToRetake.value.id;
    await retakePracticeExam(targetId);
    uiStore.showToast('Practice exam reset. Good luck!', 'success');
    showRetakeConfirm.value = false;
    examToRetake.value = null;
    router.push({
      name: 'student.practice.take',
      params: { practiceExamId: targetId },
    });
  } catch (error) {
    handleApiError(error, uiStore, undefined, 'Failed to reset practice exam');
  } finally {
    retaking.value = false;
  }
}

onMounted(() => {
  fetchExams();
});
</script>

<template>
  <div class="max-w-6xl mx-auto space-y-7 px-4 py-6 md:px-8">
    <!-- Header -->
    <header class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 border-b border-border pb-6">
      <div class="space-y-1">
        <div class="flex items-center gap-2 text-xs font-mono uppercase tracking-wider text-accent font-semibold">
          <Sparkles class="w-3.5 h-3.5" />
          <span>Self-Paced Mastery &amp; AI Practice</span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-text font-display">Practice Hub</h1>
        <p class="text-xs sm:text-sm text-text/60">Generate targeted practice drills, test your conceptual knowledge, and receive instant explanations.</p>
      </div>

      <BaseButton variant="primary" class="font-semibold shadow-xs flex items-center gap-2" @click="showCreateModal = true">
        <Sparkles class="w-4 h-4" />
        <span>Create Practice Exam</span>
      </BaseButton>
    </header>

    <!-- Stat Counters (4-Grid) -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
      <div class="bg-surface border border-border rounded-xl p-4 shadow-2xs space-y-1">
        <div class="flex items-center justify-between text-text/50 text-xs font-medium">
          <span>Total Sets</span>
          <BrainCircuit class="w-4 h-4 text-accent" />
        </div>
        <p class="text-2xl font-bold text-text font-mono">{{ stats.totalSets }}</p>
      </div>

      <div class="bg-surface border border-border rounded-xl p-4 shadow-2xs space-y-1">
        <div class="flex items-center justify-between text-text/50 text-xs font-medium">
          <span>In Progress</span>
          <Play class="w-4 h-4 text-warning" />
        </div>
        <p class="text-2xl font-bold text-text font-mono">{{ stats.activeSets }}</p>
      </div>

      <div class="bg-surface border border-border rounded-xl p-4 shadow-2xs space-y-1">
        <div class="flex items-center justify-between text-text/50 text-xs font-medium">
          <span>Completed</span>
          <CheckCircle2 class="w-4 h-4 text-success" />
        </div>
        <p class="text-2xl font-bold text-text font-mono">{{ stats.completedSets }}</p>
      </div>

      <div class="bg-surface border border-border rounded-xl p-4 shadow-2xs space-y-1">
        <div class="flex items-center justify-between text-text/50 text-xs font-medium">
          <span>Total Questions</span>
          <Flame class="w-4 h-4 text-accent" />
        </div>
        <p class="text-2xl font-bold text-text font-mono">{{ stats.totalQuestions }}</p>
      </div>
    </div>

    <!-- Filter Toolbar -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-border pb-3">
      <div class="flex items-center gap-1.5 overflow-x-auto w-full sm:w-auto">
        <button
          v-for="tab in [
            { key: 'all', label: 'All Sets' },
            { key: 'active', label: 'In Progress' },
            { key: 'draft', label: 'Drafts' },
            { key: 'completed', label: 'Completed' },
          ]"
          :key="tab.key"
          type="button"
          class="px-3 py-1 rounded-md text-xs font-medium transition-all cursor-pointer whitespace-nowrap"
          :class="activeTab === tab.key ? 'bg-accent text-white shadow-xs font-semibold' : 'bg-surface border border-border text-text/70 hover:text-text'"
          @click="handleTabChange(tab.key as any)">
          {{ tab.label }}
        </button>
      </div>

      <div class="relative w-full sm:w-64 shrink-0">
        <Search class="w-3.5 h-3.5 text-text/40 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
        <input
          v-model="search"
          type="text"
          placeholder="Search by exam title..."
          class="w-full bg-surface border border-border rounded-lg pl-8 pr-3 py-1.5 text-xs sm:text-sm text-text placeholder:text-text/40 focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent transition-colors"
          @keyup.enter="handleSearch" />
      </div>
    </div>

    <!-- Practice Cards Grid -->
    <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
      <div v-for="i in 6" :key="i" class="h-44 bg-surface rounded-xl border border-border animate-pulse p-5" />
    </div>

    <div v-else-if="filteredExams.length === 0" class="text-center py-16 bg-surface rounded-2xl border border-border space-y-3">
      <BrainCircuit class="w-12 h-12 mx-auto text-text/30" />
      <h3 class="text-base font-semibold text-text">No practice exams found</h3>
      <p class="text-xs text-text/50 max-w-sm mx-auto">Create a customized AI practice exam to sharpen your understanding on any academic topic.</p>
      <BaseButton variant="primary" class="font-semibold shadow-xs" @click="showCreateModal = true">
        <Sparkles class="w-4 h-4 mr-1.5" />
        <span>Create Your First Practice Exam</span>
      </BaseButton>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
      <article
        v-for="exam in filteredExams"
        :key="exam.id"
        class="relative bg-surface rounded-xl border border-border shadow-2xs p-5 flex flex-col justify-between transition-all duration-150 hover:border-accent/40 hover:shadow-xs group">
        <!-- Top Status Row -->
        <div class="space-y-3">
          <div class="flex items-center justify-between text-xs">
            <span
              v-if="exam.status === 'active'"
              class="inline-flex items-center gap-1.5 font-mono text-[11px] px-2.5 py-0.5 rounded-full bg-success/15 text-success font-semibold">
              <span class="w-1.5 h-1.5 rounded-full bg-success animate-pulse" />
              In Progress
            </span>
            <span
              v-else-if="exam.status === 'completed'"
              class="inline-flex items-center gap-1.5 font-mono text-[11px] px-2.5 py-0.5 rounded-full bg-accent/10 text-accent font-semibold">
              <CheckCircle2 class="w-3.5 h-3.5" />
              Completed
            </span>
            <span
              v-else
              class="inline-flex items-center gap-1 font-mono text-[11px] px-2.5 py-0.5 rounded-full bg-bg border border-border text-text/60 font-medium">
              Draft
            </span>

            <div class="flex items-center gap-2">
              <span class="font-mono text-[11px] text-text/50 flex items-center gap-1">
                <Clock class="w-3 h-3 text-text/40" />
                {{ exam.duration_minutes }}m
              </span>
              <button
                type="button"
                class="text-text/30 hover:text-error transition-colors p-1 rounded hover:bg-error/10 cursor-pointer"
                title="Delete exam"
                @click="promptDelete(exam.id)">
                <Trash2 class="w-3.5 h-3.5" />
              </button>
            </div>
          </div>

          <div>
            <h2 class="text-base font-bold tracking-tight text-text font-display group-hover:text-accent transition-colors line-clamp-2">
              {{ exam.title }}
            </h2>
            <div class="mt-2 flex items-center gap-3 text-xs text-text/60 font-mono">
              <span class="flex items-center gap-1">
                <Layers class="w-3.5 h-3.5 text-text/40" />
                {{ exam.total_questions }} Questions
              </span>
              <span class="text-border">·</span>
              <span>{{ exam.total_marks }} Points</span>
            </div>
          </div>
        </div>

        <!-- Footer CTA -->
        <div class="mt-5 pt-3 border-t border-border/60 flex items-center justify-between">
          <span class="text-[11px] text-text/40 font-mono">{{ exam.created_at }}</span>
          <BaseButton
            v-if="exam.status === 'active'"
            variant="primary"
            class="text-xs font-semibold py-1.5 px-3 flex items-center gap-1"
            @click="handleStartOrResume(exam)">
            <span>Resume</span>
            <ArrowRight class="w-3.5 h-3.5" />
          </BaseButton>
          <BaseButton
            v-else-if="exam.status === 'draft'"
            variant="primary"
            class="text-xs font-semibold py-1.5 px-3 flex items-center gap-1"
            @click="handleStartOrResume(exam)">
            <span>Start Practice</span>
            <Play class="w-3.5 h-3.5" />
          </BaseButton>
          <div v-else class="flex items-center gap-2">
            <BaseButton
              variant="secondary"
              class="text-xs font-medium py-1.5 px-2.5 flex items-center gap-1"
              @click="handleStartOrResume(exam)">
              <span>Review</span>
              <BookOpen class="w-3.5 h-3.5" />
            </BaseButton>
            <BaseButton
              variant="primary"
              class="text-xs font-semibold py-1.5 px-2.5 flex items-center gap-1"
              @click="promptRetake(exam)">
              <RotateCcw class="w-3 h-3" />
              <span>Retake</span>
            </BaseButton>
          </div>
        </div>
      </article>
    </div>

    <!-- Pagination -->
    <AppPagination
      v-if="pagination.last_page > 1"
      :pagination="pagination"
      @change-page="handlePageChange" />

    <!-- Create Exam Modal -->
    <CreatePracticeExamModal
      v-model="showCreateModal"
      @created="fetchExams(1)" />

    <!-- Retake Confirmation Modal -->
    <ConfirmModal
      :show="showRetakeConfirm"
      title="Retake Practice Exam"
      :description="`Are you sure you want to retake '${examToRetake?.title}'? All your previous answers will be cleared so you can practice again.`"
      confirm-text="Retake Now"
      :loading="retaking"
      @close="showRetakeConfirm = false"
      @confirm="confirmRetake" />

    <!-- Delete Confirmation Modal -->
    <ConfirmModal
      :show="showDeleteConfirm"
      title="Delete Practice Exam"
      description="Are you sure you want to delete this practice exam? All questions and recorded answers will be permanently removed."
      confirm-text="Delete"
      variant="danger"
      @close="showDeleteConfirm = false"
      @confirm="confirmDelete" />
  </div>
</template>
