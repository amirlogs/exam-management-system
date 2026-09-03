<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';

import { ArrowLeft, CheckCircle2, AlertCircle, Trash2 } from 'lucide-vue-next';
import { useRoute, useRouter } from 'vue-router';
import ResourceToolbar from '@/shared/components/ResourceToolbar.vue';
import BaseButton from '@/shared/components/ui/BaseButton.vue';
import ConfirmModal from '@/shared/components/ConfirmModal.vue';
import ImportProcessingStep from '@/modules/admin/imports/components/ImportProcessingStep.vue';
import ImportQuestionsReviewTable from '@/modules/admin/imports/components/ImportQuestionsReviewTable.vue';
import { useImportWizard } from '@/modules/admin/imports/composables/useImportWizard';
import { IMPORT_TYPE_CONFIG } from '@/modules/admin/imports/config/importTypes';

const route = useRoute();
const router = useRouter();
const importId = computed(() => Number(route.params.id));
const { record, isConfirming, isCancelling, savingRow, loadExisting, updateRow, deleteRow, confirm, cancel } = useImportWizard();
const loading = ref(false);
const showConfirmModal = ref(false);
const showCancelModal = ref(false);

async function load() {
  loading.value = true;

  try {
    await loadExisting(importId.value);
  } finally {
    loading.value = false;
  }
}

onMounted(load);

const typeLabel = computed(() => {
  if (!record.value) {
    return 'Questions';
  }

  return IMPORT_TYPE_CONFIG[record.value.type]?.label ?? 'Questions';
});

function back() {
  router.back();
}

async function confirmFinal() {
  try {
    await confirm();

    showConfirmModal.value = false;
  } catch {
    // Error already handled by useImportWizard.
  }
}

async function cancelFinal() {
  await cancel();

  showCancelModal.value = false;

  back();
}

const canConfirm = computed(() => {
  if (!record.value) {
    return false;
  }

  return record.value.status === 'ready_for_review' && record.value.total_rows > 0 && record.value.error_count === 0;
});
</script>

<template>
  <div class="mx-auto min-h-[calc(100vh-68px)] w-full max-w-360 space-y-6 px-6 py-8">
    <ResourceToolbar
      :title="record ? `Question import #${record.id}` : 'Question import'"
      :description="record ? `Review ${typeLabel.toLowerCase()} before adding them to the question bank.` : 'Review, correct, and confirm your imported questions.'"
      :show-search="false"
      :show-filter="false"
      :show-refresh="true"
      :refreshing="loading"
      @refresh="load">
      <template #actions>
        <BaseButton variant="secondary" @click="back">
          <template #icon>
            <ArrowLeft class="h-4 w-4" />
          </template>

          Back
        </BaseButton>
      </template>
    </ResourceToolbar>

    <!-- PROCESSING -->
    <ImportProcessingStep v-if="record && (record.status === 'pending' || record.status === 'processing')" :total-rows="record.total_rows" />

    <!-- REVIEW -->
    <div v-else-if="record?.status === 'ready_for_review'" class="space-y-6">
      <!-- Summary -->
      <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <!-- Total -->
        <div class="rounded-2xl border border-border bg-surface p-5">
          <p class="text-xs font-medium uppercase tracking-wide text-text/40">Total rows</p>

          <p class="mt-2 text-2xl font-bold text-text">
            {{ record.total_rows }}
          </p>
        </div>

        <!-- Valid -->
        <div class="rounded-2xl border border-success/20 bg-success/5 p-5">
          <div class="flex items-center gap-2">
            <CheckCircle2 class="h-4 w-4 text-success" />

            <p class="text-xs font-medium uppercase tracking-wide text-text/45">Valid</p>
          </div>

          <p class="mt-2 text-2xl font-bold text-success">
            {{ record.valid_count }}
          </p>
        </div>

        <!-- Errors -->
        <div class="rounded-2xl border border-error/20 bg-error/5 p-5">
          <div class="flex items-center gap-2">
            <AlertCircle class="h-4 w-4 text-error" />

            <p class="text-xs font-medium uppercase tracking-wide text-text/45">Errors</p>
          </div>

          <p class="mt-2 text-2xl font-bold text-error">
            {{ record.error_count }}
          </p>
        </div>
      </div>

      <!-- Context -->
      <div v-if="record.context?.course_id" class="rounded-2xl border border-border bg-surface p-5">
        <p class="text-xs font-medium uppercase tracking-wide text-text/40">Import target</p>

        <div class="mt-2 flex flex-wrap items-center gap-3">
          <span class="rounded-lg bg-bg px-3 py-1.5 text-sm font-medium text-text"> Course #{{ record.context.course_id }} </span>

          <span v-if="record.context.exam_id" class="rounded-lg bg-bg px-3 py-1.5 text-sm font-medium text-text"> Exam #{{ record.context.exam_id }} </span>
        </div>
      </div>

      <!-- Review table -->
      <ImportQuestionsReviewTable :rows="record.validated_data ?? {}" :saving-row="savingRow" @update-row="({ row, data }) => updateRow(row, data)" @delete-row="deleteRow" />

      <!-- Actions -->
      <div class="flex flex-col-reverse gap-3 border-t border-border pt-5 sm:flex-row sm:items-center sm:justify-between">
        <BaseButton variant="secondary" @click="showCancelModal = true">
          <template #icon>
            <Trash2 class="h-4 w-4" />
          </template>

          Cancel import
        </BaseButton>

        <BaseButton :disabled="!canConfirm" @click="showConfirmModal = true">
          <template #icon>
            <CheckCircle2 class="h-4 w-4" />
          </template>

          Confirm import
        </BaseButton>
      </div>

      <!-- Validation hint -->
      <p v-if="record.error_count > 0" class="text-right text-xs text-error">Fix or remove all invalid rows before confirming this import.</p>
    </div>

    <!-- CONFIRMED -->
    <div v-else-if="record?.status === 'confirmed'" class="space-y-6">
      <div class="flex flex-col gap-4 rounded-2xl border border-success/20 bg-success/5 p-5 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-start gap-3">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-success/10">
            <CheckCircle2 class="h-5 w-5 text-success" />
          </div>

          <div>
            <h3 class="font-semibold text-text">Import completed</h3>

            <p class="mt-1 text-sm text-text/60">
              {{ record.valid_count }}
              questions were imported successfully.
            </p>
          </div>
        </div>

        <BaseButton variant="secondary" @click="back"> Back </BaseButton>
      </div>

      <ImportQuestionsReviewTable :rows="record.validated_data ?? {}" :saving-row="null" readonly />
    </div>

    <!-- FAILED / CANCELLED / UNKNOWN -->
    <div v-else-if="record" class="rounded-2xl border border-border bg-surface p-10 text-center">
      <AlertCircle class="mx-auto h-8 w-8 text-text/30" />

      <h3 class="mt-4 font-semibold text-text">Import is {{ record.status.replaceAll('_', ' ') }}</h3>

      <p class="mt-1 text-sm text-text/50">There is currently nothing to review.</p>

      <div class="mt-5">
        <BaseButton variant="secondary" @click="back"> Back </BaseButton>
      </div>
    </div>
  </div>

  <!-- Confirm -->
  <ConfirmModal
    :show="showConfirmModal"
    title="Confirm question import?"
    :description="`This will add ${record?.valid_count ?? 0} questions to the question bank. This action cannot be undone.`"
    confirm-text="Confirm import"
    variant="danger"
    :icon="CheckCircle2"
    :loading="isConfirming"
    @close="showConfirmModal = false"
    @confirm="confirmFinal" />

  <!-- Cancel -->
  <ConfirmModal
    :show="showCancelModal"
    title="Cancel question import?"
    description="This will discard the uploaded file and its reviewed rows."
    confirm-text="Cancel import"
    variant="danger"
    :loading="isCancelling"
    @close="showCancelModal = false"
    @confirm="cancelFinal" />
</template>
