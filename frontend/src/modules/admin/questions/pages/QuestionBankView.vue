<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue';
import { HelpCircle, Archive, RotateCcw, Upload, Sparkles } from 'lucide-vue-next';
import ResourceToolbar from '@/shared/components/ResourceToolbar.vue';
import TableRowActions from '@/shared/components/TableRowActions.vue';
import BaseBadge from '@/shared/components/ui/BaseBadge.vue';
import BaseButton from '@/shared/components/ui/BaseButton.vue';
import BaseSelect from '@/shared/components/ui/BaseSelect.vue';
import ConfirmModal from '@/shared/components/ConfirmModal.vue';
import AppPagination from '@/shared/components/AppPagination.vue';
import AiQuestionGeneratorModal from '@/modules/instructor/questions/components/AiQuestionGeneratorModal.vue';
import { useCrudResource } from '@/shared/composables/useCrudResource';
import { getQuestions, getArchivedQuestions, archiveQuestion, restoreQuestion } from '../api/questions';
import { getCourses } from '@/modules/admin/courses/api/courses';
import { useUiStore } from '@/stores/ui';
import { useRouter } from 'vue-router';

const uiStore = useUiStore();
const router = useRouter();

const showAiModal = ref(false);
const search = ref('');
const filters = ref({ course_id: null as number | null, type: null as string | null, difficulty: null as string | null });

const crud = useCrudResource<any>(
  {
    list: (page) => getQuestions(page, 12, { content: search.value, ...filters.value }),
    listArchived: (page) => getArchivedQuestions(page, 12, { content: search.value, ...filters.value }),
    remove: (q) => archiveQuestion(q.id),
    restore: async (q) => {
      const r = await restoreQuestion(q.id);
      return r;
    },
  },
  'content',
);

const courses = ref<{ id: number; name: string; code: string }[]>([]);
const courseOptions = computed(() => [{ value: null, label: 'All courses' }, ...courses.value.map((c) => ({ value: c.id, label: `${c.code} — ${c.name}` }))]);
const typeOptions = [
  { value: null, label: 'All types' },
  { value: 'mcq', label: 'MCQ' },
  { value: 'true_false', label: 'True/False' },
  { value: 'short_answer', label: 'Short Answer' },
  { value: 'essay', label: 'Essay' },
];
const difficultyOptions = [
  { value: null, label: 'Any difficulty' },
  { value: 'easy', label: 'Easy' },
  { value: 'medium', label: 'Medium' },
  { value: 'hard', label: 'Hard' },
];
const hasActiveFilters = computed(() => !!filters.value.course_id || !!filters.value.type || !!filters.value.difficulty);

async function loadCourses() {
  if (courses.value.length) return;
  const res = await getCourses(1, 200);
  courses.value = res.data;
}

watch([search, () => filters.value.course_id, () => filters.value.type, () => filters.value.difficulty], () => crud.load(1));

function clearFilters() {
  search.value = '';
  filters.value = { course_id: null, type: null, difficulty: null };
}
function retry() {
  crud.load(crud.currentPagination().current_page);
}

const showArchiveModal = ref(false);
const showRestoreModal = ref(false);
const selected = ref<any>(null);
const archiving = ref(false);
const restoring = ref(false);

function openArchive(q: any) {
  selected.value = q;
  showArchiveModal.value = true;
}
function openRestore(q: any) {
  selected.value = q;
  showRestoreModal.value = true;
}
async function confirmArchive() {
  if (!selected.value) return;
  archiving.value = true;
  try {
    await crud.archive(selected.value);
    showArchiveModal.value = false;
    selected.value = null;
  } catch {
    uiStore.showToast('Failed to archive question.', 'error');
  } finally {
    archiving.value = false;
  }
}
async function confirmRestore() {
  if (!selected.value) return;
  restoring.value = true;
  try {
    await crud.restore(selected.value);
    showRestoreModal.value = false;
    selected.value = null;
  } catch {
    uiStore.showToast('Failed to restore question.', 'error');
  } finally {
    restoring.value = false;
  }
}

const typeBadge: Record<string, any> = { mcq: 'info', true_false: 'neutral', short_answer: 'warning', essay: 'success' };

onMounted(async () => {
  await loadCourses();
  await crud.load(1);
});
</script>

<template>
  <div class="mx-auto w-full max-w-360 space-y-6 px-6 py-6">
    <ResourceToolbar
      title="Question Bank"
      description="All questions across every course."
      search-placeholder="Search question content…"
      :show-search="true"
      :show-filter="true"
      :show-refresh="true"
      :show-tabs="true"
      :active-tab="crud.activeTab.value"
      :active-count="crud.activePagination.value.total"
      :archived-count="crud.archivedPagination.value.total"
      :has-active-filters="hasActiveFilters"
      :refreshing="crud.loading.value"
      @change-tab="crud.changeTab"
      @update:search="search = $event"
      @refresh="retry"
      @clear-filters="clearFilters">
      <template #actions>
        <BaseButton v-can="'question.create_all'" variant="primary" @click="showAiModal = true">
          <template #icon><Sparkles class="h-4 w-4" /></template>
          Generate with AI
        </BaseButton>

        <BaseButton v-can="'question.create_all'" variant="secondary" @click="router.push({ name: 'admin-import-new', query: { type: 'questions' } })">
          <template #icon><Upload class="h-4 w-4" /></template>
          Import Questions
        </BaseButton>
      </template>
      <template #filters>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
          <BaseSelect v-model="filters.course_id" label="Course" :options="courseOptions" />
          <BaseSelect v-model="filters.type" label="Type" :options="typeOptions" />
          <BaseSelect v-model="filters.difficulty" label="Difficulty" :options="difficultyOptions" />
        </div>
      </template>
    </ResourceToolbar>

    <div v-can:any="['question.view_all']" class="rounded-md border border-border bg-surface">
      <div class="overflow-x-auto">
        <table class="w-full min-w-190 border-collapse">
          <thead>
            <tr class="border-b border-border bg-text/2.5">
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Question</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Course</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Type</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Difficulty</th>
              <th class="w-16 px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-text/50"><span class="sr-only">Actions</span></th>
            </tr>
          </thead>
          <tbody v-if="crud.loading.value" class="divide-y divide-border">
            <tr v-for="row in 6" :key="row">
              <td colspan="5" class="px-4 py-4"><div class="h-3.5 w-full max-w-sm animate-pulse rounded bg-text/5" /></td>
            </tr>
          </tbody>
          <tbody v-else-if="crud.currentList().length" class="divide-y divide-border">
            <tr v-for="q in crud.currentList()" :key="q.id" class="hover:bg-text/2">
              <td class="px-4 py-3.5 text-sm text-text max-w-md truncate">{{ q.content || '—' }}</td>
              <td class="px-4 py-3.5 text-sm text-text/60">{{ q.course?.code || '—' }}</td>
              <td class="px-4 py-3.5">
                <BaseBadge :variant="typeBadge[q.type] || 'neutral'" class="uppercase text-[10px]">{{ q.type }}</BaseBadge>
              </td>
              <td class="px-4 py-3.5 text-sm text-text/60 capitalize">{{ q.difficulty || '—' }}</td>
              <td class="px-4 py-3.5 text-right">
                <TableRowActions
                  :active-tab="crud.activeTab.value"
                  archive-permission="question.archive_all"
                  restore-permission="question.restore_all"
                  @archive="openArchive(q)"
                  @restore="openRestore(q)" />
              </td>
            </tr>
          </tbody>
          <tbody v-else>
            <tr>
              <td colspan="5" class="px-6 py-16 text-center text-sm text-text/55">No questions found.</td>
            </tr>
          </tbody>
        </table>
      </div>
      <AppPagination :pagination="crud.currentPagination()" @change-page="crud.load" />
    </div>
  </div>

  <ConfirmModal
    :show="showArchiveModal"
    title="Archive question?"
    description="This question will be removed from active views."
    confirm-text="Archive question"
    variant="danger"
    :icon="Archive"
    :loading="archiving"
    @close="showArchiveModal = false"
    @confirm="confirmArchive" />
  <ConfirmModal
    :show="showRestoreModal"
    title="Restore question?"
    description="This question will return to active views."
    confirm-text="Restore question"
    variant="accent"
    :icon="RotateCcw"
    :loading="restoring"
    @close="showRestoreModal = false"
    @confirm="confirmRestore" />

  <AiQuestionGeneratorModal
    v-model="showAiModal"
    scope="admin"
    @confirmed="crud.load(1)"
  />
</template>
