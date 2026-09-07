<script setup lang="ts">
import { computed, ref } from 'vue';
import { useRouter } from 'vue-router';
import { ArrowLeft, Upload, Users, GraduationCap, Layers, UserRound, Check } from 'lucide-vue-next';
import BaseButton from '@/shared/components/ui/BaseButton.vue';
import ResourceToolbar from '@/shared/components/ResourceToolbar.vue';
import ImportUploadStep from '../components/ImportUploadStep.vue';
import { IMPORT_TYPE_CONFIG } from '../config/importTypes';
import { useImportWizard } from '../composables/useImportWizard';
import type { ImportType } from '../types/import';
import { useUiStore } from '@/stores/ui';

const router = useRouter();
const uiStore = useUiStore();

const ADMIN_IMPORT_TYPES: ImportType[] = ['students', 'instructors', 'sections', 'users', 'questions'];

const TYPE_ICONS: Record<ImportType, any> = {
  students: Users,
  instructors: GraduationCap,
  sections: Layers,
  questions: UserRound,
  users: UserRound,
};

const type = ref<ImportType | null>(null);

const config = computed(() => (type.value ? IMPORT_TYPE_CONFIG[type.value] : null));

const { isUploading, uploadError, upload } = useImportWizard();

async function submitUpload({ file, context }: { file: File; context: Record<string, any> }) {
  if (!type.value) return;

  const record = await upload(type.value, file, context);

  if (!record) return;

  uiStore.showToast('Import started. Processing your file.', 'success');

  router.push({
    name: 'admin-import-detail',
    params: {
      id: record.id,
    },
  });
}
</script>

<template>
  <div class="mx-auto w-full max-w-360 space-y-6 px-6 py-6">
    <ResourceToolbar
      title="New data import"
      description="Choose a data type and import records from a CSV file."
      :show-search="false"
      :show-refresh="false"
      :show-fullscreen="false">
      <template #actions>
        <BaseButton variant="secondary" @click="router.push({ name: 'admin-imports' })">
          <template #icon>
            <ArrowLeft class="h-4 w-4" />
          </template>
          Back
        </BaseButton>
      </template>
    </ResourceToolbar>
    <div>
      <p class="mb-3 text-xs font-bold uppercase tracking-wide text-text/50">What are you importing?</p>

      <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <button
          v-for="key in ADMIN_IMPORT_TYPES"
          :key="key"
          v-can="IMPORT_TYPE_CONFIG[key].permission"
          type="button"
          class="relative flex flex-col items-start gap-3 rounded-md border p-5 text-left transition-colors"
          :class="type === key ? 'border-accent bg-accent/5' : 'border-border bg-surface hover:border-accent/50'"
          @click="type = key">
          <div class="flex w-full items-start justify-between">
            <div class="flex h-10 w-10 items-center justify-center rounded-md bg-accent/10 text-accent">
              <component :is="TYPE_ICONS[key]" class="h-5 w-5" />
            </div>

            <Check v-if="type === key" class="h-5 w-5 text-accent" />
          </div>

          <div>
            <h3 class="font-semibold text-text">
              {{ IMPORT_TYPE_CONFIG[key].label }}
            </h3>

            <p class="mt-1 text-xs leading-relaxed text-text/55">
              {{ IMPORT_TYPE_CONFIG[key].description }}
            </p>
          </div>
        </button>
      </div>
    </div>

    <ImportUploadStep v-if="type && config" :type="type" :uploading="isUploading" :error="uploadError" @upload="submitUpload" />
  </div>
</template>
