<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { ArrowLeft, CheckCircle2 } from 'lucide-vue-next';
import { useRouter } from 'vue-router';

import ResourceToolbar from '@/shared/components/ResourceToolbar.vue';
import BaseButton from '@/shared/components/ui/BaseButton.vue';
import ConfirmModal from '@/shared/components/ConfirmModal.vue';

import ImportUploadStep from '@/modules/admin/imports/components/ImportUploadStep.vue';
import ImportProcessingStep from '@/modules/admin/imports/components/ImportProcessingStep.vue';
import ImportQuestionsReviewTable from '@/modules/admin/imports/components/ImportQuestionsReviewTable.vue';

import { useImportWizard } from '@/modules/admin/imports/composables/useImportWizard';

import { useUiStore } from '@/stores/ui';

const router = useRouter();
const uiStore = useUiStore();

const type = 'questions' as const;

const { record, isUploading, isConfirming, isCancelling, savingRow, uploadError, upload, updateRow, deleteRow, confirm, cancel, loadExisting } = useImportWizard();

const showConfirmModal = ref(false);
const showCancelModal = ref(false);

const loadingExisting = ref(false);

const hasRecord = computed(() => !!record.value);

async function handleUpload({ file, context }: { file: File; context: Record<string, any> }) {
  await upload(type, file, context);
}

async function confirmFinal() {
  try {
    await confirm();

    showConfirmModal.value = false;

    uiStore.showToast('Questions imported successfully.', 'success');
  } catch {
    // Toast handled by composable.
  }
}

async function cancelFinal() {
  await cancel();

  showCancelModal.value = false;

  router.push({
    name: 'instructor.questions.list',
  });
}

function back() {
  router.push({
    name: 'instructor.questions.add',
  });
}

onMounted(() => {
  // This page starts a fresh question import.
  // Existing records are handled by the same reusable
  // import components/composable when coming from
  // the Admin workflow.
});

onUnmounted(() => {
  // useImportWizard handles polling cleanup.
});
</script>

<template>
  <div class="mx-auto w-full max-w-360 space-y-6 px-6 py-6">
    <ResourceToolbar
      title="Upload Questions"
      description="Import questions using the same CSV validation and review workflow used by the system import module."
      :show-search="false"
      :show-refresh="false"
      :show-fullscreen="false">
      <template #actions>
        <BaseButton variant="secondary" @click="back">
          <template #icon>
            <ArrowLeft class="h-4 w-4" />
          </template>

          Back
        </BaseButton>
      </template>
    </ResourceToolbar>

    <ImportUploadStep v-if="!hasRecord" :type="type" :uploading="isUploading" :error="uploadError" @upload="handleUpload" />

    <ImportProcessingStep v-else-if="record?.status === 'pending' || record?.status === 'processing'" :total-rows="record?.total_rows ?? 0" />

    <div v-else-if="record?.status === 'ready_for_review'" class="space-y-6">
      <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
        <div class="rounded-md border border-border bg-surface p-4">
          <p class="text-xs uppercase tracking-wide text-text/45">Total rows</p>

          <p class="mt-1 text-xl font-semibold text-text">
            {{ record.total_rows }}
          </p>
        </div>

        <div class="rounded-md border border-success/20 bg-success/5 p-4">
          <p class="text-xs uppercase tracking-wide text-text/45">Valid</p>

          <p class="mt-1 text-xl font-semibold text-success">
            {{ record.valid_count }}
          </p>
        </div>

        <div class="rounded-md border border-error/20 bg-error/5 p-4">
          <p class="text-xs uppercase tracking-wide text-text/45">Errors</p>

          <p class="mt-1 text-xl font-semibold text-error">
            {{ record.error_count }}
          </p>
        </div>

        <div class="rounded-md border border-border bg-surface p-4">
          <p class="text-xs uppercase tracking-wide text-text/45">Status</p>

          <p class="mt-1 text-sm font-semibold text-text">Ready for review</p>
        </div>
      </div>

      <ImportQuestionsReviewTable :rows="record.validated_data ?? {}" :saving-row="savingRow" @update-row="({ row, data }) => updateRow(row, data)" @delete-row="deleteRow" />

      <div class="flex items-center justify-between border-t border-border pt-4">
        <BaseButton variant="secondary" @click="showCancelModal = true"> Cancel import </BaseButton>

        <BaseButton :disabled="record.error_count > 0 || record.total_rows === 0" @click="showConfirmModal = true">
          {{ record.error_count > 0 ? `Resolve ${record.error_count} invalid rows` : 'Confirm import' }}
        </BaseButton>
      </div>
    </div>

    <div v-else-if="record?.status === 'confirmed'" class="flex flex-col items-center rounded-md border border-success/30 bg-success/5 p-12 text-center">
      <CheckCircle2 class="mb-4 h-10 w-10 text-success" />

      <h3 class="mb-1 font-semibold text-text">Import confirmed</h3>

      <p class="text-sm text-text/60">
        {{ record.valid_count }}
        questions were imported successfully.
      </p>

      <BaseButton
        class="mt-5"
        @click="
          router.push({
            name: 'instructor.questions.list',
          })
        ">
        Back to Question Bank
      </BaseButton>
    </div>

    <div v-else-if="record?.status === 'failed'" class="rounded-md border border-error/30 bg-error/5 p-8 text-center">
      <p class="text-sm font-medium text-error">This import failed to process.</p>

      <p class="mt-1 text-sm text-text/60">You can go back and upload the CSV again.</p>

      <BaseButton variant="secondary" class="mt-4" @click="back"> Back </BaseButton>
    </div>
  </div>

  <ConfirmModal
    :show="showConfirmModal"
    title="Confirm this import?"
    :description="`This will create ${record?.valid_count ?? 0} new questions. This action cannot be undone.`"
    confirm-text="Confirm import"
    variant="danger"
    :icon="CheckCircle2"
    :loading="isConfirming"
    @close="showConfirmModal = false"
    @confirm="confirmFinal" />

  <ConfirmModal
    :show="showCancelModal"
    title="Cancel this import?"
    description="This will discard the uploaded file and all reviewed rows."
    confirm-text="Cancel import"
    variant="danger"
    :loading="isCancelling"
    @close="showCancelModal = false"
    @confirm="cancelFinal" />
</template>
