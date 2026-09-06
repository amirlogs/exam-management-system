<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Archive, CircleHelp, Eye, Plus, RotateCcw } from 'lucide-vue-next';
import { useRouter } from 'vue-router';

import ResourceToolbar from '@/shared/components/ResourceToolbar.vue';
import TableRowActions from '@/shared/components/TableRowActions.vue';
import ConfirmModal from '@/shared/components/ConfirmModal.vue';
import BaseButton from '@/shared/components/ui/BaseButton.vue';
import BaseBadge from '@/shared/components/ui/BaseBadge.vue';
import BaseSelect from '@/shared/components/ui/BaseSelect.vue';
import AppPagination from '@/shared/components/AppPagination.vue';

import { handleApiError } from '@/shared/utils/apiError';
import { useUiStore } from '@/stores/ui';

import { getTeaching } from '@/modules/instructor/teaching/api/teaching';
import * as api from '../api/questions';

import type { Question, QuestionDifficulty, QuestionType } from '../types/question';
import type { Pagination } from '@/shared/composables/useCrudResource';

type QuestionColumn = 'question' | 'course' | 'type' | 'chapter' | 'difficulty' | 'status' | 'created_at';

const router = useRouter();
const uiStore = useUiStore();

const questions = ref<Question[]>([]);
const activeTab = ref<'active' | 'archived'>('active');

const loading = ref(false);
const refreshing = ref(false);

const search = ref('');
const filterCourse = ref('');
const filterType = ref<QuestionType | ''>('');
const filterDifficulty = ref<QuestionDifficulty | ''>('');

const activeCount = ref<number | null>(null);
const archivedCount = ref<number | null>(null);

const columns = [
  {
    key: 'question' as QuestionColumn,
    label: 'Question',
    required: true,
  },
  {
    key: 'course' as QuestionColumn,
    label: 'Course',
    required: false,
  },
  {
    key: 'type' as QuestionColumn,
    label: 'Type',
    required: false,
  },
  {
    key: 'chapter' as QuestionColumn,
    label: 'Chapter',
    required: false,
  },
  {
    key: 'difficulty' as QuestionColumn,
    label: 'Difficulty',
    required: false,
  },
  {
    key: 'status' as QuestionColumn,
    label: 'Status',
    required: false,
  },
  {
    key: 'created_at' as QuestionColumn,
    label: 'Created',
    required: false,
  },
];

const showColumns = ref(false);
const visibleColumns = ref<QuestionColumn[]>(['question', 'course', 'type', 'difficulty', 'status']);

const isColumnVisible = (column: QuestionColumn) => visibleColumns.value.includes(column);

const toggleColumn = (column: QuestionColumn) => {
  const config = columns.find((item) => item.key === column);
  if (config?.required) return;

  if (isColumnVisible(column)) {
    visibleColumns.value = visibleColumns.value.filter((item) => item !== column);
  } else {
    visibleColumns.value = [...visibleColumns.value, column];
  }
};

const resetColumns = () => {
  visibleColumns.value = ['question', 'course', 'type', 'difficulty', 'status'];
};

const visibleColumnCount = computed(() => visibleColumns.value.length);
const totalTableColumns = computed(() => visibleColumnCount.value + 1);

function handleDocumentClick(event: MouseEvent) {
  const target = event.target as HTMLElement;
  if (!target.closest('[data-columns-container]')) {
    showColumns.value = false;
  }
}

const pagination = ref<Pagination>({
  current_page: 1,
  last_page: 1,
  per_page: 12,
  total: 0,
  from: null,
  to: null,
});

const courseOptions = ref<
  {
    value: string;
    label: string;
  }[]
>([
  {
    value: '',
    label: 'All courses',
  },
]);

const typeOptions = [
  {
    value: '',
    label: 'All types',
  },
  {
    value: 'mcq',
    label: 'Multiple choice',
  },
  {
    value: 'true_false',
    label: 'True / False',
  },
  {
    value: 'short_answer',
    label: 'Short answer',
  },
  {
    value: 'essay',
    label: 'Essay',
  },
];

const difficultyOptions = [
  {
    value: '',
    label: 'All difficulties',
  },
  {
    value: 'easy',
    label: 'Easy',
  },
  {
    value: 'medium',
    label: 'Medium',
  },
  {
    value: 'hard',
    label: 'Hard',
  },
];

const statusVariant: Record<string, 'neutral' | 'info' | 'danger' | 'warning' | 'success' | 'dark'> = {
  active: 'success',
  archived: 'dark',
};

const difficultyVariant: Record<string, 'neutral' | 'info' | 'danger' | 'warning' | 'success' | 'dark'> = {
  easy: 'success',
  medium: 'warning',
  hard: 'danger',
};

const hasActiveFilters = computed(() => {
  return filterCourse.value !== '' || filterType.value !== '' || filterDifficulty.value !== '';
});

const filterCount = computed(() => {
  let count = 0;
  if (filterCourse.value) count++;
  if (filterType.value) count++;
  if (filterDifficulty.value) count++;
  return count;
});

function formatType(type: string) {
  const labels: Record<string, string> = {
    mcq: 'Multiple choice',
    true_false: 'True / False',
    short_answer: 'Short answer',
    essay: 'Essay',
  };

  return labels[type] || type;
}

function formatDifficulty(value: string) {
  return value.charAt(0).toUpperCase() + value.slice(1);
}

async function loadCourses() {
  try {
    const res = await getTeaching(1, 1000);

    const unique = new Map<
      number,
      {
        value: string;
        label: string;
      }
    >();

    for (const teaching of res.data) {
      if (!teaching.course) {
        continue;
      }

      if (!unique.has(teaching.course.id)) {
        unique.set(teaching.course.id, {
          value: String(teaching.course.id),
          label: `${teaching.course.code} - ${teaching.course.name}`,
        });
      }
    }

    courseOptions.value = [
      {
        value: '',
        label: 'All courses',
      },
      ...Array.from(unique.values()),
    ];
  } catch (error) {
    handleApiError(error, uiStore, undefined, 'Failed to load course filters.');
  }
}

function buildFilters() {
  return {
    search: search.value || undefined,
    course_id: filterCourse.value || undefined,
    type: filterType.value || undefined,
    difficulty: filterDifficulty.value || undefined,
  };
}

async function loadCurrent(page = 1) {
  loading.value = true;

  try {
    const res = await api.listQuestions(page, pagination.value.per_page, activeTab.value === 'archived', buildFilters());

    questions.value = res.data;
    pagination.value = res.pagination;
  } catch (error) {
    handleApiError(error, uiStore, undefined, 'Failed to load questions.');
  } finally {
    loading.value = false;
  }
}

async function loadCounts() {
  try {
    const filters = buildFilters();

    const [activeResponse, archivedResponse] = await Promise.all([api.listQuestions(1, 1, false, filters), api.listQuestions(1, 1, true, filters)]);

    activeCount.value = activeResponse.pagination.total;
    archivedCount.value = archivedResponse.pagination.total;
  } catch (error) {
    handleApiError(error, uiStore, undefined, 'Failed to load question counts.');
  }
}

async function reload(page = 1, includeCounts = true) {
  await loadCurrent(page);

  if (includeCounts) {
    await loadCounts();
  }
}

async function refresh() {
  refreshing.value = true;

  try {
    await reload(pagination.value.current_page, true);
  } finally {
    refreshing.value = false;
  }
}

function changeTab(tab: 'active' | 'archived') {
  if (activeTab.value === tab) {
    return;
  }

  activeTab.value = tab;
  reload(1, false);
}

function clearFilters() {
  search.value = '';
  filterCourse.value = '';
  filterType.value = '';
  filterDifficulty.value = '';

  reload(1, true);
}

function addQuestion() {
  router.push({
    name: 'instructor.questions.add',
  });
}

function openQuestion(question: Question) {
  router.push({
    name: 'instructor.questions.detail',
    params: {
      questionId: question.id,
    },
  });
}

function openEdit(question: Question) {
  router.push({
    name: 'instructor.questions.edit',
    params: {
      questionId: question.id,
    },
  });
}

const showArchiveModal = ref(false);
const showRestoreModal = ref(false);
const selectedQuestion = ref<Question | null>(null);
const actionLoading = ref(false);

const rowExtraActions = [
  {
    key: 'view',
    label: 'View details',
    icon: Eye,
  },
];

function handleRowAction(key: string, question: Question) {
  if (key === 'view') {
    openQuestion(question);
  }
}

function openArchive(question: Question) {
  selectedQuestion.value = question;
  showArchiveModal.value = true;
}

function openRestore(question: Question) {
  selectedQuestion.value = question;
  showRestoreModal.value = true;
}

async function confirmArchive() {
  if (!selectedQuestion.value) return;

  actionLoading.value = true;
  try {
    await api.archiveQuestion(selectedQuestion.value.id);
    uiStore.showToast('Question archived successfully.', 'success');
    showArchiveModal.value = false;
    selectedQuestion.value = null;
    await reload(pagination.value.current_page, true);
  } catch (error) {
    handleApiError(error, uiStore, undefined, 'Failed to archive question.');
  } finally {
    actionLoading.value = false;
  }
}

async function confirmRestore() {
  if (!selectedQuestion.value) return;

  actionLoading.value = true;
  try {
    await api.restoreQuestion(selectedQuestion.value.id);
    uiStore.showToast('Question restored successfully.', 'success');
    showRestoreModal.value = false;
    selectedQuestion.value = null;
    await reload(pagination.value.current_page, true);
  } catch (error) {
    handleApiError(error, uiStore, undefined, 'Failed to restore question.');
  } finally {
    actionLoading.value = false;
  }
}

watch(search, () => {
  reload(1, true);
});

watch([filterCourse, filterType, filterDifficulty], () => {
  reload(1, true);
});

onMounted(async () => {
  document.addEventListener('click', handleDocumentClick);
  await loadCourses();
  await reload(1, true);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', handleDocumentClick);
});
</script>

<template>
  <div class="mx-auto w-full max-w-360 space-y-6 px-6 py-6">
    <div data-columns-container class="relative">
      <ResourceToolbar
        title="Question Bank"
        description="Create, review, and manage questions available to your teaching context."
        search-placeholder="Search question text or chapter…"
        :search="search"
        :show-search="true"
        :show-filter="true"
        :show-refresh="true"
        :show-columns="true"
        :show-fullscreen="true"
        :refreshing="refreshing"
        :show-tabs="true"
        :active-tab="activeTab"
        :active-count="activeCount"
        :archived-count="archivedCount"
        :has-active-filters="hasActiveFilters"
        :filter-count="filterCount"
        @update:search="(value) => (search = value)"
        @change-tab="changeTab"
        @refresh="refresh"
        @columns="showColumns = !showColumns"
        @clear-filters="clearFilters">
        <template #actions>
          <BaseButton v-can="'question.create'" @click="addQuestion">
            <template #icon>
              <Plus class="h-4 w-4" />
            </template>

            Add question
          </BaseButton>
        </template>

        <template #filters>
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <BaseSelect v-model="filterCourse" label="Course" :options="courseOptions" placeholder="All courses" />

            <BaseSelect v-model="filterType" label="Question type" :options="typeOptions" placeholder="All types" />

            <BaseSelect v-model="filterDifficulty" label="Difficulty" :options="difficultyOptions" placeholder="All difficulties" />
          </div>
        </template>
      </ResourceToolbar>

      <!-- Admin-style Columns Dropdown -->
      <div v-if="showColumns" class="absolute right-0 top-full z-40 mt-2 w-64 rounded-lg border border-border bg-surface p-2 shadow-xl" @click.stop>
        <div class="flex items-center justify-between px-2 py-2">
          <div>
            <p class="text-sm font-semibold text-text">Columns</p>
            <p class="mt-0.5 text-xs text-text/45">Choose what appears in the table</p>
          </div>

          <button type="button" class="rounded-md px-2 py-1 text-xs font-medium text-text/50 transition-colors hover:bg-text/5 hover:text-accent" @click="resetColumns">
            Reset
          </button>
        </div>

        <div class="my-1 border-t border-border" />

        <div class="space-y-0.5">
          <button
            v-for="column in columns"
            :key="column.key"
            type="button"
            class="flex w-full items-center gap-3 rounded-md px-2 py-2 text-left transition-colors hover:bg-text/5"
            @click="toggleColumn(column.key)">
            <span
              class="flex h-4 w-4 shrink-0 items-center justify-center rounded border transition-colors"
              :class="isColumnVisible(column.key) ? 'border-accent bg-accent text-white' : 'border-border bg-surface'">
              <svg v-if="isColumnVisible(column.key)" viewBox="0 0 12 12" class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M2 6l2.5 2.5L10 3" />
              </svg>
            </span>

            <span class="flex-1 text-sm text-text/80">
              {{ column.label }}
            </span>

            <span v-if="column.required" class="text-[10px] font-medium text-text/35"> Always </span>
          </button>
        </div>

        <div class="mt-1 border-t border-border px-2 pt-2">
          <p class="text-[11px] text-text/40">{{ visibleColumnCount }} columns visible</p>
        </div>
      </div>
    </div>

    <div class="overflow-hidden rounded-md border border-border bg-surface">
      <div class="overflow-x-auto">
        <table class="w-full min-w-220 border-collapse">
          <thead>
            <tr class="border-b border-border bg-text/2.5">
              <th v-if="isColumnVisible('question')" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Question</th>

              <th v-if="isColumnVisible('course')" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Course</th>

              <th v-if="isColumnVisible('type')" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Type</th>

              <th v-if="isColumnVisible('chapter')" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Chapter</th>

              <th v-if="isColumnVisible('difficulty')" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Difficulty</th>

              <th v-if="isColumnVisible('status')" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Status</th>

              <th v-if="isColumnVisible('created_at')" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Created</th>

              <th class="w-16 px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-text/50">
                <span class="sr-only">Actions</span>
              </th>
            </tr>
          </thead>

          <tbody v-if="loading" class="divide-y divide-border">
            <tr v-for="row in 6" :key="row">
              <td :colspan="totalTableColumns" class="px-4 py-5">
                <div class="h-3.5 w-72 animate-pulse rounded bg-text/5" />
              </td>
            </tr>
          </tbody>

          <tbody v-else-if="questions.length" class="divide-y divide-border">
            <tr v-for="question in questions" :key="question.id" class="group transition-colors hover:bg-text/2">
              <td v-if="isColumnVisible('question')" class="max-w-120 cursor-pointer px-4 py-4" @click="openQuestion(question)">
                <div class="flex min-w-0 items-start gap-3">
                  <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-accent/10 text-accent">
                    <CircleHelp class="h-4 w-4" />
                  </div>

                  <div class="min-w-0">
                    <p class="line-clamp-2 text-sm font-medium text-text group-hover:text-accent">
                      {{ question.content }}
                    </p>

                    <p class="mt-1 text-xs text-text/40">#{{ question.id }}</p>
                  </div>
                </div>
              </td>

              <td v-if="isColumnVisible('course')" class="px-4 py-4">
                <p class="text-sm font-medium text-text">
                  {{ question.course?.name || '—' }}
                </p>

                <p class="mt-0.5 font-mono text-xs text-text/40">
                  {{ question.course?.code || '—' }}
                </p>
              </td>

              <td v-if="isColumnVisible('type')" class="px-4 py-4">
                <BaseBadge variant="neutral">
                  {{ formatType(question.type) }}
                </BaseBadge>
              </td>

              <td v-if="isColumnVisible('chapter')" class="px-4 py-4 text-sm text-text/60">
                {{ question.chapter || '—' }}
              </td>

              <td v-if="isColumnVisible('difficulty')" class="px-4 py-4">
                <BaseBadge :variant="difficultyVariant[question.difficulty] || 'neutral'">
                  {{ formatDifficulty(question.difficulty) }}
                </BaseBadge>
              </td>

              <td v-if="isColumnVisible('status')" class="px-4 py-4">
                <BaseBadge :variant="statusVariant[question.status] || 'neutral'">
                  {{ question.status === 'active' ? 'Active' : question.status === 'archived' ? 'Archived' : question.status }}
                </BaseBadge>
              </td>

              <td v-if="isColumnVisible('created_at')" class="px-4 py-4 font-mono text-xs text-text/50">
                {{ question.created_at || '—' }}
              </td>

              <td class="px-4 py-4 text-right">
                <TableRowActions
                  :active-tab="activeTab"
                  edit-permission="question.update"
                  archive-permission="question.archive"
                  restore-permission="question.restore"
                  :extra-actions="rowExtraActions"
                  @edit="openEdit(question)"
                  @archive="openArchive(question)"
                  @restore="openRestore(question)"
                  @action="(key) => handleRowAction(key, question)" />
              </td>
            </tr>
          </tbody>

          <tbody v-else>
            <tr>
              <td :colspan="totalTableColumns" class="px-6 py-16 text-center">
                <div class="mx-auto max-w-sm">
                  <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-bg">
                    <CircleHelp class="h-6 w-6 text-text/30" />
                  </div>

                  <p class="text-sm font-medium text-text">
                    {{ activeTab === 'archived' ? 'No archived questions found' : 'No questions found' }}
                  </p>

                  <p class="mt-1 text-sm text-text/45">
                    {{ activeTab === 'archived' ? 'Archived questions will appear here.' : 'Create a question or upload questions to build your bank.' }}
                  </p>

                  <BaseButton v-if="activeTab === 'active'" v-can="'question.create'" class="mt-4" @click="addQuestion">
                    <template #icon>
                      <Plus class="h-4 w-4" />
                    </template>

                    Add question
                  </BaseButton>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <AppPagination :pagination="pagination" @change-page="loadCurrent" />
    </div>
  </div>

  <ConfirmModal
    :show="showArchiveModal"
    title="Archive question?"
    :description="`Are you sure you want to archive question #${selectedQuestion?.id}? It can be restored later.`"
    confirm-text="Archive question"
    variant="danger"
    :icon="Archive"
    :loading="actionLoading"
    @close="showArchiveModal = false"
    @confirm="confirmArchive" />

  <ConfirmModal
    :show="showRestoreModal"
    title="Restore question?"
    :description="`This will return question #${selectedQuestion?.id} to the active question bank.`"
    confirm-text="Restore question"
    variant="accent"
    :icon="RotateCcw"
    :loading="actionLoading"
    @close="showRestoreModal = false"
    @confirm="confirmRestore" />
</template>
