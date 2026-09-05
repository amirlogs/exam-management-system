<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import { 
  FileText, 
  Clock, 
  CheckCircle2, 
  AlertCircle, 
  Play, 
  ArrowRight,
  BookOpen,
  Calendar,
  Layers
} from 'lucide-vue-next';

import ResourceToolbar from '@/shared/components/ResourceToolbar.vue';
import BaseBadge from '@/shared/components/ui/BaseBadge.vue';
import BaseButton from '@/shared/components/ui/BaseButton.vue';
import BaseSelect from '@/shared/components/ui/BaseSelect.vue';
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
const refreshing = ref(false);
const search = ref('');
const filterStatus = ref('');
const filterType = ref('');

const pagination = ref<Pagination>({
  current_page: 1,
  last_page: 1,
  per_page: 12,
  total: 0,
  from: null,
  to: null,
});

const statusOptions = [
  { value: '', label: 'All Statuses' },
  { value: 'active', label: 'Active (Available Now)' },
  { value: 'scheduled', label: 'Upcoming / Scheduled' },
  { value: 'completed', label: 'Completed' },
];

const typeOptions = [
  { value: '', label: 'All Exam Types' },
  { value: 'quiz', label: 'Quiz' },
  { value: 'midterm', label: 'Midterm Exam' },
  { value: 'final', label: 'Final Exam' },
];

async function load(page = 1) {
  loading.value = true;
  try {
    const params: Record<string, any> = {};
    if (search.value) params.search = search.value;
    if (filterStatus.value) params.status = filterStatus.value;
    if (filterType.value) params.type = filterType.value;

    const res = await api.getStudentExams(page, pagination.value.per_page, params);
    exams.value = res.data || [];
    if (res.pagination) {
      pagination.value = res.pagination;
    }
  } catch (err: any) {
    handleApiError(err, uiStore, undefined, 'Failed to load exams.');
  } finally {
    loading.value = false;
  }
}

async function handleRefresh() {
  refreshing.value = true;
  try {
    await load(pagination.value.current_page);
  } finally {
    refreshing.value = false;
  }
}

function handleSearch(val: string) {
  search.value = val;
  pagination.value.current_page = 1;
  load(1);
}

function handlePageChange(page: number) {
  pagination.value.current_page = page;
  load(page);
}

function getExamStatusBadge(exam: StudentExam) {
  if (exam.attempt?.status === 'completed' || exam.attempt?.status === 'graded') {
    return { label: 'Submitted', variant: 'info' as const };
  }
  if (exam.attempt?.status === 'in_progress') {
    return { label: 'In Progress', variant: 'warning' as const };
  }
  if (exam.status === 'active') {
    return { label: 'Active / Ready', variant: 'success' as const };
  }
  if (exam.status === 'scheduled') {
    return { label: 'Upcoming', variant: 'neutral' as const };
  }
  return { label: exam.status, variant: 'neutral' as const };
}

function navigateToExam(exam: StudentExam) {
  if (exam.status === 'active' && exam.attempt?.status !== 'completed' && exam.attempt?.status !== 'graded') {
    router.push({ name: 'student.exams.overview', params: { examId: exam.id } });
  } else {
    router.push({ name: 'student.exams.overview', params: { examId: exam.id } });
  }
}

watch([filterStatus, filterType], () => {
  pagination.value.current_page = 1;
  load(1);
});

onMounted(() => {
  load(1);
});
</script>

<template>
  <div class="space-y-6">
    <ResourceToolbar
      title="My Exams"
      :total-count="pagination.total"
      :refreshing="refreshing"
      search-placeholder="Search by exam title or course..."
      @search="handleSearch"
      @refresh="handleRefresh"
    />

    <!-- Filters Row -->
    <div class="flex flex-wrap items-center gap-4 bg-surface p-4 rounded-xl border border-border">
      <div class="w-48">
        <BaseSelect
          v-model="filterStatus"
          label=""
          :options="statusOptions"
          placeholder="Filter by status"
        />
      </div>
      <div class="w-48">
        <BaseSelect
          v-model="filterType"
          label=""
          :options="typeOptions"
          placeholder="Filter by type"
        />
      </div>
    </div>

    <!-- Loading Skeleton / Empty State / Grid -->
    <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
      <div
        v-for="i in 6"
        :key="i"
        class="bg-surface rounded-xl border border-border p-6 animate-pulse space-y-4"
      >
        <div class="flex justify-between">
          <div class="h-4 bg-border/60 rounded w-1/3" />
          <div class="h-4 bg-border/60 rounded w-16" />
        </div>
        <div class="h-6 bg-border/60 rounded w-3/4" />
        <div class="space-y-2 pt-2">
          <div class="h-4 bg-border/40 rounded w-1/2" />
          <div class="h-4 bg-border/40 rounded w-2/3" />
        </div>
        <div class="h-10 bg-border/40 rounded w-full pt-4" />
      </div>
    </div>

    <div
      v-else-if="exams.length === 0"
      class="text-center py-16 bg-surface rounded-xl border border-border"
    >
      <FileText class="w-12 h-12 mx-auto text-muted-foreground mb-3 opacity-40" />
      <h3 class="text-base font-medium text-foreground">No exams found</h3>
      <p class="text-sm text-muted-foreground mt-1 max-w-md mx-auto">
        There are currently no exams available matching your search or filters.
      </p>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
      <div
        v-for="exam in exams"
        :key="exam.id"
        class="bg-surface rounded-xl border border-border flex flex-col justify-between hover:border-primary/40 transition-all shadow-xs overflow-hidden group"
      >
        <!-- Card Header -->
        <div class="p-5 border-b border-border/60 bg-surface">
          <div class="flex items-center justify-between gap-2 mb-2.5">
            <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-primary uppercase tracking-wide">
              <BookOpen class="w-3.5 h-3.5" />
              {{ exam.course?.code || 'Course' }}
            </span>
            <BaseBadge :variant="getExamStatusBadge(exam).variant" size="sm">
              {{ getExamStatusBadge(exam).label }}
            </BaseBadge>
          </div>

          <h3 class="text-base font-semibold text-foreground group-hover:text-primary transition-colors line-clamp-1">
            {{ exam.title }}
          </h3>
          <p class="text-xs text-muted-foreground mt-0.5 line-clamp-1">
            {{ exam.course?.name }}
          </p>
        </div>

        <!-- Card Body -->
        <div class="p-5 space-y-3 flex-1 text-sm bg-surface">
          <div class="grid grid-cols-2 gap-2 text-xs">
            <div class="flex items-center gap-1.5 text-muted-foreground">
              <Clock class="w-3.5 h-3.5 text-muted-foreground/70" />
              <span>Duration: <strong class="text-foreground font-medium">{{ exam.duration_minutes }}m</strong></span>
            </div>
            <div class="flex items-center gap-1.5 text-muted-foreground">
              <Layers class="w-3.5 h-3.5 text-muted-foreground/70" />
              <span>Questions: <strong class="text-foreground font-medium">{{ exam.total_questions }}</strong></span>
            </div>
            <div class="flex items-center gap-1.5 text-muted-foreground">
              <CheckCircle2 class="w-3.5 h-3.5 text-muted-foreground/70" />
              <span>Marks: <strong class="text-foreground font-medium">{{ exam.total_marks }} pts</strong></span>
            </div>
            <div class="flex items-center gap-1.5 text-muted-foreground">
              <FileText class="w-3.5 h-3.5 text-muted-foreground/70" />
              <span>Type: <strong class="text-foreground font-medium capitalize">{{ exam.type }}</strong></span>
            </div>
          </div>

          <div v-if="exam.scheduled_start || exam.scheduled_end" class="pt-2 border-t border-border/40 text-xs text-muted-foreground flex items-start gap-1.5">
            <Calendar class="w-3.5 h-3.5 mt-0.5 shrink-0 text-muted-foreground/70" />
            <div class="truncate">
              <span>Schedule: </span>
              <span class="text-foreground font-medium">{{ exam.scheduled_start || 'Anytime' }} - {{ exam.scheduled_end || 'Open' }}</span>
            </div>
          </div>
        </div>

        <!-- Card Footer -->
        <div class="p-4 bg-muted/20 border-t border-border/60 flex items-center justify-between">
          <div v-if="exam.attempt?.score !== null && exam.attempt?.score !== undefined" class="text-xs">
            <span class="text-muted-foreground">Score: </span>
            <span class="font-bold text-foreground">{{ exam.attempt.score }} / {{ exam.total_marks }}</span>
          </div>
          <div v-else class="text-xs text-muted-foreground">
            {{ exam.semester?.name || '' }}
          </div>

          <BaseButton
            :variant="exam.status === 'active' && exam.attempt?.status !== 'completed' ? 'primary' : 'outline'"
            size="sm"
            class="ml-auto"
            @click="navigateToExam(exam)"
          >
            <template v-if="exam.attempt?.status === 'in_progress'">
              <Play class="w-3.5 h-3.5 mr-1.5 fill-current" />
              Resume Exam
            </template>
            <template v-else-if="exam.status === 'active' && exam.attempt?.status !== 'completed'">
              <Play class="w-3.5 h-3.5 mr-1.5 fill-current" />
              Start Exam
            </template>
            <template v-else>
              View Overview
              <ArrowRight class="w-3.5 h-3.5 ml-1" />
            </template>
          </BaseButton>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <AppPagination
      v-if="pagination.last_page > 1"
      :current-page="pagination.current_page"
      :last-page="pagination.last_page"
      :total="pagination.total"
      :per-page="pagination.per_page"
      @page-change="handlePageChange"
    />
  </div>
</template>
