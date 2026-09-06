<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import { FileText, Clock, CheckCircle2, Play, ArrowRight, BookOpen, Calendar, Layers, ShieldCheck, Lock, Info, Check, ChevronRight, Search, Filter } from 'lucide-vue-next';

import BaseBadge from '@/shared/components/ui/BaseBadge.vue';
import BaseButton from '@/shared/components/ui/BaseButton.vue';
import BaseInput from '@/shared/components/ui/BaseInput.vue';
import AppPagination from '@/shared/components/AppPagination.vue';
import { useUiStore } from '@/stores/ui';
import { handleApiError } from '@/shared/utils/apiError';

import * as api from '../api/studentExams';
import type { StudentExam } from '../types/studentExam';
import type { Pagination } from '../api/studentExams';

const router = useRouter();
const uiStore = useUiStore();

const exams = ref<StudentExam[]>([]);
const loading = ref(false);
const search = ref('');
const activeTab = ref<'all' | 'active' | 'scheduled' | 'completed'>('all');

const pagination = ref<Pagination>({
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0,
  from: null,
  to: null,
});

async function load(page = 1) {
  loading.value = true;
  try {
    const params: Record<string, any> = {};
    if (search.value) params.search = search.value;
    if (activeTab.value !== 'all') {
      params.status = activeTab.value;
    }

    const res = await api.getStudentExams(page, pagination.value.per_page, params);
    exams.value = res.data || [];
    if (res.pagination) {
      pagination.value = res.pagination;
    }
  } catch (err: any) {
    handleApiError(err, uiStore, undefined, 'Failed to load exams list.');
  } finally {
    loading.value = false;
  }
}

function handleSearch() {
  pagination.value.current_page = 1;
  load(1);
}

function setTab(tab: 'all' | 'active' | 'scheduled' | 'completed') {
  activeTab.value = tab;
  pagination.value.current_page = 1;
  load(1);
}

function handlePageChange(page: number) {
  pagination.value.current_page = page;
  load(page);
}

function navigateToExam(exam: StudentExam) {
  router.push({ name: 'student.exams.overview', params: { examId: exam.id } });
}

onMounted(() => {
  load(1);
});
</script>

<template>
  <div class="max-w-4xl mx-auto space-y-8">
    <!-- ── Page Header ─────────────────────────────────────────────── -->
    <header class="space-y-2">
      <div class="flex items-center gap-2 text-xs font-mono uppercase tracking-wider text-text/60">
        <span class="w-1.5 h-1.5 rounded-full bg-accent inline-block" />
        <span>Candidate Portal · Examination Schedule</span>
      </div>
      <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-text font-display">My Exams</h1>
      <p class="text-sm text-text/60">All registered upcoming assessments, active examination sessions, and past examination records.</p>
    </header>

    <!-- ── Filter Tabs + Search ────────────────────────────────────── -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-border/60 pb-4">
      <!-- Status tab pills -->
      <div class="flex items-center gap-2 overflow-x-auto w-full sm:w-auto">
        <button
          v-for="tab in [
            { key: 'all', label: 'All' },
            { key: 'active', label: 'Active' },
            { key: 'scheduled', label: 'Upcoming' },
            { key: 'completed', label: 'Completed' },
          ] as const"
          :key="tab.key"
          type="button"
          class="px-3.5 py-1.5 rounded-full text-xs font-medium transition-all cursor-pointer whitespace-nowrap"
          :class="
            activeTab === tab.key
              ? 'bg-accent text-white shadow-xs font-semibold'
              : 'bg-surface border border-border text-text/70 hover:text-text hover:border-accent/40'
          "
          @click="setTab(tab.key)">
          {{ tab.label }}
        </button>
      </div>

      <!-- Search — inline input with icon overlay -->
      <div class="relative w-full sm:w-64 shrink-0">
        <Search class="w-3.5 h-3.5 text-text/40 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
        <input
          v-model="search"
          type="text"
          placeholder="Filter by title or course..."
          class="w-full bg-surface border border-border rounded-lg pl-8 pr-3 py-2 text-sm text-text placeholder:text-text/40 focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent transition-colors"
          @keyup.enter="handleSearch" />
      </div>
    </div>

    <!-- ── Loading Skeleton ────────────────────────────────────────── -->
    <div v-if="loading" class="space-y-4 animate-pulse">
      <div v-for="i in 3" :key="i" class="h-44 bg-surface rounded-2xl border border-border" />
    </div>

    <!-- ── Empty State ─────────────────────────────────────────────── -->
    <div v-else-if="exams.length === 0" class="text-center py-20 bg-surface rounded-2xl border border-border space-y-3">
      <FileText class="w-12 h-12 mx-auto text-text/30" />
      <h3 class="text-base font-semibold text-text">No examination sessions found</h3>
      <p class="text-xs text-text/60 max-w-xs mx-auto">There are no examinations matching your active filters.</p>
    </div>

    <!-- ── Exam Cards ──────────────────────────────────────────────── -->
    <div v-else class="space-y-4">
      <article
        v-for="exam in exams"
        :key="exam.id"
        class="relative bg-surface rounded-2xl border border-border shadow-xs p-6 sm:p-7 overflow-hidden transition-all duration-200 hover:shadow-md hover:border-accent/30 group">
        <!-- Left accent bar for active/in-progress exams -->
        <div
          v-if="exam.status === 'active' && exam.attempt?.status !== 'completed' && exam.attempt?.status !== 'graded'"
          class="absolute left-0 top-0 bottom-0 w-1.5 bg-accent rounded-l-2xl" />

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6">
          <!-- Left: meta + title + metrics -->
          <div class="flex flex-col min-w-0 pr-2 space-y-2.5">
            <!-- Status badges row -->
            <div class="flex items-center flex-wrap gap-2 text-xs">
              <span
                v-if="exam.status === 'active' && exam.attempt?.status === 'in_progress'"
                class="inline-flex items-center gap-1.5 font-mono text-[11px] px-2.5 py-0.5 rounded-full bg-warning/15 text-warning font-semibold">
                <span class="w-1.5 h-1.5 rounded-full bg-warning animate-pulse" />
                In Progress
              </span>

              <span
                v-else-if="exam.status === 'active' && exam.attempt?.status !== 'completed' && exam.attempt?.status !== 'graded'"
                class="inline-flex items-center gap-1.5 font-mono text-[11px] px-2.5 py-0.5 rounded-full bg-success/15 text-success font-semibold">
                <span class="w-1.5 h-1.5 rounded-full bg-success animate-pulse" />
                Active Now
              </span>

              <span
                v-else-if="exam.attempt?.status === 'completed' || exam.attempt?.status === 'graded'"
                class="inline-flex items-center gap-1 font-mono text-[11px] px-2.5 py-0.5 rounded-full bg-accent/10 text-accent font-semibold">
                <Check class="w-3 h-3" />
                Submitted
              </span>

              <span
                v-if="(exam.attempt?.status === 'completed' || exam.attempt?.status === 'graded') && exam.attempt?.score !== null && exam.attempt?.score !== undefined"
                class="inline-flex items-center gap-1 font-mono text-[11px] px-2.5 py-0.5 rounded-full bg-bg border border-border text-text/70 font-semibold">
                Score: {{ exam.attempt.score }} / {{ exam.total_marks }}
              </span>

              <span v-else-if="exam.status !== 'active'" class="inline-flex items-center gap-1 font-mono text-[11px] px-2.5 py-0.5 rounded-full bg-bg border border-border text-text/70 font-semibold">
                <Calendar class="w-3 h-3" />
                Scheduled
              </span>

              <span class="inline-flex items-center gap-1 text-text/60 font-mono text-[11px]">
                <Clock class="w-3 h-3 text-accent" />
                {{ exam.scheduled_start ? `Window: ${exam.scheduled_start}` : 'Open window' }}
              </span>
            </div>

            <!-- Title + course -->
            <div>
              <span class="font-mono text-xs font-semibold text-accent uppercase tracking-wide block mb-0.5">
                {{ exam.course?.code }}: {{ exam.course?.name }}
              </span>
              <h2 class="text-xl font-bold tracking-tight text-text font-display group-hover:text-accent transition-colors leading-snug">
                {{ exam.title }}
              </h2>
              <p class="text-xs text-text/60 mt-0.5">
                {{ exam.semester?.name ? `${exam.semester.name} · ` : '' }}<span class="capitalize">{{ exam.type }} Assessment</span>
              </p>
            </div>

            <!-- Metrics row -->
            <div class="flex items-center gap-3 text-xs text-text/60 flex-wrap pt-1">
              <span class="flex items-center gap-1">
                <Clock class="w-3.5 h-3.5 text-text/40" />
                {{ exam.duration_minutes }} mins
              </span>
              <span class="text-border">•</span>
              <span class="flex items-center gap-1">
                <Layers class="w-3.5 h-3.5 text-text/40" />
                {{ exam.total_questions }} questions
              </span>
              <span class="text-border">•</span>
              <span class="flex items-center gap-1">
                <CheckCircle2 class="w-3.5 h-3.5 text-text/40" />
                {{ exam.total_marks }} pts
              </span>
              <span class="text-border">•</span>
              <span class="flex items-center gap-1 text-success font-medium">
                <ShieldCheck class="w-3.5 h-3.5" />
                Monitored
              </span>
            </div>
          </div>

          <!-- Right: CTA -->
          <div class="sm:shrink-0 flex sm:flex-col sm:items-end justify-start gap-2">
            <!-- Active: primary CTA -->
            <BaseButton
              v-if="exam.status === 'active' && exam.attempt?.status !== 'completed' && exam.attempt?.status !== 'graded'"
              variant="primary"
              class="w-full sm:w-auto font-semibold shadow-xs"
              @click="navigateToExam(exam)">
              <span>{{ exam.attempt?.status === 'in_progress' ? 'Resume Exam' : 'Enter Exam' }}</span>
              <ArrowRight class="w-4 h-4 group-hover:translate-x-0.5 transition-transform" />
            </BaseButton>

            <!-- Completed: secondary button -->
            <BaseButton v-else-if="exam.attempt?.status === 'completed' || exam.attempt?.status === 'graded'" variant="secondary" class="w-full sm:w-auto font-medium" @click="navigateToExam(exam)">
              <span>View Submission</span>
              <ChevronRight class="w-4 h-4 text-text/50" />
            </BaseButton>

            <!-- Scheduled: secondary outline-style -->
            <BaseButton v-else variant="secondary" class="w-full sm:w-auto text-xs" @click="navigateToExam(exam)">
              <Lock class="w-3.5 h-3.5 text-text/50" />
              <span>Overview &amp; Rules</span>
            </BaseButton>
          </div>
        </div>
      </article>
    </div>

    <!-- ── Footer note ─────────────────────────────────────────────── -->
    <div class="pt-4 flex items-start gap-2.5 text-text/60 text-xs leading-relaxed border-t border-border/40">
      <Info class="w-4 h-4 text-accent shrink-0 mt-0.5" />
      <p>
        Examination links activate precisely at scheduled start times. Ensure your browser is up to date and a stable internet connection is maintained throughout the evaluation window.
      </p>
    </div>

    <!-- ── Pagination ──────────────────────────────────────────────── -->
    <AppPagination
      v-if="pagination.last_page > 1"
      :current-page="pagination.current_page"
      :last-page="pagination.last_page"
      :total="pagination.total"
      :per-page="pagination.per_page"
      @page-change="handlePageChange" />
  </div>
</template>
