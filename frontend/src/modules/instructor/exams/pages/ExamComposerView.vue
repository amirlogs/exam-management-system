<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { Plus, X, Pencil } from 'lucide-vue-next';
import BaseCard from '@/shared/components/ui/BaseCard.vue';
import BaseButton from '@/shared/components/ui/BaseButton.vue';
import BaseInput from '@/shared/components/ui/BaseInput.vue';
import BaseBadge from '@/shared/components/ui/BaseBadge.vue';
import * as examsApi from '../api/exams';
import * as questionsApi from '../../questions/api/questions';
import { getCourseOffering } from '../../course-offerings/api/courseOfferings';
import { useUiStore } from '@/stores/ui';
import type { Exam, Question } from '../types/exam';

const route = useRoute();
const router = useRouter();
const uiStore = useUiStore();
const examId = computed(() => Number(route.params.examId));

const exam = ref<Exam | null>(null);
const courseId = ref<number | null>(null);
const attached = ref<Question[]>([]);
const bank = ref<Question[]>([]);
const loadingBank = ref(false);
const activeTab = ref<'bank' | 'import'>('bank');
const search = ref('');

async function load() {
  exam.value = await examsApi.getExam(examId.value);

  const res = await examsApi.getExamQuestions(examId.value, 1, {});
  attached.value = res.data;

  try {
    const offering = await getCourseOffering(exam.value.course_offering_id);
    courseId.value = offering.course?.id ?? null;
  } catch (err: any) {
    uiStore.showToast(err?.response?.data?.message || 'Failed to load the course for this exam.', 'error');
  }
}

async function loadBank() {
  loadingBank.value = true;

  try {
    const res = await questionsApi.listQuestions(1, 100, false, {
      search: search.value || undefined,
      course_id: courseId.value ?? undefined,
    });

    bank.value = res.data.filter((q) => !attached.value.some((a) => a.id === q.id));
  } catch (err: any) {
    uiStore.showToast(err?.response?.data?.message || 'Failed to load the question bank.', 'error');
  } finally {
    loadingBank.value = false;
  }
}

onMounted(async () => {
  await load();
  await loadBank();
});

const attachedByType = computed(() => {
  const groups: Record<string, Question[]> = {};
  for (const q of attached.value) {
    if (!groups[q.type]) groups[q.type] = [];
    groups[q.type].push(q);
  }
  return groups;
});
const totalMarks = computed(() => attached.value.reduce((sum, q) => sum + (q.marks || 0), 0));

const editingComposition = ref(false);
const compositionDraft = ref<{ mcq: number; true_false: number }>({ mcq: 1, true_false: 1 });
function openCompositionEdit() {
  compositionDraft.value = {
    mcq: exam.value?.composition.mcq?.marks_each ?? 1,
    true_false: exam.value?.composition.true_false?.marks_each ?? 1,
  };
  editingComposition.value = true;
}
async function saveComposition() {
  if (!exam.value) return;
  try {
    exam.value = await examsApi.updateComposition(exam.value.id, {
      mcq: { marks_each: compositionDraft.value.mcq },
      true_false: { marks_each: compositionDraft.value.true_false },
    });
    editingComposition.value = false;
    uiStore.showToast('Composition updated.', 'success');
  } catch (err: any) {
    uiStore.showToast(err?.response?.data?.message || 'Failed to update composition.', 'error');
  }
}

const manualMarks = ref<Record<number, number>>({});
async function addFromBank(q: Question) {
  try {
    const isAuto = q.type === 'mcq' || q.type === 'true_false';
    await examsApi.addQuestion(examId.value, q.id, isAuto ? undefined : manualMarks.value[q.id] || 1);
    uiStore.showToast('Question added.', 'success');
    await load();
    await loadBank();
  } catch (err: any) {
    uiStore.showToast(err?.response?.data?.message || 'Failed to add question.', 'error');
  }
}

async function removeQuestion(examQuestionId: number) {
  try {
    await examsApi.removeExamQuestion(examId.value, examQuestionId);
    uiStore.showToast('Question removed.', 'success');
    await load();
    await loadBank();
  } catch (err: any) {
    uiStore.showToast(err?.response?.data?.message || 'Failed to remove question.', 'error');
  }
}

function goImport() {
  router.push({
    name: 'instructor.questions.import',
    query: {
      examId: String(examId.value),
      ...(courseId.value ? { courseId: String(courseId.value) } : {}),
    },
  });
}

let searchTimer: ReturnType<typeof setTimeout> | null = null;
function onSearchInput() {
  if (searchTimer) clearTimeout(searchTimer);
  searchTimer = setTimeout(loadBank, 300);
}

const canEdit = computed(() => exam.value?.status === 'draft');
</script>

<template>
  <div v-if="exam" class="mx-auto w-full max-w-360 space-y-6 px-6 py-8 min-h-[calc(100vh-68px)]">
    <div>
      <h1 class="text-2xl font-bold font-display text-text">{{ exam.title }}</h1>
      <p class="text-sm text-text/60 mt-1">Question Composer</p>
    </div>

    <!-- Composition summary -->
    <div class="flex flex-wrap gap-3 bg-surface border border-border rounded-xl p-4">
      <div class="flex items-center gap-2 bg-bg border border-border rounded-full px-4 py-2">
        <span class="font-mono text-xs">MCQ</span>
        <span class="text-xs text-text/60">· {{ exam.composition.mcq?.marks_each ?? 0 }} marks each</span>
        <button v-if="canEdit" class="text-text/40 hover:text-accent" @click="openCompositionEdit">
          <Pencil class="w-3.5 h-3.5" />
        </button>
      </div>
      <div class="flex items-center gap-2 bg-bg border border-border rounded-full px-4 py-2">
        <span class="font-mono text-xs">TRUE_FALSE</span>
        <span class="text-xs text-text/60">· {{ exam.composition.true_false?.marks_each ?? 0 }} marks each</span>
        <button v-if="canEdit" class="text-text/40 hover:text-accent" @click="openCompositionEdit">
          <Pencil class="w-3.5 h-3.5" />
        </button>
      </div>
      <div v-if="editingComposition" class="w-full flex items-end gap-3 pt-2 border-t border-border mt-2">
        <BaseInput v-model.number="compositionDraft.mcq" type="number" step="0.5" label="MCQ marks each" />
        <BaseInput v-model.number="compositionDraft.true_false" type="number" step="0.5" label="True/False marks each" />
        <BaseButton @click="saveComposition">Save</BaseButton>
        <BaseButton variant="secondary" @click="editingComposition = false">Cancel</BaseButton>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
      <!-- Left: bank / import -->
      <div class="lg:col-span-8">
        <BaseCard :padded="false" class="rounded-xl border border-border overflow-hidden shadow-sm">
          <div class="flex border-b border-border">
            <button
              class="flex-1 py-3.5 text-sm font-semibold border-b-2 transition-colors"
              :class="activeTab === 'bank' ? 'text-accent border-accent' : 'text-text/50 border-transparent'"
              @click="activeTab = 'bank'">
              Add from Bank
            </button>
            <button
              class="flex-1 py-3.5 text-sm font-semibold border-b-2 transition-colors"
              :class="activeTab === 'import' ? 'text-accent border-accent' : 'text-text/50 border-transparent'"
              @click="activeTab = 'import'">
              Import
            </button>
          </div>

          <div v-if="activeTab === 'bank'" class="p-5">
            <input v-model="search" placeholder="Search questions…" class="w-full px-3 py-2.5 rounded-lg border border-border bg-bg text-sm mb-4" @input="onSearchInput" />

            <p v-if="loadingBank" class="text-sm text-text/40 text-center py-8">Loading…</p>

            <div v-else class="divide-y divide-border max-h-[420px] overflow-y-auto">
              <div v-for="q in bank" :key="q.id" class="py-3 flex items-center justify-between gap-3">
                <div class="min-w-0 flex-1">
                  <p class="text-sm text-text truncate">{{ q.content }}</p>
                  <BaseBadge variant="neutral" class="uppercase text-[10px] mt-1">{{ q.type }} </BaseBadge>
                </div>
                <input
                  v-if="q.type !== 'mcq' && q.type !== 'true_false'"
                  v-model.number="manualMarks[q.id]"
                  type="number"
                  step="0.5"
                  placeholder="Marks"
                  class="w-20 px-2 py-1.5 rounded-md border border-border text-sm" />
                <BaseButton size="sm" variant="secondary" :disabled="!canEdit" @click="addFromBank(q)"> Add</BaseButton>
              </div>
              <p v-if="!bank.length" class="text-sm text-text/40 text-center py-8">No available questions for this course.</p>
            </div>
          </div>

          <div v-else class="p-10 text-center">
            <p class="text-sm text-text/60 mb-4">Import a CSV of questions directly into this course's bank, then add them here.</p>
            <BaseButton @click="goImport">Go to Import</BaseButton>
          </div>
        </BaseCard>
      </div>

      <!-- Right: attached list -->
      <div class="lg:col-span-4">
        <BaseCard class="sticky top-6">
          <h3 class="font-semibold text-text mb-4 pb-2 border-b border-border">Exam Structure</h3>
          <div class="space-y-5 max-h-[420px] overflow-y-auto">
            <div v-for="(qs, type) in attachedByType" :key="type">
              <h4 class="text-xs font-bold uppercase tracking-wide text-text/50 mb-2">{{ type }} ({{ qs.length }})</h4>
              <div class="space-y-1.5">
                <div v-for="q in qs" :key="q.id" class="flex items-center justify-between gap-2 bg-bg border border-border rounded-lg px-3 py-2">
                  <span class="text-sm text-text truncate">{{ q.content }}</span>
                  <button v-if="canEdit" class="text-text/40 hover:text-red-500 shrink-0" @click="removeQuestion(q.id)">
                    <X class="w-4 h-4" />
                  </button>
                </div>
              </div>
            </div>
            <p v-if="!attached.length" class="text-sm text-text/40 text-center py-8">No questions attached yet.</p>
          </div>
          <div class="mt-4 pt-4 border-t border-border">
            <div class="bg-accent/10 text-accent rounded-lg px-4 py-3 flex justify-between items-center font-semibold text-sm">
              <span>Total</span><span>{{ attached.length }} questions · {{ totalMarks }} marks</span>
            </div>
          </div>
        </BaseCard>
      </div>
    </div>
  </div>
</template>
