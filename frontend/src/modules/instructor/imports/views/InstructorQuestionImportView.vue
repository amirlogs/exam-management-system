<script setup lang="ts">
import { useRouter } from 'vue-router';
import ResourceToolbar from '@/shared/components/ResourceToolbar.vue';
import ImportUploadStep from '@/modules/admin/imports/components/ImportUploadStep.vue';
import { useImportWizard } from '@/modules/admin/imports/composables/useImportWizard';
import { useUiStore } from '@/stores/ui';
import BaseButton from '@/shared/components/ui/BaseButton.vue';

const router = useRouter();
const uiStore = useUiStore();

const { isUploading, uploadError, upload } = useImportWizard();

async function submitUpload({ file, context }: { file: File; context: Record<string, any> }) {
  const record = await upload('questions', file, context);

  if (!record) return;

  uiStore.showToast('Question import started. Processing your file.', 'success');

  router.push({
    name: 'instructor-question-import-detail',
    params: {
      id: record.id,
    },
  });
}
</script>

<template>
  <div class="mx-auto w-full max-w-360 space-y-6 px-6 py-6">
    <ResourceToolbar
      title="Import questions"
      description="Import questions into your assigned course question bank."
      :show-search="false"
      :show-filter="false"
      :show-refresh="false">
      <template #actions>
        <BaseButton variant="secondary" @click="router.back()"> Back </BaseButton>
      </template>
    </ResourceToolbar>

    <div class="rounded-md border border-accent/20 bg-accent/5 p-4">
      <p class="text-sm font-medium text-text">Question CSV</p>

      <p class="mt-1 text-xs leading-relaxed text-text/60">Select the course and optionally an exam before uploading. The questions will be validated before they can be added.</p>
    </div>

    <ImportUploadStep type="questions" :uploading="isUploading" :error="uploadError" @upload="submitUpload" />
  </div>
</template>
