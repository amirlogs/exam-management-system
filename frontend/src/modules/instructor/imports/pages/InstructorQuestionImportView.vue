<script setup lang="ts">
import { useRouter } from 'vue-router';

import ResourceToolbar from '@/shared/components/ResourceToolbar.vue';
import BaseButton from '@/shared/components/ui/BaseButton.vue';

import ImportUploadStep from '@/modules/admin/imports/components/ImportUploadStep.vue';

import { useImportWizard } from '@/modules/admin/imports/composables/useImportWizard';

import { useUiStore } from '@/stores/ui';

const router = useRouter();
const uiStore = useUiStore();

const { isUploading, uploadError, upload } = useImportWizard();

async function submitUpload({ file, context }: { file: File; context: Record<string, any> }) {
  const record = await upload('questions', file, context);

  if (!record) {
    return;
  }

  uiStore.showToast('Question import started. Processing your file.', 'success');

  router.push({
    name: 'instructor.question-import.detail',
    params: {
      id: record.id,
    },
  });
}

function goBack() {
  router.back();
}
</script>

<template>
  <div class="mx-auto min-h-[calc(100vh-68px)] w-full max-w-360 space-y-6 px-6 py-8">
    <ResourceToolbar title="Import Questions" description="Import questions into your course question bank." :show-search="false" :show-filter="false" :show-refresh="false">
      <template #actions>
        <BaseButton variant="secondary" @click="goBack"> Back </BaseButton>
      </template>
    </ResourceToolbar>

    <div class="rounded-2xl border border-accent/20 bg-accent/5 p-5">
      <div class="flex items-start gap-3">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-accent/10">
          <span class="text-sm font-bold text-accent"> CSV </span>
        </div>

        <div>
          <h2 class="font-semibold text-text">Question CSV import</h2>

          <p class="mt-1 text-sm leading-6 text-text/60">
            Upload your question CSV and select the course it belongs to. The file will be validated before questions are added to the question bank.
          </p>
        </div>
      </div>
    </div>

    <ImportUploadStep type="questions" :uploading="isUploading" :error="uploadError" @upload="submitUpload" />
  </div>
</template>
