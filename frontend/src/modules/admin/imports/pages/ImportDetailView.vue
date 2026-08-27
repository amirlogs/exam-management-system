<script setup lang="ts">
import { onMounted, computed, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { ArrowLeft, CheckCircle2, RefreshCw } from 'lucide-vue-next';
import ResourceToolbar from '@/shared/components/ResourceToolbar.vue';
import BaseButton from '@/shared/components/ui/BaseButton.vue';
import ConfirmModal from '@/shared/components/ConfirmModal.vue';

import ImportProcessingStep from '../components/ImportProcessingStep.vue';
import ImportReviewTable from '../components/ImportReviewTable.vue';
import ImportQuestionsReviewTable from '../components/ImportQuestionsReviewTable.vue';

import { useImportWizard } from '../composables/useImportWizard';
import { IMPORT_TYPE_CONFIG } from '../config/importTypes';

const route = useRoute();
const router = useRouter();

const id = computed(() => Number(route.params.id));

const { record, isConfirming, isCancelling, savingRow, loadExisting, updateRow, deleteRow, confirm, cancel } = useImportWizard();

const loading = ref(false);
const showConfirmModal = ref(false);
const showCancelModal = ref(false);

async function load() {
  loading.value = true;

  try {
    await loadExisting(id.value);
  } finally {
    loading.value = false;
  }
}

onMounted(load);

const typeLabel = computed(() => (record.value ? IMPORT_TYPE_CONFIG[record.value.type].label : ''));

const isQuestionImport = computed(() => record.value?.type === 'questions');

function back() {
  router.push({
    name: 'admin-imports',
  });
}

async function confirmFinal() {
  try {
    await confirm();
    showConfirmModal.value = false;
  } catch {
    // Toast already handled by composable.
  }
}

async function cancelFinal() {
  await cancel();

  showCancelModal.value = false;
  back();
}
</script>

<template>
  <div class="mx-auto w-full max-w-360 space-y-6 px-6 py-6">
    <ResourceToolbar
      :title="record ? `Import #${record.id} - ${typeLabel}` : 'Import'"
      :description="record ? `Review and manage this ${typeLabel.toLowerCase()} import.` : 'Loading import...'"
      :show-search="false"
      :show-refresh="true"
      :refreshing="loading"
      :show-fullscreen="true"
      @refresh="load">
      <template #actions>
        <BaseButton variant="secondary" @click="back">
          <template #icon>
            <ArrowLeft class="h-4 w-4" />
          </template>
          Back to imports
        </BaseButton>
      </template>
    </ResourceToolbar>

    <ImportProcessingStep v-if="record && (record.status === 'pending' || record.status === 'processing')" :total-rows="record.total_rows" />

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

      <component
        :is="isQuestionImport ? ImportQuestionsReviewTable : ImportReviewTable"
        :rows="record.validated_data ?? {}"
        :saving-row="savingRow"
        @update-row="({ row, data }) => updateRow(row, data)"
        @delete-row="deleteRow" />

      <div class="flex items-center justify-between border-t border-border pt-4">
        <BaseButton variant="secondary" @click="showCancelModal = true"> Cancel import </BaseButton>

        <BaseButton :disabled="record.error_count > 0 || record.total_rows === 0" @click="showConfirmModal = true">
          {{ record.error_count > 0 ? `Resolve ${record.error_count} invalid rows` : 'Confirm import' }}
        </BaseButton>
      </div>
    </div>

    <div v-else-if="record?.status === 'confirmed'" class="space-y-6">
      <div class="flex items-center justify-between rounded-md border border-success/30 bg-success/5 p-5">
        <div class="flex items-center gap-3">
          <CheckCircle2 class="h-6 w-6 shrink-0 text-success" />

          <div>
            <h3 class="font-semibold text-text">Import confirmed</h3>

            <p class="text-sm text-text/60">
              {{ record.valid_count }}
              {{ typeLabel.toLowerCase() }}
              imported successfully.
            </p>
          </div>
        </div>
      </div>

      <component :is="isQuestionImport ? ImportQuestionsReviewTable : ImportReviewTable" :rows="record.validated_data ?? {}" :saving-row="null" readonly />
    </div>

    <div v-else-if="record?.status === 'failed'" class="rounded-md border border-error/30 bg-error/5 p-8 text-center">
      <p class="text-sm font-medium text-error">This import failed to process.</p>

      <p class="mt-1 text-sm text-text/60">You can cancel this import and upload the file again.</p>

      <div class="mt-4 flex justify-center gap-2">
        <BaseButton variant="secondary" @click="back"> Back to imports </BaseButton>

        <BaseButton variant="secondary" @click="showCancelModal = true"> Cancel import </BaseButton>
      </div>
    </div>
  </div>

  <ConfirmModal
    :show="showConfirmModal"
    title="Confirm this import?"
    :description="`This will create ${record?.valid_count ?? 0} new ${typeLabel.toLowerCase()} records. This action cannot be undone.`"
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
