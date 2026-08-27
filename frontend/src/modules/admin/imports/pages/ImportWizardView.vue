<script setup lang="ts">
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { CheckCircle2 } from 'lucide-vue-next';
import BaseButton from '@/shared/components/ui/BaseButton.vue';
import ImportUploadStep from '../components/ImportUploadStep.vue';
import ImportProcessingStep from '../components/ImportProcessingStep.vue';
import ImportReviewTable from '../components/ImportReviewTable.vue';
import { useImportWizard } from '../composables/useImportWizard';
import { IMPORT_TYPE_CONFIG } from '../config/importTypes';
import type { ImportType } from '../types/import';
import { ref } from 'vue';
import ConfirmModal from '@/shared/components/ConfirmModal.vue';

const route = useRoute();
const router = useRouter();
const type = computed(() => route.params.type as ImportType);
const config = computed(() => IMPORT_TYPE_CONFIG[type.value]);

const { record, isUploading, isConfirming, isCancelling, savingRow, uploadError, upload, updateRow, deleteRow, confirm, cancel } = useImportWizard(type.value);

const showConfirmModal = ref(false);
const showCancelModal = ref(false);

async function handleUpload({ file, context }: { file: File; context: Record<string, any> }) {
  await upload(file, context);
}
async function confirmFinal() {
  try {
    await confirm();
    showConfirmModal.value = false;
  } catch {
    /* toast already shown by composable */
  }
}
async function cancelFinal() {
  await cancel();
  showCancelModal.value = false;
  router.push({ name: 'admin-imports' });
}
</script>

<template>
  <div class="mx-auto w-full max-w-260 space-y-6 px-6 py-6">
    <div>
      <p class="text-xs uppercase tracking-wide text-text/50">Import · {{ config.label }}</p>
      <h1 class="mt-1 text-2xl font-semibold tracking-tight text-text">
        {{ !record ? 'Upload file' : record.status === 'ready_for_review' ? 'Review data' : record.status === 'confirmed' ? 'Import complete' : 'Processing' }}
      </h1>
    </div>

    <ImportUploadStep v-if="!record" :type="type" :uploading="isUploading" :error="uploadError" @upload="handleUpload" />

    <ImportProcessingStep v-else-if="record.status === 'pending' || record.status === 'processing'" :total-rows="record.total_rows" />

    <div v-else-if="record.status === 'ready_for_review'" class="space-y-6">
      <ImportReviewTable :rows="record.validated_data ?? {}" :saving-row="savingRow" @update-row="({ row, data }) => updateRow(row, data)" @delete-row="deleteRow" />

      <div class="flex justify-between border-t border-border pt-4">
        <BaseButton variant="secondary" @click="showCancelModal = true">Cancel import</BaseButton>
        <BaseButton :disabled="record.error_count > 0" @click="showConfirmModal = true">
          {{ record.error_count > 0 ? `Resolve ${record.error_count} invalid rows` : 'Confirm import' }}
        </BaseButton>
      </div>
    </div>

    <div v-else-if="record.status === 'confirmed'" class="flex flex-col items-center rounded-md border border-border bg-surface p-12 text-center">
      <CheckCircle2 class="mb-4 h-10 w-10 text-success" />
      <h3 class="mb-1 font-semibold text-text">Import confirmed</h3>
      <p class="text-sm text-text/60">{{ record.valid_count }} {{ config.label.toLowerCase() }} added successfully.</p>
    </div>
  </div>

  <ConfirmModal
    :show="showConfirmModal"
    title="Confirm this import?"
    :description="`This will create ${record?.valid_count ?? 0} new ${config.label.toLowerCase()} records. This action cannot be undone.`"
    confirm-text="Confirm import"
    variant="danger"
    :icon="CheckCircle2"
    :loading="isConfirming"
    @close="showConfirmModal = false"
    @confirm="confirmFinal" />
  <ConfirmModal
    :show="showCancelModal"
    title="Cancel this import?"
    description="This will discard the uploaded file and all reviewed rows. This cannot be undone."
    confirm-text="Cancel import"
    variant="danger"
    :loading="isCancelling"
    @close="showCancelModal = false"
    @confirm="cancelFinal" />
</template>
