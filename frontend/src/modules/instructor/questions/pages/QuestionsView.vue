<script setup lang="ts">
import {
  computed,
  onMounted,
  ref,
  watch,
} from 'vue';
import {
  ChevronRight,
  CircleHelp,
  Columns3,
  Plus,
  RefreshCw,
} from 'lucide-vue-next';
import { useRouter } from 'vue-router';

import ResourceToolbar from '@/shared/components/ResourceToolbar.vue';
import BaseButton from '@/shared/components/ui/BaseButton.vue';
import BaseBadge from '@/shared/components/ui/BaseBadge.vue';
import BaseSelect from '@/shared/components/ui/BaseSelect.vue';
import AppPagination from '@/shared/components/AppPagination.vue';

import { handleApiError } from '@/shared/utils/apiError';
import { useUiStore } from '@/stores/ui';

import {
  getTeaching,
} from '@/modules/instructor/teaching/api/teaching';

import * as api from '../api/questions';

import type {
  Question,
  QuestionDifficulty,
  QuestionType,
} from '../types/question';

import type { Pagination } from '@/shared/composables/useCrudResource';

const router = useRouter();
const uiStore = useUiStore();

const questions = ref<Question[]>([]);

const activeTab = ref<'active' | 'archived'>(
  'active',
);

const loading = ref(false);
const refreshing = ref(false);

const search = ref('');
const filterCourse = ref('');
const filterType = ref<QuestionType | ''>('');
const filterChapter = ref('');
const filterDifficulty =
  ref<QuestionDifficulty | ''>('');

const activeCount = ref<number | null>(null);
const archivedCount = ref<number | null>(null);

const showColumnMenu = ref(false);

const visibleColumns = ref({
  question: true,
  course: true,
  type: true,
  chapter: true,
  difficulty: true,
  status: true,
});

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

const statusVariant: Record<
  string,
  'neutral' | 'info' | 'danger' | 'warning' | 'success' | 'dark'
> = {
  active: 'success',
  archived: 'dark',
};

const difficultyVariant: Record<
  string,
  'neutral' | 'info' | 'danger' | 'warning' | 'success' | 'dark'
> = {
  easy: 'success',
  medium: 'warning',
  hard: 'danger',
};

const hasActiveFilters = computed(() => {
  return (
    filterCourse.value !== '' ||
    filterType.value !== '' ||
    filterChapter.value !== '' ||
    filterDifficulty.value !== ''
  );
});

const filterCount = computed(() => {
  let count = 0;

  if (filterCourse.value) count++;
  if (filterType.value) count++;
  if (filterChapter.value) count++;
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
    handleApiError(
      error,
      uiStore,
      undefined,
      'Failed to load course filters.',
    );
  }
}

function buildFilters() {
  return {
    search: search.value || undefined,
    course_id:
      filterCourse.value || undefined,
    type:
      filterType.value || undefined,
    chapter:
      filterChapter.value || undefined,
    difficulty:
      filterDifficulty.value || undefined,
  };
}

async function loadCurrent(page = 1) {
  loading.value = true;

  try {
    const res = await api.listQuestions(
      page,
      pagination.value.per_page,
      activeTab.value === 'archived',
      buildFilters(),
    );

    questions.value = res.data;
    pagination.value = res.pagination;
  } catch (error) {
    handleApiError(
      error,
      uiStore,
      undefined,
      'Failed to load questions.',
    );
  } finally {
    loading.value = false;
  }
}

async function loadCounts() {
  try {
    const filters = buildFilters();

    const [
      activeResponse,
      archivedResponse,
    ] = await Promise.all([
      api.listQuestions(
        1,
        1,
        false,
        filters,
      ),
      api.listQuestions(
        1,
        1,
        true,
        filters,
      ),
    ]);

    activeCount.value =
      activeResponse.pagination.total;

    archivedCount.value =
      archivedResponse.pagination.total;
  } catch (error) {
    handleApiError(
      error,
      uiStore,
      undefined,
      'Failed to load question counts.',
    );
  }
}

async function reload(
  page = 1,
  includeCounts = true,
) {
  await loadCurrent(page);

  if (includeCounts) {
    await loadCounts();
  }
}

async function refresh() {
  refreshing.value = true;

  try {
    await reload(
      pagination.value.current_page,
      true,
    );
  } finally {
    refreshing.value = false;
  }
}

function changeTab(
  tab: 'active' | 'archived',
) {
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
  filterChapter.value = '';
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

function toggleColumn(
  column: keyof typeof visibleColumns.value,
) {
  if (column === 'question') {
    return;
  }

  visibleColumns.value[column] =
    !visibleColumns.value[column];
}

watch(
  search,
  () => {
    reload(1, true);
  },
);

watch(
  [
    filterCourse,
    filterType,
    filterChapter,
    filterDifficulty,
  ],
  () => {
    reload(1, true);
  },
);

onMounted(async () => {
  await loadCourses();
  await reload(1, true);
});
</script>

<template>
  <div
    class="mx-auto w-full max-w-360 space-y-6 px-6 py-6"
  >
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
      @update:search="
        (value) => (search = value)
      "
      @change-tab="changeTab"
      @refresh="refresh"
      @columns="
        showColumnMenu = !showColumnMenu
      "
      @clear-filters="clearFilters"
    >
      <template #actions>
        <BaseButton
          v-can="'question.create'"
          @click="addQuestion"
        >
          <template #icon>
            <Plus class="h-4 w-4" />
          </template>

          Add question
        </BaseButton>
      </template>

      <template #filters>
        <div
          class="grid grid-cols-1 gap-4 md:grid-cols-2"
        >
          <BaseSelect
            v-model="filterCourse"
            label="Course"
            :options="courseOptions"
            placeholder="All courses"
          />

          <BaseSelect
            v-model="filterType"
            label="Question type"
            :options="typeOptions"
            placeholder="All types"
          />

          <BaseSelect
            v-model="filterDifficulty"
            label="Difficulty"
            :options="difficultyOptions"
            placeholder="All difficulties"
          />

          <div>
            <label
              class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-text/60"
            >
              Chapter
            </label>

            <input
              v-model="filterChapter"
              type="text"
              placeholder="Filter by chapter…"
              class="w-full rounded-md border border-border bg-surface px-3 py-2 text-sm text-text outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/10"
              @keyup.enter="reload(1, true)"
            />
          </div>
        </div>
      </template>
    </ResourceToolbar>

    <div
      v-if="showColumnMenu"
      class="rounded-md border border-border bg-surface p-4 shadow-sm"
    >
      <div
        class="mb-3 flex items-center gap-2"
      >
        <Columns3
          class="h-4 w-4 text-text/50"
        />

        <p
          class="text-xs font-bold uppercase tracking-wide text-text/60"
        >
          Columns
        </p>
      </div>

      <div
        class="flex flex-wrap gap-2"
      >
        <button
          v-for="column in [
            {
              key: 'question',
              label: 'Question',
            },
            {
              key: 'course',
              label: 'Course',
            },
            {
              key: 'type',
              label: 'Type',
            },
            {
              key: 'chapter',
              label: 'Chapter',
            },
            {
              key: 'difficulty',
              label: 'Difficulty',
            },
            {
              key: 'status',
              label: 'Status',
            },
          ]"
          :key="column.key"
          type="button"
          class="rounded-md border px-3 py-1.5 text-xs font-medium transition-colors"
          :class="
            visibleColumns[
              column.key as keyof typeof visibleColumns
            ]
              ? 'border-accent/30 bg-accent/10 text-accent'
              : 'border-border text-text/45 hover:bg-bg'
          "
          @click="
            toggleColumn(
              column.key as keyof typeof visibleColumns,
            )
          "
        >
          {{ column.label }}
        </button>
      </div>
    </div>

    <div
      class="rounded-md border border-border bg-surface"
    >
      <div class="overflow-x-auto">
        <table
          class="w-full min-w-220 border-collapse"
        >
          <thead>
            <tr
              class="border-b border-border bg-text/2.5"
            >
              <th
                v-if="visibleColumns.question"
                class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50"
              >
                Question
              </th>

              <th
                v-if="visibleColumns.course"
                class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50"
              >
                Course
              </th>

              <th
                v-if="visibleColumns.type"
                class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50"
              >
                Type
              </th>

              <th
                v-if="visibleColumns.chapter"
                class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50"
              >
                Chapter
              </th>

              <th
                v-if="visibleColumns.difficulty"
                class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50"
              >
                Difficulty
              </th>

              <th
                v-if="visibleColumns.status"
                class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50"
              >
                Status
              </th>

              <th class="w-10 px-4 py-3" />
            </tr>
          </thead>

          <tbody
            v-if="loading"
            class="divide-y divide-border"
          >
            <tr
              v-for="row in 6"
              :key="row"
            >
              <td
                :colspan="
                  Object.values(visibleColumns).filter(
                    Boolean,
                  ).length + 1
                "
                class="px-4 py-5"
              >
                <div
                  class="h-3.5 w-72 animate-pulse rounded bg-text/5"
                />
              </td>
            </tr>
          </tbody>

          <tbody
            v-else-if="questions.length"
            class="divide-y divide-border"
          >
            <tr
              v-for="question in questions"
              :key="question.id"
              class="group cursor-pointer transition-colors hover:bg-text/2"
              @click="openQuestion(question)"
            >
              <td
                v-if="visibleColumns.question"
                class="max-w-120 px-4 py-4"
              >
                <div
                  class="flex min-w-0 items-start gap-3"
                >
                  <div
                    class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-accent/10 text-accent"
                  >
                    <CircleHelp class="h-4 w-4" />
                  </div>

                  <div class="min-w-0">
                    <p
                      class="line-clamp-2 text-sm font-medium text-text"
                    >
                      {{ question.content }}
                    </p>

                    <p
                      class="mt-1 text-xs text-text/40"
                    >
                      #{{ question.id }}
                    </p>
                  </div>
                </div>
              </td>

              <td
                v-if="visibleColumns.course"
                class="px-4 py-4"
              >
                <p
                  class="text-sm font-medium text-text"
                >
                  {{ question.course?.name || '—' }}
                </p>

                <p
                  class="mt-0.5 font-mono text-xs text-text/40"
                >
                  {{ question.course?.code || '—' }}
                </p>
              </td>

              <td
                v-if="visibleColumns.type"
                class="px-4 py-4"
              >
                <BaseBadge variant="neutral">
                  {{ formatType(question.type) }}
                </BaseBadge>
              </td>

              <td
                v-if="visibleColumns.chapter"
                class="px-4 py-4 text-sm text-text/60"
              >
                {{ question.chapter || '—' }}
              </td>

              <td
                v-if="visibleColumns.difficulty"
                class="px-4 py-4"
              >
                <BaseBadge
                  :variant="
                    difficultyVariant[
                      question.difficulty
                    ] || 'neutral'
                  "
                >
                  {{
                    formatDifficulty(
                      question.difficulty,
                    )
                  }}
                </BaseBadge>
              </td>

              <td
                v-if="visibleColumns.status"
                class="px-4 py-4"
              >
                <BaseBadge
                  :variant="
                    statusVariant[
                      question.status
                    ] || 'neutral'
                  "
                >
                  {{
                    question.status === 'active'
                      ? 'Active'
                      : question.status === 'archived'
                        ? 'Archived'
                        : question.status
                  }}
                </BaseBadge>
              </td>

              <td
                class="px-4 py-4 text-right"
              >
                <ChevronRight
                  class="inline h-4 w-4 text-text/30 transition-transform group-hover:translate-x-0.5"
                />
              </td>
            </tr>
          </tbody>

          <tbody v-else>
            <tr>
              <td
                :colspan="
                  Object.values(visibleColumns).filter(
                    Boolean,
                  ).length + 1
                "
                class="px-6 py-16 text-center"
              >
                <div class="mx-auto max-w-sm">
                  <div
                    class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-bg"
                  >
                    <CircleHelp
                      class="h-6 w-6 text-text/30"
                    />
                  </div>

                  <p
                    class="text-sm font-medium text-text"
                  >
                    {{
                      activeTab === 'archived'
                        ? 'No archived questions found'
                        : 'No questions found'
                    }}
                  </p>

                  <p
                    class="mt-1 text-sm text-text/45"
                  >
                    {{
                      activeTab === 'archived'
                        ? 'Archived questions will appear here.'
                        : 'Create a question or upload questions to build your bank.'
                    }}
                  </p>

                  <BaseButton
                    v-if="activeTab === 'active'"
                    v-can="'question.create'"
                    class="mt-4"
                    @click="addQuestion"
                  >
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

      <AppPagination
        :pagination="pagination"
        @change-page="loadCurrent"
      />
    </div>
  </div>
</template>
