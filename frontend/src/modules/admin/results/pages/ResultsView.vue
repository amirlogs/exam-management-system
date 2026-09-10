<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import {
  BarChart3,
  Check,
  RotateCcw,
  GraduationCap,
  Eye,
  Award,
  BookOpen,
  CheckCircle2,
  Clock,
  X,
} from 'lucide-vue-next';

import ResourceToolbar from '@/shared/components/ResourceToolbar.vue';
import BaseBadge from '@/shared/components/ui/BaseBadge.vue';
import BaseSelect from '@/shared/components/ui/BaseSelect.vue';
import AppPagination from '@/shared/components/AppPagination.vue';

import { getResults, getResultDetail } from '../api/results';
import { getSemesters } from '@/modules/admin/semesters/api/semesters';

import { handleApiError } from '@/shared/utils/apiError';
import { useUiStore } from '@/stores/ui';

import type { AdminResult } from '../types/result';
import type { Pagination } from '@/shared/composables/useCrudResource';

const uiStore = useUiStore();

const results = ref<AdminResult[]>([]);
const loading = ref(false);
const refreshing = ref(false);
const search = ref('');

// Column visibility
type ResultColumn =
  | 'student'
  | 'course'
  | 'score'
  | 'letter_grade'
  | 'section'
  | 'semester'
  | 'status'
  | 'published_by'
  | 'published_at'
  | 'actions';

const showColumns = ref(false);
const columns = [
  { key: 'student' as ResultColumn, label: 'Student', required: true },
  { key: 'course' as ResultColumn, label: 'Course', required: true },
  { key: 'score' as ResultColumn, label: 'Score', required: true },
  { key: 'letter_grade' as ResultColumn, label: 'Grade', required: true },
  { key: 'section' as ResultColumn, label: 'Section', required: false },
  { key: 'semester' as ResultColumn, label: 'Semester', required: false },
  { key: 'status' as ResultColumn, label: 'Status', required: false },
  { key: 'published_by' as ResultColumn, label: 'Published By', required: false },
  { key: 'published_at' as ResultColumn, label: 'Published At', required: false },
  { key: 'actions' as ResultColumn, label: 'Actions', required: false },
];

const visibleColumns = ref<ResultColumn[]>([
  'student',
  'course',
  'score',
  'letter_grade',
  'section',
  'semester',
  'status',
  'actions',
]);

const isColumnVisible = (col: ResultColumn) => visibleColumns.value.includes(col);

function toggleColumn(col: ResultColumn) {
  const cfg = columns.find((c) => c.key === col);
  if (cfg?.required) return;
  if (isColumnVisible(col)) {
    visibleColumns.value = visibleColumns.value.filter((k) => k !== col);
  } else {
    visibleColumns.value = [...visibleColumns.value, col];
  }
}

function resetColumns() {
  visibleColumns.value = [
    'student',
    'course',
    'score',
    'letter_grade',
    'section',
    'semester',
    'status',
    'actions',
  ];
}

function toggleColumnSelector() {
  setTimeout(() => {
    showColumns.value = !showColumns.value;
  }, 0);
}

function handleColumnOutsideClick(event: MouseEvent) {
  if (!showColumns.value) return;
  const target = event.target as HTMLElement;
  if (!target.closest('[title="Show/hide columns"]') && !target.closest('[data-columns-menu]')) {
    showColumns.value = false;
  }
}

const visibleColumnCount = computed(() => visibleColumns.value.length);

// Filters
const filters = ref<{
  semester_id: number | null;
  letter_grade: string | null;
  status: string | null;
}>({
  semester_id: null,
  letter_grade: null,
  status: null,
});

const semesters = ref<{ id: number; name: string }[]>([]);

const semesterOptions = computed(() => [
  { value: null, label: 'All Semesters' },
  ...semesters.value.map((s) => ({ value: s.id, label: s.name })),
]);

const gradeOptions = [
  { value: '', label: 'All Grades' },
  { value: 'A', label: 'A (90%+)' },
  { value: 'A-', label: 'A- (85-89%)' },
  { value: 'B+', label: 'B+ (80-84%)' },
  { value: 'B', label: 'B (75-79%)' },
  { value: 'B-', label: 'B- (70-74%)' },
  { value: 'C+', label: 'C+ (65-69%)' },
  { value: 'C', label: 'C (60-64%)' },
  { value: 'D', label: 'D (50-59%)' },
  { value: 'F', label: 'F (<50%)' },
];

const statusOptions = [
  { value: '', label: 'All Statuses' },
  { value: 'published', label: 'Published' },
  { value: 'draft', label: 'Draft' },
  { value: 'archived', label: 'Archived' },
];

const hasActiveFilters = computed(() => {
  return (
    filters.value.semester_id !== null ||
    (filters.value.letter_grade !== null && filters.value.letter_grade !== '') ||
    (filters.value.status !== null && filters.value.status !== '')
  );
});

const filterCount = computed(() => {
  let count = 0;
  if (filters.value.semester_id !== null) count++;
  if (filters.value.letter_grade !== null && filters.value.letter_grade !== '') count++;
  if (filters.value.status !== null && filters.value.status !== '') count++;
  return count;
});

const pagination = ref<Pagination>({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
  from: null,
  to: null,
});

// Metrics
const totalResults = computed(() => pagination.value.total);
const publishedCount = computed(() => results.value.filter((r) => r.status === 'published').length);
const averageScore = computed(() => {
  if (results.value.length === 0) return 0;
  const total = results.value.reduce((acc, r) => acc + Number(r.total_score || 0), 0);
  return (total / results.value.length).toFixed(1);
});
const passRate = computed(() => {
  if (results.value.length === 0) return 0;
  const passed = results.value.filter((r) => r.letter_grade !== 'F').length;
  return Math.round((passed / results.value.length) * 100);
});

// Detail Modal
const selectedResult = ref<AdminResult | null>(null);
const isDetailOpen = ref(false);

function openDetail(result: AdminResult) {
  selectedResult.value = result;
  isDetailOpen.value = true;
}

function closeDetail() {
  isDetailOpen.value = false;
  selectedResult.value = null;
}

function getGradeBadgeVariant(grade: string): 'success' | 'info' | 'warning' | 'error' | 'neutral' {
  if (grade === 'A' || grade === 'A-') return 'success';
  if (grade.startsWith('B')) return 'info';
  if (grade.startsWith('C')) return 'warning';
  if (grade === 'D') return 'warning';
  if (grade === 'F') return 'error';
  return 'neutral';
}

function getStatusBadgeVariant(status: string): 'success' | 'warning' | 'neutral' {
  if (status === 'published') return 'success';
  if (status === 'draft') return 'warning';
  return 'neutral';
}

async function load(page = 1) {
  loading.value = true;
  try {
    const params: any = {};
    if (filters.value.semester_id) params.semester_id = filters.value.semester_id;
    if (filters.value.letter_grade) params.letter_grade = filters.value.letter_grade;
    if (filters.value.status) params.status = filters.value.status;
    if (search.value.trim()) params.search = search.value.trim();

    const res = await getResults(page, pagination.value.per_page, params);
    results.value = res.data;
    if (res.pagination) {
      pagination.value = res.pagination;
    }
  } catch (err) {
    handleApiError(err, uiStore, undefined, 'Failed to load results.');
  } finally {
    loading.value = false;
    refreshing.value = false;
  }
}

async function loadSemesters() {
  try {
    const res = await getSemesters(1, 100);
    semesters.value = res.data;
  } catch {
    // Non-blocking
  }
}

function handleSearch(val: string) {
  search.value = val;
  pagination.value.current_page = 1;
  load(1);
}

function handleRefresh() {
  refreshing.value = true;
  load(pagination.value.current_page);
}

function clearFilters() {
  filters.value = {
    semester_id: null,
    letter_grade: null,
    status: null,
  };
  search.value = '';
  load(1);
}

onMounted(() => {
  load();
  loadSemesters();
  document.addEventListener('click', handleColumnOutsideClick);
});

onUnmounted(() => {
  document.removeEventListener('click', handleColumnOutsideClick);
});
</script>

<template>
  <div class="mx-auto w-full max-w-360 space-y-6 px-6 py-6">
    <!-- Header Toolbar -->
    <ResourceToolbar
      title="Results & Verification"
      description="View student final exam results, letter grades, and publication status."
      search-placeholder="Search by student, ID, or course…"
      :search="search"
      :show-search="true"
      :show-filter="true"
      :show-columns="true"
      :show-refresh="true"
      :refreshing="refreshing"
      :has-active-filters="hasActiveFilters"
      :filter-count="filterCount"
      @update:search="handleSearch"
      @refresh="handleRefresh"
      @columns="toggleColumnSelector"
      @clear-filters="clearFilters"
    >
      <!-- Column Picker -->
      <template #columns>
        <div
          v-if="showColumns"
          data-columns-menu
          class="absolute right-0 top-full mt-2 z-50 w-56 rounded-xl border border-border bg-surface p-2 shadow-xl"
        >
          <div class="flex items-center justify-between px-2 py-1.5">
            <span class="text-xs font-semibold text-text">Columns</span>
            <button
              type="button"
              class="text-xs font-medium text-text/50 transition-colors hover:text-accent cursor-pointer"
              @click="resetColumns"
            >
              Reset
            </button>
          </div>

          <div class="my-1 border-t border-border" />

          <button
            v-for="column in columns"
            :key="column.key"
            type="button"
            class="flex w-full items-center gap-2 rounded-lg px-2 py-1.5 text-left text-sm transition-colors hover:bg-text/5 cursor-pointer"
            @click="toggleColumn(column.key)"
          >
            <span
              class="flex h-4 w-4 shrink-0 items-center justify-center rounded border transition-colors"
              :class="isColumnVisible(column.key) ? 'border-accent bg-accent text-white' : 'border-border bg-surface'"
            >
              <Check v-if="isColumnVisible(column.key)" class="h-3 w-3" />
            </span>
            <span class="flex-1 text-xs font-medium text-text/80">{{ column.label }}</span>
            <span v-if="column.required" class="text-[10px] font-mono text-text/40">Req</span>
          </button>

          <div class="mt-1 border-t border-border pt-1">
            <div class="px-2 py-1 text-[11px] text-text/40">{{ visibleColumnCount }} columns visible</div>
          </div>
        </div>
      </template>

      <!-- Filter Bar -->
      <template #filters>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
          <BaseSelect
            v-model="filters.semester_id"
            label="Semester"
            :options="semesterOptions"
            placeholder="All Semesters"
            @update:model-value="() => load(1)"
          />
          <BaseSelect
            v-model="filters.letter_grade"
            label="Letter Grade"
            :options="gradeOptions"
            placeholder="All Grades"
            @update:model-value="() => load(1)"
          />
          <BaseSelect
            v-model="filters.status"
            label="Status"
            :options="statusOptions"
            placeholder="All Statuses"
            @update:model-value="() => load(1)"
          />
        </div>

        <div v-if="hasActiveFilters" class="mt-4 flex justify-end">
          <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-xs font-medium text-text/55 transition-colors hover:bg-text/5 hover:text-accent cursor-pointer"
            @click="clearFilters"
          >
            <RotateCcw class="h-3.5 w-3.5" />
            Reset all filters
          </button>
        </div>
      </template>
    </ResourceToolbar>

    <!-- Metrics Cards -->
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
      <div class="rounded-xl border border-border bg-surface p-4 shadow-sm">
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-accent/10 text-accent">
            <BarChart3 class="h-5 w-5" />
          </div>
          <div>
            <p class="text-xs font-medium text-text/60">Total Results</p>
            <p class="text-xl font-bold text-text">{{ totalResults }}</p>
          </div>
        </div>
      </div>

      <div class="rounded-xl border border-border bg-surface p-4 shadow-sm">
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600">
            <CheckCircle2 class="h-5 w-5" />
          </div>
          <div>
            <p class="text-xs font-medium text-text/60">Published</p>
            <p class="text-xl font-bold text-text">{{ publishedCount }}</p>
          </div>
        </div>
      </div>

      <div class="rounded-xl border border-border bg-surface p-4 shadow-sm">
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-500/10 text-blue-600">
            <Award class="h-5 w-5" />
          </div>
          <div>
            <p class="text-xs font-medium text-text/60">Average Score</p>
            <p class="text-xl font-bold text-text">{{ averageScore }} pts</p>
          </div>
        </div>
      </div>

      <div class="rounded-xl border border-border bg-surface p-4 shadow-sm">
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-500/10 text-indigo-600">
            <GraduationCap class="h-5 w-5" />
          </div>
          <div>
            <p class="text-xs font-medium text-text/60">Pass Rate</p>
            <p class="text-xl font-bold text-text">{{ passRate }}%</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Data Table Container -->
    <div class="overflow-hidden rounded-xl border border-border bg-surface shadow-sm">
      <div class="overflow-x-auto min-w-full">
        <table class="w-full text-left border-collapse text-sm">
          <thead>
            <tr class="border-b border-border bg-bg/50 text-xs font-medium text-text/60">
              <th v-if="isColumnVisible('student')" class="px-4 py-3 font-semibold text-text">Student</th>
              <th v-if="isColumnVisible('course')" class="px-4 py-3 font-semibold text-text">Course</th>
              <th v-if="isColumnVisible('score')" class="px-4 py-3 font-semibold text-text">Total Score</th>
              <th v-if="isColumnVisible('letter_grade')" class="px-4 py-3 font-semibold text-text">Letter Grade</th>
              <th v-if="isColumnVisible('section')" class="px-4 py-3">Section</th>
              <th v-if="isColumnVisible('semester')" class="px-4 py-3">Semester</th>
              <th v-if="isColumnVisible('status')" class="px-4 py-3">Status</th>
              <th v-if="isColumnVisible('published_by')" class="px-4 py-3">Published By</th>
              <th v-if="isColumnVisible('published_at')" class="px-4 py-3">Published At</th>
              <th v-if="isColumnVisible('actions')" class="px-4 py-3 text-right">Actions</th>
            </tr>
          </thead>

          <tbody v-if="loading" class="divide-y divide-border">
            <tr v-for="n in 5" :key="n" class="animate-pulse">
              <td v-for="c in visibleColumnCount" :key="c" class="px-4 py-4">
                <div class="h-4 w-24 rounded bg-text/10" />
              </td>
            </tr>
          </tbody>

          <tbody v-else-if="results.length > 0" class="divide-y divide-border">
            <tr
              v-for="item in results"
              :key="item.id"
              class="group transition-colors hover:bg-bg/40 cursor-pointer"
              @click="openDetail(item)"
            >
              <!-- Student -->
              <td v-if="isColumnVisible('student')" class="px-4 py-3.5">
                <div class="flex items-center gap-2.5">
                  <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-accent/10 text-xs font-bold text-accent">
                    {{ item.student?.user?.first_name?.[0] || 'S' }}{{ item.student?.user?.last_name?.[0] || '' }}
                  </div>
                  <div class="min-w-0">
                    <p class="truncate font-semibold text-text group-hover:text-accent transition-colors">
                      {{ item.student?.user?.full_name || 'Unknown Student' }}
                    </p>
                    <p class="font-mono text-xs text-text/50">
                      {{ item.student?.student_number || '—' }}
                    </p>
                  </div>
                </div>
              </td>

              <!-- Course -->
              <td v-if="isColumnVisible('course')" class="px-4 py-3.5">
                <div class="min-w-0">
                  <span class="inline-block font-mono text-xs font-semibold text-accent">
                    {{ item.course_offering?.course?.code || '—' }}
                  </span>
                  <p class="truncate text-xs text-text/70">
                    {{ item.course_offering?.course?.title || '—' }}
                  </p>
                </div>
              </td>

              <!-- Score -->
              <td v-if="isColumnVisible('score')" class="px-4 py-3.5 whitespace-nowrap">
                <span class="font-mono font-bold text-text text-sm">
                  {{ item.total_score }}
                </span>
                <span class="text-xs text-text/50 ml-1">pts</span>
              </td>

              <!-- Grade -->
              <td v-if="isColumnVisible('letter_grade')" class="px-4 py-3.5 whitespace-nowrap">
                <BaseBadge :variant="getGradeBadgeVariant(item.letter_grade)">
                  <span class="font-bold font-mono">{{ item.letter_grade }}</span>
                </BaseBadge>
              </td>

              <!-- Section -->
              <td v-if="isColumnVisible('section')" class="px-4 py-3.5 text-xs text-text/70 whitespace-nowrap">
                {{ item.student?.section?.name || '—' }}
              </td>

              <!-- Semester -->
              <td v-if="isColumnVisible('semester')" class="px-4 py-3.5 text-xs text-text/70 whitespace-nowrap">
                {{ item.course_offering?.semester?.name || '—' }}
              </td>

              <!-- Status -->
              <td v-if="isColumnVisible('status')" class="px-4 py-3.5 whitespace-nowrap">
                <BaseBadge :variant="getStatusBadgeVariant(item.status)">
                  {{ item.status }}
                </BaseBadge>
              </td>

              <!-- Published By -->
              <td v-if="isColumnVisible('published_by')" class="px-4 py-3.5 text-xs text-text/60 whitespace-nowrap">
                {{ item.publisher?.full_name || '—' }}
              </td>

              <!-- Published At -->
              <td v-if="isColumnVisible('published_at')" class="px-4 py-3.5 font-mono text-xs text-text/50 whitespace-nowrap">
                {{ item.published_at || '—' }}
              </td>

              <!-- Actions -->
              <td v-if="isColumnVisible('actions')" class="px-4 py-3.5 text-right whitespace-nowrap" @click.stop>
                <button
                  type="button"
                  class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1 text-xs font-medium text-text/70 hover:bg-bg hover:text-accent border border-border/60 transition-colors cursor-pointer"
                  @click="openDetail(item)"
                >
                  <Eye class="h-3.5 w-3.5" />
                  View
                </button>
              </td>
            </tr>
          </tbody>

          <tbody v-else>
            <tr>
              <td :colspan="visibleColumnCount" class="px-6 py-16 text-center">
                <div class="mx-auto flex max-w-sm flex-col items-center">
                  <div class="flex h-12 w-12 items-center justify-center rounded-full bg-accent/10 text-accent">
                    <BarChart3 class="h-6 w-6" />
                  </div>
                  <p class="mt-4 text-base font-semibold text-text">No results found</p>
                  <p class="mt-1 text-xs text-text/50">
                    {{ hasActiveFilters ? 'Try adjusting your filters or search terms.' : 'No course results have been published yet.' }}
                  </p>
                  <button
                    v-if="hasActiveFilters"
                    type="button"
                    class="mt-4 inline-flex items-center gap-1.5 rounded-lg border border-border bg-surface px-3 py-1.5 text-xs font-medium text-text hover:bg-bg transition-colors cursor-pointer"
                    @click="clearFilters"
                  >
                    <RotateCcw class="h-3.5 w-3.5" />
                    Reset filters
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <AppPagination :pagination="pagination" @change-page="load" />
    </div>

    <!-- Detail Modal -->
    <div
      v-if="isDetailOpen && selectedResult"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs"
      @click.self="closeDetail"
    >
      <div class="w-full max-w-lg rounded-2xl border border-border bg-surface p-6 shadow-2xl space-y-5 animate-in fade-in zoom-in-95 duration-150">
        <!-- Modal Header -->
        <div class="flex items-center justify-between border-b border-border pb-4">
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-accent/10 text-accent">
              <Award class="h-5 w-5" />
            </div>
            <div>
              <h3 class="font-bold text-text text-base">Result Details</h3>
              <p class="text-xs text-text/60">Final grade and performance summary</p>
            </div>
          </div>
          <button
            type="button"
            class="rounded-lg p-1.5 text-text/40 hover:bg-bg hover:text-text transition-colors cursor-pointer"
            @click="closeDetail"
          >
            <X class="h-5 w-5" />
          </button>
        </div>

        <!-- Student Info Card -->
        <div class="rounded-xl border border-border bg-bg/50 p-4 space-y-2">
          <p class="text-xs font-semibold text-text/50 uppercase tracking-wider">Student Information</p>
          <div class="flex items-center justify-between">
            <div>
              <p class="font-bold text-text text-sm">{{ selectedResult.student?.user?.full_name }}</p>
              <p class="text-xs text-text/60 font-mono">{{ selectedResult.student?.user?.email }}</p>
            </div>
            <div class="text-right">
              <span class="inline-block rounded-md bg-accent/10 px-2 py-0.5 font-mono text-xs font-semibold text-accent">
                {{ selectedResult.student?.student_number }}
              </span>
              <p v-if="selectedResult.student?.section" class="text-xs text-text/50 mt-0.5">
                {{ selectedResult.student.section.name }}
              </p>
            </div>
          </div>
        </div>

        <!-- Grade & Course Performance -->
        <div class="grid grid-cols-2 gap-3">
          <div class="rounded-xl border border-border bg-surface p-4 text-center">
            <p class="text-xs text-text/60 font-medium">Total Score</p>
            <p class="mt-1 text-2xl font-black font-mono text-text">
              {{ selectedResult.total_score }}
            </p>
          </div>

          <div class="rounded-xl border border-border bg-surface p-4 text-center">
            <p class="text-xs text-text/60 font-medium">Letter Grade</p>
            <div class="mt-1 flex justify-center">
              <BaseBadge :variant="getGradeBadgeVariant(selectedResult.letter_grade)" size="lg">
                <span class="text-lg font-black font-mono">{{ selectedResult.letter_grade }}</span>
              </BaseBadge>
            </div>
          </div>
        </div>

        <!-- Course & Academic Context -->
        <div class="space-y-2 text-xs">
          <div class="flex justify-between py-1.5 border-b border-border/50">
            <span class="text-text/60">Course</span>
            <span class="font-semibold text-text">
              {{ selectedResult.course_offering?.course?.code }} — {{ selectedResult.course_offering?.course?.title }}
            </span>
          </div>
          <div class="flex justify-between py-1.5 border-b border-border/50">
            <span class="text-text/60">Semester</span>
            <span class="font-medium text-text">{{ selectedResult.course_offering?.semester?.name || '—' }}</span>
          </div>
          <div class="flex justify-between py-1.5 border-b border-border/50">
            <span class="text-text/60">Status</span>
            <BaseBadge :variant="getStatusBadgeVariant(selectedResult.status)">
              {{ selectedResult.status }}
            </BaseBadge>
          </div>
          <div v-if="selectedResult.publisher" class="flex justify-between py-1.5 border-b border-border/50">
            <span class="text-text/60">Published By</span>
            <span class="font-medium text-text">{{ selectedResult.publisher.full_name }}</span>
          </div>
          <div v-if="selectedResult.published_at" class="flex justify-between py-1.5">
            <span class="text-text/60">Published Date</span>
            <span class="font-mono text-text/80">{{ selectedResult.published_at }}</span>
          </div>
        </div>

        <!-- Close Button -->
        <div class="pt-2 flex justify-end">
          <button
            type="button"
            class="rounded-xl bg-accent px-4 py-2 text-xs font-semibold text-white hover:bg-accent/90 transition-colors cursor-pointer"
            @click="closeDetail"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
