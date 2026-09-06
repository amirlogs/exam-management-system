<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { ArrowLeft, CheckCircle2, FileSpreadsheet } from 'lucide-vue-next';
import { useRoute, useRouter } from 'vue-router';

import BaseButton from '@/shared/components/ui/BaseButton.vue';
import ConfirmModal from '@/shared/components/ConfirmModal.vue';

import ImportUploadStep from '@/modules/admin/imports/components/ImportUploadStep.vue';
import ImportProcessingStep from '@/modules/admin/imports/components/ImportProcessingStep.vue';
import ImportQuestionsReviewTable from '@/modules/admin/imports/components/ImportQuestionsReviewTable.vue';

import { useImportWizard } from '@/modules/admin/imports/composables/useImportWizard';
import { getExam } from '../api/exams';
import { getTeaching } from '@/modules/instructor/teaching/api/teaching';
import { useUiStore } from '@/stores/ui';
import { handleApiError } from '@/shared/utils/apiError';

import type { Exam } from '../types/exam';

const route = useRoute();
const router = useRouter();
const uiStore = useUiStore();

const examId = Number(route.params.examId);
const type = 'questions' as const;

const { record, isUploading, isConfirming, isCancelling, savingRow, uploadError, upload, updateRow, deleteRow, confirm, cancel } = useImportWizard();

const exam = ref<Exam | null>(null);
const courseId = ref<number | null>(null);
const courseLabel = ref<string>('');
const loadingContext = ref(true);

const showConfirmModal = ref(false);
const showCancelModal = ref(false);

const hasRecord = computed(() => !!record.value);

const fixedContext = computed(() => {
  if (!courseId.value) return undefined;
  return {
    course_id: String(courseId.value),
    exam_id: String(examId),
  };
});

async function loadContext() {
  loadingContext.value = true;
  try {
    const [examResponse, teachingResponse] = await Promise.all([getExam(examId), getTeaching(1, 1000)]);

    exam.value = examResponse.data;
    const teaching = teachingResponse.data?.find((item: any) => item?.id === exam.value?.course_offering_id);

    if (teaching?.course) {
      courseId.value = teaching.course.id;
      courseLabel.value = `${teaching.course.code} — ${teaching.course.name}`;
    }
  } catch (error) {
    handleApiError(error, uiStore, undefined, 'Unable to load exam context.');
  } finally {
    loadingContext.value = false;
  }
}

function goBack() {
  router.push({
    name: 'instructor.exams.questions',
    params: { examId },
  });
}

async function handleUpload({ file, context: uploadedContext }: { file: File; context: Record<string, any> }) {
  const mergedContext = {
    ...uploadedContext,
    course_id: courseId.value,
    exam_id: examId,
  };
  await upload(type, file, mergedContext);
}

async function confirmFinal() {
  try {
    await confirm();
    showConfirmModal.value = false;
    uiStore.showToast('Questions imported and attached to exam successfully.', 'success');
    router.push({
      name: 'instructor.exams.questions',
      params: { examId },
    });
  } catch (error) {
    handleApiError(error, uiStore, undefined, 'Failed to confirm question import.');
  }
}

async function cancelFinal() {
  await cancel();
  showCancelModal.value = false;
  goBack();
}

onMounted(loadContext);
</script>

<template>
  <div class="mx-auto w-full max-w-360 space-y-6 px-6 py-6 min-h-[calc(100vh-68px)]">
    <!-- Top Navigation -->
    <div class="flex items-center gap-3">
      <button
        type="button"
        class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-border bg-surface text-text/60 transition-colors hover:border-accent/40 hover:text-accent hover:bg-accent/5"
        title="Back to exam questions"
        @click="goBack">
        <ArrowLeft class="h-4 w-4" />
      </button>

      <div>
        <h1 class="font-display text-xl font-bold text-text">Import Questions to Exam</h1>
        <p class="text-xs text-text/45">Upload questions via CSV to be validated and attached directly to this exam paper.</p>
      </div>
    </div>

    <!-- Target Exam Info Card -->
    <div v-if="exam" class="rounded-xl border border-border bg-surface p-6 shadow-sm">
      <div class="flex items-start gap-4">
        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-accent/10">
          <FileSpreadsheet class="h-6 w-6 text-accent" />
        </div>

        <div class="min-w-0 flex-1">
          <h2 class="font-display text-lg font-bold text-text">Target Exam: {{ exam.title }}</h2>
          <p class="mt-0.5 text-xs text-text/50">Imported questions will be added to the course bank and attached directly to this exam paper.</p>

          <div v-if="courseLabel" class="mt-3 flex flex-wrap items-center gap-3 rounded-lg border border-border bg-bg/50 px-3.5 py-2 text-xs">
            <span class="text-text/50">Course:</span>
            <span class="font-semibold text-text">{{ courseLabel }}</span>
            <span class="text-text/30">•</span>
            <span class="text-text/50">Exam Type:</span>
            <span class="font-semibold uppercase text-text">{{ exam.type }}</span>
          </div>
        </div>
      </div>
    </div>

    <div v-if="loadingContext" class="rounded-xl border border-border bg-surface p-12 text-center shadow-sm">
      <p class="text-sm text-text/50">Loading import configuration…</p>
    </div>

    <template v-else-if="exam && courseId">
      <!-- Upload Step -->
      <ImportUploadStep
        v-if="!hasRecord"
        :type="type"
        :fixed-context="fixedContext"
        :fixed-course-label="courseLabel"
        :uploading="isUploading"
        :error="uploadError"
        @upload="handleUpload" />

      <!-- Processing Step -->
      <ImportProcessingStep v-else-if="record?.status === 'pending' || record?.status === 'processing'" :total-rows="record?.total_rows ?? 0" />

      <!-- Review Step -->
      <div v-else-if="record?.status === 'ready_for_review'" class="space-y-6">
        <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
          <div class="rounded-xl border border-border bg-surface p-4">
            <p class="text-xs font-semibold uppercase tracking-wider text-text/45">Total rows</p>
            <p class="mt-1 text-xl font-bold text-text">{{ record.total_rows }}</p>
          </div>

          <div class="rounded-xl border border-success/20 bg-success/5 p-4">
            <p class="text-xs font-semibold uppercase tracking-wider text-text/45">Valid</p>
            <p class="mt-1 text-xl font-bold text-success">{{ record.valid_count }}</p>
          </div>

          <div class="rounded-xl border border-error/20 bg-error/5 p-4">
            <p class="text-xs font-semibold uppercase tracking-wider text-text/45">Errors</p>
            <p class="mt-1 text-xl font-bold text-error">{{ record.error_count }}</p>
          </div>

          <div class="rounded-xl border border-border bg-surface p-4">
            <p class="text-xs font-semibold uppercase tracking-wider text-text/45">Status</p>
            <p class="mt-1 text-sm font-semibold text-text">Ready for review</p>
          </div>
        </div>

        <ImportQuestionsReviewTable :rows="record.validated_data ?? {}" :saving-row="savingRow" @update-row="({ row, data }) => updateRow(row, data)" @delete-row="deleteRow" />

        <div class="flex items-center justify-between border-t border-border pt-4">
          <BaseButton variant="secondary" @click="showCancelModal = true"> Cancel import </BaseButton>

          <BaseButton :disabled="record.error_count > 0 || record.total_rows === 0" @click="showConfirmModal = true">
            {{ record.error_count > 0 ? `Resolve ${record.error_count} invalid rows` : 'Confirm & attach to exam' }}
          </BaseButton>
        </div>
      </div>

      <!-- Confirmed Step -->
      <div v-else-if="record?.status === 'confirmed'" class="flex flex-col items-center rounded-xl border border-success/30 bg-success/5 p-12 text-center">
        <CheckCircle2 class="mb-4 h-10 w-10 text-success" />
        <h3 class="mb-1 text-lg font-bold text-text">Import confirmed</h3>
        <p class="text-sm text-text/60">{{ record.valid_count }} question(s) were imported and attached to this exam.</p>
        <BaseButton class="mt-5" @click="goBack"> Back to Exam Questions </BaseButton>
      </div>

      <!-- Failed Step -->
      <div v-else-if="record?.status === 'failed'" class="rounded-xl border border-error/30 bg-error/5 p-8 text-center">
        <p class="text-sm font-medium text-error">This import failed to process.</p>
        <p class="mt-1 text-sm text-text/60">You can go back and upload the CSV again.</p>
        <BaseButton variant="secondary" class="mt-4" @click="cancelFinal"> Try Again </BaseButton>
      </div>
    </template>

    <!-- Modals -->
    <ConfirmModal
      :show="showConfirmModal"
      title="Confirm question import?"
      :description="`This will import ${record?.valid_count ?? 0} question(s) and attach them directly to ${exam?.title || 'this exam'}.`"
      confirm-text="Confirm and attach"
      variant="danger"
      :icon="CheckCircle2"
      :loading="isConfirming"
      @close="showConfirmModal = false"
      @confirm="confirmFinal" />

    <ConfirmModal
      :show="showCancelModal"
      title="Cancel import?"
      description="This will discard the uploaded file and all reviewed rows."
      confirm-text="Discard import"
      variant="danger"
      :loading="isCancelling"
      @close="showCancelModal = false"
      @confirm="cancelFinal" />
  </div>
</template>
